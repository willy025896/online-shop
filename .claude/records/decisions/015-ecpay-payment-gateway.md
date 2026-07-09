---
id: ADR-015
title: 金流串接（綠界 ECPay）與補齊取消已付款訂單退款缺口
date: 2026-07-07
status: Accepted
---

# ADR-015: 金流串接（綠界 ECPay）與補齊取消已付款訂單退款缺口

## Context

`PaymentService` 原本完全是模擬：`simulatePayment()` 由買家按下「付款」按鈕後同步、直接標記 `orders.status = paid`；`simulateRefund()`（ADR-013 新增）也只是本地 `increment('refunded_amount', ...)`。兩者都沒有任何對外的金流呼叫。

選擇**綠界 ECPay** 作為第一個真實串接對象，原因是它公開了官方測試特店憑證（`MerchantID=2000132`／`HashKey=5294y06JbISpM5x9`／`HashIV=v77hoKGq4kWxNNIS`，測試環境 `https://payment-stage.ecpay.com.tw/...`），可以完全不需要申請商家帳號、不用等審核就開始開發與測試——這對目前階段（先驗證串接可行性，尚未正式營運）是最低摩擦的選擇。相較之下，藍新金流（NewebPay）的沙盒是完全獨立的站台，仍須另外註冊帳號才能拿到測試憑證；Stripe 雖然也能立即建立測試模式，但仍需要建立一個帳號（即使免審核）。

串接真金流把付款流程的本質從「買家觸發、同步標記」換成「買家導向綠界收銀台付款 → 綠界 server 端非同步回調通知 → 我方驗簽後才標記已付款」，因此不只是替換 `PaymentService` 內部實作，路由/controller/前端都要跟著調整。同時，過程中一併補上 ADR-013 明確排除、留給後續評估的既有缺口：**取消已付款訂單完全沒有退款邏輯**。

## Decision

### 1. 職責切分：`PaymentService` vs `EcpayGateway`

`app/Services/EcpayGateway.php` 封裝所有綠界的 wire-format 細節（CheckMacValue 簽章/驗章、HTTP 呼叫、金額/欄位格式轉換）；`PaymentService`/`OrderService` 完全不接觸原始綠界參數，只呼叫 `EcpayGateway` 提供的高階方法。憑證與模式（`stage`/`production`）走 `config/ecpay.php` + `ECPAY_*` env（比照 `config/shipping.php` 的 config-driven 慣例），本機開發預設值直接帶上面的公開測試憑證。

### 2. 付款：導向 + 非同步回調，notify webhook 是唯一真相來源

- 買家按「付款」（`OrderController::pay`，原 `simulatePayment` 改名）不再同步標記付款，而是回傳一個帶隱藏欄位的自動送出表單（`resources/views/payments/ecpay-redirect.blade.php`），把買家的瀏覽器導到綠界收銀台（`AioCheckOut`）完成付款。
- 綠界的 server 端會回打我方的 `ReturnURL`（`POST /api/payments/ecpay/notify`，放在 `routes/api.php`——這個 group 預設無 CSRF、無 session 驗證，剛好符合「綠界 server 呼叫，不是登入使用者」的情境，不用動 `bootstrap/app.php` 去額外排除 CSRF）。`EcpayController::notify` 呼叫 `PaymentService::handleGatewayNotification()`：驗證 `CheckMacValue`、確認 `RtnCode == 1`、用 `$order->isPaid()` 做冪等防護（綠界會重送 notify 直到收到 `"1|OK"`，reply 必須是這個確切的純文字），通過才呼叫 `PaymentService::markAsPaid()`（原 `simulatePayment` 改名，內容不變）。
- 買家瀏覽器導回頁（`ClientBackURL` → `GET /orders/{order}/pay/return` → `OrderController::payReturn`）純粹顯示「確認中」，**不**標記任何狀態——因為它不是可信任的付款結果來源，只有 server 端的 notify 才是。
- `MerchantTradeNo` 用 `'ORD'.$order->id` 反推（綠界限制英數、≤20 字，現有 `order_number` 格式不符合），`EcpayGateway::merchantTradeNoFor()`/`tradeNoToOrderId()` 是唯一做這個映射的地方。
- 新增 `orders.gateway_trade_no` 欄位（migration `2026_07_07_100000_add_gateway_trade_no_to_orders_table`），儲存綠界自己的 `TradeNo`（notify payload 帶來的，不同於我方的 `MerchantTradeNo`/`order_number`）——這是退款 API（`CreditDetail/DoAction`）指定退款交易時必填的欄位，串接退款前這個欄位不存在，是實作過程中發現的必要補充。

### 3. 退款：真實呼叫綠界，失敗就整筆 rollback

`PaymentService::refund()`（原 `simulateRefund`）改呼叫 `EcpayGateway::refund()`（`CreditDetail/DoAction`，`Action=R`）。**退款 API 呼叫失敗（網路錯誤、綠界拒絕、缺少 `gateway_trade_no`）一律拋 `EcpayException`**，不吞掉、不記錄成「待重試」的中間狀態。兩個退款呼叫點（`OrderService::finalizeReturn`、`finalizeCancellation`）都在既有的 `DB::transaction` 內呼叫它，所以拋出的例外會讓整個 transaction rollback——庫存回補、優惠券釋放、狀態變更全部一起復原，退貨/取消請求維持在退款前的狀態，可以直接重試核准。Controller 層（`OrderController::cancel`、`Seller\OrderController::cancel`/`approveCancellation`/`approveReturn`）接住 `EcpayException`，flash 一般化錯誤訊息，不需要額外的 lang 檔（這是賣家操作時才會遇到的內部錯誤，不像 `CouponException` 需要給買家看的翻譯訊息）。

選擇「失敗就整筆 rollback」而不是「允許中間狀態、另外告警讓後台重試」，是因為金額類操作一旦允許「已核准但沒退到款」的中間狀態，後續要嘛需要額外的補償/重試機制，要嘛容易被忽略造成金流帳目對不上；rollback 讓系統永遠只有「完全成功」或「完全沒發生」兩種狀態，心智負擔最低，代價是賣家需要手動重新按一次核准。

### 4. 補齊 ADR-013 的缺口：取消已付款訂單也退款

`OrderService::finalizeCancellation()` 新增：若 `$order->isPaid()`，在狀態改成 `cancelled` 之前，用退貨流程已有的同一個比例折扣試算方法（`CouponService::refundableAmount($order, $order->subtotal)`，全額商品金額，運費不退，跟退貨的規則一致）算出退款金額並呼叫 `PaymentService::refund()`。三個會走到 `finalizeCancellation()` 的路徑（`directCancelByBuyer`、`cancelBySeller`、`approveCancellation`）全部一次補上，不需要個別處理。

### 5. CheckMacValue 手刻，不引入新套件

專案目前零第三方套件依賴（`composer.json` 只有 Laravel 核心套件）。CheckMacValue 演算法（`EcpayGateway::generateCheckMacValue()`）比對 ECPay 官方 SDK（`ECPay/SDK_PHP` 的 `CheckMacValueService`/`UrlService`）與社群公開實作後手刻：**case-insensitive 排序**（`uksort` + `strcasecmp`，不是單純的 `ksort`——這是官方 SDK 特有、容易漏掉的細節）、組字串、`urlencode` + 轉小寫、套用 PHP→.NET 字元對照表、SHA256、轉大寫。`tests/Feature/EcpayGatewayTest.php` 涵蓋演算法的確定性/一致性、`verify()` 正確拒絕竄改過的簽章、notify webhook 的驗簽/冪等/未知訂單情境、退款成功/失敗（含 rollback）。

**驗證侷限**：依使用者指示，這次實作**不**對綠界的測試環境發送任何真實 HTTP 請求（包含 stage 測試 API）——所有測試都用 `Http::fake()`。這代表 CheckMacValue 演算法目前只驗證了「內部一致性」（同樣輸入產生同樣雜湊、竄改會讓 `verify()` 失敗），還沒有拿綠界真實 stage 環境跑過一次端對端付款/退款來確認雜湊格式與 API 回應格式完全正確。正式使用前務必找一個時機做一次真實的 stage 環境端對端測試。

## Consequences

- 優點：
  - 綠界的 wire-format 細節完全封裝在 `EcpayGateway`，`PaymentService`/`OrderService` 的業務邏輯不需要認識簽章演算法，未來要換/加其他金流（藍新、Stripe）只需要新增一個對應的 Gateway class。
  - notify webhook 作為唯一真相來源，且做了冪等防護，符合金流串接的標準作法（不信任瀏覽器導回、防止重複回調造成的重複通知/重複記帳）。
  - 退款失敗一律 rollback，系統永遠不會停在「已核准但沒退到款」的中間狀態。
  - 一次性補齊「取消已付款訂單」的退款缺口，三個既有呼叫路徑不需要個別修改。
- 缺點：
  - 付款從同步變非同步，本機端對端手動測試比模擬時麻煩——ECPay 的 server 端 notify 打不到本機 `localhost`，需要 ngrok 之類的工具開對外網址才能測到 notify 這一段，不是程式碼問題，只是本機驗證的限制。
  - 退款失敗直接 rollback 意味著賣家看到的是「操作失敗，請稍後再試」，沒有自動重試機制；如果綠界端持續性故障，賣家需要手動反覆重試核准。
  - CheckMacValue 手刻雖然對照官方 SDK 邏輯撰寫，但**還沒有跑過真實綠界環境驗證**（見上方「驗證侷限」），存在演算法細節理解有誤而在真實環境才會暴露的風險。
  - 取消已付款訂單現在會退款，但如果同一張訂單之後又走「售後退貨」流程，兩者的退款計算都各自基於當下的 `order->subtotal` 全額計算，沒有互相感知彼此的退款歷史（因為取消後訂單已經是終態 `cancelled`，不可能再進入退貨流程，邏輯上互斥，這裡不是真的風險，只是實作時確認過的前提，記錄下來避免日後誤解）。

## Post-Review Fixes（post-change-review 發現並已修正）

實作完成後跑了一輪 code-review + security-review，發現並修正了以下問題（都已補上對應測試）：

- **[CRITICAL] 延遲 notify 復活已取消訂單** — 原本 `OrderController::pay()` 只擋「已付款」，`PaymentService::handleGatewayNotification()` 的冪等檢查也只看 `isPaid()`，都沒檢查訂單目前狀態。修正：`pay()` 改成只允許 `STATUS_PENDING` 的訂單開新的結帳流程；`handleGatewayNotification()` 在 `lockForUpdate()` 鎖定下確認訂單仍是 `STATUS_PENDING` 才會標記付款——若訂單已經在付款到達前被取消，改成直接用 notify 帶來的 `TradeNo` 觸發退款（全額，含運費，因為訂單完全沒履行），而不是靜默把訂單改回 `paid`。
- **[WARNING] `handleGatewayNotification` 缺少 row lock** — 補上 `Order::lockForUpdate()`，跟專案其他並發保護的慣例一致，避免 ECPay 重送 notify 時兩個近乎同時的請求都通過冪等檢查、重複標記付款。
- **[WARNING] `payReturn()` 導回頁無論實際付款結果都顯示成功訊息** — 改成中性措辭（「已返回，付款結果確認中」），不再預設「Payment received」。
- **[WARNING] `refunded_amount` 用未四捨五入的浮點金額累加** — `PaymentService::refund()` 改成先 `round()` 一次，本地帳目跟實際送給 ECPay 的整數金額（`EcpayGateway::refund()` 內部也會 round）保持一致，不會有小數餘額累積誤差。
- **[SUGGESTION] 退款外部呼叫成功後，同筆 transaction 後續步驟失敗會讓 rollback 跟真實退款狀態不一致** — `finalizeCancellation()`/`finalizeReturn()` 都調整成把 `paymentService->refund()` 移到方法最後一行執行，讓退款成功後這筆 transaction 不再有其他可能失敗的步驟。退款呼叫期間持續握住 row lock 的代價本身是決策 #2（退款失敗即 rollback）的必然代價，予以保留、不額外處理。
- **[SUGGESTION] 4 處重複的 `try { ... } catch (EcpayException) { ... }`** — 移除，改為 `EcpayException::render()`（Laravel 會自動呼叫有定義 `render()` 的例外類別），四個 controller 呼叫點不再需要各自 catch。
- **[SUGGESTION] `EcpayException` 沒有 `translatedMessage()`** — 補上，比照 `CouponException`，新增 `lang/{locale}/orders.php` 的 `payment_errors.{reason}` 翻譯鍵，`render()` 直接使用。
- **已確認非問題** — 「webhook 觸發的付款讓 `order_status_logs.changed_by` 變成 null」：`resources/js/Pages/Admin/Orders/Show.vue` 本來就有 `v-else` 顯示「系統」（`t.system`）處理 null 的情況，這是既有、正確的設計，不是本次改動引入的缺陷。

## Alternatives Considered

- **買家按下付款後同步呼叫綠界並直接標記付款**（不做 notify webhook）：實作更簡單，但完全違反金流串接的標準作法——買家可能在導到綠界後關閉分頁、付款失敗、或走 ATM/超商代碼等非即時付款方式，同步標記會導致「訂單顯示已付款但實際上沒收到錢」，不採用。
- **退款失敗記錄成中間狀態、允許後台重試**：對賣家體驗更友善（核准動作本身「成功」，退款另外非同步處理/重試），但需要額外的重試佇列/背景 job、以及「已核准但退款中」這個新狀態要串進 `OrderReturn`/`OrderCancellation` 的狀態機與前端顯示，複雜度大幅提高；目前規模用不到這麼複雜的補償機制，選擇「失敗就整筆 rollback、讓賣家手動重試」的簡單模型，之後有真實故障率數據再評估要不要做重試佇列。
- **引入現成的 composer ECPay SDK 套件**：可以省去手刻 CheckMacValue 的風險，但專案目前刻意保持零第三方套件依賴，且需要額外評估套件的維護狀態與程式碼品質，使用者明確選擇自己刻，不採用。
- **`MerchantTradeNo` 直接用 `order_number`**：語意上更直覺（不需要額外的映射函式），但 `order_number` 格式（`ORD-XXXXXXXX-<timestamp>`）含 `-` 且長度可能超過 20 字，不符合綠界限制，改用 `order.id` 反推是最小改動的解法，不採用。

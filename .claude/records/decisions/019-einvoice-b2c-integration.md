---
id: ADR-019
title: 電子發票（B2C）整合設計 — 開立/作廢/折讓觸發時機
date: 2026-07-09
status: Accepted（核心路徑已實作；測試與 post-change-review 尚未進行）
---

# ADR-019: 電子發票（B2C）整合設計 — 開立/作廢/折讓觸發時機

## Context

`EcpayGateway`/`PaymentService`（見 ADR-015）目前只串了綠界信用卡收單，完全沒有碰電子發票開立 API。台灣線上交易對消費者原則上都要開立統一發票（電子發票 B2C），這是既有、已知的功能缺口（記錄於 README）。

研究 ECPay 電子發票 API 後確認幾個影響設計的事實：

- **獨立的測試環境與憑證**：測試環境端點為 `https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue`，官方公開測試憑證 `MerchantID=2000132`（跟付款測試共用同一個特店編號），但 **`HashKey=ejCk326UnaZWKisg`／`HashIV=q9jcZX8Ib9LM8wYk` 跟付款用的（`5294y06JbISpM5x9`／`v77hoKGq4kWxNNIS`）是不同的一組**——代表電子發票在綠界內部是獨立的服務/憑證範疇，不能沿用 `config/ecpay.php`。
- **加密機制跟付款 API 完全不同**：付款 API（`AioCheckOut`）是參數明文帶出，排序後 SHA256 產生 `CheckMacValue`；電子發票 API 是把整包參數組成 JSON、URL encode 後 **AES 加密**成密文，包在外層的 `Data` 欄位裡（外層只有 `MerchantID`／`RqHeader.Timestamp`／`Data`／選填的 `PlatformID`）。因此**不能複用 `EcpayGateway::generateCheckMacValue()`**，需要一套獨立的 AES 加解密邏輯。
- **字軌／配號不屬於本 ADR 或應用程式碼的決策範圍**：字軌號碼的核發是「營業人向所在地稽徵機關申請 → 核准後於財政部電子發票整合服務平台取號 → 若有授權綠界，於綠界廠商後台（資料管理與維護 > 字軌與配號設定）登記」的行政前置作業。這是帳號層級的設定，跟這個 codebase 的實作無關——程式碼呼叫 Issue API 時，綠界系統會自動套用廠商後台已登記好的字軌配號，我們的程式碼不指定、也不管理字軌本身。
- 反之，**「何時開立」以及「取消/退貨時如何作廢/折讓」才是需要在程式碼層決定的邏輯**，牽涉 `Order`/`PaymentService`/`OrderService` 既有的狀態機與 hook 點，這是本 ADR 真正要決定的範圍。
- 目前系統唯一實際的收款路徑是 ECPay 線上刷卡預付（`orders.payment_method` 目前只有 `'simulated'` 一種值），沒有真正的貨到付款（COD）邏輯，因此「收到款項」目前就等於訂單狀態轉 `paid` 的那一刻，不需要考慮 COD 的第二條開立路徑。

## Decision

### 1. 職責切分：新增 `EcpayInvoiceGateway` + `InvoiceService`，比照 ADR-015 的慣例

跟 `EcpayGateway`/`PaymentService` 的切分方式一致：

- `app/Services/EcpayInvoiceGateway.php` 封裝電子發票的 wire-format 細節——AES 加解密、`Data` 欄位組裝/解密、HTTP 呼叫。**不與 `EcpayGateway` 共用任何程式碼或設定**，因為底層演算法（AES vs SHA256）與憑證都不同。
- `app/Services/InvoiceService.php` 是業務邏輯層——決定「這張訂單現在該不該開發票/作廢/折讓」，呼叫 `EcpayInvoiceGateway` 執行實際的 API 呼叫，`OrderService`/`PaymentService` 只呼叫 `InvoiceService` 的高階方法，不觸碰任何綠界電子發票的原始參數。
- 憑證與模式走新的 `config/ecpay_invoice.php` + `ECPAY_INVOICE_*` env（獨立於 `config/ecpay.php`，比照該檔案 config-driven 的慣例），本機開發預設值帶上面的官方公開測試憑證。

### 2. 開立時機：綁在 `PaymentService::markAsPaid()`

在 `markAsPaid()` 內、跟 `OrderPaidNotification` 一樣的 transaction 裡，額外呼叫 `InvoiceService::issueForOrder($order)`。理由：

- 這是目前系統唯一代表「已收到價款」的時間點，符合線上刷卡預付「收到價款時開立」的原則。
- `markAsPaid()` 已經是 transactional、冪等（ECPay notify 重送不會重複觸發）的 hook，符合這個專案「狀態變更相關的副作用都掛在同一個 transaction」的既有慣例（比照通知、`order_status_logs`）。
- 若未來要支援 COD，開立時機需要在 `Seller\OrderController::updateStatus` 轉 `STATUS_SHIPPED` 那條路徑另外加一個觸發點（COD 是交貨時才收到錢），跟線上刷卡的路徑分開判斷，**不在本次範圍內**，先留著這個口子。

### 3. 取消/退貨的反向邏輯：作廢 vs 折讓，分兩種情境處理

- **全額取消**（`OrderService::finalizeCancellation`，已付款訂單走 `PaymentService::refund()` 全額退款）→ 呼叫 `InvoiceService::voidForOrder($order)`：作廢整張發票。
- **部分/全額退貨**（`OrderService::finalizeReturn`，按 `CouponService::refundableAmount()` 算出的金額比例退款）→ 呼叫 `InvoiceService::allowanceForOrder($orderReturn, $refundAmount)`：開立**折讓單**（金額 = 這次退款金額），發票本身不作廢，只沖銷部分金額。

兩者都比照 `PaymentService::refund()` 目前的慣例，放在同一個 `DB::transaction` 的**最後**執行（`refund()` 呼叫之後）——但**跟退款不同，發票的作廢/折讓呼叫刻意用 `try/catch(\Throwable)` 包起來，只 `Log::warning`，絕不往外拋**。這點在寫下這份 ADR 時漏想了，是實作到一半才發現的：如果讓發票 API 的例外照 ADR-015 的「失敗就整筆 rollback」慣例往外拋，會把**已經成功送出、真實發生的 ECPay 退款一起 rollback**——而退款是真金流動作，一旦 ECPay 那邊執行了就無法用「資料庫 rollback」撤銷，硬要 rollback 只會讓我方資料庫狀態（訂單狀態、`refunded_amount`）跟 ECPay 的實際狀態不一致，比「發票沒開成功」的後果嚴重得多。

因此這裡刻意**不**沿用 ADR-015「失敗就整筆 rollback」的模型：退款失敗 → rollback（金流還沒發生，可以安全重試）；發票失敗 → 只記 log、不 rollback（金流已經發生，回滾只會製造更嚴重的不一致，發票本身是可以之後手動補開/補送的低風險副作用）。`PaymentService::markAsPaid()` 呼叫 `InvoiceService::issueForOrder()` 時也是同一個原則——不能因為開發票失敗就讓「訂單已付款」這個真實狀態被撤銷。

作廢 vs 折讓的精確界線（例如「當期/可作廢期限內」的定義）**直接定案為簡化規則**（本專案是 side project、非真實營運事業，不需要真的對稅捐機關負責，因此不必等財稅顧問核可才能往下做——見下方決策依據）：同一個發票開立當月內取消 → 作廢；跨月才取消，或任何退貨（無論是否跨月）→ 一律開折讓單。

### 4. 資料模型：`orders` 新增電子發票相關欄位

比照 ADR-015 新增 `gateway_trade_no` 的模式，新增：

- `orders.invoice_number`（nullable, varchar）— 綠界回傳的發票號碼（字軌+號碼）。
- `orders.invoice_random_code`（nullable, varchar(4)）— 發票隨機碼，跟號碼一起是消費者對獎/查詢的必要資訊。
- `orders.invoice_issued_at`（nullable, timestamp）。
- `orders.invoice_status`（nullable, varchar）— `Order` model 新增常數 `INVOICE_ISSUED` / `INVOICE_VOIDED` / `INVOICE_ALLOWANCED`，比照專案「狀態值一律走 model 常數」的慣例（見 CLAUDE.md「Model Constants」一節），不用 raw string。

不新增獨立的 `invoices` 資料表——目前一張訂單對應最多一張發票（折讓不換發票號碼，只是金額被沖銷），欄位量也不大，跟 `gateway_trade_no` 一樣直接掛在 `orders` 上即可；如果之後有「一張訂單開多張發票」的情境（目前規則下不存在）才需要拆表。

### 5. B2C API 已內建統編欄位，不需要額外串 B2B Invoice API

電子發票 Issue 參數裡 `CustomerIdentifier`（統編，選填）本來就支援買家索取公司戶發票的情境，所以**只需要串 B2C Issue API 一支**，`CustomerIdentifier` 有值時綠界會自動視為登記統編的發票；不需要另外串一支 B2B Invoice API（那是給企業對企業、需要另一套發票類別/稅別欄位的情境，目前用不到）。

## Consequences

- 優點：
  - 跟 ADR-015 一致的 Service/Gateway 切分，`OrderService`/`PaymentService` 的業務邏輯不需要認識電子發票的加密細節，之後要換開票服務商也只需要換一個 Gateway class。
  - 開立/作廢/折讓都掛在既有、已經 transactional 的 hook 點上（`markAsPaid`／`finalizeCancellation`／`finalizeReturn`），不需要新增額外的狀態機或非同步機制。
  - 字軌/配號的行政前置作業與程式碼設計明確切開，避免未來討論範圍時再次混淆「誰該做什麼」。
  - 發票 API 失敗（`try/catch` + log）不會回滾已經真實發生的付款/退款，避免「資料庫 rollback 但 ECPay 那邊金流已經執行」的更嚴重不一致——這比機械套用 ADR-015 的「失敗就整筆 rollback」更符合實際風險等級（金流 > 發票）。
- 缺點：
  - 發票開立/作廢/折讓失敗目前只寫一行 `Log::warning`，沒有重試佇列或後台提示——如果 ECPay 電子發票那邊持續性故障，唯一的線索是應用程式 log，需要人工發現並手動補開/補送，本專案規模暫時接受這個取捨（見 Alternatives Considered）。
  - 作廢 vs 折讓的界線是自訂的簡化規則，未必精確符合稅法對「可作廢期限」的實際定義；本專案是 side project、非真實營運，接受這個簡化，不因此卡住實作進度。
  - 跟 ADR-015 當初的驗證侷限一樣：目前設計**還沒有對 stage 環境發送過任何真實 HTTP 請求**，AES 加解密邏輯的正確性只能先靠官方文件比對與單元測試的內部一致性驗證，正式使用前必須做一次真實 stage 環境端對端測試（開立、作廢、折讓各跑一次）。
  - 新增 `orders` 的四個發票欄位是輕量的資料模型變動，但仍是一次 migration，且未來若真的出現「一張訂單多次折讓」的情境（例如分批退貨、每次都要查詢對應哪次折讓），目前欄位設計無法記錄折讓歷史，屆時需要另外拆一張 `invoice_allowances` 表。

## Open Questions / Follow-ups

1. 正式實作後需要對 ECPay 電子發票 stage 環境做一次真實端對端測試（開立/作廢/折讓），比照 ADR-015 的驗證侷限說明。
2. 若未來要支援 COD，需要在 `Seller\OrderController::updateStatus` 轉 `STATUS_SHIPPED` 的路徑加第二個開立觸發點，並跟線上刷卡路徑的觸發邏輯分流。
3. 若未來出現「一張訂單多次折讓」的情境，需要另外拆 `invoice_allowances` 表記錄折讓歷史，而不是只在 `orders` 上覆蓋單一欄位。

（作廢 vs 折讓的精確期限規則不再列為阻塞性問題——本專案是 side project，已直接採用上方「當月內取消=作廢，其餘一律折讓」的簡化規則定案，不等待財稅顧問確認。）

## Alternatives Considered

- **開立時機選在訂單建立時**（`OrderService::createOrdersFromCart`）：不採用——買家可能中途放棄 ECPay 導頁、卡片被拒等，此時還沒有真正收到款項，對著未成立的交易開發票不合規。
- **開立時機選在 `STATUS_COMPLETED`**：不採用——距離實際收款/交易時間可能已經過好幾天，且跟七天退貨窗重疊，時間點太晚。
- **沿用 `config/ecpay.php` 同一組憑證**：不採用——官方文件證實電子發票的 `HashKey`/`HashIV` 跟付款是不同的一組，硬要共用會直接導致簽章/加密失敗。
- **複用 `EcpayGateway::generateCheckMacValue()` 的邏輯**：不採用——電子發票是 AES 加密整包 JSON，付款是 SHA256 雜湊明文參數，兩者演算法本質不同，無法共用同一支方法，只能共用「這是綠界的服務、走 config-driven 憑證」這個外層慣例。
- **新增獨立 `invoices` 資料表**：目前規則下一張訂單最多對應一張發票，欄位量不大，暫不拆表；等出現多次折讓需要歷史記錄的情境才拆（見 Open Questions #4）。
- **串 B2B Invoice API**：不採用——B2C Issue API 的 `CustomerIdentifier` 欄位已經涵蓋消費者索取公司戶發票的情境，不需要額外一支 API。
- **發票 API 失敗比照 ADR-015 直接往外拋、讓整個 transaction rollback**：不採用——會把已經真實送出的 ECPay 付款/退款一起 rollback，造成資料庫狀態跟 ECPay 實際狀態不一致，風險比「發票沒開成功、需要人工補救」嚴重得多。改為 `try/catch` + log，接受「發票是可事後補救的低風險副作用」這個定位。

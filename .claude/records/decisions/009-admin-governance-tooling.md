---
id: ADR-009
title: 平台治理工具 — Admin 全站優惠券、Admin 操作稽核紀錄、賣家商品 CSV 匯入/匯出
date: 2026-07-03
status: Accepted
---

# ADR-009: 平台治理工具 — Admin 全站優惠券、Admin 操作稽核紀錄、賣家商品 CSV 匯入/匯出

## Context

盤點現有系統後發現三個「平台治理」缺口：(1) `coupons.shop_id` 資料層與 `CouponPolicy` 已支援全站優惠券（`shop_id = null`），但沒有對應的 Admin CRUD；(2) Admin 對使用者角色、賣場狀態、分類、優惠券的異動完全沒有留痕，只有訂單狀態變更有 `OrderStatusLog`；(3) 賣家商品只能單筆新增/編輯，商品量大時操作成本高。三者都屬於「平台維運工具」性質，一併規劃。

## Decision

- **Admin 全站優惠券**：沿用 ADR-008 既有的 `CouponService`/`CouponPolicy`，新增 `Admin\CouponController`，`store()` 強制 `shop_id = null`，`edit/update/destroy` 限定只能操作 `shop_id === null` 的優惠券（`abort_unless`），避免和賣家自己的優惠券管理路徑重疊。驗證規則抽出 `Concerns/ValidatesCouponRequest` trait，供 Seller 與 Admin controller 共用。
- **Admin 操作稽核紀錄**：新增 `admin_action_logs` 表 + `AdminActionLog` model（`admin_id`/`action`/`subject_type`/`subject_id`/`changes` JSON，morphTo subject）。寫入邏輯集中在 `AdminAuditLogger::log()` 單一入口，**由各 Admin controller 動作點明確呼叫**，不採 event-based。原因：`OrderStatusLog` 監聽單一 model（`Order`）的 `updated` 事件可行，但稽核紀錄橫跨 User/Shop/Category/Coupon 四種異質 model 與不同操作語意（角色變更、狀態變更、CUD），事件監聽需要在每個 model 各自掛 hook 且難以統一 action 命名；明確呼叫在可讀性與掌控性上更好。
- **賣家商品 CSV 匯入/匯出**：新增 `league/csv` 套件（RFC4180 相容，避免手刻 `fputcsv`/`fgetcsv` 的引號/跳脫邊界問題；專案先前無任何 CSV 相關程式碼）。匯入以 **CSV `name` 欄位在賣家自己商店範圍內比對既有商品**（找到即更新、找不到即新增）；每列各自 try/catch、不包在同一個 DB transaction，單列失敗不影響其餘列。

## Consequences

- 優點：全站優惠券零 schema 異動即可上線（ADR-008 已預留）；稽核紀錄補上 Admin 側的可追溯性，出問題可回答「誰在何時改了什麼」；CSV 匯入/匯出大幅降低賣家大量商品維護的操作成本。
- 優點：稽核寫入與商品匯入錯誤處理都做到「單點失敗不影響其他操作」（稽核寫入是附加動作、失敗不應擋主流程；CSV 逐列 try/catch 避免一列打字錯誤讓整批 rollback）。
- 缺點：`AdminAuditLogger.log()` 為明確呼叫，日後新增 Admin 異動端點時容易忘記補呼叫（event-based 可自動涵蓋但犧牲可讀性/掌控性，此處判斷可讀性優先）；需仰賴 code review 把關。
- 缺點：CSV 匯入以「商品名稱」比對，若賣家匯出後修改商品名稱再匯入，會被當成新商品而非更新舊商品（已與商品名稱唯一性假設綁定，v1 先接受此限制）。

## Alternatives Considered

- **稽核紀錄改為 event-based**（比照 `OrderStatusLog` 監聽各 model 的 `updated`/`created`/`deleted` 事件）：可自動涵蓋所有異動、不怕漏呼叫，但四種 model 的事件語意不同（角色變更是 `User` 的欄位變化、優惠券刪除需要 subject 快照），仍需在各 model 額外寫判斷邏輯區分「是不是 admin 操作的」，複雜度未必比明確呼叫低；且明確呼叫在稽核紀錄這種「該做什麼很明確」的場景更直觀，不採用。
- **CSV 匯入以選填 `id` 欄位比對**（有 id 就更新、沒有就新增）：比對更精確（不怕改名誤判），但要求使用者理解「不能亂改 id 欄位」，v1 先以較直覺的商品名稱比對為預設，未來如有需求再加 id 比對模式。
- **CSV 匯入包在單一 DB transaction 內**：任何一列失敗即整批 rollback，行為單純，但一份數百列的 CSV 只要一列打字錯誤就整批失敗，對批次匯入的使用情境不友善，不採用。

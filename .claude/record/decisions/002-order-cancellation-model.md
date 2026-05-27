---
id: ADR-002
title: 取消訂單採用獨立 order_cancellations 表
date: 2026-05-27
status: Accepted
---

# ADR-002: 取消訂單採用獨立 order_cancellations 表

## Context
取消訂單需支援兩種發起來源（買家 / 賣家）、審核狀態（requested / approved / rejected）、發起與回應理由、回應者與時間。買家發起且賣家已開始處理時需經審核，且拒絕後不可再申請，未來可能擴充雙向審核或取消歷史。

## Decision
新增獨立 `order_cancellations` 表（order_id, initiated_by, status, reason, responder_id, response_reason, responded_at），而非在 orders 表加多個欄位。Order 以 hasMany/latestOfMany 關聯，並提供 canBeCancelledDirectly / canRequestCancellation / pendingCancellation / wasCancellationRejected 等 helper。

## Consequences
- 優點：可記錄多次取消嘗試與完整審核軌跡；orders 表不膨脹；易擴充拒絕後封鎖、雙向審核等流程。
- 缺點：查詢取消狀態需 join/load 關聯；orders.status 仍是顯示用的最終狀態，「審核中」狀態靠關聯判斷。

## Alternatives Considered
- 直接在 orders 加 cancel_status / cancel_reason / cancel_requested_by 欄位：較簡單，但無法保留歷史、難支援拒絕後再申請的判斷與未來擴充，故不採用。

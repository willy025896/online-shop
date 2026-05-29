---
id: ADR-004
title: 通知系統採用 database + broadcast 通道，不做 mail
date: 2026-05-28
status: Accepted
---

# ADR-004: 通知系統採用 database + broadcast 通道

## Context

導入通知系統時，需選擇 Laravel Notification 的傳送通道。

Laravel 內建支援多種通道：`database`、`mail`、`broadcast`、`sms`、`slack`、`vonage` 等。一個 Notification 可同時走多個通道。

專案情境：

- 已安裝 Laravel Reverb + Laravel Echo + Pusher.js，且 `MessageSent` event 已驗證 broadcast 運作
- `.env.example` 沒有設定 SMTP（`MAIL_MAILER=log`）
- 沒有 SMS、Slack 整合需求
- 第一期目標：賣家/買家在站內看到鈴鐺，登入中時即時推播

## Decision

第一版 Notification 一律 `via()` 回傳 `['database', 'broadcast']`：

- `database` 寫入 Laravel 內建 `notifications` 表，鈴鐺與通知中心讀取
- `broadcast` 透過 Reverb 推送到 `private-App.Models.User.{id}` 私人頻道，前端用 `Echo.private(...).notification(cb)` 即時更新 badge

不做 `mail`、`sms`、`slack`。

## Consequences

優點：

- 零外部依賴（不需要 SMTP、不需要 Mailgun/SendGrid 設定）
- Reverb 已就緒，廣播零增量成本
- 未來想加 mail，只需在個別 Notification 的 `via()` 加 `'mail'` + 寫 `toMail()`，不影響既有實作

缺點：

- 使用者未登入時不會收到任何外部通知（email/sms 都沒有）
- 賣家若離線，新訂單通知要等下次登入才會看到

## Alternatives Considered

1. **只做 `database`**：純站內，連 broadcast 都不做 → 鈴鐺需手動刷新或輪詢。已被排除——基礎設施完備、不做白不做。
2. **同時做 `mail`**：能 reach 離線使用者 → 但 SMTP/模板/Mailpit 開發環境設定都要花時間，第一版不值得。第二期再加。
3. **不用 Laravel Notification、自寫 events**：失去 `database` channel 的 `notifications` 表與 `markAsRead` 等便利方法 → 沒理由重造輪子。

---
id: 2026-05-08/001
type: Feature
status: Done
---

# Task: 新增 npm run dev:full

## Request

希望能用單一指令同時啟動前後端開發環境（npm run dev + php artisan serve），Ctrl+C 能乾淨結束所有 process。

## Changes

| File | Action | Notes |
|------|--------|-------|
| package.json | Modified | 新增 dev:full script；加入 concurrently ^9.2.1 devDependency |

## Outcome

`npm run dev:full` 同時跑 `php artisan serve` 與 `vite`，`--kill-others` 確保 Ctrl+C 同時停掉兩個 process。

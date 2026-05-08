# AI Assistant Skills - 自動記錄規範

> 本文件定義 AI Assistant 在此專案中的行為規範，每次對話都必須遵守。

## 核心規則

**每次執行任務時，AI 必須：**

1. 在 `tasks/` 建立合併的任務記錄（包含請求與變更）
2. 若任務涉及架構決策，建立 ADR 至 `decisions/`
3. 更新 `record/INDEX.md`

> Claude Code 的 memory（使用者偏好/反饋）與 plan mode（實作計畫）由 Claude 內建功能管理，不放入 `.claude/`。

---

## 分工說明

| 儲存位置 | 用途 |
|----------|------|
| `.claude/record/tasks/` | 每次任務的請求與程式碼變更記錄 |
| `.claude/record/decisions/` | 架構/技術層面的決策記錄（ADR） |
| `.claude/record/INDEX.md` | 所有任務與決策的索引 |
| Claude memory | 使用者偏好、協作風格、專案脈絡 |
| Claude plan mode | 任務實作計畫 |

---

## Task 記錄模板

路徑：`.claude/record/tasks/YYYY-MM-DD/XXX-description.md`

```markdown
---
id: YYYY-MM-DD/XXX
type: Feature | Bug Fix | Refactor | Docs
status: Done | In Progress
---

# Task: 簡短標題

## Request
使用者請求了什麼（一到三句話）。

## Changes
| File | Action | Notes |
|------|--------|-------|
| path/to/file | Added / Modified / Deleted | 說明 |

## Outcome
實際完成了什麼，結果如何（含測試結果）。

## Decision
（選填）若涉及架構決策 → 連結到 decisions/ 對應 ADR。
```

---

## ADR 模板（架構決策記錄）

路徑：`.claude/record/decisions/XXX-title.md`

**觸發時機（符合任一條件才建立）：**
- 引入新的套件、技術或框架
- 改變資料模型設計（影響多個功能）
- 改變架構模式（e.g., REST → WebSocket、同步 → 非同步）
- 安全性或認證設計變更

**不需要建立 ADR：** 一般功能開發、UI 調整、常規 bug 修正。

```markdown
---
id: ADR-XXX
title: 決策標題
date: YYYY-MM-DD
status: Accepted
---

# ADR-XXX: 決策標題

## Context
決策背景與問題描述。

## Decision
做了什麼決定。

## Consequences
- 優點：...
- 缺點：...

## Alternatives Considered
其他方案與不採用原因。
```

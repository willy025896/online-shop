# 記錄模板

## Task 模板

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

## ADR 模板

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

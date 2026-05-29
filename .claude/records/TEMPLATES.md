# 記錄模板

> 程式碼變更記錄由 `implementation-recorder` skill 自動產生至 `.claude/records/{type}/`，不需手動建立。
> 本模板僅供 ADR 使用。

---

## ADR 模板

路徑：`.claude/records/decisions/XXX-title.md`

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

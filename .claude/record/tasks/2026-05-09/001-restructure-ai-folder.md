---
id: 2026-05-09/001
type: Refactor
status: Done
---

# Task: 重構 AI 操作記錄資料夾結構

## Request

將 `.ai/` 資料夾重新設計，目標是減少維護負擔、整合 Claude Code 內建功能、補齊空缺功能的明確定義。

## Changes

| File | Action | Notes |
|------|--------|-------|
| .ai/ → .claude/ | Renamed | 配合 Claude Code 專案目錄慣例 |
| .claude/record/tasks/ | Added | 合併原 prompts/ + changes/，每任務一份記錄 |
| .claude/record/INDEX.md | Moved | 從根層移入 record/，與任務/決策記錄同層 |
| .claude/AI-SKILLS.md | Modified | 新模板、新觸發規則、分工說明、路徑全面更新 |
| .claude/README.md | Modified | 更新為新結構說明 |
| CLAUDE.md | Modified | AI-SKILLS.md 路徑與目錄樹說明更新 |
| .claude/record/tasks/2026-03-06/ | Added | 遷移 10 個歷史任務（原 prompts/ + changes/ 合併） |
| .claude/record/tasks/2026-04-30/ | Added | 遷移 2 個歷史任務 |
| .claude/record/tasks/2026-05-08/ | Added | 遷移 1 個歷史任務 |
| prompts/ | Deleted | 合併入 tasks/ |
| changes/ | Deleted | 合併入 tasks/ |
| sessions/ | Deleted | 功能由 Claude Code 內建 context 取代 |

## Outcome

舊結構（6 個資料夾、每任務 2 份檔案）簡化為新結構（`record/tasks/` + `record/decisions/`，每任務 1 份）。13 個歷史任務全數遷移完成，重複編號問題（原 009-stage4）修正為 010。

`.claude/` 根層保留 AI-SKILLS.md（規則）與 README.md（說明），`record/` 專放歷史記錄，與 Claude Code 未來可能新增的 `settings.json`、`commands/` 等工具設定區隔開來。

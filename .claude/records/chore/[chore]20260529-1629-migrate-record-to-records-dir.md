# [chore] Migrate record dir and unify with implementation-recorder

**日期：** 2026-05-29 16:29
**類型：** chore

## 變更內容

將 `.claude/record/` 目錄重命名為 `.claude/records/`，並更新 `AI-RULES.md` 改為觸發 `implementation-recorder` skill 來自動建立變更記錄，消除手動維護 tasks 記錄的重複規範。

## 異動檔案

- `.claude/record/` → `.claude/records/` — 目錄更名，保留所有子目錄與既有檔案
- `.claude/AI-RULES.md` — 移除手動建立 tasks 記錄的規則，改為觸發 `implementation-recorder`；更新 ADR 路徑參照至 `.claude/records/decisions/`
- `.claude/records/TEMPLATES.md` — 移除 Task 模板（已由 skill 取代），保留 ADR 模板並更新路徑

## 實作思路

原本 `AI-RULES.md` 要求手動在 `tasks/` 建立記錄，與 user scope 的 `implementation-recorder` skill 在 `.claude/records/` 自動產生記錄產生衝突。統一以 skill 為準，手動 tasks 規則廢除，ADR decisions 仍保留手動建立。

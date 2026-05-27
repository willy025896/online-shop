# [docs] Update CLAUDE.md and README.md

**日期：** 2026-05-27 17:12
**類型：** docs

## 變更內容

新增 Model Constants 說明章節至 CLAUDE.md，並修正兩份文件中已過時的 controller 數量、model 數量與 Policies 目錄位置。

## 異動檔案

- `CLAUDE.md` — 新增 Model Constants 章節（常數表格與使用規範）；controller 數由 21 → 22；model 數由「8 + User」→ 12；`.claude/` 說明更新
- `README.md` — 修正 Policies 路徑（從 `app/Http/` 移至正確的 `app/`）；補充 SetLocale middleware；controller/model 數同步修正；新增 `.claude/` 目錄說明

## 實作思路

README.md 原本將 `Policies/` 誤放在 `app/Http/` 下，但實際路徑是 `app/Policies/`。CLAUDE.md 的 Model Constants 章節是本次重構的直接產出，讓 AI 未來在同專案工作時能立即知道此慣例的存在，避免重新引入魔術字串。

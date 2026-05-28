# AI 操作規範

> 本文件定義 AI Assistant 在此專案中的行為規範，每次對話都必須遵守。

## 核心規則

**每次執行任務時，AI 必須：**

1. 在 `tasks/` 建立合併的任務記錄（包含請求與變更）
2. 若任務涉及架構決策，建立 ADR 至 `decisions/`
3. 完成任何程式碼異動（新增、修復、重構）後，**立即執行 `post-change-review` skill** 進行審查

> Claude Code 的 memory（使用者偏好/反饋）與 plan mode（實作計畫）由 Claude 內建功能管理，不放入 `.claude/`。

---

## 分工說明

| 儲存位置 | 用途 |
|----------|------|
| `.claude/record/tasks/` | 每次任務的請求與程式碼變更記錄 |
| `.claude/record/decisions/` | 架構/技術層面的決策記錄（ADR） |
| Claude memory | 使用者偏好、協作風格、專案脈絡 |
| Claude plan mode | 任務實作計畫 |

---

## 檔案操作規範

此專案執行環境為 **Windows**。操作檔案時必須使用對應的專用工具，禁止混用：

| 操作 | 使用工具 | 說明 |
|------|---------|------|
| 讀取檔案 | `Read` tool | 唯一合法的讀檔方式 |
| 新增檔案 | `Write` tool | 用於建立全新檔案 |
| 編輯檔案 | `Edit` tool | 用於修改現有檔案內容，**必須先 `Read` 過才能 `Edit`** |
| 搜尋檔名 | `Glob` tool | 用於 pattern 比對找檔案 |
| 搜尋內容 | `Grep` tool | 用於搜尋檔案內文字 |
| **刪除檔案** | `PowerShell` tool | 使用 `Remove-Item "絕對路徑"` |

**嚴禁**在 `Bash` tool 中呼叫 `Remove-Item`、`Get-ChildItem` 等 PowerShell cmdlet——Bash tool 是 POSIX 環境，不認識這些指令，必定失敗。

**Edit 前必須先 Read**：`Edit` tool 要求同一對話中必須先用 `Read` 讀過該檔案，否則會拋出 `File must be read first` 錯誤。即使只需要改一行，也要先 `Read` 再 `Edit`。

---

## 記錄模板

建立 task 或 ADR 時，讀取 `.claude/record/TEMPLATES.md` 取得格式。

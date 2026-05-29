# .claude/ - AI 操作記錄資料夾

> 此資料夾用於記錄 AI Assistant 在本專案中的所有操作歷史與規範。

## 資料夾結構

```
.claude/
├── AI-RULES.md         # AI 操作規範（每次任務前必讀）
├── README.md           # 本說明文件
└── records/
    ├── feature/        # 新功能記錄（implementation-recorder 自動產生）
    ├── fix/            # bug 修復記錄（implementation-recorder 自動產生）
    ├── refactor/       # 重構記錄（implementation-recorder 自動產生）
    ├── style/          # 樣式調整記錄（implementation-recorder 自動產生）
    ├── docs/           # 文件變更記錄（implementation-recorder 自動產生）
    ├── chore/          # 設定/工具異動記錄（implementation-recorder 自動產生）
    ├── test/           # 測試記錄（implementation-recorder 自動產生）
    ├── tasks/          # 歷史遺留（舊格式任務記錄，2026-03 ~ 2026-05 初）
    ├── decisions/      # 架構決策記錄（ADR，手動建立）
    └── TEMPLATES.md    # ADR 格式模板
```

## 運作方式

1. 使用者向 AI 發出請求
2. AI 完成任何程式碼異動後，執行 `implementation-recorder` skill 自動在對應 `records/{type}/` 建立記錄
3. 若涉及架構/技術決策，手動建立 ADR 至 `records/decisions/`（格式參考 `records/TEMPLATES.md`）

詳細規範請參考 [AI-RULES.md](AI-RULES.md)。

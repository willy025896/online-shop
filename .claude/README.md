# .claude/ - AI 操作記錄資料夾

> 此資料夾用於記錄 AI Assistant 在本專案中的所有操作歷史與規範。

## 資料夾結構

```
.claude/
├── AI-RULES.md         # AI 操作規範與檔案模板
├── README.md           # 本說明文件
└── record/
    ├── tasks/          # 任務記錄（請求 + 變更合併）
    └── decisions/      # 架構決策記錄（ADR）
```

## 運作方式

1. 使用者向 AI 發出請求
2. AI 在 `record/tasks/` 建立任務記錄（包含請求與變更）
3. 若涉及架構/技術決策，記錄到 `record/decisions/`

詳細規範請參考 [AI-RULES.md](AI-RULES.md)。

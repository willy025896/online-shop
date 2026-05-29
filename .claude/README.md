# .claude/ - AI 操作記錄資料夾

> 此資料夾用於記錄 AI Assistant 在本專案中的所有操作歷史與規範。

## 資料夾結構

```
.claude/
├── AI-RULES.md         # AI 操作規範（每次任務前必讀）
├── README.md           # 本說明文件
└── records/
    ├── feature/        # 歷史遺留（舊實作記錄）
    ├── fix/            # 歷史遺留（舊實作記錄）
    ├── refactor/       # 歷史遺留（舊實作記錄）
    ├── style/          # 歷史遺留（舊實作記錄）
    ├── docs/           # 歷史遺留（舊實作記錄）
    ├── chore/          # 歷史遺留（舊實作記錄）
    ├── test/           # 歷史遺留（舊實作記錄）
    ├── tasks/          # 歷史遺留（舊格式任務記錄，2026-03 ~ 2026-05 初）
    ├── decisions/      # 架構決策記錄（ADR，手動建立）
    └── TEMPLATES.md    # ADR 格式模板
```

## 運作方式

1. 使用者向 AI 發出請求
2. 若涉及架構/技術決策，手動建立 ADR 至 `records/decisions/`（格式參考 `records/TEMPLATES.md`）

詳細規範請參考 [AI-RULES.md](AI-RULES.md)。

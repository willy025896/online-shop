# .ai/ - AI 操作記錄資料夾

> 此資料夾用於記錄 AI Assistant 在本專案中的所有操作歷史。

## 資料夾結構

```
.ai/
├── AI-SKILLS.md        # AI 行為規範與檔案模板
├── INDEX.md            # 操作索引 (由 AI 自動維護)
├── README.md           # 本說明文件
├── changes/            # 程式碼變更記錄
├── decisions/          # 架構決策記錄 (ADR)
├── prompts/            # 使用者請求記錄
└── sessions/           # 完整對話 session 記錄
```

## 運作方式

1. 使用者向 AI 發出請求
2. AI 在 `prompts/` 記錄原始請求
3. AI 執行任務後在 `changes/` 記錄變更
4. 若涉及重要決策,記錄到 `decisions/`
5. AI 更新 `INDEX.md` 索引

詳細規範請參考 [AI-SKILLS.md](AI-SKILLS.md)。
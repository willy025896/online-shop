# Changes

記錄 AI 對程式碼的所有變更。

## 對應關係

每個 change 應對應一個 prompt,使用相同編號:

```
prompts/2026-03-06/001-add-payment-feature.md
   ↓ 對應
changes/2026-03-06/001-add-payment-feature.md
```

## 必須包含

- **變更的檔案清單** - 新增/修改/刪除的檔案
- **變更說明** - 做了什麼、為什麼這樣做
- **測試結果** - 是否有加測試、測試是否通過
- **相關決策** - 如有重要決策,連結到 decisions

## 檔案結構

```
YYYY-MM-DD/
├── 001-add-payment-feature.md
├── 002-fix-cart-validation.md
└── 003-refactor-order-service.md
```

詳細格式請參考 `AI-SKILLS.md` 中的模板。

## 提示

- 清楚說明變更原因
- 列出所有受影響的檔案
- 記錄測試狀態
- 標記破壞性變更 (Breaking Changes)
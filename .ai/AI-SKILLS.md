# AI Assistant Skills - 自動記錄規範

> 本文件定義 AI Assistant 在此專案中的行為規範,每次對話都必須遵守。

## 核心規則

**每次執行任務時,AI 必須:**

1. 記錄使用者的 prompt
2. 記錄完成後的變更
3. 重要決策記錄到 decisions
4. 更新 INDEX.md
5. 更新 PLAN.md

---

## 檔案模板

### Prompt 記錄模板

路徑: `.ai/prompts/YYYY-MM-DD/XXX-description.md`

```markdown
# Prompt: [簡短描述]

**Date**: YYYY-MM-DD HH:MM
**Type**: Feature / Bug Fix / Refactor / Documentation

## User Request

[使用者原始請求內容]

## Expected Outcome

[預期結果]

## Status

- [ ] 開始執行
- [ ] 變更完成
- [ ] 測試通過
- [ ] 記錄完成
```

---

### Change 記錄模板

路徑: `.ai/changes/YYYY-MM-DD/XXX-description.md`

```markdown
# Change: [簡短描述]

**Date**: YYYY-MM-DD HH:MM
**Prompt**: [對應的 prompt 編號與連結]
**Type**: Feature / Bug Fix / Refactor / Documentation

## Summary

[變更摘要]

## Files Changed

| Action | File Path | Description |
|--------|-----------|-------------|
| Added  | path/to/file | 說明 |
| Modified | path/to/file | 說明 |
| Deleted | path/to/file | 說明 |

## Testing

- **Tests Added**: Yes / No
- **Tests Passed**: Yes / No / N/A
- **Manual Testing**: [說明]

## Related Decisions

- [連結到相關 ADR，若無則填 N/A]

## Breaking Changes

- [若無則填 None]
```

---

### Decision 記錄模板 (ADR)

路徑: `.ai/decisions/XXX-title.md`

```markdown
# ADR-XXX: [決策標題]

**Date**: YYYY-MM-DD
**Status**: Proposed / Accepted / Deprecated / Superseded

## Context

[決策背景與問題描述]

## Decision

[做了什麼決定]

## Consequences

### Pros
- [優點]

### Cons
- [缺點]

## Alternatives Considered

- [其他方案與不採用原因]
```

---

### Session 記錄模板

路徑: `.ai/sessions/YYYY-MM-DD-topic.md`

```markdown
# Session: [主題描述]

**Date**: YYYY-MM-DD
**Duration**: [大約時長]

## Prompts Included

- [prompt 編號與連結列表]

## Summary

[整個 session 的摘要]

## Key Outcomes

- [重要成果列表]
```

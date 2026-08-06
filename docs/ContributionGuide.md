# Contribution Guide

Thanks for contributing to AMS.

## Process

1. Open an issue or agree the milestone scope first
2. Create a focused branch
3. Implement **one** coherent change set
4. Add/adjust Feature tests
5. Run Pint + `php artisan test`
6. Open a PR with summary + test plan

## Standards

- Follow `.cursorrules` and `docs/CodingStandards.md`
- Prefer DDD domain placement over dumping code in `app/Http`
- Do not commit secrets (`.env`, credentials)
- Do not expand scope into Phase 2 modules without approval

## Commit Messages

Use concise, why-focused messages:

```text
fix company controller to resolve models via service layer
```

## PR Template (suggested)

```markdown
## Summary
- …

## Test plan
- [ ] php artisan test
- [ ] vendor/bin/pint --test
- [ ] Manual smoke of affected UI
```

More detail: `docs/Contributing.md`

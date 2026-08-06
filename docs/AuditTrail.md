# Audit Trail

See detailed module documentation:

**`docs/modules/Audit.md`**

## Summary

| Store | Purpose |
|-------|---------|
| Spatie `activity_log` | Cross-module activity |
| `audit_logs` | Before/after audit trails |
| `user_login_histories` | Login/logout device tracking |
| `api_logs` | API request monitoring |
| `system_events` | Operational events |
| `error_logs` | Exception persistence |

## Integration for Future Modules

```php
use App\Shared\Helpers\AuditHelper;

AuditHelper::activity('module', 'Description', $user, $model, [], 'created');
AuditHelper::trail('module', 'updated', $user, $model, $before, $after);
AuditHelper::systemEvent('queue_failed', 'queue', ['id' => $jobId], 'error');
```

## Paths

- Backend: `backend/app/Domains/Audit/`
- Frontend: `frontend/src/modules/audit/`
- Tests: `backend/tests/Feature/Audit/AuditMonitoringTest.php`

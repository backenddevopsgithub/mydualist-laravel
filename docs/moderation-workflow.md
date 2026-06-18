# Reported Duas moderation workflow

Admins review user-reported dua submissions in Filament under **Moderation → Reported Duas**.

## Queue scope

The queue lists submissions with an active report (`reported_at` is set). After a report is dismissed, the submission leaves the queue even if other moderation metadata remains on the record.

## Available actions

| Action | Effect |
| --- | --- |
| **Hide submission** | Sets status to `hidden`. The report stays active so the item remains in the queue. |
| **Restore submission** | Sets status to `pending` and clears the report timestamp via the shared status transition. The submission leaves the queue. |
| **Dismiss report** | Clears report fields, restores `status_before_report` (or `pending` when unknown), and records moderation. |

Row and bulk actions require confirmation and show a success notification when complete. Optional moderation notes can be added in the confirmation modal.

## Audit trail

Each moderation action writes:

- Latest summary on the submission: `moderated_by`, `moderated_at`, `moderation_action`, `moderation_notes`
- A full history row in `dua_submission_moderation_logs`

Open **View full submission** to see report details and the moderation history timeline.

## Auto-hide threshold

Optional automatic hiding is controlled in `config/mydualist.php`:

```php
'moderation' => [
    'auto_hide_threshold' => env('MYDUALIST_MODERATION_AUTO_HIDE_THRESHOLD') !== null
        ? (int) env('MYDUALIST_MODERATION_AUTO_HIDE_THRESHOLD')
        : null,
],
```

- Disabled by default (`null`)
- Existing reports are backfilled with `report_count = 1` but do **not** trigger auto-hide retroactively
- Each new report increments `report_count`; when the count reaches the threshold, the submission is hidden and an `auto_hide` audit entry is created

Example `.env` value:

```env
MYDUALIST_MODERATION_AUTO_HIDE_THRESHOLD=3
```

## Permissions

- Filament panel access still requires an active admin account
- Reported Duas queue and moderation actions use `DuaSubmissionPolicy::moderateAny()` / `moderate()` (admin only)
- List-owner moderation outside Filament is unchanged and still uses `manage()` / `report()`

## Filters and search

Filters: report reason, date reported, list occasion, submission status, moderated / unmoderated.

Search: submitter name, submitter email, list title, dua content.

## Related code

- Filament resource: `app/Filament/Resources/ReportedDuaSubmissionResource.php`
- Domain actions: `app/Domains/Submissions/Actions/*ReportedDuaSubmissionAction.php`
- Policy: `app/Policies/DuaSubmissionPolicy.php`
- Tests: `tests/Feature/Filament/ReportedDuaModerationTest.php`

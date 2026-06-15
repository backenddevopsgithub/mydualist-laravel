# Scheduler and Queue Setup

This document describes the server configuration required for Laravel's task scheduler and queue workers in the MyDualist Laravel application.

## Required server cron entry

Laravel's scheduler must be invoked every minute. Add this cron entry for the deployment user:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/project` with the absolute path to the `mydualist-laravel` application root (the directory containing `artisan`).

## Required queue worker command

Background jobs (scheduled reminders, queued notifications, and future email automations) require a long-running queue worker:

```bash
php artisan queue:work
```

For production, run this under process supervision (see [Supervisor recommendations](#supervisor-recommendations) below) rather than manually in a terminal session.

## Scheduled tasks

The following tasks are registered in `routes/console.php`:

| Task | Frequency | Purpose |
|------|-----------|---------|
| `scheduler:heartbeat` | Every minute | Records scheduler health in cache |
| `SendDailyDigestJob` | Daily at 23:59 (`MYDUALIST_DAILY_DIGEST_AT`) | Batches owner digests for `daily_summary` lists |
| `SendNoActivityReminderJob` | Hourly | Placeholder for no-activity reminders |
| `SendClosingSoonReminderJob` | Every 30 minutes | Placeholder for closing-soon reminders |
| `SendListImageReminderJob` | Daily | Placeholder for list-image reminders |

List scheduled tasks:

```bash
php artisan schedule:list
```

## Verification steps

### Verify the scheduler heartbeat

1. Ensure the cron entry is installed and `php artisan schedule:run` executes without errors.
2. Run the heartbeat manually:

   ```bash
   php artisan scheduler:heartbeat
   ```

3. Confirm the cache key is set (Redis example):

   ```bash
   php artisan tinker --execute="dump(cache()->get('scheduler:last_run'));"
   ```

4. Check application logs for:

   ```
   Laravel scheduler heartbeat updated.
   ```

5. Open the Filament admin dashboard (`/admin`). The **System Health** widget should show **Healthy** when the heartbeat is within the last 2 minutes.

### Verify jobs are dispatched

1. Run the scheduler once:

   ```bash
   php artisan schedule:run
   ```

2. For due scheduled jobs, confirm entries appear in the queue:
   - **Redis** (`QUEUE_CONNECTION=redis`): inspect the `queues:default` list.
   - **Database** (`QUEUE_CONNECTION=database`): inspect the `jobs` table.

3. Review registered schedules:

   ```bash
   php artisan schedule:list
   ```

### Verify queue workers are running

1. Confirm a worker process is active (Supervisor status, `ps`, or your hosting panel).
2. Dispatch a test job or wait for a scheduled job to become due, then confirm the pending job count decreases.
3. On the Filament dashboard, the **System Health** widget shows the active queue connection and current pending job count.

### Verify failed jobs

1. List failed jobs:

   ```bash
   php artisan queue:failed
   ```

2. Inspect the `failed_jobs` table directly if using the default `database-uuids` failed-job driver.

3. The Filament **System Health** widget displays the failed job count and the timestamp of the most recent failure.

### Verify the Filament dashboard widget

1. Log in to the admin panel at `/admin`.
2. On the dashboard, locate the **System Health** widget at the top of the stats row.
3. Confirm it displays:
   - Scheduler status (Healthy / Warning / Offline)
   - Last heartbeat timestamp
   - Queue connection name
   - Pending jobs count
   - Failed jobs count
   - Last failed job timestamp (when applicable)

#### Scheduler health thresholds

| Status | Condition |
|--------|-----------|
| Healthy | Heartbeat within the last 2 minutes |
| Warning | Heartbeat between 2 and 5 minutes old |
| Offline | Heartbeat older than 5 minutes, or never recorded |

## Production setup notes

### Supervisor recommendations

Run both the scheduler (optional `schedule:work` alternative) and queue workers under Supervisor.

**Queue worker** (`/etc/supervisor/conf.d/mydualist-queue.conf`):

```ini
[program:mydualist-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Adjust `redis` if `QUEUE_CONNECTION` differs. Scale `numprocs` if throughput requires multiple workers.

**Scheduler** — prefer the system cron entry above. Alternatively, for single-server setups:

```ini
[program:mydualist-scheduler]
command=php /path/to/project/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/scheduler.log
```

Do not run both cron `schedule:run` and `schedule:work` on the same server.

### Queue restart procedures after deployment

After deploying code changes that affect jobs or workers:

```bash
php artisan queue:restart
```

This signals workers to exit gracefully after their current job, then Supervisor (or your process manager) restarts them with the new code.

Also clear and rebuild caches if configuration changed:

```bash
php artisan config:cache
php artisan route:cache
```

### Scheduler troubleshooting tips

- **Widget shows Offline**: Confirm cron is installed, the cron user can run `php artisan`, and `schedule:run` is not erroring. Check `storage/logs/laravel.log`.
- **Heartbeat never updates**: Verify `CACHE_STORE` is reachable (Redis/Memcached/file). The heartbeat uses cache key `scheduler:last_run` (with the application cache prefix).
- **Jobs scheduled but not processed**: Ensure `queue:work` is running and `QUEUE_CONNECTION` matches your worker command.
- **Pending count stays high**: Add workers, check for failing jobs (`queue:failed`), and confirm Redis/database connectivity.
- **Jobs fail immediately**: Inspect `failed_jobs` and logs; email delivery is not configured on staging, so notification jobs may fail once business logic is implemented.

## Environment variables

| Variable | Typical production value | Notes |
|----------|-------------------------|-------|
| `QUEUE_CONNECTION` | `redis` | Default in `.env.example` |
| `CACHE_STORE` | `redis` | Required for scheduler heartbeat persistence |
| `REDIS_HOST` | Host of Redis instance | Used by queue and cache |

Ensure migrations have been run so `jobs`, `job_batches`, and `failed_jobs` tables exist even when using the Redis queue driver.

# Architecture overview

## Request flow

1. Apache routes non-file requests to `public_html/index.php`.
2. `app/bootstrap.php` loads config, session, autoloads `App\*` classes.
3. `App\Core\Router` matches method + path and invokes a controller action.
4. Controllers use models (PDO) and render views under `app/views/`.

## Roles

| Role    | Capabilities |
|---------|--------------|
| admin   | Users, departments, classes, schedules, assignments, rejections, attendance view, reports CSV, announcements, audit log, CSV import |
| teacher | Browse classes, accept/reject offers (with reason), PIN check-in, profile |
| monitor | Verify attendance for assigned schedules, escalate, reports |
| student | Read-only timetable, announcements, live status polling |

## Real-time behaviour

There are no WebSockets (often blocked on shared hosting). Browsers poll `GET /api/live-status` every ~15s (configurable) for dashboard updates.

## Database

See `database/schema.sql`. Core entities: users, departments, classes, schedules, class_assignments, rejection_explanations, attendance_records, enrollments, announcements, notifications, audit_logs.

## Cron

`public_html/cron_reminders.php` sends in-app notifications roughly one hour before class start. Protect with `cron_key` in config.

# Security checklist (v1)

- [x] Prepared statements (PDO) for all SQL
- [x] CSRF tokens on POST forms
- [x] `htmlspecialchars` via `e()` for output
- [x] `password_hash` / `password_verify` for passwords and PIN
- [x] Session cookie: HttpOnly; Secure configurable; SameSite
- [x] Session idle + absolute timeout
- [x] Login rate limiting (per email + IP)
- [x] RBAC checks on admin / teacher / monitor / student routes
- [x] Audit log for sensitive actions
- [x] Upload directory `.htaccess` denies script execution
- [x] Cron endpoint protected by shared secret
- [ ] **Production:** HTTPS + HSTS, `display_errors=Off`, strong `cron_key`, restrict DB user privileges

## Reporting

Document your college incident response contact and data retention policy in **Privacy** page content.

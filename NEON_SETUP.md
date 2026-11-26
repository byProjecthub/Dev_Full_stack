# Vueports: Neon database connection and Vercel hosting

## 1. Create a new Neon database

Create a project in the Neon console. Use a separate database/branch for Vueports so this installation does not mix with another CRM or project. PostgreSQL 17 is the tested reference version; the schema uses PostgreSQL 16+ features.

Click **Connect**, choose the intended branch/database/role and keep the supplied connection security parameters. Copy the pooled URI for web requests and the direct URI with connection pooling disabled for administration.

| Setting | Purpose |
| --- | --- |
| `DATABASE_URL` | Pooled Neon URI for the website and APIs |
| `DATABASE_URL_UNPOOLED` | Direct Neon URI for migrations, checks, administrator creation and backups |
| `APP_URL` | Final HTTPS application URL, including an installation subfolder if used |
| `APP_ENV` | `production` on Vercel; `development` locally |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE` | `1` for HTTPS production; `0` for local HTTP |
| `JWT_SECRET` | Random secret of at least 32 characters for the existing JWT APIs |

A URI has this general shape; these are placeholders, not login details:

```text
postgresql://ROLE:PASSWORD@NEON-HOST/neondb?sslmode=require&channel_binding=require
```

Keep TLS/channel-binding settings from the Neon console. Remote plaintext connections are rejected. `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`, `PGSSLMODE` and `PGCHANNELBINDING` are also supported when no URI is set. The default port is 5432. `sslrootcert`, `sslcert`, `sslkey`, `connect_timeout` and `target_session_attrs` URL options are preserved.

`DIRECT_URL` is accepted as a compatibility alias for `DATABASE_URL_UNPOOLED`. Administrator/installation commands use the direct endpoint when supplied. Connection credentials remain server-side and must never use a public frontend environment-variable prefix.

## 2. Configure your local project

Extract the ZIP and open the inner `Dev_Full_stack` directory in VS Code. Copy `.env.example` to `.env`, then paste the actual connection strings into that local file. Do not commit it. Actual process/hosting environment values override `.env`; `.env.local` only fills values that were not already configured.

Generate a JWT secret:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Install/enable PDO PostgreSQL and mbstring in the PHP installation you use for these commands. On XAMPP, enable its PostgreSQL PDO extension and restart the PHP/Apache process. The old MySQL connection variables and MySQL-specific PDO options are no longer used by the app.

## 3. Install and verify the schema

From the project directory:

```sh
php scripts/migrate.php
php scripts/check-database.php
php scripts/check-database.php --direct
php tests/neon-smoke.php
```

The installer creates 32 application tables, a migration ledger, public service content, configuration and email templates. It contains no DROP TABLE operations and reruns by checking its migration version. No administrator credentials are seeded.

For an SQL-console installation, paste the complete contents of `database.sql` into the Neon SQL Editor on a new database/branch. Run it once. The schema, content and version ledger are installed in one transaction. On subsequent installations use the CLI migrator, which detects the installed version. Avoid pasting fragments or the historical MySQL dump.

The smoke test writes synthetic rows inside a transaction and rolls them back. It does not email anyone, contact a payment gateway or commit customer data. Identity sequences may advance during a rolled-back test; gaps in IDs are normal.

## 4. Create your administrator

On Windows PowerShell, collect a password without showing it on screen:

```powershell
$securePassword = Read-Host "Administrator password (12+ characters)" -AsSecureString
$env:ADMIN_PASSWORD = ([pscredential]::new('unused', $securePassword)).GetNetworkCredential().Password
php scripts/create-admin.php --email=you@example.com --username=youradmin --name="Your Name"
Remove-Item Env:\ADMIN_PASSWORD
```

On macOS/Linux, you can supply a password through standard input:

```sh
read -s -p 'Administrator password: ' admin_password
printf '%s' "$admin_password" | php scripts/create-admin.php --email=you@example.com --username=youradmin --password-stdin
unset admin_password
```

Use your actual email, chosen username and a unique strong password. The command hashes the password, creates a `super_admin` and refuses to overwrite existing usernames/emails. Sign in through `/admin/login.php`. Client accounts use the normal registration pages.

The first admin login field accepts either the `--username` value or the `--email`
value used at account creation. The password is the one supplied to the creation
command. There are no default administrator login details. Updating `ADMIN_PASSWORD`
in an environment file later does not change an existing administrator's password.

If you are using Windows Command Prompt (CMD), create the account with a hidden
password prompt inside the Docker container. Replace the example email first:

```cmd
docker run --rm -it --env-file .env vueports:local bash -lc "read -r -s -p 'Administrator password (12+ characters): ' ADMIN_PASSWORD; printf '\n'; export ADMIN_PASSWORD; php scripts/create-admin.php --email=you@example.com --username=njabulo"
```

The old `/admin/Login1.php` address uses the same administrator form and checks.

## 5. Deploy to Vercel

1. Put the application source in a private Git repository; keep `.env`, backups and runtime data out of Git.
2. Import the repository into Vercel. Select the directory containing `Dockerfile`, `index.php` and `vercel.json` as the project root.
3. Select the **Container** framework preset. The supplied `vercel.json` declares it. Vercel builds the Apache/PHP image from the root Dockerfile.
4. The image listens on port **80**. Use Vercel's default routing port; remove an old `PORT=8080` override if one was carried over from a previous deployment. Do not run an npm frontend build or select the static design-preview directory for this PHP application.
5. Add the settings listed above under **Settings → Environment Variables**. Use a preview Neon branch for Preview and the production branch for Production. Save database passwords, connection URIs, JWT and provider keys as secrets.
6. Set `APP_URL` to the deployed HTTPS address, `APP_ENV=production`, `SESSION_DRIVER=database` and `SESSION_SECURE=1`.
7. Install the schema and create the administrator using the direct endpoint from a trusted local terminal before relying on the web application. The web server never runs database installation automatically.
8. Deploy/redeploy. Environment-variable changes require a new deployment.
9. Open `/monitoring/health-check.php`, then verify home, client/admin login, enquiries, invoices and sandbox checkout record creation.

The Dockerfile installs PDO PostgreSQL, mbstring, cURL, ZIP and a PostgreSQL client. It retains the bundled PHPMailer files and uses Apache rather than PHP's local development server. Docker itself and the Vercel account were not available for a real container build/deployment in the conversion environment; validate the build logs on Vercel.

## 6. Configure the remaining providers

- Email: configure your existing Resend/SendGrid/SMTP provider settings. Use a verified sending address. Provider secrets are read from the environment before database defaults.
- Payments: the existing PayFast code remains in sandbox mode by default. Verify callback/signature handling and actual merchant settings separately before taking live payments. No payment was submitted during conversion tests.
- Calendar: configure the existing calendar provider credentials and callback URLs if you use that integration.
- Uploads: the original upload helpers use local filesystem storage. Files inside a Vercel instance are not durable across replacement; add your chosen external object-storage adapter before enabling persistent uploads there.
- Scheduled work: run `cron/process-email.php`, `cron/subscription-renewal.php` and `scripts/cleanup.php` with a CLI scheduler/worker. They are deliberately blocked over public HTTP. An HTTP cron integration needs its own authenticated wrapper and is not included.
- Backups: Neon restore/history is managed through Neon. `php cron/daily-backup.php` also creates a local `pg_dump` custom archive using the direct connection. Copy it to durable private storage; `/tmp` on Vercel is temporary. Match the `pg_dump` client to your PostgreSQL server major version.
- WebSockets: the retained websocket server is a separate long-running service and is not started by the web container.

## Existing MySQL data

The ZIP contains source, schema and public seed content; it does not contain an export of your live customer database. This conversion did not access that database or move its records. To transfer existing production data, export it, map columns into this PostgreSQL schema, preserve IDs/relationships, transform packed IP addresses and JSON/date values, and reset PostgreSQL identity sequences after loading explicit IDs. Validate row counts and account/payment records on a staging branch before switching production traffic. Never import another project's or the old MySQL SQL file directly into the Neon SQL Editor.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| `could not find driver` | Enable `pdo_pgsql` in the PHP executable running the command |
| Database check fails | Use the complete Neon URI, correct branch/role, TLS settings and an active endpoint |
| `relation ... does not exist` | Run `php scripts/migrate.php` against that exact database/branch |
| Login does not persist locally | Set `SESSION_SECURE=0` for HTTP; production HTTPS uses `1` |
| API tokens fail | Configure the same 32+ character `JWT_SECRET` across application instances |
| Duplicate administrator | The creation command is intentionally non-destructive; use a new account or your own password-reset procedure |
| Vercel returns 502 | Check container startup logs and ensure Vercel routes to Apache's port 80 |
| Email or payments do not complete | Configure and test the relevant external provider independently |

## Official references

- Neon connection and Vercel environment setup: https://neon.com/docs/guides/vercel-manual
- Neon pooled/direct connections: https://neon.com/docs/connect/connect-from-any-app
- Vercel Container Runtime: https://vercel.com/docs/functions/runtimes/container
- PHP PostgreSQL PDO DSN: https://www.php.net/manual/en/ref.pdo-pgsql.connection.php
- PostgreSQL INSERT/RETURNING/ON CONFLICT: https://www.postgresql.org/docs/17/sql-insert.html

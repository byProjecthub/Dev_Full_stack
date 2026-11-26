# Vueports Solutions — Neon PostgreSQL Edition

The complete Vueports PHP application, converted to Neon/PostgreSQL while retaining the Connected Enterprise light frontend, card navigation, forms, account screens, APIs and PayFast integration code.

Start with **NEON_SETUP.md** for Neon connection and Vercel deployment steps. Read **NEON_VALIDATION.md** for completed checks and verification limits.

## Requirements

- PHP 8.3 or later; the supplied container uses PHP 8.4 and Apache.
- PDO PostgreSQL (`pdo_pgsql`), mbstring, OpenSSL, JSON and sessions. Provider calls use cURL; database backups use `pg_dump`.
- A new PostgreSQL 16+ database/branch. Local validation used PostgreSQL 17.12.
- The bundled PHPMailer code is retained. `composer.json` is now valid and declares the application dependencies.

## Quick start with Neon

1. Copy `.env.example` to `.env` for local development. Set `DATABASE_URL` to the complete Neon pooled URI and `DATABASE_URL_UNPOOLED` to the direct URI. Hosting environment values take precedence over local files.
2. Set `APP_URL`, generate a random `JWT_SECRET`, and select appropriate `APP_ENV` and `SESSION_SECURE` values.
3. Run `php scripts/migrate.php`. The installer is versioned, does not drop tables, and refuses to adopt an unrelated existing schema.
4. Run `php scripts/check-database.php` and `php tests/neon-smoke.php`.
5. Create an administrator with `php scripts/create-admin.php --email=you@example.com --username=youradmin`, supplying its password through `ADMIN_PASSWORD` or `--password-stdin`. No default administrator account is installed.
6. Run `php -S localhost:8080 -t . router.php` for local development, or use the production Apache container.

`database.sql` is the PostgreSQL initializer for a new Neon database. `database/001_neon_postgresql.sql` is the canonical versioned migration. The historical `database - Copy.sql` filename is retained with identical PostgreSQL SQL so it cannot accidentally install the old MySQL schema.

## Local Docker development

```sh
docker compose up --build -d
docker compose exec app php scripts/migrate.php
docker compose exec app php scripts/check-database.php
docker compose exec app php tests/neon-smoke.php
```

Open `http://localhost:8080`. The Compose database and credentials are for local development only. The production application uses the externally configured Neon database. `docker-composer.yml` is retained as a compatibility filename with the same Compose configuration.

## Conversion details

- PostgreSQL connection URI parsing preserves encoded credentials, Neon TLS/channel-binding parameters, pooled and direct endpoints, and standard PG environment variables. There is no automatic MySQL fallback.
- Schema types, identity IDs, enum constraints, JSONB, foreign keys, indexes, generated invoice totals and automatic `updated_at` triggers use PostgreSQL.
- MySQL date functions, settings/unsubscribe upserts and inserted ID handling were converted. IDs use `RETURNING` so they work through transaction pooling.
- Missing fields used by existing forms/exports were reconciled. Portfolio queries use the canonical `portfolio_items` table. Login email and administrator username matching retain case-insensitive semantics through `citext`.
- Sessions and rate limits are shared in PostgreSQL. Session records use row locks on a separate connection so concurrent requests do not overwrite one another and business transactions remain independent.
- Installer, administrator creation, connection checks, backups and cleanup commands are CLI-only. Private application files are denied by Apache and the local development router.
- Public/client/admin routes and the current frontend assets remain available. The main client sign-in is `login.php`; the portal is `client/dashboard.php`; staff sign-in is `admin/login.php`.

## Runtime boundaries

This package converts the database and application code. It does not provision a Neon account, transfer records from a live MySQL server, deploy to Vercel, or send a payment/email. Existing production data requires a reviewed MySQL-to-PostgreSQL data migration into the new schema; do not run the former MySQL dump in Neon.

Provider settings and keys must be configured on the host. Email delivery, calendar services and live PayFast callbacks need provider-side testing. The existing upload helpers use local filesystem storage; durable uploads on Vercel require an external storage adapter. Scheduled jobs require a separate scheduler/worker. See NEON_SETUP.md.

Original PDF instructions are retained under `docs/legacy/` as historical material. The current instructions are the Markdown guides in this folder.

## GitHub repository deployment

The repository keeps its newer login and portfolio fixes, adapted to PostgreSQL.
The publishing workflow now tests PostgreSQL and publishes only the application image.
Composer retains its existing locked PHPMailer 6.12.0 version.
See [docs/deployment.md](docs/deployment.md) for GitHub Actions and Docker-host details;
[NEON_SETUP.md](NEON_SETUP.md) contains the Neon and Vercel setup.

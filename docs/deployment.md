# Deploy Vueports with Neon PostgreSQL

The database is hosted on Neon. The application can run on Vercel using the
root Dockerfile, or on your own Docker host using the tested GHCR application image.
The old MySQL image and MySQL Compose service are retired.

Follow [NEON_SETUP.md](../NEON_SETUP.md) for connection strings, schema installation,
administrator creation, Vercel configuration, backups and integration requirements.
Existing MySQL customer records require a separate data migration; installing the
schema only creates the public seeds and empty operational tables.

## GitHub Actions

Pull requests validate the container against a disposable PostgreSQL 17 database.
Pushes to main and version tags run the same checks, then publish the exact tested
application image to `ghcr.io/byprojecthub/dev_full_stack`. No MySQL image is built.
The pinned action revisions and built-in GitHub token are retained.

The checks cover Composer, PHP syntax, environment precedence, portfolio CRUD,
database relationships, HTTP pages, API registration/login/token refresh and private
file/upload protection. These checks are configured in CI; inspect the Actions run
for the build result. Docker was unavailable during local source preparation.

## Docker host using Neon

1. Create a private host `.env` from `deploy/.env.example`. Configure real Neon
   pooled/direct URLs, APP_URL, JWT_SECRET and the published immutable
   `sha-<full-commit>` IMAGE_TAG. Never commit this file.
2. Run `docker compose -f compose.deploy.yml pull`.
3. Run `docker compose -f compose.deploy.yml run --rm app php scripts/migrate.php`.
4. Create an administrator using the CLI instructions in NEON_SETUP.md.
5. Run `docker compose -f compose.deploy.yml up -d` and configure HTTPS on your
   reverse proxy before enabling public access. The default bind is localhost:8080.
6. Verify `/monitoring/health-check.php` and the client/admin forms.

The host Compose file mounts uploads and scratch runtime volumes; sessions and rate
limits remain shared in PostgreSQL. Back up Neon and uploaded files independently.
On Vercel, durable uploads need an external object-storage adapter. Cron commands need
a scheduler or worker; the existing websocket service needs its own runtime.

## Credentials

The formerly tracked root `.env` is removed from this version. Git history is unchanged.
Rotate any real credentials or JWT keys that were stored in that publicly visible file.
Keep Vercel settings in the project environment and local/host settings in ignored files.

# Vueports Solutions

The local site uses PHP 8.3, MySQL 8, and Composer. The Docker image installs
the dependencies from `composer.lock`; PHP extensions are installed by the
Dockerfile rather than Composer hooks.

## GitHub image publishing

GitHub Actions tests the app and publishes the production Apache/PHP image and
MySQL schema image to GitHub Container Registry:

- `ghcr.io/byprojecthub/dev_full_stack`
- `ghcr.io/byprojecthub/dev_full_stack-mysql`

Push to `main` or a `v*` version tag after committing the configuration to
activate publishing. Pull requests run validation without publishing. Use the
same `sha-<full-commit>` tag for both images when deploying. The workflow uses
GitHub's built-in token; a publishing PAT is not required.

GitHub stores the images; the PHP/MySQL site runs on a separate Docker host.
The publish job verifies and reuses the images tested by its CI job.
See [deployment instructions](docs/deployment.md) for host configuration,
persistent data, and migration from Railway.

## Local development

From the repository root, create a private `.env` without overwriting an
existing configuration:

```sh
python3 - <<'PY'
from pathlib import Path
import os
import secrets

text = Path('.env.example').read_text()
for name in ('MYSQL_ROOT_PASSWORD', 'MYSQLPASSWORD', 'JWT_SECRET'):
    text = text.replace(name + '=\n', name + '=' + secrets.token_hex(32) + '\n')
text = text.replace('DEV_UID=1000', 'DEV_UID=' + str(os.getuid()))
text = text.replace('DEV_GID=1000', 'DEV_GID=' + str(os.getgid()))
with open('.env', 'x') as stream:
    stream.write(text)
Path('.env').chmod(0o600)
PY

docker compose -f docker-composer.yml up --build --renew-anon-volumes -d --wait
```

The PHP server uses port 8080. Source changes are mounted into the app. The
anonymous vendor volume contains the image's locked dependencies; use
`--renew-anon-volumes` when rebuilding after dependency changes. MySQL data is
retained in the separate `db_data` volume. The schema is imported only when
that database volume is empty. Its database name is `vueports_database`;
`lower_case_table_names=1` handles the schema's mixed-case database declaration.

The app reads `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, and
`MYSQLPASSWORD` through exported variables or `.env`. Keep the Compose database
name as supplied to match the schema. Set `JWT_SECRET` to a private random key.
An existing `.env` or exported binding should be reused rather than replaced.

```sh
docker compose -f docker-composer.yml exec -T app composer validate --strict
docker compose -f docker-composer.yml exec -T app composer check-platform-reqs
docker compose -f docker-composer.yml exec -T app php tests/portfolio.php
curl --fail http://127.0.0.1:8080/monitoring/health-check.php
docker compose -f docker-composer.yml stop
```

The portfolio regression test uses a transaction and rolls back its fixtures.
It requires the development database and is disabled over HTTP. The health
endpoint checks the database and required PHP extensions.

## Cloud environment

The saved installation and startup configuration uses helpers in
`/workspace/.cloud-dev-full-stack`, preserving local credentials and existing
database data. Start it with:

```sh
bash /workspace/.cloud-dev-full-stack/start.sh
```

The cloud helper supplies its proxy routing and trusted CA to Docker builds.
TLS and package verification stay enabled. The startup smoke check uses the
original three seeded service records; editing those records can require
updating the baseline check. Environment-specific admin and client form smoke
checks are retained alongside the helper.

## Optional integrations

Email delivery, PayFast, Google Calendar, PDF generation, caching, and websockets
need their own credentials and/or additional dependencies. They are separate
from the verified local site, admin/client sign-in, portfolio, and JSON API
workflow. Do not commit external credentials or service-account key files.
The `extras` Compose profile starts Redis and phpMyAdmin; the PHP Redis
extension is an additional requirement for the cache helper.

# Publish through GitHub Actions

The workflow in `.github/workflows/publish.yml` replaces the Railway image
build configuration. It publishes Docker images to GHCR; it does not create a
hosting server or move an existing database. PHP and MySQL run on a separate
Docker host, rather than GitHub Pages.

## Activate publishing

Commit the deployment files together with the application fixes and lockfile,
then push them to `main`. Enable GitHub Actions for the repository if needed.

The workflow runs application checks before publishing:

- Builds the production Apache/PHP image and MySQL initialization image.
- Starts a fresh database and checks PHP dependencies and extensions.
- Runs the environment and portfolio regression checks, public-page and
  health requests, and checks that private files are inaccessible over HTTP.

The publish job loads the tested image archives and verifies their image IDs
before tagging and pushing them. The archives are retained for one day. If a
publication retry happens after they expire, rerun the whole workflow so its
test job creates fresh archives.

Pull requests to `main` run validation only. Pushes to `main`, `v*` version
tags, or a manual run selecting `main` or a version tag can publish. A manual
run on another branch validates without publishing.

The publish job uses the built-in `GITHUB_TOKEN` with `contents: read` and
`packages: write`. It does not need a personal access token in repository
secrets. Repository or organization policies must permit that package write.

Both images use matching tags:

| Image | Purpose |
| --- | --- |
| `ghcr.io/byprojecthub/dev_full_stack` | Production Apache/PHP application |
| `ghcr.io/byprojecthub/dev_full_stack-mysql` | MySQL with the initial schema |

Each published commit gets `sha-<full-40-character-commit>`. `latest` is updated
only on `main`; a `v*` tag also publishes that version tag. Prefer the matching
commit tags when deploying so the application and schema image stay paired.
For a fork, the workflow derives lowercase image names from its repository;
adjust the deployment environment's `GHCR_OWNER` and `GHCR_REPOSITORY` to match.

These changes are prepared locally. Images become available after the
configuration is committed, pushed, and its GitHub Actions run succeeds.
Local smoke tests do not execute GitHub Actions or confirm GHCR publication;
check the repository's Actions run and Packages after pushing.

## Run the published images

The workflow currently publishes Linux/amd64 images. The server needs an
x86-64 Linux Docker host with Docker Engine and the Docker Compose plugin. Copy
`compose.deploy.yml` and `deploy/.env.example` to the server, retaining their
relative paths. Create a private configuration without replacing an existing
one:

```sh
cp -n deploy/.env.example deploy/.env
chmod 600 deploy/.env
```

Edit `deploy/.env` securely on that server:

- Set `IMAGE_TAG` to a published `sha-<full-commit>` tag.
- Keep the lowercase registry owner/repository defaults, or set them to the
  package names published by your fork.
- Set independent random values for `MYSQL_ROOT_PASSWORD`, `MYSQLPASSWORD`,
  and `JWT_SECRET`.
- Set `APP_URL` to the site's actual HTTPS URL.
- Keep `SESSION_SECURE=1` behind the HTTPS reverse proxy. Use `0` only for a
  local HTTP check before enabling the public site.

Keep the database name `vueports_database`. Do not commit this `.env` file or
copy provider credentials into workflow files. Any optional email, payment,
or calendar credentials belong in the host's secure runtime configuration.

Public GHCR packages can be pulled without authentication. If the packages
are private, authenticate the deployment host using an account with package
access and a read-only package credential, for example through its secret
manager:

```sh
printf '%s' "$GHCR_READ_TOKEN" | docker login ghcr.io \
  --username "$GHCR_USER" --password-stdin
```

Start the images from the directory containing `compose.deploy.yml`:

```sh
docker compose --env-file deploy/.env -f compose.deploy.yml pull
docker compose --env-file deploy/.env -f compose.deploy.yml up -d --wait
docker compose --env-file deploy/.env -f compose.deploy.yml ps
```

The container listens on port 80, exposed on server loopback port 8080 by
default (`APP_PORT`). Configure the server's HTTPS reverse proxy to forward
the site's root path to that loopback port. Publishing images does not
configure DNS, TLS certificates, or the reverse proxy.

The initial schema seeds an administrator account. Replace its password with
a private, unique password or remove that account before exposing the site.
Locking it alone is insufficient because the API login does not check that lock.
Credentials from the initial schema must not become production credentials.

The deployment uses named volumes for the database, uploaded files, logs,
cache, and PHP sessions. Restarting or updating containers retains them. The
MySQL image applies its schema only to a new database volume; existing
databases require explicit migrations when the schema changes.

For a new application version, update `IMAGE_TAG` and repeat `pull` and
`up -d --wait`. To stop services while keeping their data:

```sh
docker compose --env-file deploy/.env -f compose.deploy.yml stop
```

## Move existing Railway data

If the repository is connected to Railway, removing `railway.json` does not
disconnect its service or deployment webhooks. Disable Railway's automatic
deployments when switching hosts so a push to `main` does not also start the
old deployment pipeline.

Before switching traffic, back up the Railway database and uploaded files.
Restore them into the new host's persistent database and upload storage,
keeping the application's configured database name. Verify the public pages,
admin/client sign-in, and health checks on the new host before changing DNS
or the reverse proxy's upstream.

The images contain application code and an initial schema. They do not copy
Railway's live database, uploads, provider credentials, or scheduled jobs.
Keep the original data and backups until the new deployment has been checked.

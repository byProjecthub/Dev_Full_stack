#!/usr/bin/env bash
# Validate already-built publication images against a disposable database.
set -euo pipefail
: "${APP_IMAGE:?Set APP_IMAGE to the built application image}"
: "${DB_IMAGE:?Set DB_IMAGE to the built MySQL image}"
cd "$(dirname "$0")/.."
umask 077
state_dir="$(mktemp -d)"
project="vueports-ci-${GITHUB_RUN_ID:-local}-$$"
app="${project}-app"
database="${project}-db"
volume="${project}-data"

cleanup() {
    result=$?
    if [ "$result" -ne 0 ]; then
        docker logs --tail 60 "$app" >&2 || true
        docker logs --tail 60 "$database" >&2 || true
    fi
    docker rm -f -v "$app" "$database" > /dev/null 2>&1 || true
    docker volume rm "$volume" > /dev/null 2>&1 || true
    docker network rm "$project" > /dev/null 2>&1 || true
    rm -rf -- "$state_dir"
    exit "$result"
}
trap cleanup EXIT

python3 - "$state_dir" <<'PY'
from pathlib import Path
import secrets
import sys

directory = Path(sys.argv[1])
password = secrets.token_hex(32)
database = {
    'MYSQL_ROOT_PASSWORD': secrets.token_hex(32),
    'MYSQL_DATABASE': 'vueports_database',
    'MYSQL_USER': 'vueports',
    'MYSQL_PASSWORD': password,
}
application = {
    'MYSQLHOST': 'db', 'MYSQLPORT': '3306',
    'MYSQLDATABASE': 'vueports_database', 'MYSQLUSER': 'vueports',
    'MYSQLPASSWORD': password, 'JWT_SECRET': secrets.token_hex(32),
    'APP_URL': 'http://127.0.0.1', 'SESSION_SECURE': '0',
}
for name, values in [('database.env', database), ('application.env', application)]:
    (directory / name).write_text(''.join(f'{key}={value}\n' for key, value in values.items()))
PY

docker network create "$project" > /dev/null
docker volume create "$volume" > /dev/null
docker run -d --name "$database" --network "$project" --network-alias db \
    --env-file "$state_dir/database.env" \
    --mount "type=volume,source=$volume,target=/var/lib/mysql" \
    --health-cmd 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --protocol=TCP --host=127.0.0.1 --user=root --execute="SELECT 1" > /dev/null' \
    --health-interval 3s --health-timeout 5s --health-retries 60 \
    "$DB_IMAGE" --lower-case-table-names=1 --skip-log-bin > /dev/null

wait_healthy() {
    local container="$1" deadline=$((SECONDS + 240)) status
    while [ "$SECONDS" -lt "$deadline" ]; do
        status="$(docker inspect --format '{{.State.Status}}:{{if .State.Health}}{{.State.Health.Status}}{{end}}' "$container")"
        case "$status" in
            running:healthy) return 0 ;;
            exited:*|dead:*|running:unhealthy) printf 'Health check failed: %s\n' "$container" >&2; return 1 ;;
        esac
        sleep 2
    done
    printf 'Health check timed out: %s\n' "$container" >&2
    return 1
}

wait_healthy "$database"
docker run -d --name "$app" --network "$project" \
    --env-file "$state_dir/application.env" \
    --publish 127.0.0.1::80 "$APP_IMAGE" > /dev/null
wait_healthy "$app"

# Tests are injected into this disposable container, not the published image.
docker cp tests "$app:/app/tests"
docker exec "$app" sh -c 'chmod 755 /app/tests; chmod 644 /app/tests/*.php'
docker exec --user www-data --env COMPOSER_HOME=/tmp/composer "$app" composer validate --strict
docker exec --user www-data "$app" composer check-platform-reqs
docker exec --user www-data "$app" php tests/env.php
docker exec --user www-data "$app" php tests/portfolio.php
docker exec "$app" sh -c 'test ! -e /app/database.sql && test ! -e /app/import-db.php && test ! -e /app/config/google-credentials.json'
docker run --rm --network none \
    --mount "type=bind,source=$PWD,target=/source,readonly" --workdir /source \
    "$APP_IMAGE" php -r '
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(".", FilesystemIterator::SKIP_DOTS));
    $passed = 0;
    foreach ($iterator as $file) {
        if ($file->getExtension() !== "php" || str_starts_with($file->getPathname(), "./vendor/")) continue;
        exec("php -l " . escapeshellarg($file->getPathname()) . " 2>&1", $output, $status);
        if ($status) { fwrite(STDERR, "PHP lint failed: " . $file->getPathname() . PHP_EOL); exit($status); }
        $passed++;
    }
    if (!$passed) exit(1);
    echo "PASS PHP lint ($passed files)" . PHP_EOL;'

# Verify writes as the actual Apache worker and deny uploaded executable files.
docker exec --user www-data "$app" php -r '
    file_put_contents("/app/uploads/ci-smoke.php", "<?php echo \"EXECUTED_UPLOAD\"; ?>");
    file_put_contents("/app/uploads/ci-smoke.txt", "STATIC_UPLOAD_OK");'
address="$(docker port "$app" 80/tcp)"
python3 - "http://$address" <<'PY'
import html
import json
import secrets
import sys
import urllib.error
import urllib.request

base = sys.argv[1]
client = urllib.request.build_opener(urllib.request.ProxyHandler({}))

def request(path, expected=200, payload=None, token=None):
    headers = {}
    data = None
    if payload is not None:
        headers['Content-Type'] = 'application/json'
        data = json.dumps(payload).encode()
    if token:
        headers['Authorization'] = 'Bearer ' + token
    outgoing = urllib.request.Request(base + path, data=data, headers=headers)
    try:
        response = client.open(outgoing, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        body = response.read().decode()
        if response.status != expected:
            raise RuntimeError(f'{path}: expected HTTP {expected}, received {response.status}')
        return body, response.headers.get('Content-Type', '')

for path in ['/', '/services.php']:
    body, _ = request(path)
    body = html.unescape(body)
    for title in ['Custom Software & Web Development', 'Data Engineering & Analytics', 'AI Agent Development']:
        if title not in body:
            raise RuntimeError(f'{path}: seeded service content missing')
health = json.loads(request('/monitoring/health-check.php')[0])
if health['status'] != 'healthy' or health['checks']['write_permissions']['status'] != 'ok':
    raise RuntimeError('Database/extensions/Apache write-permission health failed')
css, mime = request('/assets/css/variables.css')
if 'text/css' not in mime or ':root' not in css:
    raise RuntimeError('CSS was not served')
for path in ['/admin/login.php', '/client/login.php']:
    if 'csrf_token' not in request(path)[0]:
        raise RuntimeError(f'{path}: login form did not render')
if json.loads(request('/api/v1/auth.php', expected=401)[0]).get('message') != 'Token required':
    raise RuntimeError('API authentication rejection failed')
email = 'publication-smoke-' + secrets.token_hex(12) + '@example.invalid'
password = secrets.token_urlsafe(24)
registered = json.loads(request('/api/v1/auth.php', expected=201, payload={
    'action': 'client_register', 'full_name': 'Publication Smoke Test',
    'email': email, 'password': password,
})[0])
client_id = registered.get('data', {}).get('client', {}).get('id')
if registered.get('success') is not True or not client_id:
    raise RuntimeError('Production API registration failed')
signed_in = json.loads(request('/api/v1/auth.php', payload={
    'action': 'client_login', 'email': email, 'password': password,
})[0])
token = signed_in.get('data', {}).get('token')
if signed_in.get('success') is not True or not token:
    raise RuntimeError('Production API login failed')
verified = json.loads(request('/api/v1/auth.php', token=token)[0])
if verified.get('data', {}).get('payload', {}).get('sub') != client_id:
    raise RuntimeError('Apache did not preserve authenticated API access')
refreshed = json.loads(request('/api/v1/auth.php', payload={'action': 'refresh'}, token=token)[0])
refreshed_token = refreshed.get('data', {}).get('token')
if not refreshed_token or json.loads(request('/api/v1/auth.php', token=refreshed_token)[0]).get('success') is not True:
    raise RuntimeError('Production API token refresh failed')
for path in ['/.env', '/.git/config', '/database.sql', '/database%20-%20Copy.sql',
             '/config/database.php', '/includes/functions.php', '/vendor/autoload.php',
             '/tests/env.php', '/import-db.php', '/test-email.php',
             '/process-email-queue.php', '/Dockerfile', '/uploads/ci-smoke.php',
             '/sessions/', '/logs/', '/cache/']:
    try:
        client.open(base + path, timeout=10)
    except urllib.error.HTTPError as error:
        if error.code in [403, 404]:
            error.close()
            continue
        error.close()
    raise RuntimeError(f'{path}: private or executable file was accessible')
if request('/uploads/ci-smoke.txt')[0] != 'STATIC_UPLOAD_OK':
    raise RuntimeError('Static uploads cannot be served')
print('PASS production HTTP pages, health, login forms, CSS, API registration/login/token refresh, private paths and upload execution protection')
PY
printf 'All publication-image checks passed.\n'

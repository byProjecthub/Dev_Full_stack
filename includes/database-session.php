<?php
declare(strict_types=1);

/** Shared sessions survive Vercel instance replacement; row locks serialize concurrent requests. */
final class VueportsDatabaseSession implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    private ?PDO $connection = null;
    private string $lockedId = '';
    public function __construct(private int $lifetime) {}
    private function connection(): PDO { return $this->connection ??= vueports_connect(); }
    private function valid(string $id): bool { return (bool) preg_match('/^[A-Za-z0-9,-]{16,128}$/D', $id); }
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool {
        if ($this->connection?->inTransaction()) $this->connection->commit();
        $this->lockedId = ''; $this->connection = null; return true;
    }
    public function read(string $id): string|false {
        if (!$this->valid($id)) return false;
        $db = $this->connection();
        $db->beginTransaction();
        $db->prepare("INSERT INTO app_sessions(session_id,expires_at) VALUES (?, NOW() + CAST(? AS INTEGER) * INTERVAL '1 second') ON CONFLICT(session_id) DO NOTHING")->execute([$id, $this->lifetime]);
        $stmt = $db->prepare('SELECT session_data, expires_at > NOW() AS alive FROM app_sessions WHERE session_id = ? FOR UPDATE');
        $stmt->execute([$id]); $row = $stmt->fetch(); $this->lockedId = $id;
        return $row && $row['alive'] ? (base64_decode($row['session_data'], true) ?: '') : '';
    }
    public function write(string $id, string $data): bool {
        if (!$this->valid($id)) return false;
        $db = $this->connection();
        $stmt = $db->prepare("INSERT INTO app_sessions(session_id, session_data, expires_at) VALUES (?, ?, NOW() + CAST(? AS INTEGER) * INTERVAL '1 second') ON CONFLICT(session_id) DO UPDATE SET session_data = EXCLUDED.session_data, expires_at = EXCLUDED.expires_at, updated_at = NOW()");
        $stmt->execute([$id, base64_encode($data), $this->lifetime]);
        if ($db->inTransaction()) $db->commit(); $this->lockedId = ''; return true;
    }
    public function destroy(string $id): bool {
        $db = $this->connection(); $db->prepare('DELETE FROM app_sessions WHERE session_id = ?')->execute([$id]);
        if ($db->inTransaction()) $db->commit(); $this->lockedId = ''; return true;
    }
    public function gc(int $max_lifetime): int|false {
        return $this->connection()->exec('DELETE FROM app_sessions WHERE expires_at <= NOW()');
    }
    public function validateId(string $id): bool {
        if (!$this->valid($id)) return false;
        $stmt = $this->connection()->prepare('SELECT 1 FROM app_sessions WHERE session_id = ? AND expires_at > NOW()');
        $stmt->execute([$id]); return (bool) $stmt->fetchColumn();
    }
    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
}

function vueports_start_session(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    $secure = env('SESSION_SECURE', env('APP_ENV', 'production') === 'production' ? '1' : '0') === '1';
    $lifetime = max(5, (int) env('SESSION_LIFETIME', '120')) * 60;
    ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) $lifetime);
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_name('VUEPORTSSESSID');
    if (env('SESSION_DRIVER', 'database') === 'files') {
        if (env('APP_ENV', 'production') === 'production') throw new RuntimeException('Use database sessions on production hosting.');
        $directory = sys_get_temp_dir() . '/vueports-sessions';
        if (!is_dir($directory)) mkdir($directory, 0700, true);
        session_save_path($directory);
    } else {
        session_set_save_handler(new VueportsDatabaseSession($lifetime), true);
    }
    session_start();
}

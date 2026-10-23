<?php
declare(strict_types=1);
if (!file_exists(__DIR__ . '/../config.php')) {
    http_response_code(500);
    exit('Missing config.php. Copy config.example.php to config.php and set database details.');
}
require_once __DIR__ . '/../config.php';
session_name(SESSION_NAME);
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $given = (string)($_POST['csrf'] ?? '');
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $given)) {
            http_response_code(419); exit('Session expired. Go back and retry.');
        }
    }
}
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT id,name,email,balance,role FROM users WHERE id=?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('Admin access required.'); }
    return $u;
}
function setting(string $key, string $default=''): string {
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v === false ? $default : (string)$v;
}
function set_setting(string $key, string $value): void {
    $stmt = db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $stmt->execute([$key,$value]);
}
function flash(string $message, string $type='info'): void { $_SESSION['flash'] = [$message,$type]; }
function get_flash(): ?array { $f=$_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function provider_request(array $params): array {
    $url = setting('provider_url');
    $key = setting('provider_key');
    if (!$url || !$key) throw new RuntimeException('Set provider URL and API key in Admin Settings first.');
    $params['key'] = $key;
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) throw new RuntimeException('Provider connection failed: ' . $err);
    $json = json_decode($body, true);
    if ($code >= 400 || !is_array($json)) throw new RuntimeException('Provider returned an invalid response.');
    if (isset($json['error'])) throw new RuntimeException('Provider error: ' . (string)$json['error']);
    return $json;
}
function layout_start(string $title): void {
    $u = current_user(); $flash = get_flash();
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · RS SMM BD</title>
    <style>
    :root{--bg:#0b1020;--card:#141c31;--muted:#9ba8c7;--text:#f5f7ff;--pink:#ec4899;--purple:#8b5cf6;--line:#283451}
    *{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,var(--bg),#11162b);color:var(--text);font:15px system-ui,sans-serif}a{color:#f9a8d4;text-decoration:none}
    .wrap{max-width:1100px;margin:auto;padding:18px}.top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:10px 0 22px}.brand{font-size:21px;font-weight:900;color:white}.brand span{color:#f9a8d4}.nav{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-block;background:linear-gradient(100deg,var(--pink),var(--purple));border:0;border-radius:10px;color:white;padding:10px 13px;font-weight:750;cursor:pointer}.secondary{background:#202a45;border:1px solid var(--line)}.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px;margin:12px 0}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.muted{color:var(--muted)}h1{font-size:clamp(27px,5vw,42px)}input,select,textarea{width:100%;padding:12px;border-radius:9px;border:1px solid var(--line);background:#0c1428;color:white;margin:6px 0 12px}label{font-weight:650}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid var(--line);vertical-align:top}th{color:#f9a8d4}.tablewrap{overflow:auto}.notice{padding:13px;border:1px solid #514078;background:#21183b;border-radius:10px;margin:12px 0}.ok{border-color:#276749}.error{border-color:#9f3636}.small{font-size:12px}.money{font-size:26px;font-weight:900}.footer{text-align:center;color:var(--muted);padding:24px}
    </style></head><body><div class="wrap"><header class="top"><a class="brand" href="index.php">RS <span>SMM BD</span></a><nav class="nav"><a class="btn secondary" href="services.php">Services</a><?php if($u):?><a class="btn secondary" href="dashboard.php">Dashboard</a><a class="btn secondary" href="add-funds.php">Add Funds</a><?php if($u['role']==='admin'):?><a class="btn" href="admin.php">Admin</a><?php endif;?><a class="btn secondary" href="logout.php">Logout</a><?php else:?><a class="btn secondary" href="login.php">Login</a><a class="btn" href="register.php">Register</a><?php endif;?></nav></header>
    <?php if($flash):?><div class="notice <?=e($flash[1])?>"><?=e($flash[0])?></div><?php endif;?><main><?php
}
function layout_end(): void { ?></main><footer class="footer">RS SMM BD · Secure setup starter</footer></div></body></html><?php }

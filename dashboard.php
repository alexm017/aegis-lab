<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Strict',
  'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

require_once __DIR__ . '/lib/site_data.php';

const ZS_ADMIN_SESSION_KEY = 'zs_admin_authenticated';
const ZS_ADMIN_CSRF_KEY = 'zs_admin_csrf';
const ZS_LOGIN_ATTEMPT_WINDOW_SECONDS = 900;
const ZS_LOGIN_ATTEMPT_MAX = 8;
const ZS_LOGIN_LOCK_SECONDS = 900;

function zs_dashboard_url(): string
{
  return '/dashboard';
}

function zs_dashboard_redirect(array $query = []): void
{
  $url = zs_dashboard_url();
  if ($query !== []) {
    $url .= '?' . http_build_query($query);
  }
  header('Location: ' . $url);
  exit;
}

function zs_dashboard_flash(): array
{
  $type = isset($_GET['type']) && $_GET['type'] === 'error' ? 'error' : 'ok';
  $message = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
  return [$type, $message];
}

function zs_dashboard_admin_password_hash(): string
{
  $envHash = trim((string)getenv('ZS_ADMIN_PASSWORD_HASH'));
  if ($envHash !== '' && preg_match('/^\$2[aby]\$/', $envHash) === 1) {
    return $envHash;
  }

  $jsonPath = __DIR__ . '/data/dashboard_admin_auth.json';
  if (is_file($jsonPath) && is_readable($jsonPath)) {
    $decoded = json_decode((string)@file_get_contents($jsonPath), true);
    if (is_array($decoded)) {
      $jsonHash = trim((string)($decoded['password_hash'] ?? ''));
      if ($jsonHash !== '' && preg_match('/^\$2[aby]\$/', $jsonHash) === 1) {
        return $jsonHash;
      }
    }
  }

  $hashPath = __DIR__ . '/data/dashboard_admin_password_hash.txt';
  if (is_file($hashPath) && is_readable($hashPath)) {
    $fileHash = trim((string)@file_get_contents($hashPath));
    if ($fileHash !== '' && preg_match('/^\$2[aby]\$/', $fileHash) === 1) {
      return $fileHash;
    }
  }

  return '';
}

function zs_dashboard_client_ip(): string
{
  $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
  if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
    return '0.0.0.0';
  }
  return $ip;
}

function zs_dashboard_login_limit_path(): string
{
  return __DIR__ . '/data/dashboard_login_limits.json';
}

function zs_dashboard_login_limit_key(string $ip): string
{
  return hash('sha256', 'zs_login_limit|' . $ip . '|aegis');
}

function zs_dashboard_login_limit_mutate(callable $mutator): mixed
{
  $path = zs_dashboard_login_limit_path();
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }

  $handle = @fopen($path, 'c+');
  if ($handle === false) {
    return null;
  }

  if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    return null;
  }

  $raw = stream_get_contents($handle);
  $store = [];
  if (is_string($raw) && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $store = $decoded;
    }
  }

  $now = time();
  $pruneBefore = $now - 86400;
  foreach ($store as $key => $entry) {
    if (!is_array($entry)) {
      unset($store[$key]);
      continue;
    }
    $updatedAt = (int)($entry['updated_at'] ?? 0);
    $blockedUntil = (int)($entry['blocked_until'] ?? 0);
    if ($updatedAt < $pruneBefore && $blockedUntil < $pruneBefore) {
      unset($store[$key]);
    }
  }

  $result = $mutator($store, $now);

  $json = json_encode($store, JSON_UNESCAPED_SLASHES);
  if (is_string($json)) {
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, $json);
    fflush($handle);
  }

  flock($handle, LOCK_UN);
  fclose($handle);
  return $result;
}

function zs_dashboard_login_limit_status(string $ip): array
{
  $key = zs_dashboard_login_limit_key($ip);
  $result = zs_dashboard_login_limit_mutate(
    static function (array &$store, int $now) use ($key): array {
      $entry = isset($store[$key]) && is_array($store[$key]) ? $store[$key] : [];
      $blockedUntil = max(0, (int)($entry['blocked_until'] ?? 0));
      if ($blockedUntil <= $now) {
        $blockedUntil = 0;
      }

      $failures = isset($entry['failures']) && is_array($entry['failures']) ? $entry['failures'] : [];
      $windowStart = $now - ZS_LOGIN_ATTEMPT_WINDOW_SECONDS;
      $trimmedFailures = [];
      foreach ($failures as $ts) {
        $tsInt = (int)$ts;
        if ($tsInt >= $windowStart && $tsInt <= $now) {
          $trimmedFailures[] = $tsInt;
        }
      }

      if ($blockedUntil === 0 && $trimmedFailures === []) {
        unset($store[$key]);
      } else {
        $store[$key] = [
          'failures' => $trimmedFailures,
          'blocked_until' => $blockedUntil,
          'updated_at' => $now,
        ];
      }

      return [
        'blocked' => $blockedUntil > $now,
        'retry_after' => $blockedUntil > $now ? ($blockedUntil - $now) : 0,
      ];
    }
  );

  if (!is_array($result)) {
    return ['blocked' => false, 'retry_after' => 0];
  }
  return [
    'blocked' => !empty($result['blocked']),
    'retry_after' => max(0, (int)($result['retry_after'] ?? 0)),
  ];
}

function zs_dashboard_login_limit_register_failure(string $ip): void
{
  $key = zs_dashboard_login_limit_key($ip);
  zs_dashboard_login_limit_mutate(
    static function (array &$store, int $now) use ($key): void {
      $entry = isset($store[$key]) && is_array($store[$key]) ? $store[$key] : [];
      $failures = isset($entry['failures']) && is_array($entry['failures']) ? $entry['failures'] : [];
      $windowStart = $now - ZS_LOGIN_ATTEMPT_WINDOW_SECONDS;
      $trimmedFailures = [];
      foreach ($failures as $ts) {
        $tsInt = (int)$ts;
        if ($tsInt >= $windowStart && $tsInt <= $now) {
          $trimmedFailures[] = $tsInt;
        }
      }

      $trimmedFailures[] = $now;
      $blockedUntil = max(0, (int)($entry['blocked_until'] ?? 0));
      if (count($trimmedFailures) >= ZS_LOGIN_ATTEMPT_MAX) {
        $blockedUntil = $now + ZS_LOGIN_LOCK_SECONDS;
        $trimmedFailures = [];
      }

      $store[$key] = [
        'failures' => $trimmedFailures,
        'blocked_until' => $blockedUntil,
        'updated_at' => $now,
      ];
    }
  );
}

function zs_dashboard_login_limit_reset(string $ip): void
{
  $key = zs_dashboard_login_limit_key($ip);
  zs_dashboard_login_limit_mutate(
    static function (array &$store) use ($key): void {
      unset($store[$key]);
    }
  );
}

function zs_dashboard_send_security_headers(): void
{
  header('X-Content-Type-Options: nosniff', true);
  header('X-Frame-Options: DENY', true);
  header('Referrer-Policy: same-origin', true);
  header('Permissions-Policy: geolocation=(), camera=(), microphone=()', true);
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
  header('Pragma: no-cache', true);
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains', true);
  }
}

function zs_require_csrf(): bool
{
  $token = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
  $sessionToken = isset($_SESSION[ZS_ADMIN_CSRF_KEY]) ? (string)$_SESSION[ZS_ADMIN_CSRF_KEY] : '';
  return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function zs_trim_post(string $key, int $maxLen = 600): string
{
  $value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
  if (strlen($value) > $maxLen) {
    $value = substr($value, 0, $maxLen);
  }
  return $value;
}

function zs_json_encode(mixed $payload): ?string
{
  $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
  return is_string($json) ? $json : null;
}

function zs_json_response(array $payload, int $status = 200): void
{
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  $json = zs_json_encode($payload);
  if (!is_string($json)) {
    echo '{"success":false,"error":"Failed to encode JSON response."}';
    exit;
  }
  echo $json;
  exit;
}

function zs_trim_text(string $value, int $maxLen = 1200): string
{
  $value = trim($value);
  if ($value === '') {
    return '';
  }

  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($value, 'UTF-8') <= $maxLen) {
      return $value;
    }
    return trim(mb_substr($value, 0, $maxLen, 'UTF-8'));
  }

  if (strlen($value) <= $maxLen) {
    return $value;
  }
  return trim(substr($value, 0, $maxLen));
}

function zs_normalize_chat_history(mixed $rawHistory): array
{
  if (!is_array($rawHistory)) {
    return [];
  }

  $normalized = [];
  foreach ($rawHistory as $entry) {
    if (!is_array($entry)) {
      continue;
    }

    $role = strtolower(trim((string)($entry['role'] ?? '')));
    if ($role === 'ai') {
      $role = 'assistant';
    }
    if ($role !== 'assistant' && $role !== 'user') {
      continue;
    }

    $content = zs_trim_text((string)($entry['content'] ?? ''), 1200);
    if ($content === '') {
      continue;
    }

    $normalized[] = [
      'role' => $role,
      'content' => $content,
    ];
  }

  if (count($normalized) > 20) {
    $normalized = array_slice($normalized, -20);
  }
  return $normalized;
}

function zs_load_openai_key(): string
{
  $envKey = trim((string)getenv('OPENAI_API_KEY'));
  if ($envKey !== '') {
    return $envKey;
  }

  $legacyEnvKey = trim((string)getenv('openai_token'));
  if ($legacyEnvKey !== '') {
    return $legacyEnvKey;
  }

  $candidateConfigFiles = [
    __DIR__ . '/../OpenML_Alphabit/openai/assets/includes/app_config.php',
    __DIR__ . '/../OpenML_Website/openai/assets/includes/app_config.php',
  ];

  foreach ($candidateConfigFiles as $configFile) {
    if (!is_file($configFile) || !is_readable($configFile)) {
      continue;
    }

    require_once $configFile;
    if (function_exists('alphabit_config_openai_key')) {
      $key = trim((string)alphabit_config_openai_key());
      if ($key !== '') {
        return $key;
      }
    }
  }

  return '';
}

function zs_dashboard_base_url(): string
{
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string)$_SERVER['REQUEST_SCHEME']) === 'https');
  $scheme = $isHttps ? 'https' : 'http';
  $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
  if ($host === '') {
    $host = '127.0.0.1';
  }
  return $scheme . '://' . $host;
}

function zs_dashboard_normalize_url(string $url): string
{
  $value = trim($url);
  if ($value === '') {
    return '';
  }
  if (!preg_match('#^https?://#i', $value)) {
    $value = 'https://' . ltrim($value, '/');
  }
  return $value;
}

function zs_dashboard_proc_stat_snapshot(): ?array
{
  $path = '/proc/stat';
  if (!is_readable($path)) {
    return null;
  }

  $handle = @fopen($path, 'r');
  if ($handle === false) {
    return null;
  }
  $line = fgets($handle);
  fclose($handle);

  if (!is_string($line) || stripos($line, 'cpu ') !== 0) {
    return null;
  }

  $parts = preg_split('/\s+/', trim($line));
  if (!is_array($parts) || count($parts) < 5) {
    return null;
  }

  $numbers = [];
  foreach (array_slice($parts, 1) as $part) {
    if (!is_numeric($part)) {
      continue;
    }
    $numbers[] = (float)$part;
  }
  if (count($numbers) < 4) {
    return null;
  }

  $total = array_sum($numbers);
  $idle = ($numbers[3] ?? 0.0) + ($numbers[4] ?? 0.0);
  if ($total <= 0) {
    return null;
  }

  return [
    'total' => $total,
    'idle' => $idle,
  ];
}

function zs_dashboard_cpu_cores(): int
{
  static $cached = 0;
  if ($cached > 0) {
    return $cached;
  }

  $cores = 0;
  $cpuInfoPath = '/proc/cpuinfo';
  if (is_readable($cpuInfoPath)) {
    $raw = @file_get_contents($cpuInfoPath);
    if (is_string($raw) && $raw !== '') {
      $matches = [];
      if (preg_match_all('/^processor\s*:/mi', $raw, $matches) !== false && is_array($matches[0] ?? null) && count($matches[0]) > 0) {
        $cores = count($matches[0]);
      }
    }
  }

  if ($cores <= 0) {
    $load = sys_getloadavg();
    if (is_array($load) && count($load) > 0) {
      $cores = 1;
    }
  }

  if ($cores <= 0) {
    $cores = 1;
  }
  $cached = $cores;
  return $cached;
}

function zs_dashboard_cpu_usage_percent(): ?float
{
  $snapA = zs_dashboard_proc_stat_snapshot();
  if (is_array($snapA)) {
    usleep(120000);
    $snapB = zs_dashboard_proc_stat_snapshot();
    if (is_array($snapB)) {
      $totalDiff = (float)$snapB['total'] - (float)$snapA['total'];
      $idleDiff = (float)$snapB['idle'] - (float)$snapA['idle'];
      if ($totalDiff > 0.0001) {
        $usage = (1.0 - ($idleDiff / $totalDiff)) * 100.0;
        return max(0.0, min(100.0, $usage));
      }
    }
  }

  $load = sys_getloadavg();
  if (is_array($load) && isset($load[0]) && is_numeric($load[0])) {
    $cores = max(1, zs_dashboard_cpu_cores());
    $usage = (((float)$load[0]) / $cores) * 100.0;
    return max(0.0, min(100.0, $usage));
  }
  return null;
}

function zs_dashboard_memory_stats(): array
{
  $memInfoPath = '/proc/meminfo';
  if (!is_readable($memInfoPath)) {
    return [
      'total_bytes' => null,
      'used_bytes' => null,
      'percent' => null,
    ];
  }

  $raw = @file_get_contents($memInfoPath);
  if (!is_string($raw) || $raw === '') {
    return [
      'total_bytes' => null,
      'used_bytes' => null,
      'percent' => null,
    ];
  }

  $valsKb = [];
  foreach (preg_split('/\R+/', $raw) as $line) {
    if (!is_string($line) || $line === '') {
      continue;
    }
    if (preg_match('/^([A-Za-z_]+):\s+(\d+)\s+kB$/', trim($line), $m) === 1) {
      $valsKb[$m[1]] = (float)$m[2];
    }
  }

  $totalKb = (float)($valsKb['MemTotal'] ?? 0.0);
  if ($totalKb <= 0) {
    return [
      'total_bytes' => null,
      'used_bytes' => null,
      'percent' => null,
    ];
  }

  $availableKb = (float)($valsKb['MemAvailable'] ?? 0.0);
  if ($availableKb <= 0) {
    $availableKb = (float)($valsKb['MemFree'] ?? 0.0) + (float)($valsKb['Buffers'] ?? 0.0) + (float)($valsKb['Cached'] ?? 0.0);
  }

  if ($availableKb < 0) {
    $availableKb = 0.0;
  }
  if ($availableKb > $totalKb) {
    $availableKb = $totalKb;
  }

  $usedKb = max(0.0, $totalKb - $availableKb);
  $percent = $totalKb > 0 ? ($usedKb / $totalKb) * 100.0 : null;

  return [
    'total_bytes' => (int)round($totalKb * 1024.0),
    'used_bytes' => (int)round($usedKb * 1024.0),
    'percent' => is_numeric($percent) ? max(0.0, min(100.0, (float)$percent)) : null,
  ];
}

function zs_dashboard_storage_stats(): array
{
  $total = @disk_total_space(__DIR__);
  $free = @disk_free_space(__DIR__);
  if (!is_numeric($total) || !is_numeric($free) || (float)$total <= 0) {
    return [
      'total_bytes' => null,
      'used_bytes' => null,
      'percent' => null,
    ];
  }

  $totalBytes = (float)$total;
  $freeBytes = (float)$free;
  $usedBytes = max(0.0, $totalBytes - $freeBytes);
  $percent = ($usedBytes / $totalBytes) * 100.0;

  return [
    'total_bytes' => (int)round($totalBytes),
    'used_bytes' => (int)round($usedBytes),
    'percent' => max(0.0, min(100.0, $percent)),
  ];
}

function zs_dashboard_bytes_human(?int $bytes): string
{
  if (!is_int($bytes) || $bytes <= 0) {
    return '0 B';
  }
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $value = (float)$bytes;
  $idx = 0;
  while ($value >= 1024.0 && $idx < (count($units) - 1)) {
    $value /= 1024.0;
    $idx++;
  }
  if ($idx === 0) {
    return (string)((int)round($value)) . ' ' . $units[$idx];
  }
  return number_format($value, 1) . ' ' . $units[$idx];
}

function zs_dashboard_measure_response(string $url, int $timeoutMs = 1800): array
{
  $normalizedUrl = zs_dashboard_normalize_url($url);
  if ($normalizedUrl === '') {
    return [
      'ok' => false,
      'status' => null,
      'ms' => null,
    ];
  }

  $start = microtime(true);
  $statusCode = 0;
  $success = false;

  if (function_exists('curl_init')) {
    $ch = curl_init($normalizedUrl);
    if ($ch !== false) {
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT_MS => min(900, max(250, (int)round($timeoutMs * 0.55))),
        CURLOPT_TIMEOUT_MS => max(300, $timeoutMs),
        CURLOPT_USERAGENT => 'AegisLabDashboard/1.0',
      ]);
      $result = curl_exec($ch);
      $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlErr = curl_errno($ch);
      curl_close($ch);

      if ($curlErr === 0 && ($result !== false || $statusCode > 0)) {
        $success = true;
      }
    }
  }

  if (!$success) {
    $ctx = stream_context_create([
      'http' => [
        'method' => 'HEAD',
        'timeout' => max(0.3, $timeoutMs / 1000.0),
        'ignore_errors' => true,
        'header' => "User-Agent: AegisLabDashboard/1.0\r\n",
      ],
      'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
      ],
    ]);
    $result = @file_get_contents($normalizedUrl, false, $ctx);
    if ($result !== false || isset($http_response_header)) {
      $success = true;
      if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
          if (preg_match('/^HTTP\/[0-9.]+\s+(\d{3})/', (string)$line, $m) === 1) {
            $statusCode = (int)$m[1];
            break;
          }
        }
      }
    }
  }

  $ms = (int)round((microtime(true) - $start) * 1000.0);
  $reachable = $success && $statusCode > 0 && $statusCode < 600;

  return [
    'ok' => $reachable,
    'status' => $statusCode > 0 ? $statusCode : null,
    'ms' => $reachable ? $ms : null,
  ];
}

function zs_dashboard_runtime_targets(): array
{
  $siteContent = zs_get_site_content();

  $mainUrlEnv = zs_dashboard_normalize_url((string)getenv('AEGIS_MAIN_URL'));
  $mainUrl = $mainUrlEnv !== '' ? $mainUrlEnv : 'https://aegislab.ro/';

  $ctfUrlRaw = trim((string)($siteContent['ctf_url'] ?? ''));
  $ctfUrl = zs_dashboard_normalize_url($ctfUrlRaw);
  $ctfUrlEnv = zs_dashboard_normalize_url((string)getenv('AEGIS_CTF_URL'));
  if ($ctfUrlEnv !== '') {
    $ctfUrl = $ctfUrlEnv;
  } elseif ($ctfUrl === '') {
    $ctfUrl = 'https://ctf.aegislab.ro/';
  }

  return [
    ['key' => 'main', 'label' => 'aegislab.ro', 'url' => $mainUrl],
    ['key' => 'ctf', 'label' => 'ctf.aegislab.ro', 'url' => $ctfUrl],
  ];
}

function zs_dashboard_runtime_cache_path(): string
{
  return __DIR__ . '/data/dashboard_runtime_cache.json';
}

function zs_dashboard_runtime_metrics_uncached(): array
{
  $cpuPercent = zs_dashboard_cpu_usage_percent();
  $mem = zs_dashboard_memory_stats();
  $storage = zs_dashboard_storage_stats();

  $responseCards = [];
  foreach (zs_dashboard_runtime_targets() as $target) {
    $key = trim((string)($target['key'] ?? ''));
    $label = trim((string)($target['label'] ?? 'Target'));
    $url = trim((string)($target['url'] ?? ''));
    $probe = zs_dashboard_measure_response($url);
    $responseCards[] = [
      'key' => $key,
      'label' => $label,
      'url' => $url,
      'ok' => (bool)($probe['ok'] ?? false),
      'status' => is_int($probe['status'] ?? null) ? (int)$probe['status'] : null,
      'ms' => is_int($probe['ms'] ?? null) ? (int)$probe['ms'] : null,
    ];
  }

  $ramUsed = is_int($mem['used_bytes'] ?? null) ? (int)$mem['used_bytes'] : null;
  $ramTotal = is_int($mem['total_bytes'] ?? null) ? (int)$mem['total_bytes'] : null;
  $ramPercent = is_numeric($mem['percent'] ?? null) ? (float)$mem['percent'] : null;

  $storageUsed = is_int($storage['used_bytes'] ?? null) ? (int)$storage['used_bytes'] : null;
  $storageTotal = is_int($storage['total_bytes'] ?? null) ? (int)$storage['total_bytes'] : null;
  $storagePercent = is_numeric($storage['percent'] ?? null) ? (float)$storage['percent'] : null;

  return [
    'generated_at' => zs_analytics_format_ts(time(), DATE_ATOM),
    'resources' => [
      'cpu_label' => is_numeric($cpuPercent) ? (number_format((float)$cpuPercent, 1) . '%') : 'N/A',
      'ram_label' => ($ramUsed !== null && $ramTotal !== null)
        ? (zs_dashboard_bytes_human($ramUsed) . ' / ' . zs_dashboard_bytes_human($ramTotal) . (is_numeric($ramPercent) ? (' (' . number_format((float)$ramPercent, 1) . '%)') : ''))
        : 'N/A',
      'storage_label' => ($storageUsed !== null && $storageTotal !== null)
        ? (zs_dashboard_bytes_human($storageUsed) . ' / ' . zs_dashboard_bytes_human($storageTotal) . (is_numeric($storagePercent) ? (' (' . number_format((float)$storagePercent, 1) . '%)') : ''))
        : 'N/A',
    ],
    'response_cards' => $responseCards,
  ];
}

function zs_dashboard_runtime_metrics(int $ttlSeconds = 15): array
{
  $path = zs_dashboard_runtime_cache_path();
  $now = time();

  if (is_file($path) && is_readable($path)) {
    $raw = @file_get_contents($path);
    if (is_string($raw) && trim($raw) !== '') {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $ts = isset($decoded['ts']) ? (int)$decoded['ts'] : 0;
        $payload = isset($decoded['payload']) && is_array($decoded['payload']) ? $decoded['payload'] : null;
        if ($payload !== null && $ts > 0 && ($now - $ts) <= $ttlSeconds) {
          return $payload;
        }
      }
    }
  }

  $payload = zs_dashboard_runtime_metrics_uncached();
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
  $wrapper = [
    'ts' => $now,
    'payload' => $payload,
  ];
  $encoded = json_encode($wrapper, JSON_UNESCAPED_SLASHES);
  if (is_string($encoded)) {
    @file_put_contents($path, $encoded, LOCK_EX);
  }
  return $payload;
}

function zs_dashboard_ctf_cache_path(): string
{
  return __DIR__ . '/data/dashboard_ctf_analytics_cache.json';
}

function zs_dashboard_ctf_log_path(): string
{
  $path = trim((string)getenv('AEGIS_CTF_ACCESS_LOG'));
  if ($path === '') {
    $path = '/var/log/apache2/ctf_access.log';
  }
  return $path;
}

function zs_dashboard_ctf_challenge_log_index_path(): string
{
  return __DIR__ . '/data/ctf_challenge_log_paths.json';
}

function zs_dashboard_ctf_challenge_log_map(array $challengeCatalog): array
{
  $indexPath = trim((string)getenv('AEGIS_CTF_CHALLENGE_LOG_MAP'));
  if ($indexPath === '') {
    $indexPath = zs_dashboard_ctf_challenge_log_index_path();
  }

  if (!is_file($indexPath) || !is_readable($indexPath)) {
    return [];
  }

  $raw = @file_get_contents($indexPath);
  if (!is_string($raw) || trim($raw) === '') {
    return [];
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return [];
  }

  $map = [];
  foreach ($decoded as $key => $logPathRaw) {
    $challengeId = null;
    if (is_int($key)) {
      $challengeId = $key;
    } elseif (is_string($key)) {
      $candidate = trim($key);
      if ($candidate !== '' && ctype_digit($candidate)) {
        $challengeId = (int)$candidate;
      }
    }

    if (!is_int($challengeId) || $challengeId <= 0) {
      continue;
    }
    if (!isset($challengeCatalog[$challengeId])) {
      continue;
    }
    if (!is_string($logPathRaw)) {
      continue;
    }

    $logPath = trim($logPathRaw);
    if ($logPath === '') {
      continue;
    }

    $map[$challengeId] = $logPath;
  }

  return $map;
}

function zs_dashboard_ctf_parse_container_access_line(string $line): ?array
{
  $raw = trim($line);
  if ($raw === '') {
    return null;
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return null;
  }

  $message = trim((string)($decoded['log'] ?? ''));
  if ($message === '') {
    return null;
  }

  if (
    preg_match(
      '~^(?P<ip>\S+)\s+\S+\s+\S+\s+\[(?P<time>[^\]]+)\]\s+"(?P<request>[^"]*)"\s+(?P<status>\d{3})\s+\S+~',
      $message,
      $m
    ) !== 1
  ) {
    return null;
  }

  $request = trim((string)$m['request']);
  if ($request === '' || $request === '-') {
    return null;
  }

  $method = 'GET';
  $target = '/';
  if (preg_match('~^(?P<method>[A-Z]+)\s+(?P<target>\S+)(?:\s+HTTP/[0-9.]+)?$~', $request, $reqMatch) === 1) {
    $method = strtoupper((string)$reqMatch['method']);
    $target = (string)$reqMatch['target'];
  } else {
    $parts = preg_split('/\s+/', $request);
    if (!is_array($parts) || count($parts) < 2) {
      return null;
    }
    $method = strtoupper((string)$parts[0]);
    $target = (string)$parts[1];
  }

  $ts = 0;
  $isoTime = trim((string)($decoded['time'] ?? ''));
  if ($isoTime !== '') {
    try {
      $dtIso = new DateTimeImmutable($isoTime);
      $ts = $dtIso->getTimestamp();
    } catch (Exception $e) {
      $ts = 0;
    }
  }

  if ($ts <= 0) {
    $timeRaw = trim((string)$m['time']);
    $dt = DateTimeImmutable::createFromFormat('d/M/Y H:i:s', $timeRaw);
    if ($dt instanceof DateTimeImmutable) {
      $ts = $dt->getTimestamp();
    }
  }

  if ($ts <= 0) {
    return null;
  }

  $ip = trim((string)$m['ip']);
  if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
    $ip = 'Unknown';
  }

  return [
    'ip' => $ip,
    'ts' => $ts,
    'method' => $method,
    'target' => $target,
    'status' => (int)$m['status'],
    'ua' => '',
  ];
}

function zs_dashboard_ctf_collect_web_challenge_counts(array $challengeCatalog, int $cutoff30d): array
{
  $logMap = zs_dashboard_ctf_challenge_log_map($challengeCatalog);
  $buckets = [];
  $unique = [];

  foreach ($challengeCatalog as $challengeIdRaw => $catalogEntry) {
    $challengeId = (int)$challengeIdRaw;
    if ($challengeId <= 0) {
      continue;
    }

    $bucketKey = 'challenge:' . $challengeId;
    $buckets[$bucketKey] = [
      'label' => (string)($catalogEntry['name'] ?? ('Challenge #' . $challengeId)),
      'path' => '/',
      'hits' => 0,
      'unique' => [],
      'last_seen' => 0,
      'challenge_id' => $challengeId,
      'url' => (string)($catalogEntry['url'] ?? ''),
      'endpoint' => (string)($catalogEntry['endpoint'] ?? ''),
    ];

    $logPath = isset($logMap[$challengeId]) ? trim((string)$logMap[$challengeId]) : '';
    if ($logPath === '' || !is_file($logPath) || !is_readable($logPath)) {
      continue;
    }

    $handle = @fopen($logPath, 'r');
    if ($handle === false) {
      continue;
    }

    while (($line = fgets($handle)) !== false) {
      $entry = zs_dashboard_ctf_parse_container_access_line($line);
      if (!is_array($entry)) {
        continue;
      }

      $ts = (int)($entry['ts'] ?? 0);
      if ($ts <= 0 || $ts < $cutoff30d) {
        continue;
      }

      $ip = trim((string)($entry['ip'] ?? ''));
      if ($ip !== '' && $ip !== 'Unknown' && zs_analytics_is_ignored_ip($ip)) {
        continue;
      }

      $target = zs_dashboard_ctf_parse_target((string)($entry['target'] ?? '/'));
      $path = (string)($target['path'] ?? '/');
      if (zs_dashboard_ctf_is_static_path($path)) {
        continue;
      }

      $ipHash = zs_analytics_ip_hash($ip !== '' ? $ip : 'Unknown');
      $buckets[$bucketKey]['hits']++;
      if ($ipHash !== '') {
        $buckets[$bucketKey]['unique'][$ipHash] = true;
        $unique[$ipHash] = true;
      }
      if ($ts > (int)$buckets[$bucketKey]['last_seen']) {
        $buckets[$bucketKey]['last_seen'] = $ts;
      }
    }

    fclose($handle);
  }

  $hits = 0;
  foreach ($buckets as $bucket) {
    $hits += (int)($bucket['hits'] ?? 0);
  }

  return [
    'hits' => $hits,
    'unique' => $unique,
    'buckets' => $buckets,
  ];
}

function zs_dashboard_ctf_default_stats(string $logPath, int $now): array
{
  return [
    'available' => false,
    'generated_at' => zs_analytics_format_ts($now, DATE_ATOM),
    'log_path' => $logPath,
    'totals' => [
      'active_now' => 0,
      'visits_all' => 0,
      'visits_7d' => 0,
      'visits_30d' => 0,
      'unique_all' => 0,
      'unique_7d' => 0,
      'unique_30d' => 0,
      'challenge_hits_30d' => 0,
      'challenge_unique_30d' => 0,
    ],
    'series' => [
      'minutes_60' => [],
      'hours_24' => [],
      'days_7' => [],
      'days_30' => [],
    ],
    'top_paths_30d' => [],
    'challenge_activity_30d' => [],
    'recent_access_30d' => [],
    'countries_30d' => [],
  ];
}

function zs_dashboard_ctf_challenge_catalog(): array
{
  $host = trim((string)getenv('AEGIS_CHALLENGE_HOST'));
  if ($host === '') {
    $host = '84.117.149.18';
  }
  $host = preg_replace('~^https?://~i', '', $host) ?? $host;
  $host = trim($host, " \t\n\r\0\x0B/");
  if ($host === '') {
    $host = '84.117.149.18';
  }

  $scheme = strtolower(trim((string)getenv('AEGIS_CHALLENGE_SCHEME')));
  if ($scheme !== 'https') {
    $scheme = 'http';
  }

  $entries = [
    1 => ['name' => 'Cookie Jar', 'port' => 32854],
    2 => ['name' => 'SQL Rookie', 'port' => 32855],
    3 => ['name' => 'Template Leak', 'port' => 32856],
    4 => ['name' => 'SSRF Notes', 'port' => 32857],
    21 => ['name' => 'IDOR Vault', 'port' => 32858],
    22 => ['name' => 'Ping Commander', 'port' => 32859],
    23 => ['name' => 'File Viewer v2', 'port' => 32860],
  ];

  $catalog = [];
  foreach ($entries as $challengeId => $entry) {
    $port = (int)($entry['port'] ?? 0);
    if ($port <= 0) {
      continue;
    }
    $endpoint = $host . ':' . $port;
    $catalog[$challengeId] = [
      'id' => $challengeId,
      'name' => (string)($entry['name'] ?? ('Challenge #' . $challengeId)),
      'url' => $scheme . '://' . $endpoint,
      'endpoint' => $endpoint,
    ];
  }

  return $catalog;
}

function zs_dashboard_ctf_parse_target(string $target): array
{
  $value = trim($target);
  if ($value === '' || $value === '*') {
    return ['path' => '/', 'query' => ''];
  }

  $parts = @parse_url($value);
  if (!is_array($parts)) {
    return ['path' => '/', 'query' => ''];
  }

  $path = isset($parts['path']) ? trim((string)$parts['path']) : '/';
  if ($path === '') {
    $path = '/';
  }
  $path = preg_replace('~/+~', '/', $path) ?? $path;
  if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
    if ($path === '') {
      $path = '/';
    }
  }

  $query = isset($parts['query']) ? trim((string)$parts['query']) : '';
  return [
    'path' => $path,
    'query' => $query,
  ];
}

function zs_dashboard_ctf_is_static_path(string $path): bool
{
  $value = trim($path);
  if ($value === '') {
    return true;
  }

  $prefixes = [
    '/.well-known/',
    '/themes/',
    '/plugins/',
    '/assets/',
    '/files/',
    '/static/',
    '/images/',
    '/img/',
    '/js/',
    '/css/',
    '/fonts/',
    '/media/',
    '/favicon',
  ];
  foreach ($prefixes as $prefix) {
    if (str_starts_with($value, $prefix)) {
      return true;
    }
  }

  $exact = ['/robots.txt', '/sitemap.xml'];
  if (in_array($value, $exact, true)) {
    return true;
  }

  if (preg_match('/\.(?:css|js|map|png|jpe?g|gif|svg|ico|webp|woff2?|ttf|eot|otf|pdf|txt)$/i', $value) === 1) {
    return true;
  }

  return false;
}

function zs_dashboard_ctf_route_key(string $path, string $query): string
{
  if (preg_match('~^/api/v1/challenges/\d+(?:/|$)~', $path) === 1) {
    return '/api/v1/challenges/{id}';
  }

  if ($path === '/api/v1/users/me/submissions') {
    $params = [];
    if ($query !== '') {
      parse_str($query, $params);
    }
    $challengeIdRaw = isset($params['challenge_id']) && is_scalar($params['challenge_id'])
      ? trim((string)$params['challenge_id'])
      : '';
    if ($challengeIdRaw !== '' && ctype_digit($challengeIdRaw)) {
      return '/api/v1/users/me/submissions?challenge_id={id}';
    }
  }

  if (preg_match('~^/api/v1/teams/\d+(?:/|$)~', $path) === 1) {
    return '/api/v1/teams/{id}';
  }

  return $path;
}

function zs_dashboard_ctf_challenge_label(?int $challengeId, string $route): string
{
  if (is_int($challengeId) && $challengeId > 0) {
    return 'Challenge #' . $challengeId;
  }
  if ($route === '/challenges') {
    return 'Challenges Board';
  }
  if ($route === '/api/v1/challenges') {
    return 'Challenges API Index';
  }
  if ($route === '/api/v1/users/me/submissions?challenge_id={id}') {
    return 'Challenge Submissions API';
  }
  return $route;
}

function zs_dashboard_ctf_parse_access_line(string $line): ?array
{
  $raw = trim($line);
  if ($raw === '') {
    return null;
  }

  if (
    preg_match(
      '~^(?P<ip>\S+)\s+\S+\s+\S+\s+\[(?P<time>[^\]]+)\]\s+"(?P<request>[^"]*)"\s+(?P<status>\d{3})\s+\S+\s+"[^"]*"\s+"(?P<ua>[^"]*)"~',
      $raw,
      $m
    ) !== 1
  ) {
    return null;
  }

  $request = trim((string)$m['request']);
  if ($request === '' || $request === '-') {
    return null;
  }

  $method = 'GET';
  $target = '/';
  if (preg_match('~^(?P<method>[A-Z]+)\s+(?P<target>\S+)(?:\s+HTTP/[0-9.]+)?$~', $request, $reqMatch) === 1) {
    $method = strtoupper((string)$reqMatch['method']);
    $target = (string)$reqMatch['target'];
  } else {
    $parts = preg_split('/\s+/', $request);
    if (!is_array($parts) || count($parts) < 2) {
      return null;
    }
    $method = strtoupper((string)$parts[0]);
    $target = (string)$parts[1];
  }

  $timeRaw = trim((string)$m['time']);
  $dt = DateTimeImmutable::createFromFormat('d/M/Y:H:i:s O', $timeRaw);
  if (!$dt instanceof DateTimeImmutable) {
    return null;
  }

  $ip = trim((string)$m['ip']);
  if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
    $ip = 'Unknown';
  }

  return [
    'ip' => $ip,
    'ts' => $dt->getTimestamp(),
    'method' => $method,
    'target' => $target,
    'status' => (int)$m['status'],
    'ua' => trim((string)$m['ua']),
  ];
}

function zs_dashboard_ctf_analytics_uncached(): array
{
  $now = time();
  $logPath = zs_dashboard_ctf_log_path();
  $default = zs_dashboard_ctf_default_stats($logPath, $now);
  $challengeCatalog = zs_dashboard_ctf_challenge_catalog();

  if (!is_file($logPath) || !is_readable($logPath)) {
    return $default;
  }

  $handle = @fopen($logPath, 'r');
  if ($handle === false) {
    return $default;
  }

  $cutoff5m = $now - 300;
  $cutoff7d = $now - (7 * 86400);
  $cutoff30d = $now - (30 * 86400);
  $cutoff1h = $now - 3600;
  $cutoff24h = $now - (24 * 3600);

  $minutes60 = zs_analytics_minute_labels(60, $now);
  $hours24 = zs_analytics_hour_labels(24, $now);
  $days7 = zs_analytics_day_labels(7, $now);
  $days30 = zs_analytics_day_labels(30, $now);

  $allUnique = [];
  $activeIps = [];
  $unique7 = [];
  $unique30 = [];
  $challengeUnique30 = [];
  $countryCounts30 = [];
  $routeViews30 = [];
  $routeUnique30 = [];
  $challengeBuckets30 = [];
  $recentAccess30 = [];

  $visitsAll = 0;
  $visits7d = 0;
  $visits30d = 0;
  $challengeHits30d = 0;

  while (($line = fgets($handle)) !== false) {
    $entry = zs_dashboard_ctf_parse_access_line($line);
    if (!is_array($entry)) {
      continue;
    }

    $ip = trim((string)($entry['ip'] ?? ''));
    if ($ip !== '' && $ip !== 'Unknown' && zs_analytics_is_ignored_ip($ip)) {
      continue;
    }

    $target = zs_dashboard_ctf_parse_target((string)($entry['target'] ?? '/'));
    $path = (string)($target['path'] ?? '/');
    $query = (string)($target['query'] ?? '');

    if (zs_dashboard_ctf_is_static_path($path)) {
      continue;
    }

    $route = zs_dashboard_ctf_route_key($path, $query);
    if ($route === '') {
      $route = '/';
    }

    $ts = (int)($entry['ts'] ?? 0);
    if ($ts <= 0) {
      continue;
    }
    $minuteKey = zs_analytics_format_ts($ts, 'Y-m-d H:i');
    $hourKey = zs_analytics_format_ts($ts, 'Y-m-d H:00');
    $dayKey = zs_analytics_format_ts($ts, 'Y-m-d');

    $ipForHash = $ip !== '' ? $ip : 'Unknown';
    $ipHash = zs_analytics_ip_hash($ipForHash);
    $method = strtoupper((string)($entry['method'] ?? 'GET'));
    $status = (int)($entry['status'] ?? 0);
    $ua = (string)($entry['ua'] ?? '');
    $device = ($ua !== '' && $ua !== '-') ? zs_analytics_device_label($ua) : 'Unknown | Unknown | Other';
    $device = str_replace('•', '|', $device);

    $country = 'UN';

    $visitsAll++;
    if ($ipHash !== '') {
      $allUnique[$ipHash] = true;
    }

    if ($ts >= $cutoff5m && $ipHash !== '') {
      $activeIps[$ipHash] = true;
    }

    if ($ts >= $cutoff1h && isset($minutes60[$minuteKey])) {
      $minutes60[$minuteKey]['count']++;
    }

    if ($ts >= $cutoff24h && isset($hours24[$hourKey])) {
      $hours24[$hourKey]['count']++;
    }

    if ($ts >= $cutoff7d) {
      $visits7d++;
      if ($ipHash !== '') {
        $unique7[$ipHash] = true;
      }
      if (isset($days7[$dayKey])) {
        $days7[$dayKey]['count']++;
      }
    }

    if ($ts < $cutoff30d) {
      continue;
    }

    $visits30d++;
    if ($ipHash !== '') {
      $unique30[$ipHash] = true;
    }
    if (isset($days30[$dayKey])) {
      $days30[$dayKey]['count']++;
    }

    if (!isset($countryCounts30[$country])) {
      $countryCounts30[$country] = 0;
    }
    $countryCounts30[$country]++;

    if (!isset($routeViews30[$route])) {
      $routeViews30[$route] = 0;
    }
    $routeViews30[$route]++;
    if ($ipHash !== '') {
      if (!isset($routeUnique30[$route])) {
        $routeUnique30[$route] = [];
      }
      $routeUnique30[$route][$ipHash] = true;
    }

    $challengeId = null;
    if (preg_match('~^/api/v1/challenges/(\d+)(?:/|$)~', $path, $mChallenge) === 1) {
      $challengeId = (int)$mChallenge[1];
    } else {
      $params = [];
      if ($query !== '') {
        parse_str($query, $params);
      }
      $challengeIdRaw = isset($params['challenge_id']) && is_scalar($params['challenge_id'])
        ? trim((string)$params['challenge_id'])
        : '';
      if ($challengeIdRaw !== '' && ctype_digit($challengeIdRaw)) {
        $challengeId = (int)$challengeIdRaw;
      }
    }

    $isChallengeRoute = $challengeId !== null
      || $route === '/challenges'
      || $route === '/api/v1/challenges'
      || $route === '/api/v1/challenges/{id}'
      || $route === '/api/v1/users/me/submissions?challenge_id={id}';

    if ($isChallengeRoute) {
      $challengeHits30d++;
      if ($ipHash !== '') {
        $challengeUnique30[$ipHash] = true;
      }

      $catalogEntry = (is_int($challengeId) && isset($challengeCatalog[$challengeId]))
        ? $challengeCatalog[$challengeId]
        : null;
      $bucketLabel = is_array($catalogEntry)
        ? (string)($catalogEntry['name'] ?? zs_dashboard_ctf_challenge_label($challengeId, $route))
        : zs_dashboard_ctf_challenge_label($challengeId, $route);
      $bucketUrl = is_array($catalogEntry) ? (string)($catalogEntry['url'] ?? '') : '';
      $bucketEndpoint = is_array($catalogEntry) ? (string)($catalogEntry['endpoint'] ?? '') : '';

      $bucketKey = $challengeId !== null ? ('challenge:' . $challengeId) : ('route:' . $route);
      if (!isset($challengeBuckets30[$bucketKey])) {
        $challengeBuckets30[$bucketKey] = [
          'label' => $bucketLabel,
          'path' => $route,
          'hits' => 0,
          'unique' => [],
          'last_seen' => 0,
          'challenge_id' => $challengeId,
          'url' => $bucketUrl,
          'endpoint' => $bucketEndpoint,
        ];
      }
      $challengeBuckets30[$bucketKey]['hits']++;
      if ($ipHash !== '') {
        $challengeBuckets30[$bucketKey]['unique'][$ipHash] = true;
      }
      if ($ts > (int)$challengeBuckets30[$bucketKey]['last_seen']) {
        $challengeBuckets30[$bucketKey]['last_seen'] = $ts;
      }
      if (is_array($catalogEntry)) {
        $challengeBuckets30[$bucketKey]['challenge_id'] = $challengeId;
        $challengeBuckets30[$bucketKey]['label'] = $bucketLabel;
        $challengeBuckets30[$bucketKey]['url'] = $bucketUrl;
        $challengeBuckets30[$bucketKey]['endpoint'] = $bucketEndpoint;
      }
    }

    $recentAccess30[] = [
      'ts' => $ts,
      'ip' => zs_analytics_public_ip_label($ipForHash),
      'country' => $country,
      'device' => $device,
      'method' => $method,
      'status' => $status,
      'path' => $route,
    ];
  }

  fclose($handle);

  $webChallengeCounts = zs_dashboard_ctf_collect_web_challenge_counts($challengeCatalog, $cutoff30d);
  if (is_array($webChallengeCounts)) {
    $extraHits = isset($webChallengeCounts['hits']) ? (int)$webChallengeCounts['hits'] : 0;
    if ($extraHits > 0) {
      $challengeHits30d += $extraHits;
    }

    if (isset($webChallengeCounts['unique']) && is_array($webChallengeCounts['unique'])) {
      foreach ($webChallengeCounts['unique'] as $ipHash => $flag) {
        if ((bool)$flag && is_string($ipHash) && $ipHash !== '') {
          $challengeUnique30[$ipHash] = true;
        }
      }
    }

    if (isset($webChallengeCounts['buckets']) && is_array($webChallengeCounts['buckets'])) {
      foreach ($webChallengeCounts['buckets'] as $bucketKey => $bucketData) {
        if (!is_string($bucketKey) || $bucketKey === '' || !is_array($bucketData)) {
          continue;
        }

        if (!isset($challengeBuckets30[$bucketKey])) {
          $challengeBuckets30[$bucketKey] = [
            'label' => (string)($bucketData['label'] ?? ''),
            'path' => (string)($bucketData['path'] ?? '/'),
            'hits' => 0,
            'unique' => [],
            'last_seen' => 0,
            'challenge_id' => isset($bucketData['challenge_id']) ? (int)$bucketData['challenge_id'] : null,
            'url' => (string)($bucketData['url'] ?? ''),
            'endpoint' => (string)($bucketData['endpoint'] ?? ''),
          ];
        }

        $challengeBuckets30[$bucketKey]['hits'] += (int)($bucketData['hits'] ?? 0);
        if (isset($bucketData['unique']) && is_array($bucketData['unique'])) {
          foreach ($bucketData['unique'] as $ipHash => $flag) {
            if ((bool)$flag && is_string($ipHash) && $ipHash !== '') {
              $challengeBuckets30[$bucketKey]['unique'][$ipHash] = true;
            }
          }
        }

        $lastSeen = isset($bucketData['last_seen']) ? (int)$bucketData['last_seen'] : 0;
        if ($lastSeen > (int)($challengeBuckets30[$bucketKey]['last_seen'] ?? 0)) {
          $challengeBuckets30[$bucketKey]['last_seen'] = $lastSeen;
        }

        $label = trim((string)($bucketData['label'] ?? ''));
        if ($label !== '') {
          $challengeBuckets30[$bucketKey]['label'] = $label;
        }
        $url = trim((string)($bucketData['url'] ?? ''));
        if ($url !== '') {
          $challengeBuckets30[$bucketKey]['url'] = $url;
        }
        $endpoint = trim((string)($bucketData['endpoint'] ?? ''));
        if ($endpoint !== '') {
          $challengeBuckets30[$bucketKey]['endpoint'] = $endpoint;
        }
        if (isset($bucketData['challenge_id'])) {
          $candidateId = (int)$bucketData['challenge_id'];
          if ($candidateId > 0) {
            $challengeBuckets30[$bucketKey]['challenge_id'] = $candidateId;
          }
        }
      }
    }
  }

  foreach ($challengeCatalog as $catalogChallengeId => $catalogEntry) {
    $bucketKey = 'challenge:' . (int)$catalogChallengeId;
    if (!isset($challengeBuckets30[$bucketKey])) {
      $challengeBuckets30[$bucketKey] = [
        'label' => (string)($catalogEntry['name'] ?? ('Challenge #' . $catalogChallengeId)),
        'path' => '/api/v1/challenges/{id}',
        'hits' => 0,
        'unique' => [],
        'last_seen' => 0,
        'challenge_id' => (int)$catalogChallengeId,
        'url' => (string)($catalogEntry['url'] ?? ''),
        'endpoint' => (string)($catalogEntry['endpoint'] ?? ''),
      ];
      continue;
    }

    $existingLabel = trim((string)($challengeBuckets30[$bucketKey]['label'] ?? ''));
    if ($existingLabel === '' || str_starts_with($existingLabel, 'Challenge #')) {
      $challengeBuckets30[$bucketKey]['label'] = (string)($catalogEntry['name'] ?? ('Challenge #' . $catalogChallengeId));
    }
    $challengeBuckets30[$bucketKey]['challenge_id'] = (int)$catalogChallengeId;
    $challengeBuckets30[$bucketKey]['url'] = (string)($catalogEntry['url'] ?? '');
    $challengeBuckets30[$bucketKey]['endpoint'] = (string)($catalogEntry['endpoint'] ?? '');
  }

  arsort($countryCounts30);
  $countries30 = [];
  foreach ($countryCounts30 as $countryCode => $count) {
    $countries30[] = [
      'code' => $countryCode,
      'count' => (int)$count,
    ];
  }

  arsort($routeViews30);
  $topPaths30 = [];
  foreach ($routeViews30 as $route => $views) {
    $topPaths30[] = [
      'path' => $route,
      'views' => (int)$views,
      'unique' => isset($routeUnique30[$route]) && is_array($routeUnique30[$route]) ? count($routeUnique30[$route]) : 0,
    ];
  }

  uasort(
    $challengeBuckets30,
    static function (array $a, array $b): int {
      $aHasEndpoint = trim((string)($a['endpoint'] ?? '')) !== '' ? 1 : 0;
      $bHasEndpoint = trim((string)($b['endpoint'] ?? '')) !== '' ? 1 : 0;
      if ($aHasEndpoint !== $bHasEndpoint) {
        return $bHasEndpoint <=> $aHasEndpoint;
      }
      $hitsCmp = ((int)($b['hits'] ?? 0)) <=> ((int)($a['hits'] ?? 0));
      if ($hitsCmp !== 0) {
        return $hitsCmp;
      }
      return ((int)($b['last_seen'] ?? 0)) <=> ((int)($a['last_seen'] ?? 0));
    }
  );

  $challengeActivity30 = [];
  foreach ($challengeBuckets30 as $bucket) {
    $lastSeen = (int)($bucket['last_seen'] ?? 0);
    $challengeActivity30[] = [
      'label' => (string)($bucket['label'] ?? ''),
      'path' => (string)($bucket['path'] ?? '/'),
      'challenge_id' => isset($bucket['challenge_id']) ? (int)$bucket['challenge_id'] : null,
      'url' => (string)($bucket['url'] ?? ''),
      'endpoint' => (string)($bucket['endpoint'] ?? ''),
      'hits' => (int)($bucket['hits'] ?? 0),
      'unique' => isset($bucket['unique']) && is_array($bucket['unique']) ? count($bucket['unique']) : 0,
      'last_seen' => $lastSeen > 0 ? zs_analytics_format_ts($lastSeen, DATE_ATOM) : '',
    ];
  }

  usort(
    $recentAccess30,
    static function (array $a, array $b): int {
      return ((int)($b['ts'] ?? 0)) <=> ((int)($a['ts'] ?? 0));
    }
  );

  $minutes60Out = [];
  foreach ($minutes60 as $entry) {
    $minutes60Out[] = $entry;
  }
  $hours24Out = [];
  foreach ($hours24 as $entry) {
    $hours24Out[] = $entry;
  }
  $days7Out = [];
  foreach ($days7 as $entry) {
    $days7Out[] = $entry;
  }
  $days30Out = [];
  foreach ($days30 as $entry) {
    $days30Out[] = $entry;
  }

  return [
    'available' => true,
    'generated_at' => zs_analytics_format_ts($now, DATE_ATOM),
    'log_path' => $logPath,
    'totals' => [
      'active_now' => count($activeIps),
      'visits_all' => $visitsAll,
      'visits_7d' => $visits7d,
      'visits_30d' => $visits30d,
      'unique_all' => count($allUnique),
      'unique_7d' => count($unique7),
      'unique_30d' => count($unique30),
      'challenge_hits_30d' => $challengeHits30d,
      'challenge_unique_30d' => count($challengeUnique30),
    ],
    'series' => [
      'minutes_60' => $minutes60Out,
      'hours_24' => $hours24Out,
      'days_7' => $days7Out,
      'days_30' => $days30Out,
    ],
    'top_paths_30d' => array_slice($topPaths30, 0, 24),
    'challenge_activity_30d' => array_slice($challengeActivity30, 0, 24),
    'recent_access_30d' => array_slice($recentAccess30, 0, 500),
    'countries_30d' => array_slice($countries30, 0, 24),
  ];
}

function zs_dashboard_ctf_analytics(int $ttlSeconds = 18): array
{
  $path = zs_dashboard_ctf_cache_path();
  $now = time();
  if (is_file($path) && is_readable($path)) {
    $raw = @file_get_contents($path);
    if (is_string($raw) && trim($raw) !== '') {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $ts = isset($decoded['ts']) ? (int)$decoded['ts'] : 0;
        $payload = isset($decoded['payload']) && is_array($decoded['payload']) ? $decoded['payload'] : null;
        if ($payload !== null && $ts > 0 && ($now - $ts) <= $ttlSeconds) {
          return $payload;
        }
      }
    }
  }

  $payload = zs_dashboard_ctf_analytics_uncached();
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
  $wrapper = [
    'ts' => $now,
    'payload' => $payload,
  ];
  $encoded = json_encode($wrapper, JSON_UNESCAPED_SLASHES);
  if (is_string($encoded)) {
    @file_put_contents($path, $encoded, LOCK_EX);
  }
  return $payload;
}

function zs_dashboard_build_stats(): array
{
  $stats = zs_analytics_build_stats();
  $runtime = zs_dashboard_runtime_metrics();
  $stats['ctf_analytics'] = zs_dashboard_ctf_analytics();
  $stats['resources'] = is_array($runtime['resources'] ?? null) ? $runtime['resources'] : [];
  $stats['response_cards'] = is_array($runtime['response_cards'] ?? null) ? $runtime['response_cards'] : [];
  $stats['runtime_generated_at'] = (string)($runtime['generated_at'] ?? '');
  return $stats;
}

if (!isset($_SESSION[ZS_ADMIN_CSRF_KEY])) {
  $_SESSION[ZS_ADMIN_CSRF_KEY] = bin2hex(random_bytes(32));
}

zs_dashboard_send_security_headers();

if (isset($_GET['logout'])) {
  unset($_SESSION[ZS_ADMIN_SESSION_KEY]);
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
  }
  zs_dashboard_redirect(['msg' => 'Signed out.']);
}

$isAuthenticated = !empty($_SESSION[ZS_ADMIN_SESSION_KEY]);

if (isset($_GET['stats'])) {
  header('Content-Type: application/json; charset=utf-8');
  if (!$isAuthenticated) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_SLASHES);
    exit;
  }
  echo json_encode(zs_dashboard_build_stats(), JSON_UNESCAPED_SLASHES);
  exit;
}

if (isset($_GET['assistant_chat'])) {
  if (!$isAuthenticated) {
    zs_json_response(['success' => false, 'error' => 'Unauthorized'], 403);
  }

  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    zs_json_response(['success' => false, 'error' => 'Method not allowed. Use POST.'], 405);
  }

  $rawInput = file_get_contents('php://input');
  $input = json_decode((string)$rawInput, true);
  if (!is_array($input)) {
    zs_json_response(['success' => false, 'error' => 'Invalid JSON payload.'], 400);
  }

  $csrf = trim((string)($input['csrf'] ?? ''));
  $sessionCsrf = isset($_SESSION[ZS_ADMIN_CSRF_KEY]) ? (string)$_SESSION[ZS_ADMIN_CSRF_KEY] : '';
  if ($csrf === '' || $sessionCsrf === '' || !hash_equals($sessionCsrf, $csrf)) {
    zs_json_response(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
  }

  $message = zs_trim_text((string)($input['message'] ?? ''), 1400);
  if ($message === '') {
    zs_json_response(['success' => false, 'error' => 'Message is required.'], 400);
  }

  $history = zs_normalize_chat_history($input['history'] ?? null);
  $apiKey = zs_load_openai_key();
  if ($apiKey === '') {
    zs_json_response(['success' => false, 'error' => 'OpenAI API key is missing.'], 500);
  }

  $systemPrompt = 'You are Aegis Lab Assistant for a high-school cybersecurity team admin dashboard. Help with website wording, event announcements, member communication, and concise technical guidance. Keep responses clear, practical, and concise. If official facts or dates are uncertain, say they should be verified.';

  $messages = [
    ['role' => 'system', 'content' => $systemPrompt],
  ];
  foreach ($history as $item) {
    $messages[] = $item;
  }

  $lastHistory = $history[count($history) - 1] ?? null;
  if (
    !is_array($lastHistory) ||
    (string)($lastHistory['role'] ?? '') !== 'user' ||
    trim((string)($lastHistory['content'] ?? '')) !== $message
  ) {
    $messages[] = ['role' => 'user', 'content' => $message];
  }

  $payload = [
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'temperature' => 0.4,
    'max_tokens' => 420,
  ];
  $payloadJson = zs_json_encode($payload);
  if (!is_string($payloadJson)) {
    zs_json_response(['success' => false, 'error' => 'Could not prepare OpenAI request.'], 500);
  }

  $apiUrl = 'https://api.openai.com/v1/chat/completions';
  $responseBody = '';
  $statusCode = 0;

  if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
      ],
      CURLOPT_POSTFIELDS => $payloadJson,
      CURLOPT_CONNECTTIMEOUT => 12,
      CURLOPT_TIMEOUT => 30,
    ]);

    $responseBody = (string)curl_exec($ch);
    $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($responseBody === '' && $curlError !== '') {
      zs_json_response(['success' => false, 'error' => 'OpenAI request failed: ' . $curlError], 502);
    }
  } else {
    $context = stream_context_create([
      'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n" .
          'Authorization: Bearer ' . $apiKey . "\r\n",
        'content' => $payloadJson,
        'timeout' => 30,
        'ignore_errors' => true,
      ],
    ]);

    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) {
      $lastError = error_get_last();
      $msg = is_array($lastError) ? (string)($lastError['message'] ?? 'Unknown stream error') : 'Unknown stream error';
      zs_json_response(['success' => false, 'error' => 'OpenAI request failed: ' . $msg], 502);
    }

    $responseBody = (string)$response;
    if (isset($http_response_header) && is_array($http_response_header)) {
      foreach ($http_response_header as $headerLine) {
        if (preg_match('/^HTTP\/[0-9.]+\s+(\d{3})/', $headerLine, $matches) === 1) {
          $statusCode = (int)$matches[1];
          break;
        }
      }
    }
  }

  if ($statusCode >= 400 && $responseBody === '') {
    zs_json_response(['success' => false, 'error' => 'OpenAI request failed with HTTP ' . $statusCode], 502);
  }

  $responseData = json_decode($responseBody, true);
  if (!is_array($responseData)) {
    zs_json_response(['success' => false, 'error' => 'Invalid JSON from OpenAI API.'], 502);
  }

  $replyRaw = $responseData['choices'][0]['message']['content'] ?? '';
  $reply = '';
  if (is_string($replyRaw)) {
    $reply = trim($replyRaw);
  } elseif (is_array($replyRaw)) {
    $pieces = [];
    foreach ($replyRaw as $piece) {
      if (!is_array($piece)) {
        continue;
      }
      $pieceType = strtolower(trim((string)($piece['type'] ?? '')));
      if ($pieceType === 'text') {
        $textValue = trim((string)($piece['text'] ?? ''));
        if ($textValue !== '') {
          $pieces[] = $textValue;
        }
      }
    }
    $reply = trim(implode("\n", $pieces));
  }

  if ($reply !== '') {
    zs_json_response([
      'success' => true,
      'reply' => $reply,
    ]);
  }

  $apiError = trim((string)($responseData['error']['message'] ?? 'Could not generate a reply.'));
  if ($apiError === '') {
    $apiError = 'Could not generate a reply.';
  }
  zs_json_response(['success' => false, 'error' => $apiError], 502);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$isAuthenticated) {
    $clientIp = zs_dashboard_client_ip();
    $limitStatus = zs_dashboard_login_limit_status($clientIp);
    if (!empty($limitStatus['blocked'])) {
      $wait = max(1, (int)($limitStatus['retry_after'] ?? 0));
      $waitMinutes = (int)ceil($wait / 60);
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Too many login attempts. Try again in about ' . $waitMinutes . ' minute(s).']);
    }

    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $passwordHash = zs_dashboard_admin_password_hash();
    if ($password !== '' && $passwordHash !== '' && password_verify($password, $passwordHash)) {
      if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
      }
      $_SESSION[ZS_ADMIN_SESSION_KEY] = true;
      $_SESSION[ZS_ADMIN_CSRF_KEY] = bin2hex(random_bytes(32));
      zs_dashboard_login_limit_reset($clientIp);
      zs_dashboard_redirect(['msg' => 'Login successful.']);
    }
    if ($passwordHash === '') {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Dashboard password hash is not configured.']);
    }
    zs_dashboard_login_limit_register_failure($clientIp);
    zs_dashboard_redirect(['type' => 'error', 'msg' => 'Invalid password.']);
  }

  if (!zs_require_csrf()) {
    zs_dashboard_redirect(['type' => 'error', 'msg' => 'Invalid CSRF token.']);
  }

  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
  if ($action === 'save_content') {
    $content = zs_get_site_content();
    $content['hero_subtitle'] = zs_trim_post('hero_subtitle', 220);
    $content['home_intro'] = zs_trim_post('home_intro', 1000);
    $content['about_intro'] = zs_trim_post('about_intro', 1000);
    $content['members_intro'] = zs_trim_post('members_intro', 400);
    $content['contact_email'] = zs_trim_post('contact_email', 180);
    $submittedCtfUrl = zs_trim_post('ctf_url', 350);
    $content['ctf_label'] = zs_trim_post('ctf_label', 180);

    if ($content['contact_email'] !== '' && !filter_var($content['contact_email'], FILTER_VALIDATE_EMAIL)) {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Invalid contact email format.']);
    }

    $safeCtfUrl = zs_safe_http_url($submittedCtfUrl, '');
    if ($safeCtfUrl === '') {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Team CTF URL must be a valid http(s) URL.']);
    }
    $content['ctf_url'] = $safeCtfUrl;

    if (zs_save_site_content($content)) {
      zs_dashboard_redirect(['msg' => 'Website content updated.']);
    }
    zs_dashboard_redirect(['type' => 'error', 'msg' => 'Could not save website content.']);
  }

  if ($action === 'save_founder') {
    $members = zs_get_members();
    $currentFounder = $members['founder'];
    $members['founder'] = [
      'role' => zs_trim_post('founder_role', 90),
      'name' => zs_trim_post('founder_name', 120),
      'handle' => zs_trim_post('founder_handle', 120),
      'bio' => zs_trim_post('founder_bio', 500),
      'avatar' => zs_trim_post('founder_avatar', 300),
      'initials' => zs_trim_post('founder_initials', 6),
      'discord' => zs_trim_post('founder_discord', 300),
      'github' => zs_trim_post('founder_github', 300),
      'linkedin' => zs_trim_post('founder_linkedin', 300),
      'instagram' => zs_trim_post('founder_instagram', 300),
      'website' => trim((string)($currentFounder['website'] ?? '')),
      'x' => trim((string)($currentFounder['x'] ?? '')),
    ];

    if ($members['founder']['name'] === '') {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Founder name is required.']);
    }

    if (zs_save_members($members)) {
      zs_dashboard_redirect(['msg' => 'Founder profile updated.']);
    }
    zs_dashboard_redirect(['type' => 'error', 'msg' => 'Could not save founder profile.']);
  }

  if ($action === 'add_member') {
    $members = zs_get_members();
    $newMember = [
      'role' => zs_trim_post('new_role', 90),
      'name' => zs_trim_post('new_name', 120),
      'handle' => zs_trim_post('new_handle', 120),
      'bio' => zs_trim_post('new_bio', 500),
      'avatar' => zs_trim_post('new_avatar', 300),
      'initials' => zs_trim_post('new_initials', 6),
      'discord' => zs_trim_post('new_discord', 300),
      'github' => zs_trim_post('new_github', 300),
      'linkedin' => zs_trim_post('new_linkedin', 300),
      'instagram' => zs_trim_post('new_instagram', 300),
      'website' => '',
      'x' => '',
    ];

    if ($newMember['name'] === '') {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'New member name is required.']);
    }

    $members['team'][] = $newMember;
    if (zs_save_members($members)) {
      zs_dashboard_redirect(['msg' => 'New member added.']);
    }
    zs_dashboard_redirect(['type' => 'error', 'msg' => 'Could not add member.']);
  }

  if ($action === 'update_member' || $action === 'delete_member') {
    $members = zs_get_members();
    $index = isset($_POST['member_index']) ? (int)$_POST['member_index'] : -1;
    if (!isset($members['team'][$index])) {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Member index is invalid.']);
    }

    if ($action === 'delete_member') {
      array_splice($members['team'], $index, 1);
      if (zs_save_members($members)) {
        zs_dashboard_redirect(['msg' => 'Member removed.']);
      }
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Could not remove member.']);
    }

    $currentMember = $members['team'][$index];
    $members['team'][$index] = [
      'role' => zs_trim_post('role', 90),
      'name' => zs_trim_post('name', 120),
      'handle' => zs_trim_post('handle', 120),
      'bio' => zs_trim_post('bio', 500),
      'avatar' => zs_trim_post('avatar', 300),
      'initials' => zs_trim_post('initials', 6),
      'discord' => zs_trim_post('discord', 300),
      'github' => zs_trim_post('github', 300),
      'linkedin' => zs_trim_post('linkedin', 300),
      'instagram' => zs_trim_post('instagram', 300),
      'website' => trim((string)($currentMember['website'] ?? '')),
      'x' => trim((string)($currentMember['x'] ?? '')),
    ];

    if ($members['team'][$index]['name'] === '') {
      zs_dashboard_redirect(['type' => 'error', 'msg' => 'Member name is required.']);
    }

    if (zs_save_members($members)) {
      zs_dashboard_redirect(['msg' => 'Member updated.']);
    }
    zs_dashboard_redirect(['type' => 'error', 'msg' => 'Could not update member.']);
  }

  zs_dashboard_redirect(['type' => 'error', 'msg' => 'Unknown action.']);
}

[$flashType, $flashMessage] = zs_dashboard_flash();
$siteContent = zs_get_site_content();
$members = zs_get_members();
$founder = $members['founder'];
$team = $members['team'];
$csrfToken = (string)$_SESSION[ZS_ADMIN_CSRF_KEY];
$dashboardStats = $isAuthenticated ? zs_dashboard_build_stats() : [];
$dashboardStatsJson = json_encode(
  $dashboardStats,
  JSON_UNESCAPED_UNICODE
  | JSON_UNESCAPED_SLASHES
  | JSON_INVALID_UTF8_SUBSTITUTE
  | JSON_HEX_TAG
  | JSON_HEX_AMP
  | JSON_HEX_APOS
  | JSON_HEX_QUOT
);
if (!is_string($dashboardStatsJson)) {
  $dashboardStatsJson = '{}';
}
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <title>Aegis Lab Dashboard</title>
  <link rel="icon" type="image/x-icon" href="/Aegis_favicon.ico">
  <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
  <style>
    :root {
      --bg: #f4f7fb;
      --panel: #ffffff;
      --line: #d8e0ec;
      --text: #17253b;
      --muted: #566883;
      --accent: #1f4fd1;
      --danger: #de3d59;
      --ok: #0e9a69;
      --sidebar-w: 272px;
      --panel-max-w: 1120px;
      --panel-nudge-x: 28px;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--text);
      font-family: "Space Grotesk", "Segoe UI", sans-serif;
    }

    .wrap {
      width: min(1240px, calc(100% - 2rem));
      margin: 1.2rem auto 2rem;
      display: grid;
      gap: 1rem;
    }

    .panel {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 4px;
      padding: 1rem;
      box-shadow: 0 8px 24px rgba(24, 43, 74, 0.07);
    }

    h1,
    h2,
    h3 {
      margin: 0 0 0.6rem;
      font-family: "Space Grotesk", "Segoe UI", sans-serif;
      letter-spacing: 0.01em;
    }

    p {
      margin: 0;
      color: var(--muted);
      line-height: 1.5;
    }

    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .status {
      margin-top: 0.8rem;
      border: 1px solid var(--line);
      border-radius: 4px;
      padding: 0.65rem 0.8rem;
      font-size: 0.95rem;
      color: var(--text);
    }

    .status.ok {
      border-color: rgba(14, 154, 105, 0.42);
      background: #f2fbf7;
    }

    .status.error {
      border-color: rgba(222, 61, 89, 0.42);
      background: #fef2f5;
    }

    form {
      display: grid;
      gap: 0.7rem;
    }

    .row {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.7rem;
    }

    label {
      display: grid;
      gap: 0.32rem;
      font-size: 0.9rem;
      color: #3f5170;
    }

    input,
    textarea {
      width: 100%;
      border: 1px solid #c4d0e1;
      border-radius: 4px;
      background: #ffffff;
      color: #13233a;
      padding: 0.55rem 0.62rem;
      font: inherit;
      resize: vertical;
    }

    textarea {
      min-height: 90px;
    }

    .actions {
      display: flex;
      gap: 0.55rem;
      flex-wrap: wrap;
      margin-top: 0.2rem;
    }

    button,
    .logout-link {
      border: 1px solid #b9c7dc;
      background: #ffffff;
      color: #203b66;
      border-radius: 4px;
      padding: 0.52rem 0.85rem;
      font: inherit;
      text-decoration: none;
      cursor: pointer;
    }

    button:hover,
    .logout-link:hover {
      border-color: #8aa0c0;
      color: #0f2f58;
      background: #eef4fd;
    }

    .danger {
      border-color: rgba(222, 61, 89, 0.45);
      color: #aa213d;
    }

    .member-item {
      border: 1px solid var(--line);
      border-radius: 4px;
      padding: 0.8rem;
      background: #f8fbff;
      margin-top: 0.7rem;
    }

    .kicker {
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--accent);
      font-size: 0.73rem;
      margin-bottom: 0.6rem;
    }

    .hint {
      font-size: 0.84rem;
      color: var(--muted);
    }

    .stats-summary-card {
      margin-top: 0.75rem;
      border: 0;
      border-radius: 0;
      background: transparent;
      padding: 0;
    }

    .stats-grid {
      margin-top: 0;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
      gap: 0.65rem;
    }

    .stat-card {
      border: 1px solid var(--line);
      border-radius: 3px;
      background: #f8fbff;
      padding: 0.62rem 0.66rem;
      min-width: 0;
    }

    .stat-label {
      margin: 0;
      font-size: 0.78rem;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #60779d;
    }

    .stat-value {
      margin: 0.18rem 0 0;
      font-size: clamp(1.1rem, 1.9vw, 1.4rem);
      color: #152742;
      font-weight: 600;
      line-height: 1.05;
    }

    .stats-section-grid {
      margin-top: 0.86rem;
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
      gap: 0.75rem;
      align-items: stretch;
    }

    .chart-card,
    .globe-card {
      border: 1px solid var(--line);
      border-radius: 4px;
      background: #ffffff;
      padding: 0.68rem;
    }

    .chart-wrap {
      position: relative;
      width: 100%;
      min-height: 260px;
    }

    .chart-wrap canvas {
      width: 100%;
      height: 260px !important;
      display: block;
    }

    .chart-tooltip {
      position: absolute;
      z-index: 7;
      pointer-events: none;
      border: 1px solid #b8c9e2;
      background: rgba(255, 255, 255, 0.97);
      color: #1a2e4a;
      border-radius: 4px;
      padding: 0.34rem 0.44rem;
      font-size: 0.78rem;
      line-height: 1.24;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
      min-width: 132px;
      max-width: 220px;
    }

    .chart-tooltip strong {
      display: block;
      color: #132743;
      font-weight: 600;
      margin-bottom: 0.1rem;
    }

    .globe-wrap {
      display: block;
      width: 100%;
      height: 300px;
      border: 1px solid #2a3b57;
      border-radius: 4px;
      overflow: hidden;
      background: radial-gradient(circle at 30% 30%, rgba(64, 102, 156, 0.38), #04070d 72%);
      cursor: grab;
    }

    .globe-canvas-wrap {
      position: relative;
      width: 100%;
    }

    .globe-tooltip {
      position: absolute;
      z-index: 7;
      pointer-events: none;
      border: 1px solid #b8c9e2;
      background: rgba(255, 255, 255, 0.97);
      color: #1a2e4a;
      border-radius: 4px;
      padding: 0.36rem 0.45rem;
      font-size: 0.78rem;
      line-height: 1.26;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
      min-width: 140px;
      max-width: 220px;
    }

    .globe-tooltip strong {
      display: block;
      color: #132743;
      font-weight: 600;
      margin-bottom: 0.12rem;
    }

    .country-list {
      margin-top: 0.58rem;
      display: grid;
      gap: 0.3rem;
      max-height: 148px;
      overflow: auto;
      padding-right: 0.22rem;
    }

    .country-row {
      display: flex;
      justify-content: space-between;
      gap: 0.6rem;
      border: 1px solid #d4deeb;
      border-radius: 3px;
      padding: 0.34rem 0.48rem;
      color: #334a6d;
      font-size: 0.84rem;
      line-height: 1.2;
      background: #f8fbff;
    }

    .country-row strong {
      color: #1e3558;
      font-weight: 600;
    }

    .country-row span {
      color: #334a6d;
      font-weight: 400;
    }

    .chart-title {
      margin: 0;
      font-size: 0.96rem;
      color: #264168;
    }

    .chart-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.65rem;
      flex-wrap: wrap;
      margin-bottom: 0.35rem;
    }

    .range-switch {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.34rem;
    }

    .range-btn {
      border: 1px solid #c2d1e7;
      background: #ffffff;
      color: #35557f;
      border-radius: 3px;
      padding: 0.24rem 0.48rem;
      font-size: 0.72rem;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      line-height: 1.2;
      cursor: pointer;
    }

    .range-btn:hover {
      border-color: #8ca5c8;
      color: #193a66;
      background: #edf4fe;
    }

    .range-btn.active {
      border-color: #2c5ea7;
      color: #1c4277;
      background: #eaf1fd;
    }

    .chart-meta {
      margin: 0 0 0.35rem;
      font-size: 0.79rem;
      color: #60779d;
      line-height: 1.35;
    }

    .resources-strip {
      margin-top: 0.7rem;
      border-top: 1px solid #d7e1ef;
      padding-top: 0.58rem;
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.45rem;
    }

    .resource-mini-card {
      border: 1px solid #d6dfec;
      border-radius: 3px;
      background: #f8fbff;
      padding: 0.42rem 0.5rem;
    }

    .resource-mini-label {
      margin: 0;
      font-size: 0.73rem;
      letter-spacing: 0.03em;
      color: #597093;
      text-transform: uppercase;
      font-weight: 500;
    }

    .resource-mini-value {
      margin: 0.14rem 0 0;
      color: #1f365a;
      font-size: 0.86rem;
      line-height: 1.28;
      font-weight: 500;
      word-break: break-word;
    }

    .response-cards-block {
      margin-top: 0.45rem;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.5rem;
    }

    .response-mini-card {
      border: 1px solid #d6dfec;
      border-radius: 3px;
      background: #f8fbff;
      padding: 0.45rem 0.5rem;
    }

    .response-mini-host {
      margin: 0;
      font-size: 0.79rem;
      letter-spacing: 0.02em;
      color: #597093;
      font-weight: 500;
    }

    .response-mini-value {
      margin: 0.14rem 0 0;
      color: #1f365a;
      font-size: 0.98rem;
      line-height: 1.3;
      word-break: break-word;
      font-weight: 500;
    }

    .response-mini-value.down {
      color: #ab2a42;
    }

    .response-mini-meta {
      margin: 0.1rem 0 0;
      color: #6a7f9f;
      font-size: 0.76rem;
      line-height: 1.24;
    }

    .pages-card {
      grid-column: 1 / -1;
      border: 1px solid var(--line);
      border-radius: 4px;
      background: #ffffff;
      padding: 0.68rem;
    }

    .pages-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0.3rem;
      font-size: 0.86rem;
    }

    .pages-table th,
    .pages-table td {
      border-bottom: 1px solid #dbe4f0;
      padding: 0.42rem 0.36rem;
      text-align: left;
      vertical-align: top;
    }

    .pages-table th {
      color: #4c6488;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-size: 0.72rem;
    }

    .pages-table td:last-child,
    .pages-table th:last-child,
    .pages-table td:nth-child(2),
    .pages-table th:nth-child(2),
    .pages-table td:nth-child(3),
    .pages-table th:nth-child(3) {
      text-align: right;
      white-space: nowrap;
    }

    .pages-table td:nth-child(2),
    .pages-table th:nth-child(2) {
      width: 90px;
    }

    .pages-table td:nth-child(3),
    .pages-table th:nth-child(3) {
      width: 120px;
    }

    .pages-table tr:last-child td {
      border-bottom: 0;
    }

    .meta-line {
      margin-top: 0.46rem;
      font-size: 0.78rem;
      color: #617a9f;
    }

    .access-card {
      grid-column: 1 / -1;
      border: 1px solid var(--line);
      border-radius: 4px;
      background: #ffffff;
      padding: 0.68rem;
    }

    .access-table-wrap {
      overflow: auto;
      max-height: 360px;
      border: 1px solid #d8e2ef;
      border-radius: 3px;
      background: #f9fcff;
    }

    .access-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.84rem;
    }

    .access-table th,
    .access-table td {
      border-bottom: 1px solid #dbe4f0;
      padding: 0.38rem 0.42rem;
      text-align: left;
      vertical-align: top;
      white-space: nowrap;
    }

    .access-table th {
      position: sticky;
      top: 0;
      background: #edf3fb;
      color: #4e678d;
      z-index: 2;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      font-size: 0.71rem;
    }

    .access-table td.path-cell {
      max-width: 280px;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .pages-table tbody tr td,
    .access-table tbody tr td {
      background: #eef2f7;
    }

    .access-table tbody tr:hover td,
    .pages-table tbody tr:hover td {
      background: #e7edf6;
    }

    .ctf-layout {
      margin-top: 0.76rem;
    }

    .ctf-layout .ctf-chart-card {
      grid-column: 1 / -1;
    }

    .ctf-layout .pages-card {
      grid-column: auto;
    }

    .ctf-layout .ctf-access-card {
      grid-column: 1 / -1;
    }

    .ctf-challenge-table td:first-child,
    .ctf-challenge-table th:first-child {
      max-width: 280px;
    }

    .ctf-challenge-table td:nth-child(2),
    .ctf-challenge-table th:nth-child(2) {
      width: 86px;
    }

    .ctf-challenge-table td:nth-child(3),
    .ctf-challenge-table th:nth-child(3) {
      width: 86px;
    }

    .ctf-challenge-table td:nth-child(4),
    .ctf-challenge-table th:nth-child(4) {
      width: 162px;
      text-align: right;
      white-space: nowrap;
    }

    .challenge-link {
      color: #1f4fd1;
      text-decoration: underline;
      text-underline-offset: 2px;
      font-weight: 600;
    }

    .challenge-link:hover {
      color: #163fba;
    }

    .challenge-endpoint {
      display: block;
      margin-top: 0.2rem;
      color: #6c7f9c;
      font-size: 0.74rem;
      line-height: 1.28;
      word-break: break-all;
    }

    .assistant-layout {
      margin-top: 0.55rem;
      display: grid;
      gap: 0.58rem;
    }

    .assistant-log {
      border: 1px solid var(--line);
      border-radius: 4px;
      background: #ffffff;
      min-height: 340px;
      max-height: 62vh;
      overflow: auto;
      padding: 0.62rem;
      display: grid;
      gap: 0.48rem;
      align-content: start;
    }

    .assistant-empty {
      margin: 0;
      color: #6a7c97;
      font-size: 0.9rem;
    }

    .assistant-row {
      border: 1px solid #d7e1ef;
      border-radius: 4px;
      background: #f7faff;
      padding: 0.48rem 0.58rem;
      max-width: min(680px, 100%);
    }

    .assistant-row.user {
      margin-left: auto;
      background: #eaf2ff;
      border-color: #c4d4eb;
    }

    .assistant-row.assistant {
      margin-right: auto;
      background: #f6f8fc;
      border-color: #d8e2ef;
    }

    .assistant-author {
      margin: 0 0 0.15rem;
      font-size: 0.74rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #4b648a;
    }

    .assistant-text {
      margin: 0;
      color: #1f3351;
      line-height: 1.47;
      white-space: pre-wrap;
      word-break: break-word;
      overflow-wrap: anywhere;
      font-size: 0.92rem;
    }

    .assistant-form {
      display: grid;
      gap: 0.48rem;
      margin-top: 0.04rem;
    }

    .assistant-input {
      min-height: 104px;
      resize: vertical;
    }

    .assistant-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .assistant-status {
      margin: 0;
      min-height: 1.22rem;
      font-size: 0.82rem;
      color: #4b648a;
    }

    .assistant-status.error {
      color: #ab2a42;
    }

    .admin-shell {
      display: block;
    }

    .admin-sidebar {
      background: #ffffff;
      border: 1px solid var(--line);
      border-radius: 0;
      padding: 0.78rem 0.72rem 0.84rem;
      box-shadow: 0 8px 24px rgba(24, 43, 74, 0.07);
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: var(--sidebar-w);
      overflow: auto;
      z-index: 15;
      display: flex;
      flex-direction: column;
      transition: transform 0.24s ease;
    }

    .sidebar-head {
      position: sticky;
      top: 0;
      z-index: 3;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 0.56rem;
      background: inherit;
      padding-bottom: 0.42rem;
      margin-bottom: 0.44rem;
    }

    .sidebar-brand-block {
      min-width: 0;
      flex: 1 1 auto;
    }

    .admin-brand {
      margin: 0;
      font-size: 1.06rem;
      color: #1d365d;
    }

    .admin-sub {
      margin: 0.24rem 0 0;
      font-size: 0.82rem;
      color: #60779d;
    }

    .sidebar-menu {
      display: grid;
      gap: 0.14rem;
      margin-bottom: 0.5rem;
      flex: 1 1 auto;
      align-content: start;
    }

    .menu-link {
      width: 100%;
      text-align: left;
      border: 0;
      background: transparent;
      color: #2e496f;
      border-radius: 0;
      padding: 0.32rem 0.02rem;
      font-size: 0.89rem;
      cursor: pointer;
      line-height: 1.32;
    }

    .menu-link:hover {
      color: #1a3964;
      background: transparent;
      border-color: transparent;
      text-decoration: underline;
      text-underline-offset: 4px;
    }

    .menu-link.active {
      color: #173960;
      font-weight: 400;
      background: transparent;
      border-color: transparent;
      text-decoration: underline;
      text-underline-offset: 4px;
    }

    .sidebar-separator {
      height: 1px;
      width: 100%;
      background: var(--line);
      opacity: 0.9;
      margin: 0.24rem 0 0.34rem;
    }

    .sidebar-logout {
      width: 100%;
      text-align: center;
      display: block;
      margin-top: auto;
      border-radius: 4px;
      padding: 0.46rem 0.54rem;
      line-height: 1.25;
    }

    .sidebar-assistant-link {
      margin-bottom: 0.2rem;
    }

    .sidebar-toggle-icon {
      flex: 0 0 auto;
      width: 1.95rem;
      height: 1.95rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #c7d5e8;
      background: #ffffff;
      color: #28456f;
      border-radius: 4px;
      padding: 0;
      cursor: pointer;
      line-height: 1;
      margin: 0;
    }

    .sidebar-toggle-icon::before {
      content: "\2039";
      font-size: 1.22rem;
      font-weight: 700;
      transform: translateY(-0.5px);
    }

    .sidebar-toggle-icon:hover {
      border-color: #9eb3cf;
      background: #eef4fd;
      color: #173960;
    }

    .sidebar-backdrop {
      position: fixed;
      inset: 0;
      z-index: 14;
      border: 0;
      background: rgba(15, 24, 40, 0.34);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.22s ease;
      display: none;
    }

    .admin-main {
      min-width: 0;
      margin-left: var(--sidebar-w);
      width: calc(100vw - var(--sidebar-w));
      min-height: 100vh;
      padding: 1.1rem 1.25rem 1.55rem;
      transition: margin-left 0.24s ease, width 0.24s ease;
    }

    .admin-main .panel {
      background: transparent;
      border: 0;
      box-shadow: none;
      border-radius: 0;
      padding: 0;
      margin: 0 auto 1.45rem;
      max-width: var(--panel-max-w);
      transform: translateX(
        calc(
          var(--panel-nudge-x) - min(
            calc(var(--sidebar-w) / 2),
            max(0px, calc((100vw - var(--sidebar-w) - 2.5rem - var(--panel-max-w)) / 2))
          )
        )
      );
    }

    .admin-section {
      display: none;
    }

    .admin-section.active {
      display: block;
    }

    .admin-header {
      margin-bottom: 1.3rem;
      border-bottom: 1px solid #dbe4f1;
      padding-bottom: 0.64rem;
    }

    body.auth-dashboard {
      overflow-x: hidden;
    }

    body.auth-dashboard .wrap {
      width: 100%;
      margin: 0;
      gap: 0;
      max-width: none;
    }

    body.auth-dashboard.sidebar-collapsed .admin-sidebar {
      transform: translateX(calc(-1 * var(--sidebar-w) + 2.9rem));
    }

    body.auth-dashboard.sidebar-collapsed .sidebar-brand-block,
    body.auth-dashboard.sidebar-collapsed .sidebar-menu,
    body.auth-dashboard.sidebar-collapsed .sidebar-separator,
    body.auth-dashboard.sidebar-collapsed .sidebar-assistant-link,
    body.auth-dashboard.sidebar-collapsed .sidebar-logout {
      opacity: 0;
      pointer-events: none;
    }

    body.auth-dashboard.sidebar-collapsed .sidebar-toggle-icon::before {
      content: "\203A";
    }

    body.auth-dashboard.sidebar-collapsed .admin-main {
      margin-left: 0;
      width: 100vw;
    }

    body.auth-dashboard.sidebar-collapsed .admin-main .panel {
      transform: none;
    }

    /* Login view: match main website dark/gold theme */
    body:not(.auth-dashboard) {
      min-height: 100vh;
      display: grid;
      place-items: center;
      background:
        radial-gradient(120% 90% at 68% 30%, rgba(255, 141, 22, 0.09) 0%, rgba(0, 0, 0, 0) 40%),
        #000000;
      color: #fff4c9;
    }

    body:not(.auth-dashboard) .wrap {
      width: min(560px, calc(100% - 2rem));
      margin: 0;
    }

    body:not(.auth-dashboard) .panel {
      background: #14100b;
      border: 1px solid #3b2518;
      border-radius: 10px;
      box-shadow: 0 14px 38px rgba(0, 0, 0, 0.44);
      padding: 1.12rem;
    }

    body:not(.auth-dashboard) h1 {
      color: #ffe9b8;
      margin-bottom: 0.72rem;
    }

    body:not(.auth-dashboard) label {
      color: #e9c389;
    }

    body:not(.auth-dashboard) input,
    body:not(.auth-dashboard) textarea {
      border-color: #4b2f20;
      background: #100b08;
      color: #fff4d5;
    }

    body:not(.auth-dashboard) input:focus,
    body:not(.auth-dashboard) textarea:focus {
      outline: 1px solid rgba(255, 194, 58, 0.38);
      outline-offset: 0;
      border-color: #8d5b30;
    }

    body:not(.auth-dashboard) button,
    body:not(.auth-dashboard) .logout-link {
      border-color: #6a442b;
      background: #1b130e;
      color: #ffe2b0;
    }

    body:not(.auth-dashboard) button:hover,
    body:not(.auth-dashboard) .logout-link:hover {
      border-color: #b0713d;
      background: #251912;
      color: #fff4d6;
    }

    body:not(.auth-dashboard) .status {
      border-color: #4b3223;
      background: #17110c;
      color: #f9dfb0;
    }

    body:not(.auth-dashboard) .status.ok {
      border-color: rgba(81, 192, 139, 0.5);
      background: rgba(23, 55, 40, 0.52);
      color: #d9f8e8;
    }

    body:not(.auth-dashboard) .status.error {
      border-color: rgba(230, 95, 124, 0.55);
      background: rgba(66, 21, 31, 0.58);
      color: #ffd8df;
    }

    /* Authenticated view: darker dashboard variant */
    body.auth-dashboard {
      --bg: #000000;
      --panel: #070707;
      --line: #1b1b1b;
      --text: #f2f2f2;
      --muted: #b1b1b1;
      --accent: #cda06a;
      background: var(--bg);
      color: var(--text);
    }

    body.auth-dashboard h1,
    body.auth-dashboard h2,
    body.auth-dashboard h3 {
      color: var(--text);
    }

    body.auth-dashboard .admin-sidebar {
      background: #050505;
      border-color: var(--line);
      box-shadow: 0 10px 26px rgba(0, 0, 0, 0.58);
    }

    body.auth-dashboard .admin-brand {
      color: #f0f5fc;
    }

    body.auth-dashboard .admin-sub {
      color: #9f9f9f;
    }

    body.auth-dashboard .menu-link {
      color: #d3d3d3;
    }

    body.auth-dashboard .menu-link:hover {
      color: #ededed;
      background: transparent;
      border-color: transparent;
    }

    body.auth-dashboard .menu-link.active {
      color: #ffffff;
      background: transparent;
      border-color: transparent;
      text-decoration-color: #ffffff;
    }

    body.auth-dashboard .sidebar-toggle-icon {
      border-color: #313131;
      background: #0f0f0f;
      color: #ffffff;
    }

    body.auth-dashboard .sidebar-toggle-icon:hover {
      border-color: #4a4a4a;
      background: #1a1a1a;
      color: #ffffff;
    }

    body.auth-dashboard .sidebar-separator {
      background: #1b1b1b;
    }

    body.auth-dashboard .sidebar-logout {
      color: #d4dfef;
      border-color: #313131;
      background: #0f0f0f;
    }

    body.auth-dashboard .sidebar-logout:hover {
      color: #edf4fc;
      border-color: #4a4a4a;
      background: #1a1a1a;
      text-decoration: none;
    }

    body.auth-dashboard .sidebar-backdrop {
      background: rgba(0, 0, 0, 0.72);
    }

    body.auth-dashboard .admin-header {
      border-bottom-color: #1b1b1b;
    }

    body.auth-dashboard .admin-main {
      background: #000000;
    }

    body.auth-dashboard input,
    body.auth-dashboard textarea {
      border-color: #2a2a2a;
      background: #080808;
      color: #f2f2f2;
    }

    body.auth-dashboard input:focus,
    body.auth-dashboard textarea:focus {
      outline: 1px solid rgba(205, 160, 106, 0.35);
      outline-offset: 0;
      border-color: #cda06a;
    }

    body.auth-dashboard label {
      color: #b9b9b9;
    }

    body.auth-dashboard button:not(.menu-link):not(.sidebar-toggle-icon),
    body.auth-dashboard .logout-link {
      border-color: #313131;
      background: #0f0f0f;
      color: #e2e2e2;
    }

    body.auth-dashboard button:not(.menu-link):not(.sidebar-toggle-icon):hover,
    body.auth-dashboard .logout-link:hover {
      border-color: #4a4a4a;
      background: #1a1a1a;
      color: #f2f2f2;
    }

    body.auth-dashboard .danger {
      border-color: rgba(236, 101, 127, 0.58);
      color: #ff9eb0;
    }

    body.auth-dashboard .status {
      border-color: #2a2a2a;
      background: #0c0c0c;
      color: #e8e8e8;
    }

    body.auth-dashboard .status.ok {
      border-color: rgba(92, 196, 144, 0.45);
      background: rgba(14, 44, 34, 0.58);
      color: #d7f7e8;
    }

    body.auth-dashboard .status.error {
      border-color: rgba(235, 108, 133, 0.55);
      background: rgba(66, 20, 33, 0.58);
      color: #ffd7df;
    }

    body.auth-dashboard .chart-card,
    body.auth-dashboard .globe-card,
    body.auth-dashboard .pages-card,
    body.auth-dashboard .access-card,
    body.auth-dashboard .assistant-log {
      border-color: var(--line);
      background: var(--panel);
    }

    body.auth-dashboard .admin-main #section-analytics.panel {
      background: transparent;
      border: 0;
      border-radius: 0;
      padding: 0;
    }

    body.auth-dashboard .admin-main #section-analytics .stats-summary-card,
    body.auth-dashboard .admin-main #section-analytics .chart-card,
    body.auth-dashboard .admin-main #section-analytics .globe-card,
    body.auth-dashboard .admin-main #section-analytics .pages-card,
    body.auth-dashboard .admin-main #section-analytics .access-card {
      border: 0;
      background: transparent;
      border-radius: 0;
      box-shadow: none;
      padding: 0;
    }

    body.auth-dashboard .stat-card,
    body.auth-dashboard .resource-mini-card,
    body.auth-dashboard .response-mini-card,
    body.auth-dashboard .country-row,
    body.auth-dashboard .member-item {
      border-color: #212121;
      background: #0b0b0b;
    }

    body.auth-dashboard .admin-main #section-analytics .globe-card {
      border-left: 1px solid var(--line);
      margin-left: 0.16rem;
      padding-left: 0.82rem;
    }

    body.auth-dashboard .admin-main #section-analytics .pages-card,
    body.auth-dashboard .admin-main #section-analytics .access-card {
      border-top: 1px solid var(--line);
      margin-top: 0.14rem;
      padding-top: 0.84rem;
    }

    body.auth-dashboard .stat-label,
    body.auth-dashboard .resource-mini-label,
    body.auth-dashboard .response-mini-host,
    body.auth-dashboard .chart-meta,
    body.auth-dashboard .hint,
    body.auth-dashboard .meta-line {
      color: #a9a9a9;
    }

    body.auth-dashboard .challenge-link {
      color: #e2c07a;
    }

    body.auth-dashboard .challenge-link:hover {
      color: #f0d398;
    }

    body.auth-dashboard .challenge-endpoint {
      color: #b3b3b3;
    }

    body.auth-dashboard .stat-value,
    body.auth-dashboard .resource-mini-value,
    body.auth-dashboard .response-mini-value,
    body.auth-dashboard .country-row strong,
    body.auth-dashboard .assistant-text {
      color: #f2f2f2;
    }

    body.auth-dashboard .country-row span {
      color: #e0e0e0;
    }

    body.auth-dashboard .response-mini-meta {
      color: #9d9d9d;
    }

    body.auth-dashboard .response-mini-value.down {
      color: #ff9eb0;
    }

    body.auth-dashboard .chart-title {
      color: #ebebeb;
    }

    body.auth-dashboard .range-btn {
      border-color: #313131;
      background: #0d0d0d;
      color: #d6d6d6;
    }

    body.auth-dashboard .range-btn:hover {
      border-color: #4a4a4a;
      background: #171717;
      color: #f0f0f0;
    }

    body.auth-dashboard .range-btn.active {
      border-color: #cda06a;
      background: #1f1a14;
      color: #f0d5ae;
    }

    body.auth-dashboard .pages-table th,
    body.auth-dashboard .pages-table td,
    body.auth-dashboard .access-table th,
    body.auth-dashboard .access-table td {
      border-bottom-color: #1f1f1f;
    }

    body.auth-dashboard .pages-table th,
    body.auth-dashboard .access-table th {
      color: #c9c9c9;
      background: #0f0f0f;
    }

    body.auth-dashboard .pages-table tbody tr td,
    body.auth-dashboard .access-table tbody tr td {
      background: #080808;
      color: #e3e3e3;
    }

    body.auth-dashboard .access-table-wrap {
      border-color: #1f1f1f;
      background: #050505;
    }

    body.auth-dashboard .access-table tbody tr:hover td,
    body.auth-dashboard .pages-table tbody tr:hover td {
      background: #111111;
    }

    body.auth-dashboard .assistant-empty,
    body.auth-dashboard .assistant-status {
      color: #b0b0b0;
    }

    body.auth-dashboard .assistant-status.error {
      color: #ff9fb3;
    }

    body.auth-dashboard .assistant-row {
      border-color: #212121;
      background: #090909;
    }

    body.auth-dashboard .assistant-row.user {
      background: #121212;
      border-color: #303030;
    }

    body.auth-dashboard .assistant-row.assistant {
      background: #080808;
      border-color: #1e1e1e;
    }

    body.auth-dashboard .assistant-author {
      color: #c3c3c3;
    }

    body.auth-dashboard .chart-tooltip,
    body.auth-dashboard .globe-tooltip {
      border-color: #2e2e2e;
      background: rgba(8, 8, 8, 0.97);
      color: #ececec;
    }

    body.auth-dashboard .chart-tooltip strong,
    body.auth-dashboard .globe-tooltip strong {
      color: #e6c798;
    }

    @media (max-width: 980px) {
      .row {
        grid-template-columns: 1fr;
      }
      .topbar {
        align-items: flex-start;
        flex-direction: column;
      }
      .stats-grid {
        grid-template-columns: 1fr 1fr;
      }
      .stats-section-grid {
        grid-template-columns: 1fr;
      }
      body.auth-dashboard .admin-main #section-analytics .globe-card {
        border-left: 0;
        margin-left: 0;
        padding-left: 0;
        border-top: 1px solid var(--line);
        margin-top: 0.14rem;
        padding-top: 0.84rem;
      }
      .resources-strip,
      .response-cards-block {
        grid-template-columns: 1fr;
      }
      .globe-wrap {
        height: 230px;
      }
      .admin-sidebar {
        width: min(86vw, 320px);
        border-radius: 0;
        border-right: 1px solid var(--line);
        box-shadow: 0 14px 28px rgba(19, 35, 60, 0.2);
      }
      .admin-main {
        margin-left: 0;
        width: 100%;
        min-height: 100vh;
        padding: 0.82rem 0.78rem 1.2rem;
      }
      .admin-main .panel {
        transform: none;
        max-width: 100%;
      }
      .sidebar-menu {
        grid-template-columns: 1fr;
      }
      .sidebar-backdrop {
        display: block;
      }
      body.auth-dashboard:not(.sidebar-collapsed) .sidebar-backdrop {
        opacity: 1;
        pointer-events: auto;
      }
      .chart-wrap canvas {
        height: 224px !important;
      }
      .pages-table,
      .access-table {
        font-size: 0.78rem;
      }
      .access-table th,
      .access-table td {
        padding: 0.3rem 0.34rem;
      }
      .assistant-log {
        min-height: 260px;
        max-height: 50vh;
      }
      .assistant-row {
        max-width: 100%;
      }
    }

    @media (max-width: 560px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      .chart-toolbar {
        gap: 0.4rem;
      }
    }
  </style>
</head>
<body class="<?= $isAuthenticated ? 'auth-dashboard' : '' ?>">
  <main class="wrap">
    <?php if (!$isAuthenticated): ?>
      <section class="panel">
        <h1>Aegis Lab Dashboard Login</h1>
        <?php if ($flashMessage !== ''): ?>
          <div class="status <?= $flashType === 'error' ? 'error' : 'ok' ?>"><?= zs_escape($flashMessage) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <label>
            Password
            <input type="password" name="password" required>
          </label>
          <div class="actions">
            <button type="submit">Sign In</button>
          </div>
        </form>
      </section>
    <?php else: ?>
      <div class="admin-shell">
        <aside class="admin-sidebar" id="adminSidebar">
          <div class="sidebar-head">
            <div class="sidebar-brand-block">
              <h2 class="admin-brand">Aegis Lab Dashboard</h2>
              <p class="admin-sub">Dashboard sections</p>
            </div>
            <button
              id="sidebarToggleBtn"
              class="sidebar-toggle-icon"
              type="button"
              aria-controls="adminSidebar"
              aria-expanded="true"
              aria-label="Hide menu"
              title="Hide menu"
            ></button>
          </div>
          <nav class="sidebar-menu" id="adminSidebarMenu">
            <button class="menu-link active" type="button" data-section="section-analytics">Aegis Analytics</button>
            <button class="menu-link" type="button" data-section="section-ctf-analytics">CTF Analytics</button>
            <div class="sidebar-separator" aria-hidden="true"></div>
            <button class="menu-link" type="button" data-section="section-content">Website Content</button>
            <button class="menu-link" type="button" data-section="section-founder">Founder Profile</button>
            <button class="menu-link" type="button" data-section="section-team">Team Members</button>
            <button class="menu-link" type="button" data-section="section-add-member">Add Member</button>
            <div class="sidebar-separator" aria-hidden="true"></div>
            <button class="menu-link sidebar-assistant-link" type="button" data-section="section-assistant">Assistant Chat</button>
          </nav>
          <a class="logout-link sidebar-logout" href="<?= zs_escape(zs_dashboard_url()) ?>?logout=1">Logout</a>
        </aside>
        <button id="sidebarBackdrop" class="sidebar-backdrop" type="button" aria-label="Close menu"></button>

        <div class="admin-main">
          <section id="section-analytics" class="panel admin-section active">
            <div class="admin-header">
              <h1>Aegis Lab Dashboard</h1>
              <?php if ($flashMessage !== ''): ?>
                <div class="status <?= $flashType === 'error' ? 'error' : 'ok' ?>"><?= zs_escape($flashMessage) ?></div>
              <?php endif; ?>
            </div>
            <h2>Aegis Analytics</h2>
            <div class="stats-summary-card">
              <div class="stats-grid">
                <article class="stat-card">
                  <p class="stat-label">Live Now</p>
                  <p class="stat-value" id="stat-active-now"><?= (int)($dashboardStats['totals']['active_now'] ?? 0) ?></p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Unique (7 Days)</p>
                  <p class="stat-value" id="stat-unique-7d"><?= (int)($dashboardStats['totals']['unique_7d'] ?? 0) ?></p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Unique (30 Days)</p>
                  <p class="stat-value" id="stat-unique-30d"><?= (int)($dashboardStats['totals']['unique_30d'] ?? 0) ?></p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Unique (All)</p>
                  <p class="stat-value" id="stat-unique-all"><?= (int)($dashboardStats['totals']['unique_all'] ?? 0) ?></p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Page Views (7D)</p>
                  <p class="stat-value" id="stat-visits-7d"><?= (int)($dashboardStats['totals']['visits_7d'] ?? 0) ?></p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Page Views (30D)</p>
                  <p class="stat-value" id="stat-visits-30d"><?= (int)($dashboardStats['totals']['visits_30d'] ?? 0) ?></p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Page Views (All)</p>
                  <p class="stat-value" id="stat-visits-all"><?= (int)($dashboardStats['totals']['visits_all'] ?? 0) ?></p>
                </article>
              </div>
            </div>
            <div class="stats-section-grid">
              <section class="chart-card">
                <div class="chart-toolbar">
                  <p class="chart-title">Traffic Graph</p>
                  <div class="range-switch" id="chartRangeButtons">
                    <button class="range-btn" type="button" data-range="1h">1H</button>
                    <button class="range-btn" type="button" data-range="24h">24H</button>
                    <button class="range-btn" type="button" data-range="7d">7D</button>
                    <button class="range-btn active" type="button" data-range="30d">30D</button>
                    <button class="range-btn" type="button" data-range="reset">Reset Zoom</button>
                  </div>
                </div>
                <p class="chart-meta" id="chartMeta">Use mouse wheel to zoom and drag to pan in the selected range.</p>
                <div class="chart-wrap">
                  <canvas id="visitsChart" aria-label="Visitor traffic chart"></canvas>
                  <div id="chartTooltip" class="chart-tooltip" hidden></div>
                </div>
                <div class="resources-strip">
                  <article class="resource-mini-card">
                    <p class="resource-mini-label">CPU</p>
                    <p class="resource-mini-value" id="resource-cpu">N/A</p>
                  </article>
                  <article class="resource-mini-card">
                    <p class="resource-mini-label">RAM</p>
                    <p class="resource-mini-value" id="resource-ram">N/A</p>
                  </article>
                  <article class="resource-mini-card">
                    <p class="resource-mini-label">Storage</p>
                    <p class="resource-mini-value" id="resource-storage">N/A</p>
                  </article>
                </div>
                <div class="response-cards-block">
                  <article class="response-mini-card">
                    <p class="response-mini-host">aegislab.ro</p>
                    <p class="response-mini-value" id="response-main-value">Checking...</p>
                    <p class="response-mini-meta" id="response-main-meta"></p>
                  </article>
                  <article class="response-mini-card">
                    <p class="response-mini-host">ctf.aegislab.ro</p>
                    <p class="response-mini-value" id="response-ctf-value">Checking...</p>
                    <p class="response-mini-meta" id="response-ctf-meta"></p>
                  </article>
                </div>
              </section>
              <section class="globe-card">
                <p class="chart-title">Countries (Last 30 Days)</p>
                <div class="globe-canvas-wrap">
                  <canvas id="globeViz" class="globe-wrap" aria-label="3D globe view of countries"></canvas>
                  <div id="globeTooltip" class="globe-tooltip" hidden></div>
                </div>
                <div id="countryList" class="country-list"></div>
              </section>
              <section class="pages-card">
                <p class="chart-title">Most Accessed Pages (Last 30 Days)</p>
                <table class="pages-table" aria-label="Top pages table">
                  <thead>
                    <tr>
                      <th>Path</th>
                      <th>Views</th>
                      <th>Unique</th>
                    </tr>
                  </thead>
                  <tbody id="topPagesBody"></tbody>
                </table>
                <p id="statsGeneratedAt" class="meta-line"></p>
              </section>
              <section class="access-card">
                <p class="chart-title">Recent Connections: IP + Device (Last 30 Days)</p>
                <div class="access-table-wrap">
                  <table class="access-table" aria-label="Recent IP and device access table">
                    <thead>
                      <tr>
                        <th>Time</th>
                        <th>IP</th>
                        <th>Country</th>
                        <th>Device</th>
                        <th>Path</th>
                      </tr>
                    </thead>
                    <tbody id="recentAccessBody"></tbody>
                  </table>
                </div>
              </section>
            </div>
          </section>

          <section id="section-ctf-analytics" class="panel admin-section">
            <h2>CTF Traffic &amp; Challenge Analytics</h2>
            <p class="hint">Traffic overview for <strong>ctf.aegislab.ro</strong>, including challenge-focused activity and endpoint usage.</p>
            <div class="stats-summary-card">
              <div class="stats-grid">
                <article class="stat-card">
                  <p class="stat-label">Live Now</p>
                  <p class="stat-value" id="ctf-stat-active-now">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Unique (7 Days)</p>
                  <p class="stat-value" id="ctf-stat-unique-7d">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Unique (30 Days)</p>
                  <p class="stat-value" id="ctf-stat-unique-30d">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Unique (All)</p>
                  <p class="stat-value" id="ctf-stat-unique-all">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Traffic (7 Days)</p>
                  <p class="stat-value" id="ctf-stat-visits-7d">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Traffic (30 Days)</p>
                  <p class="stat-value" id="ctf-stat-visits-30d">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Traffic (All)</p>
                  <p class="stat-value" id="ctf-stat-visits-all">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Challenge Hits (30D)</p>
                  <p class="stat-value" id="ctf-stat-challenge-hits-30d">0</p>
                </article>
                <article class="stat-card">
                  <p class="stat-label">Challenge Unique (30D)</p>
                  <p class="stat-value" id="ctf-stat-challenge-unique-30d">0</p>
                </article>
              </div>
            </div>

            <div class="stats-section-grid ctf-layout">
              <section class="chart-card ctf-chart-card">
                <div class="chart-toolbar">
                  <p class="chart-title">Traffic Graph</p>
                  <div class="range-switch" id="ctfChartRangeButtons">
                    <button class="range-btn" type="button" data-range="1h">1H</button>
                    <button class="range-btn" type="button" data-range="24h">24H</button>
                    <button class="range-btn" type="button" data-range="7d">7D</button>
                    <button class="range-btn active" type="button" data-range="30d">30D</button>
                  </div>
                </div>
                <p class="chart-meta" id="ctfChartMeta">CTF traffic over time for the selected range.</p>
                <div class="chart-wrap">
                  <canvas id="ctfVisitsChart" aria-label="CTF traffic chart"></canvas>
                  <div id="ctfChartTooltip" class="chart-tooltip" hidden></div>
                </div>
              </section>

              <section class="pages-card">
                <p class="chart-title">Top CTF Routes (Last 30 Days)</p>
                <table class="pages-table" aria-label="Top CTF routes table">
                  <thead>
                    <tr>
                      <th>Route</th>
                      <th>Views</th>
                      <th>Unique</th>
                    </tr>
                  </thead>
                  <tbody id="ctfTopPathsBody"></tbody>
                </table>
              </section>

              <section class="pages-card">
                <p class="chart-title">Challenge Activity (Last 30 Days)</p>
                <table class="pages-table ctf-challenge-table" aria-label="CTF challenge activity table">
                  <thead>
                    <tr>
                      <th>Challenge / Endpoint</th>
                      <th>Views</th>
                      <th>Unique</th>
                      <th>Last Seen</th>
                    </tr>
                  </thead>
                  <tbody id="ctfChallengeActivityBody"></tbody>
                </table>
              </section>

              <section class="access-card ctf-access-card">
                <p class="chart-title">Recent CTF Access (Last 30 Days)</p>
                <div class="access-table-wrap">
                  <table class="access-table" aria-label="Recent CTF access table">
                    <thead>
                      <tr>
                        <th>Time</th>
                        <th>IP</th>
                        <th>Country</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Path</th>
                        <th>Device</th>
                      </tr>
                    </thead>
                    <tbody id="ctfRecentAccessBody"></tbody>
                  </table>
                </div>
                <p id="ctfStatsGeneratedAt" class="meta-line"></p>
              </section>
            </div>
          </section>

          <section id="section-content" class="panel admin-section">
        <h2>Website Content</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= zs_escape($csrfToken) ?>">
          <input type="hidden" name="action" value="save_content">
          <label>
            Hero Subtitle (Main Page)
            <textarea name="hero_subtitle" required><?= zs_escape($siteContent['hero_subtitle']) ?></textarea>
          </label>
          <label>
            Home Intro (Main Page)
            <textarea name="home_intro" required><?= zs_escape($siteContent['home_intro']) ?></textarea>
          </label>
          <label>
            About Intro
            <textarea name="about_intro" required><?= zs_escape($siteContent['about_intro']) ?></textarea>
          </label>
          <label>
            Members Page Intro
            <textarea name="members_intro" required><?= zs_escape($siteContent['members_intro']) ?></textarea>
          </label>
          <div class="row">
            <label>
              Contact Email
              <input type="text" name="contact_email" value="<?= zs_escape($siteContent['contact_email']) ?>" required>
            </label>
            <label>
              Team CTF URL
              <input type="text" name="ctf_url" value="<?= zs_escape($siteContent['ctf_url']) ?>" required>
            </label>
          </div>
          <label>
            Team CTF Label Text
            <input type="text" name="ctf_label" value="<?= zs_escape($siteContent['ctf_label']) ?>" required>
          </label>
          <div class="actions">
            <button type="submit">Save Website Content</button>
          </div>
        </form>
      </section>

      <section id="section-founder" class="panel admin-section">
        <h2>Founder Profile</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= zs_escape($csrfToken) ?>">
          <input type="hidden" name="action" value="save_founder">
          <div class="row">
            <label>
              Role
              <input type="text" name="founder_role" value="<?= zs_escape($founder['role']) ?>" required>
            </label>
            <label>
              Name
              <input type="text" name="founder_name" value="<?= zs_escape($founder['name']) ?>" required>
            </label>
          </div>
          <div class="row">
            <label>
              Handle
              <input type="text" name="founder_handle" value="<?= zs_escape($founder['handle']) ?>">
            </label>
            <label>
              Initials
              <input type="text" name="founder_initials" value="<?= zs_escape($founder['initials']) ?>">
            </label>
          </div>
          <div class="row">
            <label>
              Discord URL
              <input type="text" name="founder_discord" value="<?= zs_escape($founder['discord']) ?>" placeholder="https://discord.com/users/...">
            </label>
            <label>
              GitHub URL
              <input type="text" name="founder_github" value="<?= zs_escape($founder['github']) ?>" placeholder="https://github.com/...">
            </label>
          </div>
          <div class="row">
            <label>
              LinkedIn URL
              <input type="text" name="founder_linkedin" value="<?= zs_escape($founder['linkedin']) ?>" placeholder="https://linkedin.com/in/...">
            </label>
            <label>
              Instagram URL
              <input type="text" name="founder_instagram" value="<?= zs_escape($founder['instagram']) ?>" placeholder="https://instagram.com/...">
            </label>
          </div>
          <label>
            Avatar URL (example: /Aegis.png)
            <input type="text" name="founder_avatar" value="<?= zs_escape($founder['avatar']) ?>">
          </label>
          <label>
            Bio
            <textarea name="founder_bio"><?= zs_escape($founder['bio']) ?></textarea>
          </label>
          <div class="actions">
            <button type="submit">Save Founder</button>
          </div>
        </form>
      </section>

      <section id="section-team" class="panel admin-section">
        <h2>Team Members</h2>
        <p class="hint">Use one card per member. You can update or delete each member.</p>
        <?php foreach ($team as $idx => $member): ?>
          <form method="post" class="member-item">
            <input type="hidden" name="csrf" value="<?= zs_escape($csrfToken) ?>">
            <input type="hidden" name="member_index" value="<?= (int)$idx ?>">
            <div class="row">
              <label>
                Role
                <input type="text" name="role" value="<?= zs_escape($member['role']) ?>" required>
              </label>
              <label>
                Name
                <input type="text" name="name" value="<?= zs_escape($member['name']) ?>" required>
              </label>
            </div>
            <div class="row">
              <label>
                Handle
                <input type="text" name="handle" value="<?= zs_escape($member['handle']) ?>">
              </label>
              <label>
                Initials
                <input type="text" name="initials" value="<?= zs_escape($member['initials']) ?>">
              </label>
            </div>
            <div class="row">
              <label>
                Discord URL
                <input type="text" name="discord" value="<?= zs_escape($member['discord']) ?>" placeholder="https://discord.com/users/...">
              </label>
              <label>
                GitHub URL
                <input type="text" name="github" value="<?= zs_escape($member['github']) ?>" placeholder="https://github.com/...">
              </label>
            </div>
            <div class="row">
              <label>
                LinkedIn URL
                <input type="text" name="linkedin" value="<?= zs_escape($member['linkedin']) ?>" placeholder="https://linkedin.com/in/...">
              </label>
              <label>
                Instagram URL
                <input type="text" name="instagram" value="<?= zs_escape($member['instagram']) ?>" placeholder="https://instagram.com/...">
              </label>
            </div>
            <label>
              Avatar URL (leave empty for initials avatar)
              <input type="text" name="avatar" value="<?= zs_escape($member['avatar']) ?>">
            </label>
            <label>
              Bio
              <textarea name="bio"><?= zs_escape($member['bio']) ?></textarea>
            </label>
            <div class="actions">
              <button type="submit" name="action" value="update_member">Save Member</button>
              <button class="danger" type="submit" name="action" value="delete_member" onclick="return confirm('Delete this member?');">Delete Member</button>
            </div>
          </form>
        <?php endforeach; ?>
      </section>

      <section id="section-add-member" class="panel admin-section">
        <h2>Add New Member</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= zs_escape($csrfToken) ?>">
          <input type="hidden" name="action" value="add_member">
          <div class="row">
            <label>
              Role
              <input type="text" name="new_role" placeholder="Role" required>
            </label>
            <label>
              Name
              <input type="text" name="new_name" placeholder="Full name" required>
            </label>
          </div>
          <div class="row">
            <label>
              Handle
              <input type="text" name="new_handle" placeholder="@username">
            </label>
            <label>
              Initials
              <input type="text" name="new_initials" placeholder="AB">
            </label>
          </div>
          <div class="row">
            <label>
              Discord URL (optional)
              <input type="text" name="new_discord" placeholder="https://discord.com/users/...">
            </label>
            <label>
              GitHub URL (optional)
              <input type="text" name="new_github" placeholder="https://github.com/...">
            </label>
          </div>
          <div class="row">
            <label>
              LinkedIn URL (optional)
              <input type="text" name="new_linkedin" placeholder="https://linkedin.com/in/...">
            </label>
            <label>
              Instagram URL (optional)
              <input type="text" name="new_instagram" placeholder="https://instagram.com/...">
            </label>
          </div>
          <label>
            Avatar URL (optional)
            <input type="text" name="new_avatar" placeholder="/assets/members/name.jpg">
          </label>
          <label>
            Bio (optional)
            <textarea name="new_bio" placeholder="Short description"></textarea>
          </label>
          <div class="actions">
            <button type="submit">Add Member</button>
          </div>
        </form>
      </section>

      <section id="section-assistant" class="panel admin-section">
        <h2>Assistant Chat</h2>
        <p class="hint">Ask for quick copy drafts, event text, technical explanations, and website updates.</p>
        <div class="assistant-layout">
          <div id="assistantChatLog" class="assistant-log" aria-live="polite"></div>
          <form id="assistantChatForm" class="assistant-form" autocomplete="off">
            <label for="assistantInput">Message</label>
            <textarea id="assistantInput" class="assistant-input" placeholder="Write a message for the assistant..." required></textarea>
            <div class="assistant-actions">
              <button type="submit" id="assistantSendBtn">Send</button>
              <button type="button" id="assistantClearBtn">Clear Chat</button>
            </div>
            <p id="assistantStatus" class="assistant-status"></p>
          </form>
        </div>
      </section>
        </div>
      </div>
    <?php endif; ?>
  </main>
  <?php if ($isAuthenticated): ?>
    <script src="/assets/js/d3.v7.min.js"></script>
    <script>
      (() => {
        const initialStats = <?= $dashboardStatsJson ?>;
        const statsEndpoint = "<?= zs_escape(zs_dashboard_url()) ?>?stats=1";
        const assistantChatEndpoint = "<?= zs_escape(zs_dashboard_url()) ?>?assistant_chat=1";
        const assistantCsrfToken = "<?= zs_escape($csrfToken) ?>";
        const globeGeoJsonEndpoint = "/assets/data/world-countries-ne110.geo.json";
        const refreshIntervalMs = 10000;
        let latestStats = initialStats;
        let chartResizeTimer = null;
        let refreshTimerId = null;

        const chartState = {
          range: "30d",
          zoomStart: 0,
          zoomEnd: -1,
          totalPoints: 0,
          viewport: null,
          pointerInside: false,
          pointerX: 0,
          pointerY: 0,
          dragActive: false,
          dragStartX: 0,
          dragStartRangeStart: 0,
        };

        const ctfChartState = {
          range: "30d",
          pointerInside: false,
          pointerX: 0,
          pointerY: 0,
        };

        const globeState = {
          canvas: null,
          ctx: null,
          width: 0,
          height: 0,
          rotationDeg: 0,
          tiltDeg: -8,
          zoom: 1,
          animationStarted: false,
          loadingPromise: null,
          countriesLoaded: false,
          countries: [],
          namesByCode: new Map(),
          highlightedByCode: new Map(),
          projection: null,
          pointerInside: false,
          pointerX: 0,
          pointerY: 0,
          hoveredCode: "",
          hoveredName: "",
          hoveredCount: 0,
          dragActive: false,
          dragStartX: 0,
          dragStartY: 0,
          dragStartRotation: 0,
          dragStartTilt: -8,
        };

        const assistantState = {
          messages: [],
          sending: false,
        };

        const sidebarState = {
          collapsed: false,
          initialized: false,
          storageKey: "aegis_dashboard_sidebar_collapsed_v1",
        };

        function clamp(value, min, max) {
          if (value < min) {
            return min;
          }
          if (value > max) {
            return max;
          }
          return value;
        }

        function setText(id, value) {
          const el = document.getElementById(id);
          if (!el) {
            return;
          }
          el.textContent = String(value);
        }

        function isMobileViewport() {
          return window.matchMedia("(max-width: 980px)").matches;
        }

        function readStoredSidebarPreference() {
          try {
            const raw = window.localStorage.getItem(sidebarState.storageKey);
            if (raw === "1") {
              return true;
            }
            if (raw === "0") {
              return false;
            }
          } catch (_) {
            // ignore storage errors
          }
          return null;
        }

        function storeSidebarPreference(collapsed) {
          try {
            window.localStorage.setItem(sidebarState.storageKey, collapsed ? "1" : "0");
          } catch (_) {
            // ignore storage errors
          }
        }

        function applySidebarCollapsed(collapsed, persist = true) {
          sidebarState.collapsed = Boolean(collapsed);
          document.body.classList.toggle("sidebar-collapsed", sidebarState.collapsed);

          const btn = document.getElementById("sidebarToggleBtn");
          if (btn instanceof HTMLButtonElement) {
            const actionLabel = sidebarState.collapsed ? "Show menu" : "Hide menu";
            btn.setAttribute("aria-label", actionLabel);
            btn.setAttribute("title", actionLabel);
            btn.classList.toggle("is-collapsed", sidebarState.collapsed);
            btn.setAttribute("aria-expanded", sidebarState.collapsed ? "false" : "true");
          }

          if (persist && sidebarState.initialized) {
            storeSidebarPreference(sidebarState.collapsed);
          }
        }

        function toggleSidebarCollapsed() {
          applySidebarCollapsed(!sidebarState.collapsed, true);
        }

        function initSidebarToggle() {
          const btn = document.getElementById("sidebarToggleBtn");
          const backdrop = document.getElementById("sidebarBackdrop");

          if (btn instanceof HTMLButtonElement) {
            btn.addEventListener("click", toggleSidebarCollapsed);
          }
          if (backdrop instanceof HTMLButtonElement) {
            backdrop.addEventListener("click", () => {
              applySidebarCollapsed(true, true);
            });
          }

          const stored = readStoredSidebarPreference();
          const initialCollapsed = stored !== null ? stored : isMobileViewport();
          applySidebarCollapsed(initialCollapsed, false);
          sidebarState.initialized = true;
        }

        function setActiveAdminSection(sectionId) {
          const target = String(sectionId || "section-analytics");
          const sections = document.querySelectorAll(".admin-section");
          for (const section of sections) {
            if (!(section instanceof HTMLElement)) {
              continue;
            }
            section.classList.toggle("active", section.id === target);
          }

          const menuButtons = document.querySelectorAll(".admin-sidebar .menu-link[data-section]");
          for (const button of menuButtons) {
            if (!(button instanceof HTMLButtonElement)) {
              continue;
            }
            button.classList.toggle("active", String(button.dataset.section || "") === target);
          }

          hideChartTooltip();
          hideCtfChartTooltip();
          hideGlobeTooltip();

          if (target === "section-analytics" || target === "section-ctf-analytics") {
            window.requestAnimationFrame(() => {
              if (target === "section-analytics") {
                resizeGlobe();
              }
              renderDashboard(latestStats);
            });
            return;
          }

          if (target === "section-assistant") {
            window.setTimeout(() => {
              const input = document.getElementById("assistantInput");
              if (input instanceof HTMLTextAreaElement) {
                input.focus();
              }
            }, 40);
          }
        }

        function initAdminSidebarMenu() {
          const buttons = document.querySelectorAll(".admin-sidebar .menu-link[data-section]");
          for (const button of buttons) {
            if (!(button instanceof HTMLButtonElement)) {
              continue;
            }
            button.addEventListener("click", () => {
              setActiveAdminSection(String(button.dataset.section || "section-analytics"));
              if (isMobileViewport()) {
                applySidebarCollapsed(true, false);
              }
            });
          }
        }

        function renderTotals(stats) {
          const totals = (stats && stats.totals) ? stats.totals : {};
          setText("stat-active-now", totals.active_now ?? 0);
          setText("stat-unique-7d", totals.unique_7d ?? 0);
          setText("stat-unique-30d", totals.unique_30d ?? 0);
          setText("stat-unique-all", totals.unique_all ?? 0);
          setText("stat-visits-7d", totals.visits_7d ?? 0);
          setText("stat-visits-30d", totals.visits_30d ?? 0);
          setText("stat-visits-all", totals.visits_all ?? 0);
        }

        function getCtfAnalytics(stats) {
          if (!stats || typeof stats !== "object") {
            return {};
          }
          const payload = stats.ctf_analytics;
          return payload && typeof payload === "object" ? payload : {};
        }

        function renderCtfTotals(stats) {
          const payload = getCtfAnalytics(stats);
          const totals = payload && typeof payload.totals === "object" ? payload.totals : {};

          setText("ctf-stat-active-now", totals.active_now ?? 0);
          setText("ctf-stat-unique-7d", totals.unique_7d ?? 0);
          setText("ctf-stat-unique-30d", totals.unique_30d ?? 0);
          setText("ctf-stat-unique-all", totals.unique_all ?? 0);
          setText("ctf-stat-visits-7d", totals.visits_7d ?? 0);
          setText("ctf-stat-visits-30d", totals.visits_30d ?? 0);
          setText("ctf-stat-visits-all", totals.visits_all ?? 0);
          setText("ctf-stat-challenge-hits-30d", totals.challenge_hits_30d ?? 0);
          setText("ctf-stat-challenge-unique-30d", totals.challenge_unique_30d ?? 0);
        }

        function getSeriesForRange(stats, range) {
          const safeRange = typeof range === "string" ? range : "30d";
          const series = (stats && stats.series) ? stats.series : {};
          let source = [];
          let rangeLabel = "Last 30 Days";

          if (safeRange === "1h") {
            source = Array.isArray(series.minutes_60) ? series.minutes_60 : [];
            rangeLabel = "Last 60 Minutes";
          } else if (safeRange === "24h") {
            source = Array.isArray(series.hours_24) ? series.hours_24 : [];
            rangeLabel = "Last 24 Hours";
          } else if (safeRange === "7d") {
            source = Array.isArray(series.days_7) ? series.days_7 : [];
            rangeLabel = "Last 7 Days";
          } else {
            source = Array.isArray(series.days_30) ? series.days_30 : [];
            rangeLabel = "Last 30 Days";
          }

          return {
            range: safeRange,
            rangeLabel,
            labels: source.map((entry) => String(entry.label ?? "")),
            keys: source.map((entry) => String(entry.key ?? "")),
            data: source.map((entry) => Number(entry.count ?? 0)),
          };
        }

        function getCtfSeriesForRange(stats, range) {
          const safeRange = typeof range === "string" ? range : "30d";
          const payload = getCtfAnalytics(stats);
          const series = (payload && typeof payload.series === "object" && payload.series) ? payload.series : {};
          let source = [];
          let rangeLabel = "Last 30 Days";

          if (safeRange === "1h") {
            source = Array.isArray(series.minutes_60) ? series.minutes_60 : [];
            rangeLabel = "Last 60 Minutes";
          } else if (safeRange === "24h") {
            source = Array.isArray(series.hours_24) ? series.hours_24 : [];
            rangeLabel = "Last 24 Hours";
          } else if (safeRange === "7d") {
            source = Array.isArray(series.days_7) ? series.days_7 : [];
            rangeLabel = "Last 7 Days";
          } else {
            source = Array.isArray(series.days_30) ? series.days_30 : [];
            rangeLabel = "Last 30 Days";
          }

          return {
            range: safeRange,
            rangeLabel,
            labels: source.map((entry) => String(entry.label ?? "")),
            keys: source.map((entry) => String(entry.key ?? "")),
            data: source.map((entry) => Number(entry.count ?? 0)),
          };
        }

        function setChartMeta(text) {
          const el = document.getElementById("chartMeta");
          if (!el) {
            return;
          }
          el.textContent = text;
        }

        function setCtfChartMeta(text) {
          const el = document.getElementById("ctfChartMeta");
          if (!el) {
            return;
          }
          el.textContent = text;
        }

        function setActiveRangeButton(range) {
          const buttons = document.querySelectorAll("#chartRangeButtons .range-btn");
          for (const button of buttons) {
            if (!(button instanceof HTMLButtonElement)) {
              continue;
            }
            const targetRange = String(button.dataset.range ?? "");
            button.classList.toggle("active", targetRange === range);
          }
        }

        function setActiveCtfRangeButton(range) {
          const buttons = document.querySelectorAll("#ctfChartRangeButtons .range-btn");
          for (const button of buttons) {
            if (!(button instanceof HTMLButtonElement)) {
              continue;
            }
            const targetRange = String(button.dataset.range ?? "");
            button.classList.toggle("active", targetRange === range);
          }
        }

        function getChartTooltipElement() {
          const tooltip = document.getElementById("chartTooltip");
          return tooltip instanceof HTMLDivElement ? tooltip : null;
        }

        function getCtfChartTooltipElement() {
          const tooltip = document.getElementById("ctfChartTooltip");
          return tooltip instanceof HTMLDivElement ? tooltip : null;
        }

        function hideChartTooltip() {
          const tooltip = getChartTooltipElement();
          if (!tooltip) {
            return;
          }
          tooltip.hidden = true;
        }

        function hideCtfChartTooltip() {
          const tooltip = getCtfChartTooltipElement();
          if (!tooltip) {
            return;
          }
          tooltip.hidden = true;
        }

        function showChartTooltip(label, key, value, x, y, width, height) {
          const tooltip = getChartTooltipElement();
          if (!tooltip) {
            return;
          }
          const timeLabel = label && label !== "" ? label : (key || "-");
          tooltip.innerHTML =
            "<strong>" + escapeHtml(timeLabel) + "</strong>"
            + "<span>Visits: " + String(value) + "</span>";
          tooltip.hidden = false;

          const margin = 10;
          const maxLeft = Math.max(margin, width - tooltip.offsetWidth - margin);
          const maxTop = Math.max(margin, height - tooltip.offsetHeight - margin);
          const left = clamp(x + 14, margin, maxLeft);
          let top = y - tooltip.offsetHeight - 12;
          if (top < margin) {
            top = y + 12;
          }
          top = clamp(top, margin, maxTop);
          tooltip.style.left = left + "px";
          tooltip.style.top = top + "px";
        }

        function showCtfChartTooltip(label, key, value, x, y, width, height) {
          const tooltip = getCtfChartTooltipElement();
          if (!tooltip) {
            return;
          }
          const timeLabel = label && label !== "" ? label : (key || "-");
          tooltip.innerHTML =
            "<strong>" + escapeHtml(timeLabel) + "</strong>"
            + "<span>CTF Visits: " + String(value) + "</span>";
          tooltip.hidden = false;

          const margin = 10;
          const maxLeft = Math.max(margin, width - tooltip.offsetWidth - margin);
          const maxTop = Math.max(margin, height - tooltip.offsetHeight - margin);
          const left = clamp(x + 14, margin, maxLeft);
          let top = y - tooltip.offsetHeight - 12;
          if (top < margin) {
            top = y + 12;
          }
          top = clamp(top, margin, maxTop);
          tooltip.style.left = left + "px";
          tooltip.style.top = top + "px";
        }

        function resetChartZoom(totalPoints) {
          if (totalPoints <= 0) {
            chartState.zoomStart = 0;
            chartState.zoomEnd = -1;
            chartState.totalPoints = 0;
            return;
          }
          chartState.zoomStart = 0;
          chartState.zoomEnd = totalPoints - 1;
          chartState.totalPoints = totalPoints;
        }

        function ensureChartZoom(totalPoints, forceReset = false) {
          if (totalPoints <= 0) {
            resetChartZoom(0);
            return;
          }
          if (
            forceReset ||
            chartState.totalPoints !== totalPoints ||
            chartState.zoomEnd < chartState.zoomStart ||
            chartState.zoomEnd >= totalPoints
          ) {
            resetChartZoom(totalPoints);
            return;
          }
          chartState.zoomStart = clamp(chartState.zoomStart, 0, totalPoints - 1);
          chartState.zoomEnd = clamp(chartState.zoomEnd, chartState.zoomStart, totalPoints - 1);
          chartState.totalPoints = totalPoints;
        }

        function getVisibleSeriesSlice(fullSeries) {
          const total = fullSeries.data.length;
          if (total === 0 || chartState.zoomEnd < chartState.zoomStart) {
            return {
              labels: [],
              keys: [],
              data: [],
              start: 0,
              end: -1,
            };
          }
          const start = clamp(chartState.zoomStart, 0, total - 1);
          const end = clamp(chartState.zoomEnd, start, total - 1);
          return {
            labels: fullSeries.labels.slice(start, end + 1),
            keys: fullSeries.keys.slice(start, end + 1),
            data: fullSeries.data.slice(start, end + 1),
            start,
            end,
          };
        }

        function updateChartMeta(fullSeries, visibleSlice) {
          const total = fullSeries.data.length;
          const shown = visibleSlice.data.length;
          if (total === 0 || shown === 0) {
            setChartMeta(fullSeries.rangeLabel + " · No visit data yet.");
            return;
          }

          let peakValue = -1;
          let peakIndex = 0;
          for (let i = 0; i < shown; i++) {
            const value = Number(visibleSlice.data[i] ?? 0);
            if (value > peakValue) {
              peakValue = value;
              peakIndex = i;
            }
          }

          const peakLabel = visibleSlice.labels[peakIndex] || visibleSlice.keys[peakIndex] || "-";
          const coverage = Math.round((shown / total) * 100);
          setChartMeta(
            fullSeries.rangeLabel
              + " · Showing "
              + shown
              + " of "
              + total
              + " points ("
              + coverage
              + "% window). Peak: "
              + peakValue
              + " at "
              + peakLabel
              + "."
          );
        }

        function drawChartEmptyState(ctx, width, height, message) {
          ctx.clearRect(0, 0, width, height);
          ctx.fillStyle = "#000000";
          ctx.fillRect(0, 0, width, height);
          ctx.fillStyle = "#a9a9a9";
          ctx.font = "13px Space Grotesk, sans-serif";
          ctx.textAlign = "center";
          ctx.textBaseline = "middle";
          ctx.fillText(message, width / 2, height / 2);
          ctx.textAlign = "left";
          ctx.textBaseline = "alphabetic";
        }

        function renderCtfChart(stats) {
          const canvas = document.getElementById("ctfVisitsChart");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }

          const ctx = canvas.getContext("2d");
          if (!ctx) {
            return;
          }

          const fullSeries = getCtfSeriesForRange(stats, ctfChartState.range);
          const total = fullSeries.data.length;
          if (total === 0) {
            setCtfChartMeta(fullSeries.rangeLabel + " · No CTF visits yet.");
          } else {
            const peak = Math.max(0, ...fullSeries.data);
            const peakIdx = fullSeries.data.indexOf(peak);
            const peakLabel = peakIdx >= 0 ? (fullSeries.labels[peakIdx] || fullSeries.keys[peakIdx] || "-") : "-";
            setCtfChartMeta(fullSeries.rangeLabel + " · " + total + " points. Peak: " + peak + " at " + peakLabel + ".");
          }

          const rect = canvas.getBoundingClientRect();
          const width = Math.max(240, Math.floor(rect.width || 680));
          const height = 260;
          const dpr = window.devicePixelRatio || 1;
          canvas.width = Math.floor(width * dpr);
          canvas.height = Math.floor(height * dpr);
          canvas.style.width = width + "px";
          canvas.style.height = height + "px";
          ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
          ctx.clearRect(0, 0, width, height);

          const left = 40;
          const right = 12;
          const top = 10;
          const bottom = 28;
          const plotW = width - left - right;
          const plotH = height - top - bottom;

          if (fullSeries.data.length === 0) {
            hideCtfChartTooltip();
            drawChartEmptyState(ctx, width, height, "No CTF traffic data for this range.");
            return;
          }

          ctx.fillStyle = "#000000";
          ctx.fillRect(0, 0, width, height);

          const maxValue = Math.max(1, ...fullSeries.data);
          const ticks = 4;
          ctx.lineWidth = 1;
          ctx.strokeStyle = "rgba(255, 255, 255, 0.12)";
          ctx.fillStyle = "#a9a9a9";
          ctx.font = "11px Space Grotesk, sans-serif";
          ctx.textBaseline = "middle";

          for (let i = 0; i <= ticks; i++) {
            const y = top + (plotH * i / ticks);
            ctx.beginPath();
            ctx.moveTo(left, y);
            ctx.lineTo(width - right, y);
            ctx.stroke();
            const tickValue = Math.round(maxValue - (maxValue * i / ticks));
            ctx.fillText(String(tickValue), 4, y);
          }

          const xStep = fullSeries.data.length > 1 ? plotW / (fullSeries.data.length - 1) : 0;
          const yFor = (value) => top + plotH - (value / maxValue) * plotH;

          ctx.beginPath();
          for (let i = 0; i < fullSeries.data.length; i++) {
            const x = left + i * xStep;
            const y = yFor(fullSeries.data[i]);
            if (i === 0) {
              ctx.moveTo(x, y);
            } else {
              ctx.lineTo(x, y);
            }
          }
          ctx.lineTo(left + (fullSeries.data.length - 1) * xStep, top + plotH);
          ctx.lineTo(left, top + plotH);
          ctx.closePath();
          ctx.fillStyle = "rgba(38, 64, 130, 0.25)";
          ctx.fill();

          ctx.beginPath();
          for (let i = 0; i < fullSeries.data.length; i++) {
            const x = left + i * xStep;
            const y = yFor(fullSeries.data[i]);
            if (i === 0) {
              ctx.moveTo(x, y);
            } else {
              ctx.lineTo(x, y);
            }
          }
          ctx.strokeStyle = "#2f56ae";
          ctx.lineWidth = 2;
          ctx.stroke();

          if (fullSeries.data.length <= 120) {
            ctx.fillStyle = "#4f75cf";
            for (let i = 0; i < fullSeries.data.length; i++) {
              const x = left + i * xStep;
              const y = yFor(fullSeries.data[i]);
              ctx.beginPath();
              ctx.arc(x, y, 1.7, 0, Math.PI * 2);
              ctx.fill();
            }
          }

          ctx.fillStyle = "#9f9f9f";
          ctx.textBaseline = "top";
          const labelStep = Math.max(1, Math.floor(fullSeries.labels.length / 8));
          for (let i = 0; i < fullSeries.labels.length; i += labelStep) {
            const x = left + i * xStep;
            const label = fullSeries.labels[i] || "";
            ctx.fillText(label, x - 12, top + plotH + 6);
          }

          if (ctfChartState.pointerInside) {
            const px = ctfChartState.pointerX;
            const py = ctfChartState.pointerY;
            if (px >= left && px <= left + plotW && py >= top && py <= top + plotH) {
              const hoveredIdx = fullSeries.data.length > 1
                ? clamp(Math.round(((px - left) / Math.max(1, plotW)) * (fullSeries.data.length - 1)), 0, fullSeries.data.length - 1)
                : 0;
              const hoveredX = left + hoveredIdx * xStep;
              const hoveredY = yFor(fullSeries.data[hoveredIdx]);

              ctx.save();
              ctx.strokeStyle = "rgba(255, 255, 255, 0.18)";
              ctx.lineWidth = 1;
              ctx.beginPath();
              ctx.moveTo(hoveredX, top);
              ctx.lineTo(hoveredX, top + plotH);
              ctx.stroke();
              ctx.fillStyle = "#5f84d7";
              ctx.beginPath();
              ctx.arc(hoveredX, hoveredY, 4.3, 0, Math.PI * 2);
              ctx.fill();
              ctx.strokeStyle = "#000000";
              ctx.lineWidth = 1.2;
              ctx.beginPath();
              ctx.arc(hoveredX, hoveredY, 2.45, 0, Math.PI * 2);
              ctx.stroke();
              ctx.restore();

              showCtfChartTooltip(
                fullSeries.labels[hoveredIdx] || "",
                fullSeries.keys[hoveredIdx] || "",
                Number(fullSeries.data[hoveredIdx] ?? 0),
                hoveredX,
                hoveredY,
                width,
                height
              );
            } else {
              hideCtfChartTooltip();
            }
          } else {
            hideCtfChartTooltip();
          }
        }

        function renderChart(stats) {
          const canvas = document.getElementById("visitsChart");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }

          const ctx = canvas.getContext("2d");
          if (!ctx) {
            return;
          }

          const fullSeries = getSeriesForRange(stats, chartState.range);
          ensureChartZoom(fullSeries.data.length, false);
          const visible = getVisibleSeriesSlice(fullSeries);
          updateChartMeta(fullSeries, visible);

          const rect = canvas.getBoundingClientRect();
          const width = Math.max(240, Math.floor(rect.width || 680));
          const height = 260;
          const dpr = window.devicePixelRatio || 1;
          canvas.width = Math.floor(width * dpr);
          canvas.height = Math.floor(height * dpr);
          canvas.style.width = width + "px";
          canvas.style.height = height + "px";
          ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
          ctx.clearRect(0, 0, width, height);

          const left = 40;
          const right = 12;
          const top = 10;
          const bottom = 28;
          const plotW = width - left - right;
          const plotH = height - top - bottom;

          chartState.viewport = {
            left,
            right,
            top,
            bottom,
            width,
            height,
            plotW,
            plotH,
          };

          if (visible.data.length === 0) {
            hideChartTooltip();
            drawChartEmptyState(ctx, width, height, "No traffic data yet for this range.");
            return;
          }

          ctx.fillStyle = "#000000";
          ctx.fillRect(0, 0, width, height);

          const maxValue = Math.max(1, ...visible.data);
          const ticks = 4;
          ctx.lineWidth = 1;
          ctx.strokeStyle = "rgba(255, 255, 255, 0.12)";
          ctx.fillStyle = "#a9a9a9";
          ctx.font = "11px Space Grotesk, sans-serif";
          ctx.textBaseline = "middle";

          for (let i = 0; i <= ticks; i++) {
            const y = top + (plotH * i / ticks);
            ctx.beginPath();
            ctx.moveTo(left, y);
            ctx.lineTo(width - right, y);
            ctx.stroke();

            const tickValue = Math.round(maxValue - (maxValue * i / ticks));
            ctx.fillText(String(tickValue), 4, y);
          }

          const xStep = visible.data.length > 1 ? plotW / (visible.data.length - 1) : 0;
          const yFor = (value) => top + plotH - (value / maxValue) * plotH;

          ctx.beginPath();
          for (let i = 0; i < visible.data.length; i++) {
            const x = left + i * xStep;
            const y = yFor(visible.data[i]);
            if (i === 0) {
              ctx.moveTo(x, y);
            } else {
              ctx.lineTo(x, y);
            }
          }
          ctx.lineTo(left + (visible.data.length - 1) * xStep, top + plotH);
          ctx.lineTo(left, top + plotH);
          ctx.closePath();
          ctx.fillStyle = "rgba(30, 58, 138, 0.26)";
          ctx.fill();

          ctx.beginPath();
          for (let i = 0; i < visible.data.length; i++) {
            const x = left + i * xStep;
            const y = yFor(visible.data[i]);
            if (i === 0) {
              ctx.moveTo(x, y);
            } else {
              ctx.lineTo(x, y);
            }
          }
          ctx.strokeStyle = "#1e3a8a";
          ctx.lineWidth = 2;
          ctx.stroke();

          if (visible.data.length <= 120) {
            ctx.fillStyle = "#365bb2";
            for (let i = 0; i < visible.data.length; i++) {
              const x = left + i * xStep;
              const y = yFor(visible.data[i]);
              ctx.beginPath();
              ctx.arc(x, y, 1.7, 0, Math.PI * 2);
              ctx.fill();
            }
          }

          ctx.fillStyle = "#9f9f9f";
          ctx.textBaseline = "top";
          const labelStep = Math.max(1, Math.floor(visible.labels.length / 8));
          for (let i = 0; i < visible.labels.length; i += labelStep) {
            const x = left + i * xStep;
            const label = visible.labels[i] || "";
            ctx.fillText(label, x - 12, top + plotH + 6);
          }

          if (!chartState.dragActive && chartState.pointerInside) {
            const px = chartState.pointerX;
            const py = chartState.pointerY;
            if (px >= left && px <= left + plotW && py >= top && py <= top + plotH) {
              const hoveredIdx = visible.data.length > 1
                ? clamp(Math.round(((px - left) / Math.max(1, plotW)) * (visible.data.length - 1)), 0, visible.data.length - 1)
                : 0;
              const hoveredX = left + hoveredIdx * xStep;
              const hoveredY = yFor(visible.data[hoveredIdx]);

              ctx.save();
              ctx.strokeStyle = "rgba(255, 255, 255, 0.18)";
              ctx.lineWidth = 1;
              ctx.beginPath();
              ctx.moveTo(hoveredX, top);
              ctx.lineTo(hoveredX, top + plotH);
              ctx.stroke();
              ctx.fillStyle = "#4b6fc8";
              ctx.beginPath();
              ctx.arc(hoveredX, hoveredY, 4.3, 0, Math.PI * 2);
              ctx.fill();
              ctx.strokeStyle = "#000000";
              ctx.lineWidth = 1.2;
              ctx.beginPath();
              ctx.arc(hoveredX, hoveredY, 2.45, 0, Math.PI * 2);
              ctx.stroke();
              ctx.restore();

              showChartTooltip(
                visible.labels[hoveredIdx] || "",
                visible.keys[hoveredIdx] || "",
                Number(visible.data[hoveredIdx] ?? 0),
                hoveredX,
                hoveredY,
                width,
                height
              );
            } else {
              hideChartTooltip();
            }
          } else {
            hideChartTooltip();
          }
        }

        function escapeHtml(value) {
          return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll("\"", "&quot;")
            .replaceAll("'", "&#039;");
        }

        function setAssistantStatus(message, isError = false) {
          const el = document.getElementById("assistantStatus");
          if (!(el instanceof HTMLElement)) {
            return;
          }
          el.textContent = String(message || "");
          el.classList.toggle("error", Boolean(isError));
        }

        function setAssistantBusy(isBusy) {
          assistantState.sending = Boolean(isBusy);
          const sendBtn = document.getElementById("assistantSendBtn");
          const clearBtn = document.getElementById("assistantClearBtn");
          const input = document.getElementById("assistantInput");

          if (sendBtn instanceof HTMLButtonElement) {
            sendBtn.disabled = assistantState.sending;
            sendBtn.textContent = assistantState.sending ? "Sending..." : "Send";
          }
          if (clearBtn instanceof HTMLButtonElement) {
            clearBtn.disabled = assistantState.sending;
          }
          if (input instanceof HTMLTextAreaElement) {
            input.disabled = assistantState.sending;
          }
        }

        function renderAssistantMessages() {
          const log = document.getElementById("assistantChatLog");
          if (!(log instanceof HTMLElement)) {
            return;
          }

          if (!Array.isArray(assistantState.messages) || assistantState.messages.length === 0) {
            log.innerHTML = '<p class="assistant-empty">Start a chat about team announcements, edits, or cybersecurity training content.</p>';
            return;
          }

          log.innerHTML = assistantState.messages.map((entry) => {
            const role = String(entry.role || "assistant") === "user" ? "user" : "assistant";
            const author = role === "user" ? "You" : "Assistant";
            const content = escapeHtml(String(entry.content || ""));
            return '<article class="assistant-row ' + role + '">'
              + '<p class="assistant-author">' + author + '</p>'
              + '<p class="assistant-text">' + content + '</p>'
              + '</article>';
          }).join("");
          log.scrollTop = log.scrollHeight;
        }

        function pushAssistantMessage(role, content) {
          const safeRole = role === "user" ? "user" : "assistant";
          const safeContent = String(content || "").trim();
          if (safeContent === "") {
            return;
          }
          assistantState.messages.push({
            role: safeRole,
            content: safeContent,
          });
          if (assistantState.messages.length > 30) {
            assistantState.messages = assistantState.messages.slice(-30);
          }
          renderAssistantMessages();
        }

        function getAssistantPayloadHistory() {
          return assistantState.messages.slice(-20).map((entry) => ({
            role: entry.role === "user" ? "user" : "assistant",
            content: String(entry.content || "").slice(0, 1200),
          }));
        }

        async function sendAssistantMessage(message) {
          const payload = {
            csrf: assistantCsrfToken,
            message,
            history: getAssistantPayloadHistory(),
          };

          const res = await fetch(assistantChatEndpoint + "&_=" + Date.now(), {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "Accept": "application/json",
            },
            credentials: "same-origin",
            body: JSON.stringify(payload),
          });

          let data = null;
          try {
            data = await res.json();
          } catch (_) {
            data = null;
          }

          if (!res.ok || !data || data.success !== true) {
            const apiError = data && typeof data.error === "string" ? data.error : "Assistant request failed.";
            throw new Error(apiError);
          }

          const reply = typeof data.reply === "string" ? data.reply.trim() : "";
          if (reply === "") {
            throw new Error("Assistant returned an empty response.");
          }
          return reply;
        }

        function initAssistantChat() {
          const form = document.getElementById("assistantChatForm");
          const input = document.getElementById("assistantInput");
          const clearBtn = document.getElementById("assistantClearBtn");
          if (!(form instanceof HTMLFormElement) || !(input instanceof HTMLTextAreaElement)) {
            return;
          }

          renderAssistantMessages();
          setAssistantStatus("");

          form.addEventListener("submit", async (event) => {
            event.preventDefault();
            if (assistantState.sending) {
              return;
            }

            const message = input.value.trim();
            if (message === "") {
              setAssistantStatus("Write a message first.", true);
              input.focus();
              return;
            }

            input.value = "";
            setAssistantStatus("Waiting for assistant response...");
            pushAssistantMessage("user", message);
            setAssistantBusy(true);

            try {
              const reply = await sendAssistantMessage(message);
              pushAssistantMessage("assistant", reply);
              setAssistantStatus("Reply received.");
            } catch (error) {
              const msg = error instanceof Error ? error.message : "Assistant request failed.";
              setAssistantStatus(msg, true);
            } finally {
              setAssistantBusy(false);
              input.focus();
            }
          });

          if (clearBtn instanceof HTMLButtonElement) {
            clearBtn.addEventListener("click", () => {
              if (assistantState.sending) {
                return;
              }
              assistantState.messages = [];
              renderAssistantMessages();
              setAssistantStatus("Chat cleared.");
              input.focus();
            });
          }
        }

        function renderCountryList(stats) {
          const countryList = document.getElementById("countryList");
          if (!countryList) {
            return;
          }
          const countries = (stats && Array.isArray(stats.countries_30d)) ? stats.countries_30d : [];
          if (countries.length === 0) {
            countryList.innerHTML = '<div class="country-row"><strong>No country data yet.</strong><span>0</span></div>';
            return;
          }
          countryList.innerHTML = countries.slice(0, 20).map((entry) => {
            const code = String(entry.code ?? "UN");
            const count = Number(entry.count ?? 0);
            const name = globeState.namesByCode.get(code.toUpperCase()) || code;
            return '<div class="country-row"><strong>' + escapeHtml(code) + ' · ' + escapeHtml(name) + '</strong><span>' + count + '</span></div>';
          }).join("");
        }

        function renderTopPages(stats) {
          const tbody = document.getElementById("topPagesBody");
          if (!tbody) {
            return;
          }
          const rows = (stats && Array.isArray(stats.top_pages_30d)) ? stats.top_pages_30d : [];
          if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3">No page traffic data yet.</td></tr>';
            return;
          }
          tbody.innerHTML = rows.slice(0, 14).map((row) => {
            const path = escapeHtml(row.path ?? "/");
            const views = Number(row.views ?? 0);
            const unique = Number(row.unique ?? 0);
            return '<tr><td>' + path + '</td><td>' + views + '</td><td>' + unique + '</td></tr>';
          }).join("");
        }

        function renderRecentAccess(stats) {
          const tbody = document.getElementById("recentAccessBody");
          if (!tbody) {
            return;
          }
          const rows = (stats && Array.isArray(stats.recent_access_30d)) ? stats.recent_access_30d : [];
          if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">No connection data yet.</td></tr>';
            return;
          }
          tbody.innerHTML = rows.slice(0, 120).map((row) => {
            const ts = Number(row.ts ?? 0);
            const when = ts > 0 ? new Date(ts * 1000).toLocaleString() : "-";
            const ip = escapeHtml(row.ip ?? "Unknown");
            const country = escapeHtml(row.country ?? "UN");
            const device = escapeHtml(row.device ?? "Unknown");
            const path = escapeHtml(row.path ?? "/");
            return '<tr>'
              + '<td>' + when + '</td>'
              + '<td>' + ip + '</td>'
              + '<td>' + country + '</td>'
              + '<td>' + device + '</td>'
              + '<td class="path-cell">' + path + '</td>'
              + '</tr>';
          }).join("");
        }

        function renderCtfTopPaths(stats) {
          const tbody = document.getElementById("ctfTopPathsBody");
          if (!tbody) {
            return;
          }

          const payload = getCtfAnalytics(stats);
          const rows = Array.isArray(payload.top_paths_30d) ? payload.top_paths_30d : [];
          if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3">No CTF route traffic in the last 30 days.</td></tr>';
            return;
          }

          tbody.innerHTML = rows.slice(0, 16).map((row) => {
            const path = escapeHtml(row.path ?? "/");
            const views = Number(row.views ?? 0);
            const unique = Number(row.unique ?? 0);
            return '<tr><td>' + path + '</td><td>' + views + '</td><td>' + unique + '</td></tr>';
          }).join("");
        }

        function renderCtfChallengeActivity(stats) {
          const tbody = document.getElementById("ctfChallengeActivityBody");
          if (!tbody) {
            return;
          }

          const payload = getCtfAnalytics(stats);
          const rows = Array.isArray(payload.challenge_activity_30d) ? payload.challenge_activity_30d : [];
          if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4">No challenge endpoint activity in the last 30 days.</td></tr>';
            return;
          }

          tbody.innerHTML = rows.slice(0, 18).map((row) => {
            const label = escapeHtml(row.label ?? row.path ?? "/");
            const urlRaw = typeof row.url === "string" ? row.url.trim() : "";
            const endpointRaw = typeof row.endpoint === "string" ? row.endpoint.trim() : "";
            const hits = Number(row.hits ?? 0);
            const unique = Number(row.unique ?? 0);
            const lastSeenRaw = String(row.last_seen ?? "");
            const lastSeen = lastSeenRaw !== "" && !Number.isNaN(new Date(lastSeenRaw).getTime())
              ? new Date(lastSeenRaw).toLocaleString()
              : "-";
            const hasLink = /^https?:\/\/\S+$/i.test(urlRaw);
            let challengeCell = label;
            if (hasLink) {
              const safeUrl = escapeHtml(urlRaw);
              const endpointLabel = endpointRaw !== ""
                ? escapeHtml(endpointRaw)
                : escapeHtml(urlRaw.replace(/^https?:\/\//i, ""));
              challengeCell = '<a class="challenge-link" href="' + safeUrl + '" target="_blank" rel="noopener noreferrer">' + label + '</a>'
                + '<span class="challenge-endpoint">' + endpointLabel + '</span>';
            }
            return '<tr>'
              + '<td>' + challengeCell + '</td>'
              + '<td>' + hits + '</td>'
              + '<td>' + unique + '</td>'
              + '<td>' + escapeHtml(lastSeen) + '</td>'
              + '</tr>';
          }).join("");
        }

        function renderCtfRecentAccess(stats) {
          const tbody = document.getElementById("ctfRecentAccessBody");
          if (!tbody) {
            return;
          }

          const payload = getCtfAnalytics(stats);
          const rows = Array.isArray(payload.recent_access_30d) ? payload.recent_access_30d : [];
          if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7">No recent CTF connections captured.</td></tr>';
            return;
          }

          tbody.innerHTML = rows.slice(0, 500).map((row) => {
            const ts = Number(row.ts ?? 0);
            const when = ts > 0 ? new Date(ts * 1000).toLocaleString() : "-";
            const ip = escapeHtml(row.ip ?? "Unknown");
            const country = escapeHtml(row.country ?? "UN");
            const method = escapeHtml(row.method ?? "GET");
            const status = Number(row.status ?? 0);
            const path = escapeHtml(row.path ?? "/");
            const device = escapeHtml(row.device ?? "Unknown");
            return '<tr>'
              + '<td>' + when + '</td>'
              + '<td>' + ip + '</td>'
              + '<td>' + country + '</td>'
              + '<td>' + method + '</td>'
              + '<td>' + (status > 0 ? String(status) : "-") + '</td>'
              + '<td class="path-cell">' + path + '</td>'
              + '<td>' + device + '</td>'
              + '</tr>';
          }).join("");
        }

        function renderGeneratedAt(stats) {
          const el = document.getElementById("statsGeneratedAt");
          if (!el) {
            return;
          }
          const generated = (stats && stats.generated_at) ? new Date(stats.generated_at) : null;
          if (!generated || Number.isNaN(generated.getTime())) {
            el.textContent = "";
            return;
          }
          el.textContent = "Generated: " + generated.toLocaleString();
        }

        function renderCtfGeneratedAt(stats) {
          const el = document.getElementById("ctfStatsGeneratedAt");
          if (!(el instanceof HTMLElement)) {
            return;
          }

          const payload = getCtfAnalytics(stats);
          const generatedRaw = typeof payload.generated_at === "string" ? payload.generated_at : "";
          const generated = generatedRaw !== "" ? new Date(generatedRaw) : null;
          const generatedLabel = generated && !Number.isNaN(generated.getTime())
            ? generated.toLocaleString()
            : "N/A";
          const available = Boolean(payload.available);
          const source = typeof payload.log_path === "string" && payload.log_path !== ""
            ? payload.log_path
            : "unknown";
          const sourceLabel = source.length > 64 ? ("..." + source.slice(-61)) : source;

          el.textContent = (available ? "Generated: " + generatedLabel : "CTF log unavailable")
            + " · Source: "
            + sourceLabel;
        }

        function renderResourceStrip(stats) {
          const resources = (stats && typeof stats.resources === "object" && stats.resources) ? stats.resources : {};
          setText("resource-cpu", typeof resources.cpu_label === "string" && resources.cpu_label !== "" ? resources.cpu_label : "N/A");
          setText("resource-ram", typeof resources.ram_label === "string" && resources.ram_label !== "" ? resources.ram_label : "N/A");
          setText("resource-storage", typeof resources.storage_label === "string" && resources.storage_label !== "" ? resources.storage_label : "N/A");
        }

        function renderResponseCards(stats) {
          const rows = (stats && Array.isArray(stats.response_cards)) ? stats.response_cards : [];
          let main = null;
          let ctf = null;

          for (const row of rows) {
            const key = String(row?.key ?? "").toLowerCase();
            const label = String(row?.label ?? "").toLowerCase();
            if (!main && (key === "main" || (label.includes("aegislab.ro") && !label.includes("ctf")))) {
              main = row;
              continue;
            }
            if (!ctf && (key === "ctf" || label.includes("ctf.aegislab.ro"))) {
              ctf = row;
            }
          }

          function renderCard(prefix, row) {
            const valueEl = document.getElementById("response-" + prefix + "-value");
            const metaEl = document.getElementById("response-" + prefix + "-meta");
            if (!(valueEl instanceof HTMLElement) || !(metaEl instanceof HTMLElement)) {
              return;
            }

            if (!row || typeof row !== "object") {
              valueEl.textContent = "N/A";
              valueEl.classList.add("down");
              metaEl.textContent = "No check data";
              return;
            }

            const ok = Boolean(row.ok);
            const status = Number(row.status ?? 0);
            const ms = Number(row.ms ?? 0);
            const value = ok && ms > 0 ? (String(ms) + " ms") : "Down";
            const meta = status > 0 ? ("HTTP " + String(status)) : "No response";

            valueEl.textContent = value;
            valueEl.classList.toggle("down", !ok);
            metaEl.textContent = meta;
          }

          renderCard("main", main);
          renderCard("ctf", ctf);
        }

        function initGlobe() {
          if (globeState.animationStarted) {
            return;
          }
          const canvas = document.getElementById("globeViz");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          const ctx = canvas.getContext("2d");
          if (!ctx) {
            return;
          }
          if (typeof window.d3 === "undefined") {
            return;
          }
          globeState.canvas = canvas;
          globeState.ctx = ctx;
          globeState.animationStarted = true;
          resizeGlobe();
          ensureGlobeCountryData();
          initGlobeControls();
          window.requestAnimationFrame(drawGlobeFrame);
        }

        function resizeGlobe() {
          if (!globeState.canvas || !globeState.ctx) {
            return;
          }
          const rect = globeState.canvas.getBoundingClientRect();
          const width = Math.max(240, Math.floor(rect.width || 400));
          const height = Math.max(250, Math.floor(rect.height || 300));
          const dpr = window.devicePixelRatio || 1;
          globeState.canvas.width = Math.floor(width * dpr);
          globeState.canvas.height = Math.floor(height * dpr);
          globeState.canvas.style.width = width + "px";
          globeState.canvas.style.height = height + "px";
          globeState.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
          globeState.width = width;
          globeState.height = height;
        }

        function normalizeIso2Code(value) {
          const code = String(value ?? "").trim().toUpperCase();
          if (code === "" || code === "-99" || code.length !== 2) {
            return "";
          }
          return code;
        }

        async function ensureGlobeCountryData() {
          if (globeState.countriesLoaded) {
            return true;
          }
          if (globeState.loadingPromise) {
            return globeState.loadingPromise;
          }

          globeState.loadingPromise = fetch(globeGeoJsonEndpoint, {
            cache: "force-cache",
            headers: { "Accept": "application/geo+json, application/json" },
          })
            .then((response) => {
              if (!response.ok) {
                throw new Error("Could not load globe geometry.");
              }
              return response.json();
            })
            .then((geojson) => {
              const features = Array.isArray(geojson?.features) ? geojson.features : [];
              const normalized = [];
              const namesByCode = new Map();

              for (const feature of features) {
                const props = feature?.properties ?? {};
                const iso2 = normalizeIso2Code(props.ISO_A2 ?? props.iso_a2 ?? props.POSTAL);
                const name = String(props.NAME_EN ?? props.NAME ?? props.ADMIN ?? props.name ?? "").trim();
                normalized.push({
                  type: "Feature",
                  geometry: feature.geometry,
                  properties: {
                    iso2,
                    name: name === "" ? iso2 : name,
                  },
                });
                if (iso2 !== "" && name !== "") {
                  namesByCode.set(iso2, name);
                }
              }

              globeState.countries = normalized;
              globeState.namesByCode = namesByCode;
              globeState.countriesLoaded = true;
              return true;
            })
            .catch(() => {
              globeState.countries = [];
              globeState.namesByCode = new Map();
              globeState.countriesLoaded = false;
              return false;
            });

          return globeState.loadingPromise;
        }

        function buildHighlightedCountryMap(stats) {
          const map = new Map();
          const countries = (stats && Array.isArray(stats.countries_30d)) ? stats.countries_30d : [];
          for (const entry of countries) {
            const code = normalizeIso2Code(entry?.code ?? "");
            const count = Number(entry?.count ?? 0);
            if (code === "" || count <= 0) {
              continue;
            }
            map.set(code, count);
          }
          return map;
        }

        function getGlobeTooltipElement() {
          const tooltip = document.getElementById("globeTooltip");
          return tooltip instanceof HTMLDivElement ? tooltip : null;
        }

        function hideGlobeTooltip() {
          const tooltip = getGlobeTooltipElement();
          if (!tooltip) {
            return;
          }
          tooltip.hidden = true;
        }

        function positionGlobeTooltip() {
          const tooltip = getGlobeTooltipElement();
          const canvas = globeState.canvas;
          if (!tooltip || !(canvas instanceof HTMLCanvasElement)) {
            return;
          }

          const margin = 12;
          const maxLeft = Math.max(margin, globeState.width - tooltip.offsetWidth - margin);
          const maxTop = Math.max(margin, globeState.height - tooltip.offsetHeight - margin);
          const left = clamp(globeState.pointerX + 14, margin, maxLeft);
          const top = clamp(globeState.pointerY + 14, margin, maxTop);
          tooltip.style.left = left + "px";
          tooltip.style.top = top + "px";
        }

        function showGlobeTooltip(name, code, count) {
          const tooltip = getGlobeTooltipElement();
          if (!tooltip) {
            return;
          }
          const countryName = name && name !== "" ? name : code;
          tooltip.innerHTML =
            "<strong>" + escapeHtml(countryName) + "</strong>"
            + "<span>Visits: " + String(count) + "</span>";
          tooltip.hidden = false;
          positionGlobeTooltip();
        }

        function updateGlobeHoverFromPointer() {
          if (
            globeState.dragActive ||
            !globeState.pointerInside ||
            !globeState.projection ||
            typeof window.d3 === "undefined"
          ) {
            globeState.hoveredCode = "";
            globeState.hoveredName = "";
            globeState.hoveredCount = 0;
            hideGlobeTooltip();
            return;
          }

          const inverted = globeState.projection.invert([globeState.pointerX, globeState.pointerY]);
          if (!Array.isArray(inverted) || inverted.length !== 2) {
            globeState.hoveredCode = "";
            globeState.hoveredName = "";
            globeState.hoveredCount = 0;
            hideGlobeTooltip();
            return;
          }

          let foundCode = "";
          let foundName = "";
          let foundCount = 0;
          for (const feature of globeState.countries) {
            const iso2 = normalizeIso2Code(feature?.properties?.iso2 ?? "");
            if (iso2 === "" || !globeState.highlightedByCode.has(iso2)) {
              continue;
            }
            if (window.d3.geoContains(feature, inverted)) {
              foundCode = iso2;
              foundName = String(feature?.properties?.name ?? globeState.namesByCode.get(iso2) ?? iso2);
              foundCount = Number(globeState.highlightedByCode.get(iso2) ?? 0);
              break;
            }
          }

          globeState.hoveredCode = foundCode;
          globeState.hoveredName = foundName;
          globeState.hoveredCount = foundCount;

          if (foundCode !== "" && foundCount > 0) {
            showGlobeTooltip(foundName, foundCode, foundCount);
          } else {
            hideGlobeTooltip();
          }
        }

        function drawGlobeFrame() {
          if (!globeState.ctx || typeof window.d3 === "undefined") {
            return;
          }
          const ctx = globeState.ctx;
          const width = globeState.width;
          const height = globeState.height;
          const cx = width / 2;
          const cy = height / 2;
          const radius = Math.min(width, height) * 0.49 * globeState.zoom;

          ctx.clearRect(0, 0, width, height);
          ctx.fillStyle = "rgba(0, 0, 0, 0.99)";
          ctx.fillRect(0, 0, width, height);

          const projection = window.d3.geoOrthographic()
            .translate([cx, cy])
            .scale(radius)
            .rotate([globeState.rotationDeg, globeState.tiltDeg, 0])
            .clipAngle(90)
            .precision(0.35);
          const path = window.d3.geoPath(projection, ctx);
          globeState.projection = projection;

          updateGlobeHoverFromPointer();

          const glow = ctx.createRadialGradient(cx - radius * 0.33, cy - radius * 0.4, radius * 0.12, cx, cy, radius * 1.08);
          glow.addColorStop(0, "rgba(110, 150, 210, 0.10)");
          glow.addColorStop(0.45, "rgba(34, 34, 34, 0.60)");
          glow.addColorStop(1, "rgba(0, 0, 0, 0.99)");
          ctx.fillStyle = glow;
          ctx.beginPath();
          path({ type: "Sphere" });
          ctx.fill();

          ctx.beginPath();
          path(window.d3.geoGraticule10());
          ctx.strokeStyle = "rgba(180, 180, 180, 0.16)";
          ctx.lineWidth = 0.55;
          ctx.stroke();

          const highlightValues = Array.from(globeState.highlightedByCode.values());
          const maxHighlight = Math.max(1, ...highlightValues);

          for (const feature of globeState.countries) {
            const iso2 = normalizeIso2Code(feature?.properties?.iso2 ?? "");
            const count = globeState.highlightedByCode.get(iso2) ?? 0;
            const ratio = count > 0 ? Math.min(1, count / maxHighlight) : 0;

            ctx.beginPath();
            path(feature);

            const isHovered = iso2 !== "" && globeState.hoveredCode === iso2;

            if (count > 0) {
              const alpha = 0.24 + ratio * 0.56;
              ctx.fillStyle = "rgba(97, 171, 255, " + alpha.toFixed(3) + ")";
            } else {
              ctx.fillStyle = "rgba(36, 36, 36, 0.86)";
            }
            ctx.fill();

            if (isHovered) {
              ctx.strokeStyle = "rgba(112, 183, 255, 0.94)";
              ctx.lineWidth = 1.1;
            } else if (count > 0) {
              ctx.strokeStyle = "rgba(94, 160, 236, 0.86)";
              ctx.lineWidth = 0.72;
            } else {
              ctx.strokeStyle = "rgba(122, 122, 122, 0.34)";
              ctx.lineWidth = 0.42;
            }
            ctx.stroke();
          }

          ctx.beginPath();
          path({ type: "Sphere" });
          ctx.strokeStyle = "rgba(170, 170, 170, 0.36)";
          ctx.lineWidth = 1;
          ctx.stroke();

          if (!globeState.dragActive) {
            globeState.rotationDeg = (globeState.rotationDeg + 0.11) % 360;
          }
          window.requestAnimationFrame(drawGlobeFrame);
        }

        function renderGlobe(stats) {
          initGlobe();
          globeState.highlightedByCode = buildHighlightedCountryMap(stats);
          ensureGlobeCountryData().then(() => {
            renderCountryList(stats);
          });
        }

        function handleGlobeDragStart(event) {
          const canvas = globeState.canvas;
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          globeState.dragActive = true;
          globeState.dragStartX = event.clientX;
          globeState.dragStartY = event.clientY;
          globeState.dragStartRotation = globeState.rotationDeg;
          globeState.dragStartTilt = globeState.tiltDeg;
          globeState.pointerInside = true;
          const rect = canvas.getBoundingClientRect();
          globeState.pointerX = event.clientX - rect.left;
          globeState.pointerY = event.clientY - rect.top;
          canvas.style.cursor = "grabbing";
          event.preventDefault();
        }

        function handleGlobeDragMove(event) {
          const canvas = globeState.canvas;
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }

          const rect = canvas.getBoundingClientRect();
          globeState.pointerX = event.clientX - rect.left;
          globeState.pointerY = event.clientY - rect.top;

          if (!globeState.dragActive) {
            if (globeState.pointerInside) {
              updateGlobeHoverFromPointer();
            }
            return;
          }
          const deltaX = event.clientX - globeState.dragStartX;
          const deltaY = event.clientY - globeState.dragStartY;
          let nextRotation = globeState.dragStartRotation + (deltaX * 0.36);
          nextRotation %= 360;
          if (nextRotation < 0) {
            nextRotation += 360;
          }
          globeState.rotationDeg = nextRotation;
          globeState.tiltDeg = clamp(globeState.dragStartTilt - (deltaY * 0.26), -65, 65);
        }

        function handleGlobeDragEnd() {
          globeState.dragActive = false;
          const canvas = globeState.canvas;
          if (canvas instanceof HTMLCanvasElement) {
            canvas.style.cursor = "grab";
          }
        }

        function handleGlobePointerEnter() {
          globeState.pointerInside = true;
        }

        function handleGlobePointerLeave() {
          globeState.pointerInside = false;
          globeState.hoveredCode = "";
          globeState.hoveredName = "";
          globeState.hoveredCount = 0;
          hideGlobeTooltip();
          handleGlobeDragEnd();
        }

        function handleGlobePointerMove(event) {
          const canvas = globeState.canvas;
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          const rect = canvas.getBoundingClientRect();
          globeState.pointerX = event.clientX - rect.left;
          globeState.pointerY = event.clientY - rect.top;
          globeState.pointerInside = true;
          if (!globeState.dragActive) {
            updateGlobeHoverFromPointer();
          }
        }

        function handleGlobeWheel(event) {
          const canvas = globeState.canvas;
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          event.preventDefault();
          const step = event.deltaY < 0 ? 1.12 : 0.9;
          globeState.zoom = clamp(globeState.zoom * step, 0.78, 2.4);
        }

        function handleGlobeDoubleClick() {
          globeState.zoom = 1;
          globeState.tiltDeg = -8;
        }

        function initGlobeControls() {
          const canvas = globeState.canvas;
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          if (canvas.dataset.boundGlobeEvents === "1") {
            return;
          }
          canvas.dataset.boundGlobeEvents = "1";
          canvas.addEventListener("mousedown", handleGlobeDragStart);
          canvas.addEventListener("mouseenter", handleGlobePointerEnter);
          canvas.addEventListener("mouseleave", handleGlobePointerLeave);
          canvas.addEventListener("mousemove", handleGlobePointerMove);
          canvas.addEventListener("wheel", handleGlobeWheel, { passive: false });
          canvas.addEventListener("dblclick", handleGlobeDoubleClick);
          window.addEventListener("mousemove", handleGlobeDragMove);
          window.addEventListener("mouseup", handleGlobeDragEnd);
        }

        function updateChartPointerFromEvent(event) {
          const canvas = document.getElementById("visitsChart");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          const rect = canvas.getBoundingClientRect();
          chartState.pointerX = event.clientX - rect.left;
          chartState.pointerY = event.clientY - rect.top;
        }

        function handleChartPointerEnter(event) {
          chartState.pointerInside = true;
          updateChartPointerFromEvent(event);
          renderChart(latestStats);
        }

        function handleChartPointerMove(event) {
          chartState.pointerInside = true;
          updateChartPointerFromEvent(event);
          if (!chartState.dragActive) {
            renderChart(latestStats);
          }
        }

        function handleChartPointerLeave() {
          chartState.pointerInside = false;
          hideChartTooltip();
          renderChart(latestStats);
        }

        function handleChartRangeSelection(range, shouldResetZoom = true) {
          if (!["1h", "24h", "7d", "30d"].includes(range)) {
            return;
          }
          chartState.range = range;
          const series = getSeriesForRange(latestStats, chartState.range);
          ensureChartZoom(series.data.length, shouldResetZoom);
          setActiveRangeButton(chartState.range);
          renderChart(latestStats);
        }

        function handleChartWheel(event) {
          const canvas = document.getElementById("visitsChart");
          if (!(canvas instanceof HTMLCanvasElement) || !chartState.viewport) {
            return;
          }
          const total = chartState.totalPoints;
          if (total <= 2) {
            return;
          }

          const rect = canvas.getBoundingClientRect();
          const pointerX = event.clientX - rect.left;
          const pointerY = event.clientY - rect.top;
          const view = chartState.viewport;
          if (
            pointerX < view.left ||
            pointerX > view.left + view.plotW ||
            pointerY < view.top ||
            pointerY > view.top + view.plotH
          ) {
            return;
          }

          event.preventDefault();

          const visible = chartState.zoomEnd - chartState.zoomStart + 1;
          const minWindow = chartState.range === "1h" ? 6 : (chartState.range === "24h" ? 4 : 2);
          const zoomFactor = event.deltaY < 0 ? 0.82 : 1.2;
          let nextVisible = Math.round(visible * zoomFactor);
          nextVisible = clamp(nextVisible, minWindow, total);
          if (nextVisible === visible) {
            return;
          }

          const ratio = clamp((pointerX - view.left) / Math.max(1, view.plotW), 0, 1);
          const anchorIndex = chartState.zoomStart + Math.round(ratio * Math.max(0, visible - 1));
          let nextStart = anchorIndex - Math.round(ratio * Math.max(0, nextVisible - 1));
          nextStart = clamp(nextStart, 0, total - nextVisible);

          chartState.zoomStart = nextStart;
          chartState.zoomEnd = nextStart + nextVisible - 1;
          renderChart(latestStats);
        }

        function handleChartDragStart(event) {
          const canvas = document.getElementById("visitsChart");
          if (!(canvas instanceof HTMLCanvasElement) || !chartState.viewport) {
            return;
          }

          const rect = canvas.getBoundingClientRect();
          const pointerX = event.clientX - rect.left;
          const pointerY = event.clientY - rect.top;
          const view = chartState.viewport;
          if (
            pointerX < view.left ||
            pointerX > view.left + view.plotW ||
            pointerY < view.top ||
            pointerY > view.top + view.plotH
          ) {
            return;
          }

          chartState.dragActive = true;
          chartState.dragStartX = event.clientX;
          chartState.dragStartRangeStart = chartState.zoomStart;
          updateChartPointerFromEvent(event);
          hideChartTooltip();
          canvas.style.cursor = "grabbing";
          event.preventDefault();
        }

        function handleChartDragMove(event) {
          if (!chartState.dragActive || !chartState.viewport) {
            return;
          }
          updateChartPointerFromEvent(event);
          const visible = chartState.zoomEnd - chartState.zoomStart + 1;
          const total = chartState.totalPoints;
          if (visible >= total) {
            return;
          }
          const xStep = visible > 1 ? chartState.viewport.plotW / (visible - 1) : chartState.viewport.plotW;
          if (xStep <= 0) {
            return;
          }

          const moved = Math.round((event.clientX - chartState.dragStartX) / xStep);
          let nextStart = chartState.dragStartRangeStart - moved;
          nextStart = clamp(nextStart, 0, total - visible);
          chartState.zoomStart = nextStart;
          chartState.zoomEnd = nextStart + visible - 1;
          renderChart(latestStats);
        }

        function handleChartDragEnd() {
          const wasDragging = chartState.dragActive;
          chartState.dragActive = false;
          const canvas = document.getElementById("visitsChart");
          if (canvas instanceof HTMLCanvasElement) {
            canvas.style.cursor = "default";
          }
          if (wasDragging && chartState.pointerInside) {
            renderChart(latestStats);
          }
        }

        function initChartControls() {
          const canvas = document.getElementById("visitsChart");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          if (canvas.dataset.boundEvents === "1") {
            return;
          }
          canvas.dataset.boundEvents = "1";
          canvas.addEventListener("wheel", handleChartWheel, { passive: false });
          canvas.addEventListener("mouseenter", handleChartPointerEnter);
          canvas.addEventListener("mousemove", handleChartPointerMove);
          canvas.addEventListener("mousedown", handleChartDragStart);
          canvas.addEventListener("dblclick", () => {
            const series = getSeriesForRange(latestStats, chartState.range);
            resetChartZoom(series.data.length);
            renderChart(latestStats);
          });
          window.addEventListener("mousemove", handleChartDragMove);
          window.addEventListener("mouseup", handleChartDragEnd);
          canvas.addEventListener("mouseleave", () => {
            handleChartPointerLeave();
            handleChartDragEnd();
          });

          const buttons = document.querySelectorAll("#chartRangeButtons .range-btn");
          for (const button of buttons) {
            if (!(button instanceof HTMLButtonElement)) {
              continue;
            }
            button.addEventListener("click", () => {
              const range = String(button.dataset.range ?? "");
              if (range === "reset") {
                const series = getSeriesForRange(latestStats, chartState.range);
                resetChartZoom(series.data.length);
                renderChart(latestStats);
                return;
              }
              handleChartRangeSelection(range, true);
            });
          }
        }

        function updateCtfChartPointerFromEvent(event) {
          const canvas = document.getElementById("ctfVisitsChart");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          const rect = canvas.getBoundingClientRect();
          ctfChartState.pointerX = event.clientX - rect.left;
          ctfChartState.pointerY = event.clientY - rect.top;
        }

        function handleCtfChartPointerEnter(event) {
          ctfChartState.pointerInside = true;
          updateCtfChartPointerFromEvent(event);
          renderCtfChart(latestStats);
        }

        function handleCtfChartPointerMove(event) {
          ctfChartState.pointerInside = true;
          updateCtfChartPointerFromEvent(event);
          renderCtfChart(latestStats);
        }

        function handleCtfChartPointerLeave() {
          ctfChartState.pointerInside = false;
          hideCtfChartTooltip();
          renderCtfChart(latestStats);
        }

        function handleCtfChartRangeSelection(range) {
          if (!["1h", "24h", "7d", "30d"].includes(range)) {
            return;
          }
          ctfChartState.range = range;
          setActiveCtfRangeButton(ctfChartState.range);
          renderCtfChart(latestStats);
        }

        function initCtfChartControls() {
          const canvas = document.getElementById("ctfVisitsChart");
          if (!(canvas instanceof HTMLCanvasElement)) {
            return;
          }
          if (canvas.dataset.boundEvents === "1") {
            return;
          }
          canvas.dataset.boundEvents = "1";
          canvas.addEventListener("mouseenter", handleCtfChartPointerEnter);
          canvas.addEventListener("mousemove", handleCtfChartPointerMove);
          canvas.addEventListener("mouseleave", handleCtfChartPointerLeave);

          const buttons = document.querySelectorAll("#ctfChartRangeButtons .range-btn");
          for (const button of buttons) {
            if (!(button instanceof HTMLButtonElement)) {
              continue;
            }
            button.addEventListener("click", () => {
              const range = String(button.dataset.range ?? "");
              handleCtfChartRangeSelection(range);
            });
          }
        }

        async function renderDashboard(stats) {
          latestStats = stats;
          renderTotals(stats);
          renderCtfTotals(stats);
          renderChart(stats);
          renderCtfChart(stats);
          renderResourceStrip(stats);
          renderResponseCards(stats);
          renderCountryList(stats);
          renderTopPages(stats);
          renderRecentAccess(stats);
          renderCtfTopPaths(stats);
          renderCtfChallengeActivity(stats);
          renderCtfRecentAccess(stats);
          renderGeneratedAt(stats);
          renderCtfGeneratedAt(stats);
          renderGlobe(stats);
        }

        async function fetchStats() {
          try {
            const res = await fetch(statsEndpoint + "&_=" + Date.now(), {
              cache: "no-store",
              headers: { "Accept": "application/json" },
            });
            if (!res.ok) {
              return;
            }
            const payload = await res.json();
            await renderDashboard(payload);
          } catch (error) {
            // Keep the last rendered stats on temporary network errors.
          }
        }

        function startAutoRefresh() {
          if (refreshTimerId !== null) {
            window.clearInterval(refreshTimerId);
          }
          refreshTimerId = window.setInterval(fetchStats, refreshIntervalMs);
        }

        function handleVisibilityChange() {
          if (!document.hidden) {
            fetchStats();
          }
        }

        function onResize() {
          if (chartResizeTimer !== null) {
            window.clearTimeout(chartResizeTimer);
          }
          chartResizeTimer = window.setTimeout(() => {
            resizeGlobe();
            renderChart(latestStats);
            renderCtfChart(latestStats);
          }, 120);

          const hasStoredPref = readStoredSidebarPreference() !== null;
          if (!hasStoredPref && isMobileViewport()) {
            applySidebarCollapsed(true, false);
          } else if (!hasStoredPref && !isMobileViewport()) {
            applySidebarCollapsed(false, false);
          }
        }

        window.addEventListener("resize", onResize);
        document.addEventListener("visibilitychange", handleVisibilityChange);
        initSidebarToggle();
        initAdminSidebarMenu();
        initAssistantChat();
        initChartControls();
        initCtfChartControls();
        setActiveRangeButton(chartState.range);
        setActiveCtfRangeButton(ctfChartState.range);
        renderDashboard(initialStats);
        setActiveAdminSection("section-analytics");
        startAutoRefresh();
      })();
    </script>
  <?php endif; ?>
</body>
</html>

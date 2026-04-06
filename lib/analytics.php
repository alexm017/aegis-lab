<?php
declare(strict_types=1);

function zs_analytics_data_path(): string
{
  return __DIR__ . '/../data/analytics.json';
}

function zs_analytics_country_cache_path(): string
{
  return __DIR__ . '/../data/ip_country_cache.json';
}

function zs_analytics_viewport_rate_limit_path(): string
{
  return __DIR__ . '/../data/analytics_viewport_rate_limit.json';
}

function zs_analytics_default_store(): array
{
  return [
    'visits' => [],
    'viewport_samples' => [],
    'updated_at' => 0,
  ];
}

function zs_analytics_now(): int
{
  return time();
}

function zs_analytics_timezone(): DateTimeZone
{
  static $tz = null;
  if ($tz instanceof DateTimeZone) {
    return $tz;
  }

  $tzName = (string)getenv('ZS_ANALYTICS_TZ');
  if ($tzName === '') {
    $tzName = 'Europe/Bucharest';
  }

  try {
    $tz = new DateTimeZone($tzName);
  } catch (Throwable $e) {
    $tz = new DateTimeZone('Europe/Bucharest');
  }

  return $tz;
}

function zs_analytics_format_ts(int $ts, string $format): string
{
  return (new DateTimeImmutable('@' . $ts))
    ->setTimezone(zs_analytics_timezone())
    ->format($format);
}

function zs_analytics_fetch_json(string $url, float $timeoutSeconds = 1.4): ?array
{
  $headers = "User-Agent: AegisLabAnalytics/1.0\r\nAccept: application/json\r\n";
  $context = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => max(0.3, $timeoutSeconds),
      'ignore_errors' => true,
      'header' => $headers,
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true,
    ],
  ]);

  $raw = @file_get_contents($url, false, $context);
  if (!is_string($raw) || trim($raw) === '') {
    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      if ($ch !== false) {
        curl_setopt_array($ch, [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT_MS => (int)round($timeoutSeconds * 1000),
          CURLOPT_CONNECTTIMEOUT_MS => (int)round(min(800, $timeoutSeconds * 500)),
          CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: AegisLabAnalytics/1.0',
          ],
          CURLOPT_FOLLOWLOCATION => false,
        ]);
        $payload = curl_exec($ch);
        curl_close($ch);
        if (is_string($payload) && trim($payload) !== '') {
          $raw = $payload;
        }
      }
    }
  }

  if (!is_string($raw) || trim($raw) === '') {
    return null;
  }

  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : null;
}

function zs_analytics_normalize_country_code(string $value): string
{
  $code = strtoupper(trim($value));
  if ($code === '' || $code === 'XX' || $code === 'T1') {
    return 'UN';
  }
  if (!preg_match('/^[A-Z]{2}$/', $code)) {
    return 'UN';
  }
  return $code;
}

function zs_analytics_country_from_headers(): string
{
  // Geo-IP resolution is intentionally disabled.
  return 'UN';
}

function zs_analytics_is_private_ip(string $ip): bool
{
  $trimmed = trim($ip);
  if ($trimmed === '' || $trimmed === '0.0.0.0' || $trimmed === '::1' || $trimmed === '127.0.0.1') {
    return true;
  }

  if (filter_var($trimmed, FILTER_VALIDATE_IP) === false) {
    return false;
  }

  $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
  $validated = filter_var($trimmed, FILTER_VALIDATE_IP, $flags);
  return $validated === false;
}

function zs_analytics_is_public_ip(string $ip): bool
{
  $trimmed = trim($ip);
  if ($trimmed === '') {
    return false;
  }
  $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
  return filter_var($trimmed, FILTER_VALIDATE_IP, $flags) !== false;
}

function zs_analytics_ignored_ips(): array
{
  static $cached = null;
  if (is_array($cached)) {
    return $cached;
  }

  $ips = ['127.0.0.1', '::1'];
  $extra = trim((string)getenv('ZS_ANALYTICS_IGNORE_IPS'));
  if ($extra !== '') {
    foreach (preg_split('/[\s,;]+/', $extra) as $entry) {
      $candidate = trim((string)$entry);
      if ($candidate !== '') {
        $ips[] = $candidate;
      }
    }
  }

  $clean = [];
  foreach ($ips as $ip) {
    $candidate = trim((string)$ip);
    if ($candidate === '') {
      continue;
    }
    $clean[$candidate] = true;
  }

  $cached = array_keys($clean);
  return $cached;
}

function zs_analytics_is_ignored_ip(string $ip): bool
{
  $candidate = trim($ip);
  if ($candidate === '') {
    return false;
  }
  return in_array($candidate, zs_analytics_ignored_ips(), true);
}

function zs_analytics_ignored_ip_hashes(): array
{
  static $cached = null;
  if (is_array($cached)) {
    return $cached;
  }

  $hashes = [];
  foreach (zs_analytics_ignored_ips() as $ip) {
    $hashes[zs_analytics_ip_hash($ip)] = true;
  }
  $cached = $hashes;
  return $cached;
}

function zs_analytics_country_cache_load(): array
{
  if (isset($GLOBALS['_zs_country_cache_loaded']) && $GLOBALS['_zs_country_cache_loaded'] === true) {
    return (array)($GLOBALS['_zs_country_cache_data'] ?? []);
  }
  $GLOBALS['_zs_country_cache_loaded'] = true;

  $path = zs_analytics_country_cache_path();
  if (!is_file($path)) {
    $GLOBALS['_zs_country_cache_data'] = [];
    return [];
  }

  $raw = @file_get_contents($path);
  if (!is_string($raw) || trim($raw) === '') {
    $GLOBALS['_zs_country_cache_data'] = [];
    return [];
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    $GLOBALS['_zs_country_cache_data'] = [];
    return [];
  }

  $GLOBALS['_zs_country_cache_data'] = $decoded;
  return $decoded;
}

function zs_analytics_country_cache_save(array $cache): void
{
  $GLOBALS['_zs_country_cache_loaded'] = true;
  $GLOBALS['_zs_country_cache_data'] = $cache;

  $path = zs_analytics_country_cache_path();
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
  $json = json_encode($cache, JSON_UNESCAPED_SLASHES);
  if (!is_string($json)) {
    return;
  }
  @file_put_contents($path, $json, LOCK_EX);
}

function zs_analytics_country_cache_get(string $ip): string
{
  $ip = trim($ip);
  if ($ip === '') {
    return 'UN';
  }

  $cache = zs_analytics_country_cache_load();
  if (!isset($cache[$ip]) || !is_array($cache[$ip])) {
    return 'UN';
  }

  $entry = $cache[$ip];
  $code = zs_analytics_normalize_country_code((string)($entry['country'] ?? 'UN'));
  $updatedAt = isset($entry['updated_at']) ? (int)$entry['updated_at'] : 0;
  $maxAge = 30 * 86400;
  if ($updatedAt > 0 && (zs_analytics_now() - $updatedAt) > $maxAge) {
    return 'UN';
  }
  return $code;
}

function zs_analytics_country_cache_set(string $ip, string $country): void
{
  $ip = trim($ip);
  if ($ip === '') {
    return;
  }

  $code = zs_analytics_normalize_country_code($country);
  if ($code === 'UN') {
    return;
  }

  $cache = zs_analytics_country_cache_load();
  $cache[$ip] = [
    'country' => $code,
    'updated_at' => zs_analytics_now(),
  ];
  zs_analytics_country_cache_save($cache);
}

function zs_analytics_country_from_remote(string $ip): string
{
  // Geo-IP resolution is intentionally disabled.
  $ip = trim($ip);
  return 'UN';
}

function zs_analytics_country_from_ip(string $ip): string
{
  // Geo-IP resolution is intentionally disabled.
  $ip = trim($ip);
  return 'UN';
}

function zs_analytics_client_ip(): string
{
  $remoteAddr = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
  $trustProxy = false;

  $trustProxyEnv = strtolower(trim((string)getenv('ZS_ANALYTICS_TRUST_PROXY')));
  if (in_array($trustProxyEnv, ['1', 'true', 'yes', 'on'], true)) {
    $trustProxy = true;
  }

  if (!$trustProxy && $remoteAddr !== '' && zs_analytics_is_private_ip($remoteAddr)) {
    $trustProxy = true;
  }

  if ($trustProxy && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    return trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
  }

  if ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim((string)($parts[0] ?? ''));
    if ($ip !== '') {
      return $ip;
    }
  }

  if ($remoteAddr !== '') {
    return $remoteAddr;
  }

  return '0.0.0.0';
}

function zs_analytics_ip_hash(string $ip): string
{
  return hash('sha256', 'zs_v1|' . $ip . '|aegis');
}

function zs_analytics_public_ip_label_from_hash(string $ipHash): string
{
  $hash = strtolower(trim($ipHash));
  if ($hash === '') {
    return 'anon-unknown';
  }
  $prefix = substr($hash, 0, 12);
  if ($prefix === '') {
    return 'anon-unknown';
  }
  return 'anon-' . $prefix;
}

function zs_analytics_public_ip_label(string $ip): string
{
  return zs_analytics_public_ip_label_from_hash(zs_analytics_ip_hash($ip));
}

function zs_analytics_device_type(string $ua): string
{
  $needle = strtolower(trim($ua));
  if ($needle === '') {
    return 'Unknown';
  }
  if ($needle === 'mozilla/5.0' || $needle === '-' || $needle === 'unknown') {
    return 'Bot';
  }
  $botHints = [
    'bot', 'spider', 'crawler', 'curl', 'wget', 'python', 'httpclient',
    'go-http-client', 'okhttp', 'scrapy', 'headless', 'selenium',
    'phantomjs', 'uptime', 'monitor', 'scan', 'nikto', 'sqlmap',
  ];
  foreach ($botHints as $hint) {
    if (strpos($needle, $hint) !== false) {
      return 'Bot';
    }
  }
  if (strpos($needle, 'ipad') !== false || strpos($needle, 'tablet') !== false) {
    return 'Tablet';
  }
  if (strpos($needle, 'mobi') !== false || strpos($needle, 'android') !== false || strpos($needle, 'iphone') !== false) {
    return 'Mobile';
  }
  return 'Desktop';
}

function zs_analytics_is_bot_user_agent(string $ua): bool
{
  return zs_analytics_device_type($ua) === 'Bot';
}

function zs_analytics_device_platform(string $ua): string
{
  $needle = strtolower($ua);
  if (strpos($needle, 'windows') !== false) {
    return 'Windows';
  }
  if (strpos($needle, 'android') !== false) {
    return 'Android';
  }
  if (strpos($needle, 'iphone') !== false || strpos($needle, 'ipad') !== false || strpos($needle, 'ios') !== false) {
    return 'iOS';
  }
  if (strpos($needle, 'mac os') !== false || strpos($needle, 'macintosh') !== false) {
    return 'macOS';
  }
  if (strpos($needle, 'linux') !== false) {
    return 'Linux';
  }
  return 'Unknown';
}

function zs_analytics_device_browser(string $ua): string
{
  $needle = strtolower($ua);
  if (strpos($needle, 'edg/') !== false || strpos($needle, 'edge/') !== false) {
    return 'Edge';
  }
  if (strpos($needle, 'opr/') !== false || strpos($needle, 'opera') !== false) {
    return 'Opera';
  }
  if (strpos($needle, 'firefox') !== false) {
    return 'Firefox';
  }
  if (strpos($needle, 'chrome') !== false && strpos($needle, 'chromium') === false) {
    return 'Chrome';
  }
  if (strpos($needle, 'safari') !== false && strpos($needle, 'chrome') === false) {
    return 'Safari';
  }
  if (strpos($needle, 'curl') !== false) {
    return 'curl';
  }
  if (strpos($needle, 'python') !== false) {
    return 'Python';
  }
  return 'Other';
}

function zs_analytics_device_label(string $ua): string
{
  return zs_analytics_device_type($ua) . ' | ' . zs_analytics_device_platform($ua) . ' | ' . zs_analytics_device_browser($ua);
}

function zs_analytics_should_track(): bool
{
  if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
    return false;
  }

  $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
  if ($method !== 'GET' && $method !== 'HEAD') {
    return false;
  }

  $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
  if (in_array($script, ['dashboard.php', 'dashboard-stats.php'], true)) {
    return false;
  }

  $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
  if ($requestUri !== '' && strpos($requestUri, '/dashboard') === 0) {
    return false;
  }

  return true;
}

function zs_analytics_read_locked($handle): array
{
  rewind($handle);
  $raw = stream_get_contents($handle);
  if (!is_string($raw) || $raw === '') {
    return zs_analytics_default_store();
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return zs_analytics_default_store();
  }

  if (!isset($decoded['visits']) || !is_array($decoded['visits'])) {
    $decoded['visits'] = [];
  }
  if (!isset($decoded['viewport_samples']) || !is_array($decoded['viewport_samples'])) {
    $decoded['viewport_samples'] = [];
  }
  if (!isset($decoded['updated_at']) || !is_int($decoded['updated_at'])) {
    $decoded['updated_at'] = 0;
  }

  return $decoded;
}

function zs_analytics_prune_visits(array $visits, int $now): array
{
  $minTs = $now - (120 * 86400);
  $filtered = [];
  foreach ($visits as $visit) {
    if (!is_array($visit)) {
      continue;
    }
    $ts = isset($visit['ts']) ? (int)$visit['ts'] : 0;
    if ($ts < $minTs) {
      continue;
    }
    $filtered[] = $visit;
  }

  $maxEntries = 50000;
  if (count($filtered) > $maxEntries) {
    $filtered = array_slice($filtered, -$maxEntries);
  }

  return $filtered;
}

function zs_analytics_prune_viewport_samples(array $samples, int $now): array
{
  $minTs = $now - (30 * 86400);
  $filtered = [];
  foreach ($samples as $sample) {
    if (!is_array($sample)) {
      continue;
    }
    $ts = isset($sample['ts']) ? (int)$sample['ts'] : 0;
    if ($ts < $minTs) {
      continue;
    }
    $filtered[] = $sample;
  }

  $maxEntries = 6000;
  if (count($filtered) > $maxEntries) {
    $filtered = array_slice($filtered, -$maxEntries);
  }

  return $filtered;
}

function zs_analytics_write_locked($handle, array $store): void
{
  $json = json_encode($store, JSON_UNESCAPED_SLASHES);
  if (!is_string($json)) {
    return;
  }
  rewind($handle);
  ftruncate($handle, 0);
  fwrite($handle, $json);
  fflush($handle);
}

function zs_analytics_record_visit(): void
{
  if (!zs_analytics_should_track()) {
    return;
  }

  $path = zs_analytics_data_path();
  $dir = dirname($path);
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  $handle = fopen($path, 'c+');
  if ($handle === false) {
    return;
  }

  if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    return;
  }

  $store = zs_analytics_read_locked($handle);
  $now = zs_analytics_now();
  $store['visits'] = zs_analytics_prune_visits($store['visits'], $now);
  $store['viewport_samples'] = zs_analytics_prune_viewport_samples($store['viewport_samples'], $now);

  $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
  if (!is_string($requestPath) || $requestPath === '') {
    $requestPath = '/';
  }

  $clientIp = zs_analytics_client_ip();
  if (zs_analytics_is_ignored_ip($clientIp)) {
    flock($handle, LOCK_UN);
    fclose($handle);
    return;
  }
  $ipHash = zs_analytics_ip_hash($clientIp);
  $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240);

  $store['visits'][] = [
    'ts' => $now,
    'ip' => $ipHash,
    'country' => 'UN',
    'path' => substr($requestPath, 0, 240),
    'ua' => $userAgent,
    'device' => zs_analytics_device_label($userAgent),
    'is_bot' => zs_analytics_is_bot_user_agent($userAgent),
  ];
  $store['updated_at'] = $now;

  zs_analytics_write_locked($handle, $store);
  flock($handle, LOCK_UN);
  fclose($handle);
}

function zs_analytics_parse_int_range(mixed $value, int $min, int $max): ?int
{
  if (is_string($value)) {
    $value = trim($value);
  }

  if (!is_numeric($value)) {
    return null;
  }

  $parsed = (int)round((float)$value);
  if ($parsed < $min || $parsed > $max) {
    return null;
  }
  return $parsed;
}

function zs_analytics_parse_float_range(mixed $value, float $min, float $max): ?float
{
  if (is_string($value)) {
    $value = trim($value);
  }

  if (!is_numeric($value)) {
    return null;
  }

  $parsed = (float)$value;
  if (!is_finite($parsed) || $parsed < $min || $parsed > $max) {
    return null;
  }
  return $parsed;
}

function zs_analytics_record_viewport_sample(array $input): bool
{
  $vw = zs_analytics_parse_int_range($input['vw'] ?? null, 100, 10000);
  $vh = zs_analytics_parse_int_range($input['vh'] ?? null, 100, 10000);
  if ($vw === null || $vh === null) {
    return false;
  }

  $sw = zs_analytics_parse_int_range($input['sw'] ?? null, 100, 16000);
  $sh = zs_analytics_parse_int_range($input['sh'] ?? null, 100, 16000);
  $dpr = zs_analytics_parse_float_range($input['dpr'] ?? null, 0.5, 8.0);

  $path = trim((string)($input['path'] ?? '/'));
  if ($path === '' || $path[0] !== '/') {
    $path = '/';
  }
  $path = substr($path, 0, 240);

  $reason = trim((string)($input['reason'] ?? ''));
  $reason = substr($reason, 0, 40);

  $storePath = zs_analytics_data_path();
  $dir = dirname($storePath);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }

  $handle = fopen($storePath, 'c+');
  if ($handle === false) {
    return false;
  }

  if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    return false;
  }

  $store = zs_analytics_read_locked($handle);
  $now = zs_analytics_now();
  $store['visits'] = zs_analytics_prune_visits($store['visits'], $now);
  $store['viewport_samples'] = zs_analytics_prune_viewport_samples($store['viewport_samples'], $now);

  $clientIp = zs_analytics_client_ip();
  $ipHash = zs_analytics_ip_hash($clientIp);
  $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 240);
  $isBot = zs_analytics_is_bot_user_agent($userAgent);
  if ($isBot && zs_analytics_is_ignored_ip($clientIp)) {
    flock($handle, LOCK_UN);
    fclose($handle);
    return false;
  }

  $store['viewport_samples'][] = [
    'ts' => $now,
    'ip' => $ipHash,
    'country' => 'UN',
    'path' => $path,
    'ua' => $userAgent,
    'device' => zs_analytics_device_label($userAgent),
    'vw' => $vw,
    'vh' => $vh,
    'sw' => $sw,
    'sh' => $sh,
    'dpr' => $dpr,
    'reason' => $reason,
  ];
  $store['updated_at'] = $now;

  zs_analytics_write_locked($handle, $store);
  flock($handle, LOCK_UN);
  fclose($handle);
  return true;
}

function zs_analytics_is_same_origin_or_no_origin(): bool
{
  $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
  if ($origin === '') {
    return true;
  }

  $originParts = parse_url($origin);
  if (!is_array($originParts)) {
    return false;
  }

  $originScheme = strtolower(trim((string)($originParts['scheme'] ?? '')));
  if ($originScheme !== 'http' && $originScheme !== 'https') {
    return false;
  }

  $originHost = strtolower(trim((string)($originParts['host'] ?? '')));
  if ($originHost === '') {
    return false;
  }

  $requestHostRaw = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
  $requestHost = strtolower((string)(preg_replace('/:\d+$/', '', $requestHostRaw) ?? ''));
  if ($requestHost === '') {
    return false;
  }

  return hash_equals($requestHost, $originHost);
}

function zs_analytics_viewport_rate_limit_allow(string $ipHash, int $windowSeconds = 60, int $maxRequests = 60): bool
{
  $key = trim($ipHash);
  if ($key === '') {
    return true;
  }

  $path = zs_analytics_viewport_rate_limit_path();
  $dir = dirname($path);
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }

  $handle = @fopen($path, 'c+');
  if ($handle === false) {
    return true;
  }

  if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    return true;
  }

  $raw = stream_get_contents($handle);
  $store = [];
  if (is_string($raw) && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $store = $decoded;
    }
  }

  $now = zs_analytics_now();
  $pruneBefore = $now - max(300, ($windowSeconds * 20));
  foreach ($store as $hash => $timestamps) {
    if (!is_array($timestamps)) {
      unset($store[$hash]);
      continue;
    }
    $trimmed = [];
    foreach ($timestamps as $ts) {
      $tsInt = (int)$ts;
      if ($tsInt >= $pruneBefore && $tsInt <= $now) {
        $trimmed[] = $tsInt;
      }
    }
    if ($trimmed === []) {
      unset($store[$hash]);
      continue;
    }
    $store[$hash] = $trimmed;
  }

  $windowStart = $now - max(1, $windowSeconds);
  $entry = isset($store[$key]) && is_array($store[$key]) ? $store[$key] : [];
  $hitsWindow = [];
  foreach ($entry as $ts) {
    $tsInt = (int)$ts;
    if ($tsInt >= $windowStart && $tsInt <= $now) {
      $hitsWindow[] = $tsInt;
    }
  }

  $allowed = count($hitsWindow) < $maxRequests;
  if ($allowed) {
    $hitsWindow[] = $now;
  }
  $store[$key] = $hitsWindow;

  $json = json_encode($store, JSON_UNESCAPED_SLASHES);
  if (is_string($json)) {
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, $json);
    fflush($handle);
  }

  flock($handle, LOCK_UN);
  fclose($handle);
  return $allowed;
}

function zs_analytics_handle_viewport_endpoint(): void
{
  $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
  if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"ok":false,"error":"Method not allowed"}';
    exit;
  }

  if (!zs_analytics_is_same_origin_or_no_origin()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"ok":false,"error":"Forbidden origin"}';
    exit;
  }

  $clientHash = zs_analytics_ip_hash(zs_analytics_client_ip());
  if (!zs_analytics_viewport_rate_limit_allow($clientHash, 60, 60)) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"ok":false,"error":"Rate limit exceeded"}';
    exit;
  }

  $payload = [];
  $raw = file_get_contents('php://input');
  if (is_string($raw) && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $payload = $decoded;
    }
  }
  if ($payload === [] && !empty($_POST)) {
    $payload = $_POST;
  }

  $recorded = zs_analytics_record_viewport_sample(is_array($payload) ? $payload : []);
  header('Content-Type: application/json; charset=utf-8');
  echo $recorded ? '{"ok":true}' : '{"ok":false}';
  exit;
}

function zs_analytics_load_store(): array
{
  $path = zs_analytics_data_path();
  if (!is_file($path)) {
    return zs_analytics_default_store();
  }
  $raw = file_get_contents($path);
  if (!is_string($raw) || $raw === '') {
    return zs_analytics_default_store();
  }
  $decoded = json_decode($raw, true);
  if (!is_array($decoded) || !isset($decoded['visits']) || !is_array($decoded['visits'])) {
    return zs_analytics_default_store();
  }
  return $decoded;
}

function zs_analytics_day_labels(int $days, int $now): array
{
  $labels = [];
  for ($i = $days - 1; $i >= 0; $i--) {
    $ts = $now - ($i * 86400);
    $key = zs_analytics_format_ts($ts, 'Y-m-d');
    $labels[$key] = [
      'key' => $key,
      'label' => zs_analytics_format_ts($ts, 'M j'),
      'count' => 0,
    ];
  }
  return $labels;
}

function zs_analytics_hour_labels(int $hours, int $now): array
{
  $labels = [];
  for ($i = $hours - 1; $i >= 0; $i--) {
    $ts = $now - ($i * 3600);
    $key = zs_analytics_format_ts($ts, 'Y-m-d H:00');
    $labels[$key] = [
      'key' => $key,
      'label' => zs_analytics_format_ts($ts, 'H:00'),
      'count' => 0,
    ];
  }
  return $labels;
}

function zs_analytics_minute_labels(int $minutes, int $now): array
{
  $labels = [];
  for ($i = $minutes - 1; $i >= 0; $i--) {
    $ts = $now - ($i * 60);
    $key = zs_analytics_format_ts($ts, 'Y-m-d H:i');
    $labels[$key] = [
      'key' => $key,
      'label' => zs_analytics_format_ts($ts, 'H:i'),
      'count' => 0,
    ];
  }
  return $labels;
}

function zs_analytics_build_stats(): array
{
  $store = zs_analytics_load_store();
  $visits = isset($store['visits']) && is_array($store['visits']) ? $store['visits'] : [];
  $viewportSamples = isset($store['viewport_samples']) && is_array($store['viewport_samples']) ? $store['viewport_samples'] : [];
  $now = zs_analytics_now();
  $cutoff5m = $now - 300;
  $cutoff1h = $now - 3600;
  $cutoff24h = $now - (24 * 3600);
  $cutoff7d = $now - (7 * 86400);
  $cutoff30d = $now - (30 * 86400);

  $minutes60 = zs_analytics_minute_labels(60, $now);
  $hours24 = zs_analytics_hour_labels(24, $now);
  $days7 = zs_analytics_day_labels(7, $now);
  $days30 = zs_analytics_day_labels(30, $now);

  $totalVisits = 0;
  $allUnique = [];
  $activeIps = [];
  $visits7 = 0;
  $visits30 = 0;
  $unique7 = [];
  $unique30 = [];
  $country30 = [];
  $pageViews30 = [];
  $pageUnique30 = [];
  $recentAccess30 = [];
  $ignoredHashes = zs_analytics_ignored_ip_hashes();

  foreach ($visits as $visit) {
    if (!is_array($visit)) {
      continue;
    }
    $ts = isset($visit['ts']) ? (int)$visit['ts'] : 0;
    if ($ts <= 0) {
      continue;
    }

    $ipHash = isset($visit['ip']) ? (string)$visit['ip'] : '';
    $ipRaw = isset($visit['ip_raw']) ? trim((string)$visit['ip_raw']) : '';
    if ($ipHash === '' && $ipRaw !== '') {
      $ipHash = zs_analytics_ip_hash($ipRaw);
    }
    if (($ipRaw !== '' && zs_analytics_is_ignored_ip($ipRaw)) || ($ipHash !== '' && isset($ignoredHashes[$ipHash]))) {
      continue;
    }
    $ua = isset($visit['ua']) ? (string)$visit['ua'] : '';
    $device = $ua !== '' ? zs_analytics_device_label($ua) : trim((string)($visit['device'] ?? ''));
    if ($device === '') {
      $device = 'Unknown | Unknown | Other';
    }
    $device = str_replace('•', '|', $device);
    $country = 'UN';
    $path = isset($visit['path']) ? trim((string)$visit['path']) : '/';
    if ($path === '') {
      $path = '/';
    }
    if ($path !== '/' && substr($path, -1) === '/') {
      $path = rtrim($path, '/');
      if ($path === '') {
        $path = '/';
      }
    }
    $dayKey = zs_analytics_format_ts($ts, 'Y-m-d');
    $hourKey = zs_analytics_format_ts($ts, 'Y-m-d H:00');
    $minuteKey = zs_analytics_format_ts($ts, 'Y-m-d H:i');

    $totalVisits++;
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
      $visits7++;
      if ($ipHash !== '') {
        $unique7[$ipHash] = true;
      }
      if (isset($days7[$dayKey])) {
        $days7[$dayKey]['count']++;
      }
    }

    if ($ts >= $cutoff30d) {
      $visits30++;
      if ($ipHash !== '') {
        $unique30[$ipHash] = true;
      }
      if (isset($days30[$dayKey])) {
        $days30[$dayKey]['count']++;
      }
      if (!isset($country30[$country])) {
        $country30[$country] = 0;
      }
      $country30[$country]++;

      if (!isset($pageViews30[$path])) {
        $pageViews30[$path] = 0;
      }
      $pageViews30[$path]++;
      if ($ipHash !== '') {
        if (!isset($pageUnique30[$path])) {
          $pageUnique30[$path] = [];
        }
        $pageUnique30[$path][$ipHash] = true;
      }

      $recentAccess30[] = [
        'ts' => $ts,
        'country' => $country,
        'path' => $path,
        'ip' => zs_analytics_public_ip_label_from_hash($ipHash),
        'device' => $device,
      ];
    }
  }

  arsort($country30);
  $countries = [];
  foreach ($country30 as $country => $count) {
    if ($country === 'UN') {
      continue;
    }
    $countries[] = [
      'code' => $country,
      'count' => (int)$count,
    ];
  }

  $days7Out = [];
  $hours24Out = [];
  $minutes60Out = [];
  foreach ($minutes60 as $entry) {
    $minutes60Out[] = $entry;
  }
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

  arsort($pageViews30);
  $topPages30 = [];
  foreach ($pageViews30 as $path => $views) {
    $topPages30[] = [
      'path' => $path,
      'views' => (int)$views,
      'unique' => isset($pageUnique30[$path]) ? count($pageUnique30[$path]) : 0,
    ];
  }

  usort(
    $recentAccess30,
    static function (array $a, array $b): int {
      return ((int)($b['ts'] ?? 0)) <=> ((int)($a['ts'] ?? 0));
    }
  );

  $recentViewportSamples = [];
  foreach ($viewportSamples as $sample) {
    if (!is_array($sample)) {
      continue;
    }
    $ts = isset($sample['ts']) ? (int)$sample['ts'] : 0;
    if ($ts <= 0 || $ts < $cutoff30d) {
      continue;
    }
    $sampleIpHash = trim((string)($sample['ip'] ?? ''));
    if ($sampleIpHash === '') {
      $legacyIpRaw = trim((string)($sample['ip_raw'] ?? ''));
      if ($legacyIpRaw !== '') {
        $sampleIpHash = zs_analytics_ip_hash($legacyIpRaw);
      }
    }
    $recentViewportSamples[] = [
      'ts' => $ts,
      'ip' => zs_analytics_public_ip_label_from_hash($sampleIpHash),
      'country' => 'UN',
      'path' => isset($sample['path']) ? (string)$sample['path'] : '/',
      'device' => isset($sample['device']) ? (string)$sample['device'] : 'Unknown',
      'viewport' => (
        isset($sample['vw'], $sample['vh']) &&
        is_numeric($sample['vw']) &&
        is_numeric($sample['vh'])
      ) ? ((int)$sample['vw'] . 'x' . (int)$sample['vh']) : 'Unknown',
      'screen' => (
        isset($sample['sw'], $sample['sh']) &&
        is_numeric($sample['sw']) &&
        is_numeric($sample['sh'])
      ) ? ((int)$sample['sw'] . 'x' . (int)$sample['sh']) : 'Unknown',
      'dpr' => isset($sample['dpr']) && is_numeric($sample['dpr']) ? (float)$sample['dpr'] : null,
      'reason' => isset($sample['reason']) ? (string)$sample['reason'] : '',
    ];
  }

  usort(
    $recentViewportSamples,
    static function (array $a, array $b): int {
      return ((int)($b['ts'] ?? 0)) <=> ((int)($a['ts'] ?? 0));
    }
  );

  return [
    'generated_at' => zs_analytics_format_ts($now, DATE_ATOM),
    'totals' => [
      'visits_all' => $totalVisits,
      'unique_all' => count($allUnique),
      'active_now' => count($activeIps),
      'visits_7d' => $visits7,
      'visits_30d' => $visits30,
      'unique_7d' => count($unique7),
      'unique_30d' => count($unique30),
    ],
    'series' => [
      'minutes_60' => $minutes60Out,
      'hours_24' => $hours24Out,
      'days_7' => $days7Out,
      'days_30' => $days30Out,
    ],
    'countries_30d' => array_slice($countries, 0, 40),
    'top_pages_30d' => array_slice($topPages30, 0, 25),
    'recent_access_30d' => array_slice($recentAccess30, 0, 120),
    'recent_viewports_30d' => array_slice($recentViewportSamples, 0, 120),
  ];
}

function zs_analytics_auto_track(): void
{
  static $didRun = false;
  if ($didRun) {
    return;
  }
  $didRun = true;
  zs_analytics_record_visit();
}

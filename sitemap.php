<?php
declare(strict_types=1);

function zs_sitemap_xml_escape(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function zs_sitemap_base_url(): string
{
  $configured = trim((string)getenv('SITEMAP_BASE_URL'));
  if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
    return rtrim($configured, '/');
  }
  return 'https://aegislab.ro';
}

function zs_sitemap_add_url(array &$urls, string $route, string $filePath, string $changeFreq, string $priority): void
{
  if (!is_file($filePath)) {
    return;
  }

  $modifiedAt = filemtime($filePath);
  if (!is_int($modifiedAt) || $modifiedAt <= 0) {
    $modifiedAt = time();
  }

  $urls[] = [
    'route' => $route,
    'lastmod' => gmdate('Y-m-d\TH:i:s\Z', $modifiedAt),
    'changefreq' => $changeFreq,
    'priority' => $priority,
  ];
}

function zs_sitemap_event_routes(string $eventsIndexPath): array
{
  if (!is_file($eventsIndexPath)) {
    return [];
  }

  $content = file_get_contents($eventsIndexPath);
  if (!is_string($content) || $content === '') {
    return [];
  }

  $matches = [];
  $ok = preg_match_all("/'url'\\s*=>\\s*'([^']+)'/", $content, $matches);
  if ($ok === false || !isset($matches[1]) || !is_array($matches[1])) {
    return [];
  }

  $routes = [];
  foreach ($matches[1] as $candidate) {
    $route = trim((string)$candidate);
    if ($route === '' || str_starts_with($route, 'http://') || str_starts_with($route, 'https://')) {
      continue;
    }
    if (preg_match('#^/events/[A-Za-z0-9-]+$#', $route) !== 1) {
      continue;
    }
    $routes[$route] = true;
  }

  return array_keys($routes);
}

$root = __DIR__;
$baseUrl = zs_sitemap_base_url();
$urls = [];

zs_sitemap_add_url($urls, '/', $root . '/index.php', 'weekly', '1.0');
zs_sitemap_add_url($urls, '/about', $root . '/about.php', 'monthly', '0.7');
zs_sitemap_add_url($urls, '/members', $root . '/members.php', 'weekly', '0.8');
zs_sitemap_add_url($urls, '/events', $root . '/events.php', 'weekly', '0.9');
zs_sitemap_add_url($urls, '/team-ctf', $root . '/team-ctf.php', 'weekly', '0.7');
zs_sitemap_add_url($urls, '/national-olympiads', $root . '/national-olympiads.php', 'weekly', '0.8');
zs_sitemap_add_url($urls, '/gallery', $root . '/gallery.php', 'monthly', '0.7');

foreach (zs_sitemap_event_routes($root . '/events.php') as $eventRoute) {
  $slug = basename($eventRoute);
  $eventFile = $root . '/events/' . $slug . '.php';
  zs_sitemap_add_url($urls, $eventRoute, $eventFile, 'monthly', '0.7');
}

header('Content-Type: application/xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $entry) {
  $loc = $baseUrl . $entry['route'];
  echo "  <url>\n";
  echo '    <loc>' . zs_sitemap_xml_escape($loc) . "</loc>\n";
  echo '    <lastmod>' . zs_sitemap_xml_escape((string)$entry['lastmod']) . "</lastmod>\n";
  echo '    <changefreq>' . zs_sitemap_xml_escape((string)$entry['changefreq']) . "</changefreq>\n";
  echo '    <priority>' . zs_sitemap_xml_escape((string)$entry['priority']) . "</priority>\n";
  echo "  </url>\n";
}
echo "</urlset>\n";

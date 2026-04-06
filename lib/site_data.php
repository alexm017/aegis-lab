<?php
declare(strict_types=1);

function zs_data_dir(): string
{
  return __DIR__ . '/../data';
}

function zs_ensure_data_dir(): void
{
  $dir = zs_data_dir();
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
}

function zs_read_json_file(string $path, array $default): array
{
  if (!is_file($path)) {
    return $default;
  }

  $raw = file_get_contents($path);
  if ($raw === false) {
    return $default;
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    return $default;
  }

  return $decoded;
}

function zs_write_json_file(string $path, array $payload): bool
{
  zs_ensure_data_dir();
  $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    return false;
  }

  return file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
}

function zs_default_site_content(): array
{
  return [
    'hero_subtitle' => 'We are a High School Cybersecurity team focused on practical offensive and defensive security training.',
    'home_intro' => 'Aegis Lab is a focused high-school cybersecurity team where students learn by building and breaking systems in controlled environments. We train on real workflows used in security operations and competitions so members gain practical, transferable skills.',
    'about_intro' => 'We are a high-school cybersecurity team training future engineers through offensive and defensive security labs. Our workflow combines technical depth with teamwork and communication.',
    'members_intro' => '',
    'contact_email' => 'contact@aegislab.ro',
    'ctf_url' => 'https://ctf.aegislab.ro/',
    'ctf_label' => 'Aegis Lab CTF @ ctf.aegislab.ro',
    'school_address' => 'Strada 1 Decembrie 1918 7, Petroșani',
    'google_maps_url' => 'https://maps.app.goo.gl/rCDbEXarzj5soft39',
    'google_maps_embed_url' => 'https://www.google.com/maps?q=Strada%201%20Decembrie%201918%207%2C%20Petro%C8%99ani&output=embed',
    'discord_url' => 'https://discord.gg/9bjk4FeRZS',
    'youtube_url' => 'https://www.youtube.com/@AegisLabTeam',
    'instagram_url' => 'https://www.instagram.com/aegislab_team/',
    'linkedin_url' => 'https://www.linkedin.com/company/aegis-labteam/',
  ];
}

function zs_site_content_path(): string
{
  return zs_data_dir() . '/site_content.json';
}

function zs_get_site_content(): array
{
  $defaults = zs_default_site_content();
  $stored = zs_read_json_file(zs_site_content_path(), $defaults);
  foreach ($defaults as $key => $defaultValue) {
    if (!array_key_exists($key, $stored) || !is_string($stored[$key])) {
      $stored[$key] = $defaultValue;
    }
  }
  return $stored;
}

function zs_save_site_content(array $content): bool
{
  $defaults = zs_default_site_content();
  foreach ($defaults as $key => $defaultValue) {
    if (!array_key_exists($key, $content) || !is_string($content[$key])) {
      $content[$key] = $defaultValue;
    }
  }
  return zs_write_json_file(zs_site_content_path(), $content);
}

function zs_default_members(): array
{
  return [
    'founder' => [
      'role' => 'Founder',
      'name' => 'AlexM',
      'handle' => '@alexm017',
      'bio' => 'Builds team direction, competition strategy, and training systems across offensive and defensive security practice.',
      'avatar' => '/assets/members/founder-profile.png',
      'initials' => 'ZS',
      'website' => 'https://ctf.aegislab.ro/',
      'discord' => '',
      'github' => '',
      'linkedin' => '',
      'instagram' => '',
      'x' => '',
    ],
    'team' => [
      [
        'role' => 'Web Security',
        'name' => 'Member 1',
        'handle' => '@member1',
        'bio' => '',
        'avatar' => '',
        'initials' => 'M1',
        'website' => '',
        'discord' => '',
        'github' => '',
        'linkedin' => '',
        'instagram' => '',
        'x' => '',
      ],
      [
        'role' => 'Forensics',
        'name' => 'Member 2',
        'handle' => '@member2',
        'bio' => '',
        'avatar' => '',
        'initials' => 'M2',
        'website' => '',
        'discord' => '',
        'github' => '',
        'linkedin' => '',
        'instagram' => '',
        'x' => '',
      ],
      [
        'role' => 'Reverse Engineering',
        'name' => 'Member 3',
        'handle' => '@member3',
        'bio' => '',
        'avatar' => '',
        'initials' => 'M3',
        'website' => '',
        'discord' => '',
        'github' => '',
        'linkedin' => '',
        'instagram' => '',
        'x' => '',
      ],
      [
        'role' => 'Blue Team',
        'name' => 'Member 4',
        'handle' => '@member4',
        'bio' => '',
        'avatar' => '',
        'initials' => 'M4',
        'website' => '',
        'discord' => '',
        'github' => '',
        'linkedin' => '',
        'instagram' => '',
        'x' => '',
      ],
    ],
  ];
}

function zs_members_path(): string
{
  return zs_data_dir() . '/members.json';
}

function zs_normalize_member(array $member, array $fallback = []): array
{
  $defaults = [
    'role' => 'Member',
    'name' => 'Unnamed',
    'handle' => '',
    'bio' => '',
    'avatar' => '',
    'initials' => 'M',
    'website' => '',
    'discord' => '',
    'github' => '',
    'linkedin' => '',
    'instagram' => '',
    'x' => '',
  ];

  $seed = array_merge($defaults, $fallback, $member);
  foreach ($defaults as $key => $value) {
    if (!isset($seed[$key]) || !is_string($seed[$key])) {
      $seed[$key] = $value;
    }
    $seed[$key] = trim($seed[$key]);
  }

  return $seed;
}

function zs_get_members(): array
{
  $defaults = zs_default_members();
  $stored = zs_read_json_file(zs_members_path(), $defaults);

  $founderRaw = isset($stored['founder']) && is_array($stored['founder']) ? $stored['founder'] : $defaults['founder'];
  $founder = zs_normalize_member($founderRaw, $defaults['founder']);

  $team = [];
  $rawTeam = isset($stored['team']) && is_array($stored['team']) ? $stored['team'] : $defaults['team'];
  foreach ($rawTeam as $item) {
    if (!is_array($item)) {
      continue;
    }
    $team[] = zs_normalize_member($item);
  }

  return [
    'founder' => $founder,
    'team' => $team,
  ];
}

function zs_save_members(array $members): bool
{
  $normalized = zs_get_members();

  if (isset($members['founder']) && is_array($members['founder'])) {
    $normalized['founder'] = zs_normalize_member($members['founder'], $normalized['founder']);
  }

  if (isset($members['team']) && is_array($members['team'])) {
    $team = [];
    foreach ($members['team'] as $member) {
      if (!is_array($member)) {
        continue;
      }
      $team[] = zs_normalize_member($member);
    }
    $normalized['team'] = $team;
  }

  return zs_write_json_file(zs_members_path(), $normalized);
}

function zs_escape(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function zs_safe_http_url(string $url, string $fallback = ''): string
{
  $value = trim($url);
  if ($value === '' || strlen($value) > 2048) {
    return $fallback;
  }

  $validated = filter_var($value, FILTER_VALIDATE_URL);
  if (!is_string($validated) || $validated === '') {
    return $fallback;
  }

  $parts = parse_url($validated);
  if (!is_array($parts)) {
    return $fallback;
  }

  $scheme = strtolower(trim((string)($parts['scheme'] ?? '')));
  if ($scheme !== 'http' && $scheme !== 'https') {
    return $fallback;
  }

  return $validated;
}

function zs_safe_mailto_href(string $email, string $fallback = 'contact@aegislab.ro'): string
{
  $candidate = trim($email);
  if ($candidate === '' || !filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
    $candidate = $fallback;
  }
  if (!is_string($candidate) || $candidate === '') {
    $candidate = 'contact@aegislab.ro';
  }
  return 'mailto:' . $candidate;
}

require_once __DIR__ . '/analytics.php';
zs_analytics_auto_track();

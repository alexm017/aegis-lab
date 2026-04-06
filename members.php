<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');
$membersData = zs_get_members();
$founder = $membersData['founder'];
$teamMembers = $membersData['team'];

$canonicalUrl = 'https://aegislab.ro/members';
$pageTitle = 'Members - Aegis Lab';
$pageDescription = 'Meet the Aegis Lab cybersecurity team members and founder. Our team trains weekly in practical offensive and defensive security and prepares for competitions.';
$ogImage = 'https://aegislab.ro/Aegis.png';

$breadcrumbStructuredData = [
  '@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
    [
      '@type' => 'ListItem',
      'position' => 1,
      'name' => 'Home',
      'item' => 'https://aegislab.ro/',
    ],
    [
      '@type' => 'ListItem',
      'position' => 2,
      'name' => 'Members',
      'item' => $canonicalUrl,
    ],
  ],
];
$breadcrumbStructuredDataJson = json_encode(
  $breadcrumbStructuredData,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($breadcrumbStructuredDataJson)) {
  $breadcrumbStructuredDataJson = '{}';
}

function zs_member_initials(array $member): string
{
  $initials = trim((string)($member['initials'] ?? ''));
  if ($initials !== '') {
    return strtoupper(substr($initials, 0, 3));
  }

  $name = trim((string)($member['name'] ?? 'M'));
  $parts = preg_split('/\s+/', $name);
  if (!is_array($parts) || count($parts) === 0) {
    return 'M';
  }

  $result = '';
  foreach ($parts as $part) {
    if ($part === '') {
      continue;
    }
    $result .= strtoupper(substr($part, 0, 1));
    if (strlen($result) >= 2) {
      break;
    }
  }

  return $result === '' ? 'M' : $result;
}

function zs_member_social_profiles(array $member): array
{
  $socialDefinitions = [
    'discord' => ['label' => 'Discord', 'icon' => '/assets/icons/discord.svg'],
    'linkedin' => ['label' => 'LinkedIn', 'icon' => '/assets/icons/linkedin.svg'],
    'github' => ['label' => 'GitHub', 'icon' => '/assets/icons/github.svg'],
    'instagram' => ['label' => 'Instagram', 'icon' => '/assets/icons/instagram.svg'],
  ];

  $profiles = [];
  foreach ($socialDefinitions as $key => $meta) {
    $value = trim((string)($member[$key] ?? ''));

    // Backward-compatible fallback for older data where Discord was stored in "website".
    if ($value === '' && $key === 'discord') {
      $legacyDiscord = trim((string)($member['website'] ?? ''));
      if (preg_match('/^https?:\\/\\/(www\\.)?discord\\./i', $legacyDiscord)) {
        $value = $legacyDiscord;
      }
    }

    if ($value !== '' && !preg_match('/^https?:\\/\\//i', $value)) {
      $value = '';
    }

    $profiles[] = [
      'key' => $key,
      'label' => $meta['label'],
      'icon' => $meta['icon'],
      'url' => $value,
    ];
  }

  return $profiles;
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= zs_escape($pageTitle) ?></title>
  <meta name="description" content="<?= zs_escape($pageDescription) ?>">
  <link rel="canonical" href="<?= zs_escape($canonicalUrl) ?>">
  <meta property="og:locale" content="en_US">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Aegis Lab Cybersecurity Team">
  <meta property="og:title" content="<?= zs_escape($pageTitle) ?>">
  <meta property="og:description" content="<?= zs_escape($pageDescription) ?>">
  <meta property="og:url" content="<?= zs_escape($canonicalUrl) ?>">
  <meta property="og:image" content="<?= zs_escape($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= zs_escape($pageTitle) ?>">
  <meta name="twitter:description" content="<?= zs_escape($pageDescription) ?>">
  <meta name="twitter:image" content="<?= zs_escape($ogImage) ?>">
  <link rel="icon" type="image/x-icon" href="/Aegis_favicon.ico">
  <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Jersey+10&family=Micro+5&family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&family=VT323&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css?v=20260329memberwidthfix11">
  <script type="application/ld+json"><?= $breadcrumbStructuredDataJson ?></script>
</head>
<body class="sub-page">
  <header class="site-header">
    <a class="site-brand-link" href="/" aria-label="Go to Aegis Lab home page">
      <img class="site-brand-logo" src="/Aegis.png" alt="Aegis Lab logo">
      <span class="site-brand-text">Aegis Lab</span>
    </a>
    <button class="menu-toggle" type="button" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="site-nav">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <nav id="site-nav">
      <a href="/members" class="active">Members</a>
      <a href="/events">Events</a>
      <a href="<?= zs_escape($ctfUrl) ?>" target="_blank" rel="noopener noreferrer">Team CTF Website</a>
      <a href="/national-olympiads">Roadmap</a>
      <a href="/gallery">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap members-page">
    <section>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Members</span>
      </nav>
      <h1>Members</h1>
      <p class="members-intro-copy">
        Aegis Lab members collaborate in weekly cybersecurity training, practical labs, and
        competition-oriented preparation.
      </p>
    </section>

    <section class="members-board section-divider members-founder-section" aria-label="Aegis Lab founder">
      <h2 class="members-section-title">Founder</h2>
      <div class="member-grid">
        <article class="member-card">
          <?php if ($founder['avatar'] !== ''): ?>
            <img class="member-avatar" src="<?= zs_escape($founder['avatar']) ?>" alt="<?= zs_escape($founder['name']) ?> profile">
          <?php else: ?>
            <div class="member-avatar member-avatar-placeholder"><?= zs_escape(zs_member_initials($founder)) ?></div>
          <?php endif; ?>
          <div class="member-meta">
            <h3 class="member-name"><?= zs_escape($founder['name']) ?></h3>
            <?php if ($founder['handle'] !== ''): ?>
              <p class="member-handle"><?= zs_escape($founder['handle']) ?></p>
            <?php endif; ?>
            <?php $founderSocialProfiles = zs_member_social_profiles($founder); ?>
            <div class="member-socials" aria-label="<?= zs_escape($founder['name']) ?> social links">
              <?php foreach ($founderSocialProfiles as $social): ?>
                <?php if ($social['url'] !== ''): ?>
                  <a
                    class="member-social-icon-btn"
                    href="<?= zs_escape($social['url']) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?= zs_escape($social['label']) ?>"
                    title="<?= zs_escape($social['label']) ?>"
                  >
                    <img class="member-social-icon" src="<?= zs_escape($social['icon']) ?>" alt="">
                  </a>
                <?php else: ?>
                  <span
                    class="member-social-icon-btn is-disabled"
                    aria-label="<?= zs_escape($social['label']) ?> (not set)"
                    title="<?= zs_escape($social['label']) ?> (not set)"
                  >
                    <img class="member-social-icon" src="<?= zs_escape($social['icon']) ?>" alt="">
                  </span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="members-board section-divider" aria-label="Aegis Lab members">
      <h2 class="members-section-title">Team Members</h2>
      <div class="member-grid">
        <?php foreach ($teamMembers as $member): ?>
          <article class="member-card">
            <?php if ($member['avatar'] !== ''): ?>
              <img class="member-avatar" src="<?= zs_escape($member['avatar']) ?>" alt="<?= zs_escape($member['name']) ?> profile">
            <?php else: ?>
              <div class="member-avatar member-avatar-placeholder"><?= zs_escape(zs_member_initials($member)) ?></div>
            <?php endif; ?>
            <div class="member-meta">
              <h3 class="member-name"><?= zs_escape($member['name']) ?></h3>
              <?php if ($member['handle'] !== ''): ?>
                <p class="member-handle"><?= zs_escape($member['handle']) ?></p>
              <?php endif; ?>
              <?php $memberSocialProfiles = zs_member_social_profiles($member); ?>
              <div class="member-socials" aria-label="<?= zs_escape($member['name']) ?> social links">
                <?php foreach ($memberSocialProfiles as $social): ?>
                  <?php if ($social['url'] !== ''): ?>
                    <a
                      class="member-social-icon-btn"
                      href="<?= zs_escape($social['url']) ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      aria-label="<?= zs_escape($social['label']) ?>"
                      title="<?= zs_escape($social['label']) ?>"
                    >
                      <img class="member-social-icon" src="<?= zs_escape($social['icon']) ?>" alt="">
                    </a>
                  <?php else: ?>
                    <span
                      class="member-social-icon-btn is-disabled"
                      aria-label="<?= zs_escape($social['label']) ?> (not set)"
                      title="<?= zs_escape($social['label']) ?> (not set)"
                    >
                      <img class="member-social-icon" src="<?= zs_escape($social['icon']) ?>" alt="">
                    </span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

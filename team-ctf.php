<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/site_data.php';

$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalUrl = 'https://aegislab.ro/team-ctf';
$pageTitle = 'Team CTF Website - Aegis Lab';
$pageDescription = 'Access the Aegis Lab Team CTF platform for challenge practice, warmups, and technical training that supports weekly preparation.';
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
      'name' => 'Team CTF Website',
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
  <link rel="stylesheet" href="/styles.css?v=20260329mobilemenufix6">
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
      <a href="/members">Members</a>
      <a href="/events">Events</a>
      <a href="<?= zs_escape($ctfUrl) ?>" target="_blank" rel="noopener noreferrer">Team CTF Website</a>
      <a href="/roadmap">Roadmap</a>
      <a href="/gallery">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap">
    <section>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Team CTF Website</span>
      </nav>
      <h1>Team CTF Website</h1>
      <p>
        The Aegis Lab CTF platform is where members solve practical challenges, run warmup packs,
        and train for competitions in a hands-on environment.
      </p>
      <p>
        <a class="text-link is-underlined" href="<?= zs_escape($ctfUrl) ?>" target="_blank" rel="noopener noreferrer">Open the Team CTF Website</a>
      </p>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

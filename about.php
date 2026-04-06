<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalUrl = 'https://aegislab.ro/about';
$pageTitle = 'About - Aegis Lab';
$pageDescription = 'Learn about Aegis Lab, a high school cybersecurity team training students through practical offensive and defensive security labs, weekly meetings, and competition preparation.';
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
      'name' => 'About',
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
      <a href="/national-olympiads">Roadmap</a>
      <a href="/gallery">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap">
    <section>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">About</span>
      </nav>
      <h1>Who We Are</h1>
      <p>
        <?= zs_escape($siteContent['about_intro']) ?>
      </p>
    </section>

    <section class="grid-two section-divider">
      <article>
        <h2>Mission</h2>
        <p>
          Build strong cybersecurity fundamentals and advanced skills through consistent training,
          hands-on labs, and competition-level challenges.
        </p>
      </article>
      <article>
        <h2>How We Train</h2>
        <p>
          Weekly CTF practice, blue-team incident scenarios, and structured mentoring where advanced
          members help newer members progress quickly.
        </p>
      </article>
    </section>

    <section class="section-divider">
      <h2>Focus Areas</h2>
      <ul>
        <li>Web application security</li>
        <li>Digital forensics</li>
        <li>Reverse engineering</li>
        <li>Cryptography challenges</li>
        <li>Network defense and monitoring</li>
      </ul>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

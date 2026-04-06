<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalUrl = 'https://aegislab.ro/national-olympiads';
$pageTitle = 'Roadmap - Aegis Lab';
$pageDescription = 'Key technical olympiad dates, references, and preparation directions for Aegis Lab students, with a focus on cybersecurity and informatics competitions.';
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
      'name' => 'Roadmap',
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
      <a href="/national-olympiads" class="active">Roadmap</a>
      <a href="/gallery">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap olympiads-page">
    <section>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Roadmap</span>
      </nav>
      <h1>Roadmap</h1>
      <p class="olympiads-intro-copy">
        Aegis Lab prepares students for cybersecurity and informatics olympiads. This page keeps
        key dates and official links in one place.
      </p>
    </section>

    <section class="section-divider">
      <div class="olympiad-table-wrap" role="region" aria-label="Olympiad key dates">
        <table class="olympiad-table">
          <thead>
            <tr>
              <th>Olympiad</th>
              <th>Etapa județeană / locală</th>
              <th>Etapa națională</th>
            </tr>
          </thead>
          <tbody>
            <tr class="olympiad-table-featured">
              <td>Olimpiada de Securitate Cibernetică (ONSC)</td>
              <td>24 April 2026</td>
              <td>15-17 May 2026</td>
            </tr>
            <tr>
              <td>Olimpiada Națională de Informatică - gimnaziu</td>
              <td>2 March 2026</td>
              <td>22-26 March 2026</td>
            </tr>
            <tr>
              <td>Olimpiada Națională de Informatică - liceu</td>
              <td>2 March 2026</td>
              <td>26-30 March 2026</td>
            </tr>
            <tr>
              <td>Olimpiada de Informatică Aplicată - AcadNet</td>
              <td>21 March 2026</td>
              <td>9-12 May 2026</td>
            </tr>
            <tr>
              <td>Olimpiada de Inteligență Artificială (ONIA)</td>
              <td>14 March 2026</td>
              <td>17-20 April 2026</td>
            </tr>
            <tr>
              <td>Olimpiada de Tehnologia Informației și Comunicației (TIC)</td>
              <td>22 March 2026</td>
              <td>26-29 May 2026</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="section-divider olympiad-signup-section">
      <h2>Cybersecurity Olympiad Registration</h2>
      <div class="olympiad-directory-map" role="group" aria-label="ONSC sign-up directory tree">
        <p class="olympiad-tree-root">ONSC Resources/</p>
        <p class="olympiad-tree-line">
          <span class="branch" aria-hidden="true">├──</span>
          <span class="tree-content">
            Official ONSC website:
            <a class="text-link is-underlined" href="https://onsc.ro/" target="_blank" rel="noopener noreferrer">onsc.ro</a>
          </span>
        </p>
        <p class="olympiad-tree-line">
          <span class="branch" aria-hidden="true">├──</span>
          <span class="tree-content">
            Competition rules:
            <a class="text-link is-underlined" href="https://onsc.ro/regulament" target="_blank" rel="noopener noreferrer">View ONSC Rules</a>
          </span>
        </p>
        <p class="olympiad-tree-line">
          <span class="branch" aria-hidden="true">├──</span>
          <span class="tree-content">
            Official registration:
            <a class="text-link is-underlined" href="https://app.cyber-edu.co/competition/oscj26" target="_blank" rel="noopener noreferrer">Register for ONSC</a>
          </span>
        </p>
        <p class="olympiad-tree-line">
          <span class="branch" aria-hidden="true">└──</span>
          <span class="tree-content">
            Pre-competition simulation:
            <a class="text-link is-underlined" href="https://app.cyber-edu.co/competition/simulare-oscj26" target="_blank" rel="noopener noreferrer">Open ONSC Simulation</a>
          </span>
        </p>
      </div>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

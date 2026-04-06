<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalUrl = 'https://aegislab.ro/gallery';
$pageTitle = 'Gallery - Aegis Lab';
$pageDescription = 'Photos from Aegis Lab team activity, including weekly cybersecurity training, events, and competition preparation moments.';
$ogImage = 'https://aegislab.ro/Aegis.png';

// Leave empty for now; real images will be added later.
$galleryImages = [];

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
      'name' => 'Gallery',
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
  <link rel="stylesheet" href="/styles.css?v=20260405gallerybox40">
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
      <a href="/gallery" class="active">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap gallery-page">
    <section>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Gallery</span>
      </nav>
      <h1>Gallery</h1>
      <p class="gallery-intro-copy">
        Team photos from Aegis Lab training sessions, events, and competition preparation.
      </p>
    </section>

    <section class="section-divider gallery-section">
      <div class="gallery-stream">
        <?php if (empty($galleryImages)): ?>
          <div class="gallery-item gallery-item-empty" aria-label="Gallery placeholder"></div>
        <?php else: ?>
          <?php foreach ($galleryImages as $image): ?>
            <figure class="gallery-item">
              <img
                src="<?= zs_escape((string)($image['src'] ?? '')) ?>"
                alt="Aegis Lab gallery image"
                loading="lazy"
              >
            </figure>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

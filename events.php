<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalUrl = 'https://aegislab.ro/events';
$pageTitle = 'Events - Aegis Lab';
$pageDescription = 'Track Aegis Lab upcoming and past cybersecurity events, including kickoff sessions, practice drills, and olympiad-oriented activities.';
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
      'name' => 'Events',
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

$events = [];

$futureEvents = [
  [
    'title' => 'Aegis Lab Welcome Kickoff',
    'date_left' => 'April 20, 2026',
    'summary' => 'Official kickoff session with a live demonstration, team presentation, and Pizza & Chips for attendees.',
    'badge' => 'Upcoming',
    'url' => '/events/aegis-lab-kickoff',
  ],
  [
    'title' => 'Cybersecurity Regional Simulation',
    'date_left' => 'April 21, 2026',
    'summary' => 'Practice simulation for the Cybersecurity Olympiad regional stage on CyberEDU.',
    'badge' => 'Upcoming',
    'url' => '/events/onsc-regional-simulation',
  ],
];
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
      <a href="/events" class="active">Events</a>
      <a href="<?= zs_escape($ctfUrl) ?>" target="_blank" rel="noopener noreferrer">Team CTF Website</a>
      <a href="/national-olympiads">Roadmap</a>
      <a href="/gallery">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap events-page">
    <section>
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Events</span>
      </nav>
      <h1>Events</h1>
      <p class="events-intro-copy">
        Team sessions, competition preparation, and event announcements for Aegis Lab members.
      </p>
    </section>

    <section class="section-divider events-list-section events-upcoming-section">
      <div class="events-section-head">
        <h2 class="events-list-title">Upcoming Events</h2>
      </div>
      <div class="entry-list">
        <?php foreach ($futureEvents as $event): ?>
          <article class="entry-row event-row event-row-upcoming">
            <div class="entry-date entry-date-full"><?= zs_escape((string)$event['date_left']) ?></div>
            <div class="entry-main">
              <h2>
                <a href="<?= zs_escape((string)$event['url']) ?>"><?= zs_escape((string)$event['title']) ?></a>
                <?php if (!empty($event['badge'])): ?>
                  <span class="event-badge event-badge-upcoming"><?= zs_escape((string)$event['badge']) ?></span>
                <?php endif; ?>
              </h2>
              <p class="entry-summary event-description"><?= zs_escape((string)$event['summary']) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section-divider events-list-section events-pages-section">
      <div class="events-section-head">
        <h2 class="events-list-title">Past Events</h2>
      </div>
      <?php if (empty($events)): ?>
        <p class="events-empty-note">No past events posted yet.</p>
      <?php else: ?>
        <div class="entry-list">
          <?php foreach ($events as $event): ?>
            <article class="entry-row event-row">
              <div class="entry-date"><?= zs_escape((string)$event['year']) ?></div>
              <div class="entry-main">
                <h2>
                  <a href="<?= zs_escape((string)$event['url']) ?>"><?= zs_escape((string)$event['title']) ?></a>
                  <span class="event-title-date"><?= zs_escape((string)$event['date']) ?></span>
                </h2>
                <p class="entry-summary event-description"><?= zs_escape((string)$event['summary']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

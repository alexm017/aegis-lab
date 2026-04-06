<?php
require_once __DIR__ . '/../lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Aegis Midnight Defense Drill event page">
  <title>Aegis Midnight Defense Drill - Aegis Lab Events</title>
  <link rel="icon" type="image/x-icon" href="/Aegis_favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Jersey+10&family=Micro+5&family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&family=VT323&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css?v=20260329mobilemenufix6">
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

  <main class="page-wrap">
    <section class="event-detail-section">
      <p class="back-link"><a href="/events">&larr; Back to all events</a></p>
      <article class="event-article event-article-page">
        <p class="event-article-eyebrow">Events</p>
        <h1 class="event-article-title">Aegis Midnight Defense Drill</h1>
        <p class="event-article-date">May 18, 2026 (Fictional Example)</p>

        <div class="event-article-content">
          <div class="event-article-media" aria-hidden="true"></div>

          <section class="event-article-section">
            <h2>Overview</h2>
            <p class="event-article-lead">
              This fictional event demonstrates the format for future Aegis Lab event pages.
              The scenario simulates a late-night blue-team response window with attacker noise,
              alert triage, and rapid communication under time pressure.
            </p>
          </section>

          <section class="event-article-section">
            <h2>Structure</h2>
            <ul class="plain-list">
              <li>45-minute live alert triage phase with rotating analysts.</li>
              <li>30-minute containment and verification phase.</li>
              <li>20-minute report and executive summary submission.</li>
            </ul>
          </section>

          <section class="event-article-section">
            <h2>Expected Outputs</h2>
            <ul class="plain-list">
              <li>Incident timeline with confirmed indicators.</li>
              <li>Priority remediation checklist for impacted systems.</li>
              <li>Team debrief notes for next training cycle improvements.</li>
            </ul>
          </section>

          <section class="event-article-section">
            <h2>Resources</h2>
            <p>
              Warmup packs and scenario materials can be distributed through the team CTF workspace:
              <a href="<?= zs_escape($ctfUrl) ?>" target="_blank" rel="noopener noreferrer">Open Team CTF Website</a>
            </p>
          </section>

          <div class="event-article-nav" aria-label="Event page navigation">
            <a class="event-article-nav-btn" href="/events">
              <span aria-hidden="true">&larr;</span>
              <span>Back to Events</span>
            </a>
          </div>
        </div>
      </article>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Aegis Incident Response Gauntlet event page">
  <title>Aegis Incident Response Gauntlet - Aegis Lab Events</title>
  <link rel="icon" type="image/x-icon" href="/Aegis_favicon.ico">
  <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
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
        <h1 class="event-article-title">Aegis Incident Response Gauntlet</h1>
        <p class="event-article-date">June 22, 2026 (Fictional Example)</p>

        <div class="event-article-content">
          <div class="event-article-media" aria-hidden="true"></div>

          <section class="event-article-section">
            <h2>Scenario</h2>
            <p class="event-article-lead">
              This fictional event models a high-pressure response shift where participants receive
              noisy telemetry, identify true positives, and coordinate containment actions as a structured SOC team.
            </p>
          </section>

          <section class="event-article-section">
            <h2>Flow</h2>
            <ul class="plain-list">
              <li>Initial triage with severity scoring and alert suppression decisions.</li>
              <li>Escalation and handoff between detection, response, and reporting roles.</li>
              <li>Root-cause confirmation and prioritized remediation planning.</li>
            </ul>
          </section>

          <section class="event-article-section">
            <h2>Deliverables</h2>
            <ul class="plain-list">
              <li>Incident summary with confidence levels for each indicator.</li>
              <li>Containment timeline and evidence map.</li>
              <li>Final after-action review with improvements for the next drill.</li>
            </ul>
          </section>

          <section class="event-article-section">
            <h2>Resources</h2>
            <p>
              Event lab materials can be prepared and distributed through the team CTF workspace:
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

<?php
require_once __DIR__ . '/../lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Upcoming OSC Regional Practice event page">
  <title>OSC Regional Practice - Aegis Lab Events</title>
  <link rel="icon" type="image/x-icon" href="/Aegis_favicon.ico">
  <link rel="icon" type="image/png" sizes="48x48" href="/favicon-48x48.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Jersey+10&family=Micro+5&family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&family=VT323&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css?v=20260406eventsuniform11">
</head>
<body class="sub-page upcoming-event-page">
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
        <p class="event-article-eyebrow">Upcoming Event</p>
        <h1 class="event-article-title">OSC Regional Practice</h1>
        <p class="event-article-date">April 21, 2026</p>

        <div class="event-article-content upcoming-event-content">
          <div class="simulation-info-block">
            <p class="simulation-info-item">
              <strong>CyberEDU platform familiarization</strong> - Participants can test
              platform navigation, solution submission, and interaction with the scoring system.
            </p>
            <p class="simulation-info-item">
              <strong>Technical infrastructure checks</strong> - Verify compatibility of personal
              devices or contest-center equipment with the competition environment
              (virtual machines, internet connection, and required resources).
            </p>
            <p class="simulation-info-item">
              <strong>Practice on sample tasks</strong> - Demo exercises similar to official
              contest challenges will be available so participants can understand requirements
              and test their skills.
            </p>
            <p class="simulation-info-item">
              <strong>Rules and constraints validation</strong> - Participants can clarify internet
              usage policy, external communication restrictions, and scoring rules.
            </p>
            <p class="simulation-info-item">
              <strong>CyberEDU proctoring service test</strong> - Participants will install and
              validate the proctoring service required for competition supervision. It monitors
              platform activity, screen behavior, and rule compliance, allowing participants to
              resolve technical issues before the official competition day.
            </p>
          </div>

          <div class="event-quick-grid">
            <section class="event-article-section">
              <h2>Where?</h2>
              <p class="event-article-lead">
                Online on the CyberEDU platform.
              </p>
            </section>

            <section class="event-article-section">
              <h2>When?</h2>
              <p class="event-article-lead">
                April 21, 2026.
              </p>
            </section>

            <section class="event-article-section event-article-section--wide">
              <h2>What is it?</h2>
              <p class="event-article-lead">
                A pre-competition simulation designed to familiarize participants with the
                Cybersecurity Olympiad regional format, challenge flow, and the CyberEDU system.
              </p>
            </section>
          </div>

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

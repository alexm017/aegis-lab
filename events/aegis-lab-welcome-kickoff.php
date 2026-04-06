<?php
require_once __DIR__ . '/../lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Aegis Lab Welcome Kickoff event page">
  <title>Aegis Lab Welcome Kickoff - Aegis Lab Events</title>
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
        <h1 class="event-article-title">Aegis Lab Welcome Kickoff</h1>
        <p class="event-article-date">March 16, 2026</p>

        <div class="event-article-content">
          <div class="event-article-media" aria-hidden="true"></div>

          <div class="event-article-story">
            <p>
              This is the first official event of the Aegis Lab cybersecurity team.
              The kickoff introduces our mission, weekly workflow, and training direction
              for new and returning members.
            </p>
            <p>
              The session is designed to set a clear baseline for how we train, collaborate,
              and prepare for competitions throughout the year.
            </p>
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

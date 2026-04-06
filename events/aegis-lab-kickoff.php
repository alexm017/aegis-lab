<?php
require_once __DIR__ . '/../lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');
$mapsUrl = 'https://maps.app.goo.gl/rCDbEXarzj5soft39';
$mapsEmbedUrl = 'https://www.google.com/maps?q=Strada%201%20Decembrie%201918%207%2C%20Petro%C8%99ani&output=embed';
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
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500&family=DotGothic16&family=IBM+Plex+Mono:wght@400;600&family=Jersey+10&family=Micro+5&family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&family=Syne:wght@400;600;700;800&family=VT323&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css?v=20260329mobilemenufix6">
  <style>
    .kickoff-content-page {
      --kick-fire: #F5820A;
      --kick-fire-dim: #C4620A;
      --kick-fire-bright: #FFAB3C;
      --kick-gold: #E8A820;
      --kick-bg: #0E0B07;
      --kick-bg-2: #16110A;
      --kick-bg-3: #1E1710;
      --kick-bg-card: #1C1510;
      --kick-border: rgba(245,130,10,0.18);
      --kick-border-bright: rgba(245,130,10,0.40);
      --kick-text: var(--text);
      --kick-text-muted: #9A8870;
      --kick-text-dim: #6A5840;
    }

    .kickoff-content-page .event-article-content.kickoff-event-content {
      margin-top: 1.75rem;
      max-width: 1120px;
      display: grid;
      gap: 0;
    }

    .kickoff-content-page .kickoff-facts-strip {
      background: transparent;
      border-top: 0.5px solid var(--kick-border-bright);
      border-bottom: 0.5px solid var(--kick-border-bright);
      margin: 1.45rem 0 2.05rem;
      width: 100%;
      order: 1;
    }

    .kickoff-content-page .kickoff-facts-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
    }

    .kickoff-content-page .kickoff-fact-cell {
      display: grid;
      grid-template-rows: auto 1fr;
      align-items: stretch;
      padding: 1.55rem 2.15rem;
      border-right: 0.5px solid var(--kick-border);
    }

    .kickoff-content-page .kickoff-fact-cell:last-child {
      border-right: 0;
    }

    .kickoff-content-page .kickoff-fact-label {
      display: block;
      min-height: 0.95rem;
      font-family: "DM Mono", monospace;
      font-size: 0.75rem;
      font-weight: 500;
      color: var(--kick-text-dim);
      text-transform: uppercase;
      letter-spacing: 0.13em;
      margin: 0 0 0.44rem;
      line-height: 1;
    }

    .kickoff-content-page .kickoff-fact-value {
      font-family: var(--display-font);
      font-size: 1.17rem;
      font-weight: 700;
      color: var(--kick-text);
      margin: 0;
      line-height: 1.2;
      display: grid;
      align-content: center;
    }

    .kickoff-content-page .kickoff-fact-value-lines span {
      display: block;
    }

    .kickoff-content-page .kickoff-fact-value-lines .kickoff-no-wrap {
      white-space: nowrap;
    }

    .kickoff-content-page .kickoff-fact-value-venue-lines span:first-child {
      white-space: nowrap;
    }

    .kickoff-content-page .kickoff-agenda {
      order: 3;
      padding: 1.45rem 0 0;
    }

    .kickoff-content-page .kickoff-section-kicker {
      font-family: "DM Mono", monospace;
      font-size: 0.72rem;
      color: var(--kick-fire-dim);
      letter-spacing: 0.14em;
      text-transform: uppercase;
      margin: 0 0 0.52rem;
      line-height: 1.3;
    }

    .kickoff-content-page .kickoff-section-title {
      font-family: var(--display-font);
      font-weight: 700;
      font-size: clamp(1.6rem, 3vw, 2.4rem);
      letter-spacing: -0.025em;
      color: var(--kick-text);
      margin: 0 0 0.62rem;
      line-height: 1.1;
    }

    .kickoff-content-page .kickoff-section-subtitle {
      font-family: "DM Sans", sans-serif;
      font-size: 0.9rem;
      color: var(--kick-text-muted);
      max-width: 560px;
      line-height: 1.6;
      margin: 0 0 1.15rem;
    }

    .kickoff-content-page .kickoff-timeline {
      position: relative;
      display: grid;
      gap: 0;
      padding-bottom: 0;
    }

    .kickoff-content-page .kickoff-timeline::before {
      content: "";
      position: absolute;
      left: 21px;
      top: 0;
      bottom: 0;
      width: 0.5px;
      background: var(--kick-border);
    }

    .kickoff-content-page .kickoff-item {
      display: grid;
      grid-template-columns: 44px 1fr;
      gap: 1.5rem;
      padding-bottom: 1.5rem;
      align-items: start;
    }

    .kickoff-content-page .kickoff-item:last-child {
      padding-bottom: 0;
    }

    .kickoff-content-page .kickoff-item-icon {
      width: 44px;
      height: 44px;
      border: 0.5px solid var(--kick-border-bright);
      border-radius: 2px;
      background: var(--kick-bg-card);
      display: grid;
      place-items: center;
      position: relative;
      z-index: 1;
    }

    .kickoff-content-page .kickoff-item-icon svg {
      width: 21px;
      height: 21px;
      stroke: #a9540a;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .kickoff-content-page .kickoff-item-label {
      font-family: "DM Mono", monospace;
      font-size: 0.7rem;
      color: var(--kick-fire-dim);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin: 0 0 0.4rem;
      line-height: 1.3;
    }

    .kickoff-content-page .kickoff-item-title {
      font-family: var(--display-font);
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--kick-text);
      letter-spacing: -0.01em;
      margin: 0 0 0.5rem;
      line-height: 1.25;
    }

    .kickoff-content-page .kickoff-item-desc {
      font-family: "DM Sans", sans-serif;
      font-size: 0.86rem;
      color: var(--kick-text-muted);
      line-height: 1.52;
      max-width: 600px;
      margin: 0;
    }

    .kickoff-content-page .kickoff-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
      margin-top: 0.8rem;
    }

    .kickoff-content-page .kickoff-tag {
      font-family: "DM Mono", monospace;
      font-size: 0.67rem;
      color: var(--kick-fire);
      border: 0.5px solid var(--kick-border-bright);
      border-radius: 2px;
      padding: 0.2rem 0.55rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      line-height: 1.2;
      display: inline-flex;
      align-items: center;
    }

    .kickoff-content-page .kickoff-tag-gold {
      color: var(--kick-gold);
      border-color: rgba(232,168,32,0.4);
    }

    .kickoff-content-page .kickoff-tag-live {
      color: #ff6b6b;
      border-color: rgba(255,107,107,0.35);
    }

    .kickoff-content-page .kickoff-food-banner {
      background: var(--kick-bg-2);
      border: 0.5px solid var(--kick-border-bright);
      border-radius: 4px;
      padding: 1.45rem 1.65rem;
      display: flex;
      align-items: center;
      gap: 1.6rem;
      margin-top: 2.4rem;
    }

    .kickoff-content-page .kickoff-food-emoji {
      font-size: 3.5rem;
      line-height: 1;
      flex-shrink: 0;
    }

    .kickoff-content-page .kickoff-food-title {
      font-family: var(--display-font);
      font-weight: 700;
      font-size: 1.3rem;
      color: var(--kick-text);
      margin: 0 0 0.55rem;
      line-height: 1.2;
    }

    .kickoff-content-page .kickoff-food-desc {
      font-family: "DM Sans", sans-serif;
      font-size: 0.9rem;
      color: var(--kick-text-muted);
      line-height: 1.7;
      margin: 0 0 0.82rem;
      max-width: 760px;
    }

    .kickoff-content-page .kickoff-food-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
    }

    .kickoff-content-page .kickoff-food-badge {
      background: rgba(245,130,10,0.1);
      color: var(--kick-fire-bright);
      border: 0.5px solid rgba(245,130,10,0.3);
      border-radius: 2px;
      font-family: "DM Mono", monospace;
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.3rem 0.75rem;
      line-height: 1.2;
    }

    .kickoff-content-page .kickoff-overview {
      order: 4;
      padding: 1.85rem 0 0;
      border-top: 0.5px solid var(--kick-border);
      margin-top: 1.85rem;
    }

    .kickoff-content-page .kickoff-overview-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1px;
      background: var(--kick-border);
      border: 0.5px solid var(--kick-border);
      border-radius: 4px;
      overflow: hidden;
      margin-top: 1.5rem;
    }

    .kickoff-content-page .kickoff-overview-card {
      background: var(--kick-bg-card);
      padding: 2rem;
    }

    .kickoff-content-page .kickoff-overview-head {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      margin-bottom: 0.95rem;
    }

    .kickoff-content-page .kickoff-overview-icon {
      width: 44px;
      height: 44px;
      border: 0.5px solid var(--kick-border-bright);
      border-radius: 2px;
      background: var(--kick-bg-3);
      margin-bottom: 0;
      display: grid;
      place-items: center;
    }

    .kickoff-content-page .kickoff-overview-icon svg {
      width: 20px;
      height: 20px;
      stroke: var(--kick-fire);
      fill: none;
      stroke-width: 1.8;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .kickoff-content-page .kickoff-overview-card h3 {
      font-family: var(--display-font);
      font-weight: 700;
      font-size: 1.12rem;
      color: var(--kick-text);
      margin: 0;
      line-height: 1.2;
    }

    .kickoff-content-page .kickoff-overview-card p {
      font-family: "DM Sans", sans-serif;
      font-size: 0.875rem;
      color: var(--kick-text-muted);
      line-height: 1.7;
      margin: 0;
    }

    .kickoff-content-page .kickoff-location {
      order: 5;
      padding: 2.05rem 0 0;
      border-top: 0.5px solid var(--kick-border);
      margin-top: 2.05rem;
    }

    .kickoff-content-page .kickoff-location-grid {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      align-items: stretch;
      gap: 3rem;
      margin-top: 1.6rem;
    }

    .kickoff-content-page .kickoff-location-grid aside {
      display: grid;
      grid-template-rows: auto auto minmax(0, 1fr);
      align-content: stretch;
      height: 100%;
      gap: 0.62rem;
    }

    .kickoff-content-page .kickoff-map-card {
      background: var(--kick-bg-card);
      border: 0.5px solid var(--kick-border);
      border-radius: 4px;
      overflow: hidden;
    }

    .kickoff-content-page .kickoff-map-embed {
      display: block;
      width: 100%;
      height: 250px;
      border: 0;
      background: var(--kick-bg-3);
    }

    .kickoff-content-page .kickoff-map-body {
      padding: 1.5rem;
    }

    .kickoff-content-page .kickoff-map-body h4 {
      font-family: var(--display-font);
      font-weight: 700;
      font-size: 1rem;
      color: var(--kick-text);
      margin: 0 0 0.45rem;
      line-height: 1.2;
    }

    .kickoff-content-page .kickoff-map-address {
      font-family: "DM Sans", sans-serif;
      font-size: 0.85rem;
      color: var(--kick-text-muted);
      line-height: 1.7;
      margin: 0 0 0.7rem;
    }

    .kickoff-content-page .kickoff-map-link {
      font-family: "DM Mono", monospace;
      font-size: 0.72rem;
      color: var(--kick-fire);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      text-decoration: none;
    }

    .kickoff-content-page .kickoff-map-link:hover {
      color: var(--kick-fire-bright);
    }

    .kickoff-content-page .kickoff-details-title {
      font-family: var(--display-font);
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--kick-text);
      margin: 0 0 0.95rem;
      line-height: 1.2;
    }

    .kickoff-content-page .kickoff-video-title {
      font-size: clamp(1.6rem, 3vw, 2.4rem);
      white-space: nowrap;
      margin: 0;
    }

    .kickoff-content-page .kickoff-video-subtitle {
      font-family: "DM Sans", sans-serif;
      font-size: 0.9rem;
      color: var(--kick-text-muted);
      line-height: 1.55;
      margin: 0;
      max-width: 34ch;
    }

    .kickoff-content-page .kickoff-video-box {
      width: 100%;
      aspect-ratio: auto;
      min-height: 300px;
      height: 100%;
      border: 0.5px solid var(--kick-border-bright);
      border-radius: 4px;
      background:
        radial-gradient(circle at 50% 50%, rgba(245,130,10,0.08), rgba(245,130,10,0.015) 56%, rgba(0,0,0,0) 100%),
        var(--kick-bg-card);
      overflow: hidden;
    }

    @media (min-width: 1081px) {
      .kickoff-content-page .kickoff-location-grid aside {
        margin-top: 0;
        display: block;
        position: relative;
        height: 100%;
      }

      .kickoff-content-page .kickoff-location-grid .kickoff-video-title {
        position: absolute;
        top: -6.2rem;
        left: 0;
      }

      .kickoff-content-page .kickoff-location-grid .kickoff-video-subtitle {
        position: absolute;
        top: -3.05rem;
        left: 0;
        max-width: 34ch;
      }

      .kickoff-content-page .kickoff-location-grid .kickoff-video-box {
        min-height: 418px;
        height: 100%;
      }
    }

    .kickoff-content-page .kickoff-detail-row {
      margin-bottom: 0.95rem;
    }

    .kickoff-content-page .kickoff-detail-row:last-child {
      margin-bottom: 0;
    }

    .kickoff-content-page .kickoff-detail-label {
      display: block;
      font-family: "DM Mono", monospace;
      font-size: 0.67rem;
      color: var(--kick-text-dim);
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: 0.2rem;
      line-height: 1.3;
    }

    .kickoff-content-page .kickoff-detail-value {
      font-family: var(--display-font);
      font-size: 0.9rem;
      color: var(--kick-text);
      line-height: 1.6;
      margin: 0;
    }

    .kickoff-content-page .kickoff-event-nav {
      order: 6;
      margin-top: 2.2rem;
    }

    .kickoff-content-page .kickoff-sparkle-layer {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 40;
      overflow: hidden;
    }

    .kickoff-content-page .kickoff-sparkle {
      position: absolute;
      width: var(--size, 3px);
      height: var(--size, 3px);
      border-radius: 50%;
      background: var(--spark-color, #ffd88a);
      box-shadow:
        0 0 14px rgba(255, 205, 115, 0.9),
        0 0 28px rgba(255, 196, 92, 0.48),
        0 0 40px rgba(255, 170, 72, 0.22);
      opacity: 0;
      transform: translate3d(0, 0, 0) scale(0.45);
      animation: kickoffSparkleFall var(--dur, 1.7s) ease-in var(--delay, 0s) forwards;
    }

    .kickoff-content-page .kickoff-sparkle::before,
    .kickoff-content-page .kickoff-sparkle::after {
      content: "";
      position: absolute;
      left: 50%;
      top: 50%;
      width: calc(var(--size, 3px) * 3.8);
      height: 1px;
      background: var(--spark-color, #ffd88a);
      transform: translate(-50%, -50%) rotate(var(--rot, 45deg)) scale(0.72);
      opacity: 0.94;
      animation: kickoffSparkleTwinkle var(--twinkle-dur, 0.42s) ease-in-out var(--delay, 0s) 6 alternate;
    }

    .kickoff-content-page .kickoff-sparkle::before {
      --rot: 45deg;
    }

    .kickoff-content-page .kickoff-sparkle::after {
      --rot: -45deg;
    }

    @keyframes kickoffSparkleFall {
      0% {
        opacity: 0;
        transform: translate3d(0, -14px, 0) scale(0.55);
      }
      10% {
        opacity: 1;
      }
      78% {
        opacity: 0.95;
      }
      100% {
        opacity: 0;
        transform: translate3d(var(--tx, 0), var(--ty, 220px), 0) scale(0.16);
      }
    }

    @keyframes kickoffSparkleTwinkle {
      0% {
        opacity: 0.52;
        transform: translate(-50%, -50%) rotate(var(--rot, 45deg)) scale(0.62);
      }
      50% {
        opacity: 1;
        transform: translate(-50%, -50%) rotate(var(--rot, 45deg)) scale(1.42);
      }
      100% {
        opacity: 0.6;
        transform: translate(-50%, -50%) rotate(var(--rot, 45deg)) scale(0.82);
      }
    }

    @media (max-width: 1080px) {
      .kickoff-content-page .kickoff-facts-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .kickoff-content-page .kickoff-fact-cell {
        border-right: 0;
        border-bottom: 0.5px solid var(--kick-border);
      }

      .kickoff-content-page .kickoff-fact-cell:nth-child(odd) {
        border-right: 0.5px solid var(--kick-border);
      }

      .kickoff-content-page .kickoff-fact-cell:nth-last-child(-n + 2) {
        border-bottom: 0;
      }

      .kickoff-content-page .kickoff-overview-grid {
        grid-template-columns: 1fr;
      }

      .kickoff-content-page .kickoff-location-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }

      .kickoff-content-page .kickoff-location-grid aside {
        height: auto;
        grid-template-rows: auto auto auto;
      }

      .kickoff-content-page .kickoff-video-box {
        aspect-ratio: 16 / 9;
        min-height: 240px;
        height: auto;
      }

      .kickoff-content-page .kickoff-food-banner {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.2rem;
        padding: 1.6rem 1.3rem;
        margin-top: 1.9rem;
      }
    }

    @media (max-width: 760px) {
      .kickoff-content-page .kickoff-sparkle-layer {
        z-index: 72;
      }
    }

    @media (max-width: 720px) {
      .kickoff-content-page .kickoff-facts-grid {
        grid-template-columns: 1fr;
      }

      .kickoff-content-page .kickoff-fact-cell,
      .kickoff-content-page .kickoff-fact-cell:nth-child(odd) {
        border-right: 0;
      }

      .kickoff-content-page .kickoff-fact-cell {
        border-bottom: 0.5px solid var(--kick-border);
        padding: 1.1rem 1rem;
      }

      .kickoff-content-page .kickoff-fact-cell:last-child {
        border-bottom: 0;
      }

      .kickoff-content-page .kickoff-timeline::before {
        left: 19px;
      }

      .kickoff-content-page .kickoff-item {
        grid-template-columns: 40px 1fr;
        gap: 0.82rem;
        padding-bottom: 1.25rem;
      }

      .kickoff-content-page .kickoff-item-icon {
        width: 40px;
        height: 40px;
      }

      .kickoff-content-page .kickoff-food-emoji {
        font-size: 2.6rem;
      }

      .kickoff-content-page .kickoff-map-embed {
        height: 210px;
      }

      .kickoff-content-page .kickoff-video-box {
        min-height: 220px;
      }
    }
  </style>
</head>
<body class="sub-page kickoff-content-page">
  <div class="kickoff-sparkle-layer" aria-hidden="true"></div>
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
        <h1 class="event-article-title">Aegis Lab Welcome Kickoff</h1>
        <p class="event-article-date">April 20, 2026</p>

        <div class="event-article-content kickoff-event-content">
          <section class="kickoff-facts-strip" aria-label="Quick facts">
            <div class="kickoff-facts-grid">
              <div class="kickoff-fact-cell">
                <p class="kickoff-fact-label">Date</p>
                <p class="kickoff-fact-value">Monday, April 20, 2026</p>
              </div>
              <div class="kickoff-fact-cell">
                <p class="kickoff-fact-label">Duration</p>
                <p class="kickoff-fact-value kickoff-fact-value-lines">
                  <span>15:00-16:00</span>
                  <span>(60 Minutes)</span>
                </p>
              </div>
              <div class="kickoff-fact-cell">
                <p class="kickoff-fact-label">Venue</p>
                <p class="kickoff-fact-value kickoff-fact-value-lines kickoff-fact-value-venue-lines">
                  <span>CN Mihai Eminescu,</span>
                  <span>Petroșani — Sala 20</span>
                </p>
              </div>
              <div class="kickoff-fact-cell">
                <p class="kickoff-fact-label">What's included</p>
                <p class="kickoff-fact-value kickoff-fact-value-lines">
                  <span>Presentation</span>
                  <span>Live Demo</span>
                  <span class="kickoff-no-wrap">Pizza &amp; Chips</span>
                </p>
              </div>
            </div>
          </section>

          <section class="kickoff-agenda" aria-labelledby="kickoff-program-title">
            <p class="kickoff-section-kicker">// program</p>
            <h2 id="kickoff-program-title" class="kickoff-section-title">What's happening</h2>
            <p class="kickoff-section-subtitle">
              A compact kickoff flow: team intro, cybersecurity overview, live demo, and direct signup.
            </p>

            <div class="kickoff-timeline">
              <article class="kickoff-item">
                <div class="kickoff-item-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="8"></circle>
                    <path d="M8.6 12.2l2.1 2.1 4.7-4.7"></path>
                  </svg>
                </div>
                <div>
                  <p class="kickoff-item-label">Opening</p>
                  <h3 class="kickoff-item-title">Welcome &amp; Introductions</h3>
                  <p class="kickoff-item-desc">
                    Quick opening, format of the session, and what Aegis Lab focuses on this season.
                  </p>
                  <div class="kickoff-tags">
                    <span class="kickoff-tag">5 min</span>
                  </div>
                </div>
              </article>

              <article class="kickoff-item">
                <div class="kickoff-item-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <circle cx="9" cy="8" r="2.4"></circle>
                    <circle cx="15.4" cy="9.4" r="2"></circle>
                    <path d="M5.8 16.6c.5-2 2.2-3.3 4.3-3.3s3.8 1.4 4.3 3.3"></path>
                    <path d="M13.4 16.6c.3-1.4 1.4-2.4 2.9-2.4 1.4 0 2.5.9 2.9 2.4"></path>
                  </svg>
                </div>
                <div>
                  <p class="kickoff-item-label">Part 1</p>
                  <h3 class="kickoff-item-title">Aegis Lab Presentation</h3>
                  <p class="kickoff-item-desc">
                    First official kickoff presentation covering mission, weekly format, and how members can get started.
                  </p>
                  <div class="kickoff-tags">
                    <span class="kickoff-tag">5 min</span>
                  </div>
                </div>
              </article>

              <article class="kickoff-item">
                <div class="kickoff-item-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <path d="M12 3l6.4 2.5v5.6c0 4.3-2.6 7.8-6.4 9.9-3.8-2.1-6.4-5.6-6.4-9.9V5.5L12 3z"></path>
                  </svg>
                </div>
                <div>
                  <p class="kickoff-item-label">Part 2</p>
                  <h3 class="kickoff-item-title">What is Cybersecurity? — Introduction</h3>
                  <p class="kickoff-item-desc">
                    Clear beginner-friendly overview of network security, cryptography, web exploitation,
                    and reverse engineering, with practical starting points.
                  </p>
                  <div class="kickoff-tags">
                    <span class="kickoff-tag">30 min</span>
                  </div>
                </div>
              </article>

              <article class="kickoff-item">
                <div class="kickoff-item-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <rect x="3.5" y="5" width="17" height="11" rx="1.8"></rect>
                    <path d="M8 19h8"></path>
                    <path d="M10.5 9.2l-2.1 2.1 2.1 2.1"></path>
                    <path d="M13.5 9.2l2.1 2.1-2.1 2.1"></path>
                  </svg>
                </div>
                <div>
                  <p class="kickoff-item-label">Part 3</p>
                  <h3 class="kickoff-item-title">Live Demonstration — Hacking in Real Time</h3>
                  <p class="kickoff-item-desc">
                    Live CTF exploit on a controlled target, with tools and reasoning explained step by step.
                  </p>
                  <div class="kickoff-tags">
                    <span class="kickoff-tag">20 min</span>
                  </div>
                </div>
              </article>

              <article class="kickoff-item">
                <div class="kickoff-item-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="8"></circle>
                    <path d="M12 7.8v4.5l2.8 1.7"></path>
                  </svg>
                </div>
                <div>
                  <p class="kickoff-item-label">Closing</p>
                  <h3 class="kickoff-item-title">Q&amp;A, Signup &amp; Food</h3>
                  <p class="kickoff-item-desc">
                    Open questions, on-the-spot signup, and free pizza and chips for everyone.
                  </p>
                  <div class="kickoff-tags">
                    <span class="kickoff-tag">Remaining time</span>
                  </div>
                </div>
              </article>
            </div>

            <div class="kickoff-food-banner">
              <div class="kickoff-food-emoji" aria-hidden="true">🍕</div>
              <div>
                <h3 class="kickoff-food-title">Food is on us</h3>
                <p class="kickoff-food-desc">
                  All attendees get free pizza and chips — no ticket or sign-up required beforehand.
                  Just show up. It's our way of making your first Aegis Lab experience worth remembering.
                </p>
                <div class="kickoff-food-badges">
                  <span class="kickoff-food-badge">🍕 Pizza</span>
                  <span class="kickoff-food-badge">🥔 Chips</span>
                  <span class="kickoff-food-badge">Free for all attendees</span>
                </div>
              </div>
            </div>
          </section>

          <section class="kickoff-overview" aria-labelledby="kickoff-overview-title">
            <p class="kickoff-section-kicker">// overview</p>
            <h2 id="kickoff-overview-title" class="kickoff-section-title">Why cybersecurity?</h2>
            <p class="kickoff-section-subtitle">
              No background needed. We'll cover this at the event — here's a preview of the landscape you'll be exploring.
            </p>

            <div class="kickoff-overview-grid">
              <article class="kickoff-overview-card">
                <div class="kickoff-overview-head">
                  <div class="kickoff-overview-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                      <rect x="5.5" y="10" width="13" height="9" rx="1.8"></rect>
                      <path d="M8.2 10V7.8A3.8 3.8 0 0 1 12 4a3.8 3.8 0 0 1 3.8 3.8V10"></path>
                    </svg>
                  </div>
                  <h3>Ethical Hacking &amp; CTFs</h3>
                </div>
                <p>
                  We train through Capture the Flag competitions — structured puzzles simulating real attacks.
                  Safe, legal, and directly transferable to real-world security skills.
                </p>
              </article>

              <article class="kickoff-overview-card">
                <div class="kickoff-overview-head">
                  <div class="kickoff-overview-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                      <path d="M12 3l6.4 2.5v5.6c0 4.3-2.6 7.8-6.4 9.9-3.8-2.1-6.4-5.6-6.4-9.9V5.5L12 3z"></path>
                      <path d="M9.1 11.9l2 2 3.8-3.8"></path>
                    </svg>
                  </div>
                  <h3>Defense &amp; Offense, Together</h3>
                </div>
                <p>
                  To defend systems you need to think like an attacker. Our curriculum covers both sides —
                  exploitation techniques and the countermeasures that stop them.
                </p>
              </article>

              <article class="kickoff-overview-card">
                <div class="kickoff-overview-head">
                  <div class="kickoff-overview-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                      <rect x="3.5" y="5" width="17" height="11" rx="1.8"></rect>
                      <path d="M8 19h8"></path>
                      <path d="M10.5 9.2l-2.1 2.1 2.1 2.1"></path>
                      <path d="M13.5 9.2l2.1 2.1-2.1 2.1"></path>
                    </svg>
                  </div>
                  <h3>Web, Networks &amp; Crypto</h3>
                </div>
                <p>
                  Modern security spans web vulnerabilities, network intrusion, reverse engineering, and
                  cryptographic attacks. We cover all of it through dedicated training tracks.
                </p>
              </article>

              <article class="kickoff-overview-card">
                <div class="kickoff-overview-head">
                  <div class="kickoff-overview-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="8"></circle>
                      <path d="M12 10.2v5"></path>
                      <circle cx="12" cy="7.7" r="0.7"></circle>
                    </svg>
                  </div>
                  <h3>Real Skills, Real Careers</h3>
                </div>
                <p>
                  Cybersecurity is one of the fastest-growing fields globally. What you learn here maps directly
                  to university programs, internships, and industry certifications.
                </p>
              </article>
            </div>
          </section>

          <section class="kickoff-location" aria-labelledby="kickoff-location-title">
            <p class="kickoff-section-kicker">// where to find us</p>
            <h2 id="kickoff-location-title" class="kickoff-section-title">Location</h2>
            <p class="kickoff-section-subtitle">
              Hosted at Colegiul National "Mihai Eminescu" Petrosani, Sala 20.
            </p>

            <div class="kickoff-location-grid">
              <article class="kickoff-map-card">
                <iframe
                  class="kickoff-map-embed"
                  src="<?= zs_escape($mapsEmbedUrl) ?>"
                  title="Aegis Lab kickoff location on Google Maps"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  allowfullscreen
                ></iframe>
                <div class="kickoff-map-body">
                  <h4>Colegiul Național "Mihai Eminescu"</h4>
                  <p class="kickoff-map-address">Strada 1 Decembrie 1918 7, Petroșani</p>
                  <a class="kickoff-map-link" href="<?= zs_escape($mapsUrl) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps →</a>
                </div>
              </article>

              <aside>
                <h2 class="kickoff-section-title kickoff-video-title">Directions to the Lab</h2>
                <p class="kickoff-video-subtitle">Video guide on how to arrive at the Lab.</p>
                <div class="kickoff-video-box" aria-label="Directions video placeholder"></div>
              </aside>
            </div>
          </section>

          <div class="event-article-nav kickoff-event-nav" aria-label="Event page navigation">
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

  <script>
    (function () {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      var layer = document.querySelector('.kickoff-sparkle-layer');
      if (!layer) {
        return;
      }

      var colors = ['#ffd88a', '#f7b65b', '#fff1be', '#f7a847', '#ffecc7'];
      var mobile = window.innerWidth <= 760;
      var count = mobile ? 52 : 92;
      var vw = Math.max(window.innerWidth, 360);
      var vh = Math.max(window.innerHeight, 640);

      for (var i = 0; i < count; i += 1) {
        var spark = document.createElement('span');
        spark.className = 'kickoff-sparkle';

        var originX = (Math.random() * vw);
        var originY = (-20 + (Math.random() * (vh * 0.18)));
        var tx = ((Math.random() - 0.5) * (mobile ? 70 : 130));
        var ty = 145 + (Math.random() * (mobile ? 220 : 340));

        spark.style.left = originX.toFixed(2) + 'px';
        spark.style.top = originY.toFixed(2) + 'px';
        spark.style.setProperty('--tx', tx.toFixed(2) + 'px');
        spark.style.setProperty('--ty', ty.toFixed(2) + 'px');
        spark.style.setProperty('--size', (2 + (Math.random() * 3.6)).toFixed(2) + 'px');
        spark.style.setProperty('--delay', (Math.random() * 0.32).toFixed(2) + 's');
        spark.style.setProperty('--dur', (1.35 + (Math.random() * 0.85)).toFixed(2) + 's');
        spark.style.setProperty('--twinkle-dur', (0.34 + (Math.random() * 0.18)).toFixed(2) + 's');
        spark.style.setProperty('--spark-color', colors[Math.floor(Math.random() * colors.length)]);

        spark.addEventListener('animationend', function () {
          this.remove();
        });

        layer.appendChild(spark);
      }
    })();
  </script>
  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

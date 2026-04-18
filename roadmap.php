<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalUrl = 'https://aegislab.ro/roadmap';
$pageTitle = 'Roadmap - Aegis Lab';
$pageDescription = 'A practical cybersecurity learning roadmap for Aegis Lab students: fundamentals, training platforms, CTF practice, specializations, certifications, and olympiad resources.';
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
  <style>
    .roadmap-subnav-shell {
      position: sticky;
      top: 0;
      z-index: 22;
      width: 100%;
      margin-top: 1.22rem;
      margin-bottom: 1.58rem;
      border-top: 1px solid rgba(255, 212, 146, 0.12);
      border-bottom: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(20, 16, 11, 0.92);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }

    .roadmap-subnav-inner {
      width: min(980px, calc(100% - 2rem));
      max-width: calc(100% - 2rem);
      margin: 0 auto;
      display: flex;
      justify-content: center;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .roadmap-subnav-inner::-webkit-scrollbar {
      display: none;
    }

    .roadmap-subnav {
      display: flex;
      gap: 0.68rem;
      min-width: max-content;
      margin: 0 auto;
      padding: 0.68rem 0 0.62rem;
      justify-content: center;
    }

    .roadmap-subnav-link {
      display: inline-flex;
      align-items: center;
      gap: 0.38rem;
      padding: 0.36rem 0.5rem 0.42rem;
      border-bottom: 2px solid transparent;
      color: rgba(255, 244, 201, 0.54);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.73rem;
      letter-spacing: 0.11em;
      line-height: 1;
      text-decoration: none;
      text-transform: uppercase;
      white-space: nowrap;
      transition: color 170ms ease, border-color 170ms ease, background-color 170ms ease;
    }

    .roadmap-subnav-link:hover,
    .roadmap-subnav-link:focus-visible,
    .roadmap-subnav-link.is-active {
      color: #f8ebcb;
    }

    .roadmap-subnav-link .roadmap-nav-dot {
      width: 0.34rem;
      height: 0.34rem;
      border-radius: 999px;
      border: 1px solid currentColor;
      opacity: 0.7;
      flex: 0 0 auto;
    }

    .roadmap-subnav-link.phase-foundations:hover,
    .roadmap-subnav-link.phase-foundations:focus-visible,
    .roadmap-subnav-link.phase-foundations.is-active {
      border-color: #3ca367;
      background: rgba(60, 163, 103, 0.08);
    }

    .roadmap-subnav-link.phase-platforms:hover,
    .roadmap-subnav-link.phase-platforms:focus-visible,
    .roadmap-subnav-link.phase-platforms.is-active {
      border-color: #28a8c7;
      background: rgba(40, 168, 199, 0.08);
    }

    .roadmap-subnav-link.phase-practice:hover,
    .roadmap-subnav-link.phase-practice:focus-visible,
    .roadmap-subnav-link.phase-practice.is-active {
      border-color: #4679d6;
      background: rgba(70, 121, 214, 0.08);
    }

    .roadmap-subnav-link.phase-specializations:hover,
    .roadmap-subnav-link.phase-specializations:focus-visible,
    .roadmap-subnav-link.phase-specializations.is-active {
      border-color: #8456be;
      background: rgba(132, 86, 190, 0.08);
    }

    .roadmap-subnav-link.phase-certifications:hover,
    .roadmap-subnav-link.phase-certifications:focus-visible,
    .roadmap-subnav-link.phase-certifications.is-active,
    .roadmap-subnav-link.phase-olympiads:hover,
    .roadmap-subnav-link.phase-olympiads:focus-visible,
    .roadmap-subnav-link.phase-olympiads.is-active {
      border-color: #d39a32;
      background: rgba(211, 154, 50, 0.08);
    }

    .olympiads-page section[id] {
      scroll-margin-top: 5.15rem;
    }

    .olympiads-page {
      gap: 0.86rem;
    }

    .olympiads-page .roadmap-hero {
      display: grid;
      gap: 0;
      margin-top: 0.1rem;
      justify-items: start;
      text-align: left;
      width: 100%;
    }

    .olympiads-page .roadmap-kicker {
      display: inline-flex;
      align-items: center;
      width: fit-content;
      padding: 0.38rem 0.68rem 0.4rem;
      border: 1px solid rgba(var(--phase-rgb), 0.35);
      background: rgba(var(--phase-rgb), 0.08);
      color: var(--phase-accent);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.14em;
      line-height: 1;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-block-title {
      margin: 0;
      color: var(--text);
      font-family: var(--display-font);
      font-weight: 700;
      letter-spacing: -0.025em;
      line-height: 1.1;
    }

    .olympiads-page .roadmap-block-title {
      font-size: clamp(1.6rem, 3vw, 2.4rem);
    }

    .olympiads-page .roadmap-block-copy {
      max-width: 74ch;
      margin: 0;
      color: rgba(255, 244, 201, 0.64);
      font-size: 0.98rem;
      line-height: 1.62;
    }

    .olympiads-page .roadmap-hero .page-breadcrumb {
      margin: 0 0 0.14rem !important;
      justify-self: stretch !important;
      align-self: start !important;
      place-self: start stretch !important;
      width: 100% !important;
      display: flex !important;
      justify-content: flex-start !important;
      text-align: left !important;
      margin-right: auto !important;
    }

    .olympiads-page .roadmap-hero > h1 {
      margin: 0.14rem 0 0.01rem;
      font-size: clamp(1.34rem, 2.75vw, 2.08rem);
      font-family: var(--display-font);
      font-weight: 700;
      letter-spacing: -0.025em;
      line-height: 1.1;
    }

    .olympiads-page .roadmap-hero > .olympiads-intro-copy {
      margin: 0;
    }

    .olympiads-page .roadmap-map {
      width: 100%;
      margin-top: 1.84rem;
      display: grid;
      gap: 1.08rem;
    }

    .olympiads-page .roadmap-map-column {
      display: grid;
      justify-items: center;
      gap: 0.52rem;
    }

    .olympiads-page .roadmap-map-connector {
      width: 1px;
      height: 1.3rem;
      background: rgba(255, 212, 146, 0.18);
    }

    .olympiads-page .roadmap-map-node {
      width: min(100%, 540px);
      position: relative;
      padding: 0.92rem 1rem 0.96rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      border-left: 2px solid rgba(211, 154, 50, 0.88);
      background: rgba(255, 255, 255, 0.02);
    }

    .olympiads-page .roadmap-map-link {
      display: block;
      text-decoration: none;
      transition: transform 170ms ease, border-color 170ms ease, background-color 170ms ease;
    }

    .olympiads-page .roadmap-map-link:hover,
    .olympiads-page .roadmap-map-link:focus-visible {
      transform: translateY(-1px);
      border-color: rgba(255, 212, 146, 0.24);
      background: rgba(255, 255, 255, 0.03);
    }

    .olympiads-page .roadmap-map-node.is-foundation {
      border-left-color: #3ca367;
    }

    .olympiads-page .roadmap-map-node.is-platforms {
      border-left-color: #28a8c7;
    }

    .olympiads-page .roadmap-map-node.is-practice {
      border-left-color: #4679d6;
    }

    .olympiads-page .roadmap-map-node.is-track {
      border-left-color: #8456be;
    }

    .olympiads-page .roadmap-map-node.is-advanced {
      border-left-color: #d39a32;
    }

    .olympiads-page .roadmap-stage-name {
      display: block;
      color: #f5e5c2;
      font-size: 1rem;
      font-weight: 600;
      line-height: 1.14;
    }

    .olympiads-page .roadmap-stage-note {
      margin: 0.32rem 0 0;
      color: rgba(255, 244, 201, 0.54);
      font-size: 0.82rem;
      line-height: 1.48;
    }

    .olympiads-page .roadmap-platform-rail {
      position: relative;
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.74rem;
      padding-top: 1.26rem;
    }

    .olympiads-page .roadmap-platform-rail::before {
      content: "";
      position: absolute;
      top: 0;
      left: 18%;
      right: 18%;
      height: 1px;
      background: rgba(255, 212, 146, 0.18);
    }

    .olympiads-page .roadmap-platform-lane {
      position: relative;
      min-width: 0;
      padding: 0.92rem 0.94rem 1rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
    }

    .olympiads-page .roadmap-platform-lane::before {
      content: "";
      position: absolute;
      top: -1.26rem;
      left: 50%;
      width: 1px;
      height: 1.26rem;
      background: rgba(255, 212, 146, 0.18);
      transform: translateX(-50%);
    }

    .olympiads-page .roadmap-platform-lane.is-foundation {
      border-left: 2px solid #3ca367;
    }

    .olympiads-page .roadmap-platform-lane.is-platforms {
      border-left: 2px solid #28a8c7;
    }

    .olympiads-page .roadmap-platform-lane.is-practice {
      border-left: 2px solid #4679d6;
    }

    .olympiads-page .roadmap-platform-lane h3 {
      margin: 0;
      color: #f6e9ca;
      font-size: 1rem;
      line-height: 1.16;
    }

    .olympiads-page .roadmap-platform-lane-label {
      display: inline-flex;
      margin-bottom: 0.34rem;
      color: rgba(255, 212, 146, 0.76);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.69rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      line-height: 1;
    }

    .olympiads-page .roadmap-platform-lane p {
      margin: 0.34rem 0 0.62rem;
      color: rgba(255, 244, 201, 0.6);
      font-size: 0.82rem;
      line-height: 1.48;
    }

    .olympiads-page .roadmap-platform-lane-list {
      margin: 0;
      padding: 0;
      list-style: none;
      display: grid;
      gap: 0.4rem;
    }

    .olympiads-page .roadmap-platform-lane-list li {
      display: flex;
      align-items: flex-start;
      gap: 0.42rem;
      color: #f4e5c3;
      font-size: 0.84rem;
      line-height: 1.46;
    }

    .olympiads-page .roadmap-platform-lane-list li::before {
      content: "";
      width: 0.34rem;
      height: 0.34rem;
      margin-top: 0.36rem;
      border-radius: 999px;
      border: 1px solid currentColor;
      opacity: 0.7;
      flex: 0 0 auto;
    }

    .olympiads-page .roadmap-map-branch {
      position: relative;
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 0.68rem;
      padding-top: 1.26rem;
    }

    .olympiads-page .roadmap-map-branch::before {
      content: "";
      position: absolute;
      top: 0;
      left: 12%;
      right: 12%;
      height: 1px;
      background: rgba(255, 212, 146, 0.18);
    }

    .olympiads-page .roadmap-map-branch-card {
      position: relative;
      min-width: 0;
      padding: 0.86rem 0.88rem 0.92rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
    }

    .olympiads-page .roadmap-map-branch-card.roadmap-map-link {
      text-decoration: none;
    }

    .olympiads-page .roadmap-map-branch-card::before {
      content: "";
      position: absolute;
      top: -1.26rem;
      left: 50%;
      width: 1px;
      height: 1.26rem;
      background: rgba(255, 212, 146, 0.18);
      transform: translateX(-50%);
    }

    .olympiads-page .roadmap-map-branch-card::after {
      content: "";
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      width: 2px;
      background: rgba(132, 86, 190, 0.88);
    }

    .olympiads-page .roadmap-map-branch-card h3 {
      margin: 0 0 0.26rem;
      color: #f6e9ca;
      font-size: 1rem;
      line-height: 1.18;
    }

    .olympiads-page .roadmap-map-branch-card p {
      margin: 0;
      color: rgba(255, 244, 201, 0.62);
      font-size: 0.84rem;
      line-height: 1.5;
    }

    .olympiads-page .roadmap-map-note {
      max-width: 66ch;
      margin: 0;
      color: rgba(255, 244, 201, 0.64);
      font-size: 0.93rem;
      line-height: 1.58;
    }

    .olympiads-page .roadmap-platform-board {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.88rem;
    }

    .olympiads-page .roadmap-platform-column {
      display: grid;
      gap: 0.74rem;
      min-width: 0;
    }

    .olympiads-page .roadmap-platform-column-head {
      position: relative;
      min-width: 0;
      padding: 0.92rem 0.94rem 0.98rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.015);
    }

    .olympiads-page .roadmap-platform-column-head::after {
      content: "";
      position: absolute;
      left: 50%;
      top: 100%;
      width: 1px;
      height: 1.04rem;
      background: rgba(255, 212, 146, 0.18);
      transform: translateX(-50%);
    }

    .olympiads-page .roadmap-platform-column-head h3 {
      margin: 0;
      color: #f5e8c7;
      font-size: 1.02rem;
      line-height: 1.16;
    }

    .olympiads-page .roadmap-platform-column-meta {
      display: inline-flex;
      margin-bottom: 0.28rem;
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.69rem;
      letter-spacing: 0.11em;
      text-transform: uppercase;
      line-height: 1;
    }

    .olympiads-page .roadmap-platform-column-copy {
      margin: 0.34rem 0 0;
      color: rgba(255, 244, 201, 0.58);
      font-size: 0.84rem;
      line-height: 1.5;
    }

    .olympiads-page .roadmap-platform-column.is-beginner .roadmap-platform-column-head {
      border-left: 2px solid #3ca367;
    }

    .olympiads-page .roadmap-platform-column.is-beginner .roadmap-platform-column-meta {
      color: #3ca367;
    }

    .olympiads-page .roadmap-platform-column.is-guided .roadmap-platform-column-head {
      border-left: 2px solid #28a8c7;
    }

    .olympiads-page .roadmap-platform-column.is-guided .roadmap-platform-column-meta {
      color: #28a8c7;
    }

    .olympiads-page .roadmap-platform-column.is-advanced .roadmap-platform-column-head {
      border-left: 2px solid #4679d6;
    }

    .olympiads-page .roadmap-platform-column.is-advanced .roadmap-platform-column-meta {
      color: #4679d6;
    }

    .olympiads-page .roadmap-platform-column .roadmap-card-grid {
      grid-template-columns: 1fr;
      position: relative;
      padding-top: 1.06rem;
      gap: 0.74rem;
    }

    .olympiads-page .roadmap-block {
      --phase-accent: #d39a32;
      --phase-rgb: 211, 154, 50;
      display: grid;
      gap: 1rem;
    }

    .olympiads-page .roadmap-foundations {
      --phase-accent: #3ca367;
      --phase-rgb: 60, 163, 103;
    }

    .olympiads-page .roadmap-platforms {
      --phase-accent: #28a8c7;
      --phase-rgb: 40, 168, 199;
    }

    .olympiads-page .roadmap-practice {
      --phase-accent: #4679d6;
      --phase-rgb: 70, 121, 214;
    }

    .olympiads-page .roadmap-specializations {
      --phase-accent: #8456be;
      --phase-rgb: 132, 86, 190;
    }

    .olympiads-page .roadmap-certifications,
    .olympiads-page .roadmap-olympiads {
      --phase-accent: #d39a32;
      --phase-rgb: 211, 154, 50;
    }

    .olympiads-page .roadmap-block-head {
      display: grid;
      gap: 0.54rem;
    }

    .olympiads-page .roadmap-layout-two {
      display: grid;
      grid-template-columns: minmax(0, 1.02fr) minmax(320px, 0.98fr);
      gap: 0.9rem;
      align-items: start;
    }

    .olympiads-page .roadmap-layout-practice {
      grid-template-columns: minmax(320px, 0.92fr) minmax(0, 1.08fr);
    }

    .olympiads-page .roadmap-panel {
      min-width: 0;
      padding: 1rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.015);
    }

    .olympiads-page .roadmap-panel-title {
      margin: 0 0 0.9rem;
      color: rgba(255, 244, 201, 0.54);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.74rem;
      font-weight: 600;
      letter-spacing: 0.13em;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-timeline {
      position: relative;
      display: grid;
      gap: 1rem;
      padding-left: 1.52rem;
    }

    .olympiads-page .roadmap-timeline::before {
      content: "";
      position: absolute;
      top: 0.2rem;
      bottom: 0.2rem;
      left: 0.4rem;
      width: 1px;
      background: rgba(var(--phase-rgb), 0.26);
    }

    .olympiads-page .roadmap-timeline-item {
      position: relative;
      display: grid;
      gap: 0.24rem;
    }

    .olympiads-page .roadmap-timeline-dot {
      position: absolute;
      top: 0.18rem;
      left: -1.52rem;
      width: 0.82rem;
      height: 0.82rem;
      border: 2px solid var(--phase-accent);
      border-radius: 999px;
      background: #14100b;
      box-shadow: 0 0 0 3px #14100b;
    }

    .olympiads-page .roadmap-timeline-meta {
      color: var(--phase-accent);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.7rem;
      letter-spacing: 0.11em;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-timeline-item h3,
    .olympiads-page .roadmap-card-title,
    .olympiads-page .roadmap-ladder-title {
      margin: 0;
      color: #f6e9ca;
      font-size: 1.18rem;
      font-weight: 600;
      line-height: 1.18;
    }

    .olympiads-page .roadmap-timeline-item p,
    .olympiads-page .roadmap-card p,
    .olympiads-page .roadmap-ladder-item p {
      margin: 0;
      color: rgba(255, 244, 201, 0.67);
      font-size: 0.93rem;
      line-height: 1.58;
    }

    .olympiads-page .roadmap-tag-row {
      display: flex;
      flex-wrap: wrap;
      gap: 0.38rem;
      margin-top: 0.2rem;
    }

    .olympiads-page .roadmap-tag,
    .olympiads-page .roadmap-level {
      display: inline-flex;
      align-items: center;
      padding: 0.22rem 0.46rem;
      border: 1px solid rgba(var(--phase-rgb), 0.24);
      background: rgba(var(--phase-rgb), 0.08);
      color: rgba(255, 244, 201, 0.82);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.68rem;
      letter-spacing: 0.06em;
      line-height: 1;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-card-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.88rem;
    }

    .olympiads-page .roadmap-card-grid.is-certifications {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .olympiads-page .roadmap-card-grid.is-stack {
      grid-template-columns: 1fr;
    }

    .olympiads-page .roadmap-card {
      position: relative;
      min-width: 0;
      min-height: 100%;
      padding: 1rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.015);
    }

    .olympiads-page .roadmap-card::before {
      content: "";
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      width: 2px;
      background: rgba(var(--phase-rgb), 0.9);
    }

    .olympiads-page .roadmap-card-mark {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2.1rem;
      height: 2.1rem;
      margin-bottom: 0.8rem;
      border: 1px solid rgba(var(--phase-rgb), 0.25);
      background: rgba(var(--phase-rgb), 0.12);
      color: var(--phase-accent);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.78rem;
      font-weight: 600;
      line-height: 1;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-card-mark-link {
      text-decoration: none;
      transition: transform 160ms ease, border-color 160ms ease, background-color 160ms ease;
    }

    .olympiads-page .roadmap-card-mark-link:hover {
      transform: translateY(-1px);
      border-color: rgba(var(--phase-rgb), 0.52);
      background: rgba(var(--phase-rgb), 0.16);
    }

    .olympiads-page .roadmap-card-mark-logo {
      width: 1.34rem;
      height: 1.34rem;
      object-fit: contain;
      display: block;
    }

    .olympiads-page .roadmap-card-meta {
      margin: 0 0 0.42rem;
      color: rgba(255, 244, 201, 0.42);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.68rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-card-link {
      display: inline-flex;
      align-items: center;
      margin-top: 0.72rem;
      color: var(--phase-accent);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.75rem;
      letter-spacing: 0.03em;
      text-decoration: none;
    }

    .olympiads-page .roadmap-card-link:hover {
      color: #f4e8ca;
    }

    .olympiads-page .roadmap-ladder {
      position: relative;
      display: grid;
      gap: 1rem;
      padding-left: 1.18rem;
    }

    .olympiads-page .roadmap-ladder::before {
      content: "";
      position: absolute;
      top: 0.16rem;
      bottom: 0.16rem;
      left: 0.34rem;
      width: 1px;
      background: rgba(var(--phase-rgb), 0.26);
    }

    .olympiads-page .roadmap-ladder-item {
      position: relative;
      padding-left: 1.1rem;
    }

    .olympiads-page .roadmap-ladder-marker {
      position: absolute;
      top: 0.12rem;
      left: -0.06rem;
      width: 0.8rem;
      height: 0.8rem;
      border-radius: 999px;
      border: 2px solid var(--phase-accent);
      background: #14100b;
      box-shadow: 0 0 0 3px #14100b;
      color: var(--phase-accent);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.52rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      line-height: 1;
    }

    .olympiads-page .roadmap-practice-map {
      display: grid;
      gap: 1rem;
    }

    .olympiads-page .roadmap-practice-column {
      display: grid;
      justify-items: center;
      gap: 0.52rem;
    }

    .olympiads-page .roadmap-practice-node {
      width: min(100%, 620px);
      display: grid;
      grid-template-columns: 2.45rem minmax(0, 1fr);
      gap: 0.88rem;
      align-items: center;
      padding: 0.94rem 1rem 1rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      border-left: 2px solid rgba(var(--phase-rgb), 0.9);
      background: rgba(255, 255, 255, 0.02);
      text-decoration: none;
      transition: transform 170ms ease, border-color 170ms ease, background-color 170ms ease;
    }

    .olympiads-page .roadmap-practice-node:hover,
    .olympiads-page .roadmap-practice-node:focus-visible {
      transform: translateY(-1px);
      border-color: rgba(255, 212, 146, 0.24);
      background: rgba(255, 255, 255, 0.03);
    }

    .olympiads-page .roadmap-practice-logo {
      width: 2.45rem;
      height: 2.45rem;
      border: 1px solid rgba(var(--phase-rgb), 0.25);
      background: rgba(var(--phase-rgb), 0.12);
      object-fit: contain;
      display: block;
    }

    .olympiads-page .roadmap-practice-meta {
      display: inline-flex;
      margin-bottom: 0.22rem;
      color: rgba(255, 212, 146, 0.76);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.68rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      line-height: 1;
    }

    .olympiads-page .roadmap-practice-node h3 {
      margin: 0;
      color: #f6e9ca;
      font-size: 1.04rem;
      line-height: 1.18;
    }

    .olympiads-page .roadmap-practice-node p {
      margin: 0.28rem 0 0;
      color: rgba(255, 244, 201, 0.62);
      font-size: 0.85rem;
      line-height: 1.52;
    }

    .olympiads-page .roadmap-practice-branch {
      position: relative;
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 0.68rem;
      padding-top: 1.18rem;
    }

    .olympiads-page .roadmap-practice-branch::before {
      content: "";
      position: absolute;
      top: 0;
      left: 9%;
      right: 9%;
      height: 1px;
      background: rgba(255, 212, 146, 0.18);
    }

    .olympiads-page .roadmap-practice-branch-card {
      position: relative;
      min-width: 0;
      padding: 0.82rem 0.86rem 0.9rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
    }

    .olympiads-page .roadmap-practice-branch-card::before {
      content: "";
      position: absolute;
      top: -1.18rem;
      left: 50%;
      width: 1px;
      height: 1.18rem;
      background: rgba(255, 212, 146, 0.18);
      transform: translateX(-50%);
    }

    .olympiads-page .roadmap-practice-branch-card::after {
      content: "";
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      width: 2px;
      background: rgba(70, 121, 214, 0.88);
    }

    .olympiads-page .roadmap-practice-branch-card h3 {
      margin: 0 0 0.24rem;
      color: #f6e9ca;
      font-size: 0.96rem;
      line-height: 1.18;
    }

    .olympiads-page .roadmap-practice-branch-card p {
      margin: 0;
      color: rgba(255, 244, 201, 0.6);
      font-size: 0.82rem;
      line-height: 1.46;
    }

    .olympiads-page .roadmap-practice-note {
      max-width: 70ch;
      margin: 0;
      color: rgba(255, 244, 201, 0.64);
      font-size: 0.92rem;
      line-height: 1.58;
    }

    .olympiads-page .roadmap-cert-visual {
      display: grid;
      gap: 0.82rem;
    }

    .olympiads-page .roadmap-cert-visual-head {
      display: grid;
      gap: 0.34rem;
    }

    .olympiads-page .roadmap-cert-visual-head p {
      margin: 0;
      color: rgba(255, 244, 201, 0.64);
      line-height: 1.58;
    }

    .olympiads-page .roadmap-cert-image {
      width: min(100%, 1020px);
      max-width: 100%;
      height: auto;
      display: block;
      margin: 0 auto;
      border-radius: 8px;
    }

    .olympiads-page .roadmap-cert-source {
      color: rgba(255, 244, 201, 0.52);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.75rem;
      letter-spacing: 0.04em;
    }

    .olympiads-page .roadmap-cert-source a {
      color: var(--phase-accent);
      text-decoration: none;
    }

    .olympiads-page .roadmap-cert-source a:hover {
      color: #f4e8ca;
    }

    .olympiads-page .roadmap-table-wrap {
      overflow-x: auto;
    }

    .olympiads-page .roadmap-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: #0f0a06;
    }

    .olympiads-page .roadmap-table th,
    .olympiads-page .roadmap-table td {
      padding: 0.72rem 0.76rem;
      border-bottom: 1px solid rgba(255, 212, 146, 0.1);
      text-align: left;
      vertical-align: top;
    }

    .olympiads-page .roadmap-table th {
      color: rgba(255, 244, 201, 0.62);
      background: #171009;
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.72rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-table td {
      color: rgba(255, 244, 201, 0.76);
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .olympiads-page .roadmap-table td:first-child {
      color: #f6e7cb;
      width: 41%;
    }

    .olympiads-page .roadmap-table tr.is-featured td {
      background: rgba(211, 154, 50, 0.11);
    }

    .olympiads-page .roadmap-table tr.is-featured td:first-child {
      border-left: 3px solid #d39a32;
    }

    .olympiads-page .roadmap-status {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 5.4rem;
      padding: 0.24rem 0.48rem;
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.67rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      line-height: 1;
      text-transform: uppercase;
    }

    .olympiads-page .roadmap-status-upcoming {
      background: rgba(211, 154, 50, 0.14);
      color: #f0c46b;
    }

    .olympiads-page .roadmap-status-closed {
      background: rgba(255, 255, 255, 0.05);
      color: rgba(255, 244, 201, 0.48);
    }

    .olympiads-page .roadmap-directory {
      padding: 1rem 1.1rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      background: rgba(255, 255, 255, 0.02);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.9rem;
      line-height: 1.58;
      color: rgba(255, 244, 201, 0.72);
    }

    .olympiads-page .roadmap-directory-title {
      margin: 0 0 0.34rem;
      color: #f0c46b;
      font-weight: 600;
    }

    .olympiads-page .roadmap-directory-line {
      margin: 0.12rem 0;
      display: flex;
      align-items: flex-start;
      gap: 0.12rem;
    }

    .olympiads-page .roadmap-directory-branch {
      flex: 0 0 2.38rem;
      min-width: 2.38rem;
      color: rgba(255, 212, 146, 0.74);
    }

    .olympiads-page .roadmap-directory-content {
      min-width: 0;
    }

    .olympiads-page .roadmap-prep-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.88rem;
    }

    .olympiads-page .roadmap-prep-card {
      position: relative;
      min-width: 0;
      padding: 1rem;
      border: 1px solid rgba(255, 212, 146, 0.12);
      border-left: 2px solid rgba(var(--phase-rgb), 0.9);
      background: rgba(255, 255, 255, 0.02);
    }

    .olympiads-page .roadmap-prep-card h3 {
      margin: 0.58rem 0 0.24rem;
      color: #f6e7cb;
      font-size: 1.14rem;
      line-height: 1.18;
    }

    .olympiads-page .roadmap-prep-card p {
      margin: 0;
      color: rgba(255, 244, 201, 0.67);
      line-height: 1.56;
    }

    .olympiads-page .roadmap-prep-mark {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2.1rem;
      height: 2.1rem;
      border: 1px solid rgba(var(--phase-rgb), 0.24);
      background: rgba(var(--phase-rgb), 0.12);
      color: var(--phase-accent);
      font-family: "IBM Plex Mono", monospace;
      font-size: 0.76rem;
      font-weight: 600;
      line-height: 1;
      text-transform: uppercase;
    }

    @media (max-width: 980px) {
      .roadmap-subnav-shell {
        top: 4.55rem;
        margin-top: 0.96rem;
        margin-bottom: 1.18rem;
      }

      .olympiads-page section[id] {
        scroll-margin-top: 9.6rem;
      }

      .olympiads-page .roadmap-block-title {
        font-size: clamp(1.35rem, 5.9vw, 1.9rem);
      }

      .olympiads-page .roadmap-hero > h1 {
        font-size: clamp(1.06rem, 6.3vw, 1.52rem);
      }

      .olympiads-page .roadmap-card-grid,
      .olympiads-page .roadmap-card-grid.is-stack,
      .olympiads-page .roadmap-card-grid.is-certifications,
      .olympiads-page .roadmap-platform-board,
      .olympiads-page .roadmap-prep-grid,
      .olympiads-page .roadmap-practice-branch,
      .olympiads-page .roadmap-layout-two,
      .olympiads-page .roadmap-layout-practice {
        grid-template-columns: 1fr;
      }

      .olympiads-page .roadmap-platform-rail {
        grid-template-columns: 1fr;
        padding-top: 0.5rem;
      }

      .olympiads-page .roadmap-map-branch {
        grid-template-columns: 1fr;
      }

      .olympiads-page .roadmap-map-branch {
        padding-top: 0.5rem;
      }

      .olympiads-page .roadmap-platform-rail::before,
      .olympiads-page .roadmap-platform-lane::before,
      .olympiads-page .roadmap-platform-column-head::after,
      .olympiads-page .roadmap-map-branch::before,
      .olympiads-page .roadmap-map-branch-card::before,
      .olympiads-page .roadmap-practice-branch::before,
      .olympiads-page .roadmap-practice-branch-card::before {
        content: none;
      }

      .olympiads-page .roadmap-cert-image {
        width: 100%;
        max-width: 100%;
      }

      .olympiads-page .roadmap-table th,
      .olympiads-page .roadmap-table td {
        font-size: 0.84rem;
        line-height: 1.38;
      }
    }
  </style>
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
      <a href="/roadmap" class="active">Roadmap</a>
      <a href="/gallery">Gallery</a>
      <a href="/#home-details">About</a>
    </nav>
  </header>

  <main class="page-wrap olympiads-page">
    <section class="roadmap-hero">
      <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Roadmap</span>
      </nav>
      <h1>Roadmap</h1>
      <p class="olympiads-intro-copy">
        A structured cybersecurity learning path for students who want clear direction. Start with
        foundations, train on the right platforms, move into real challenge solving, then specialize,
        certify, and compete.
      </p>
      <div class="roadmap-subnav-shell" aria-label="Roadmap phases">
        <div class="roadmap-subnav-inner">
          <nav class="roadmap-subnav">
            <a class="roadmap-subnav-link phase-foundations is-active" href="#foundations"><span class="roadmap-nav-dot" aria-hidden="true"></span>Foundations</a>
            <a class="roadmap-subnav-link phase-platforms" href="#platforms"><span class="roadmap-nav-dot" aria-hidden="true"></span>Platforms</a>
            <a class="roadmap-subnav-link phase-practice" href="#practice"><span class="roadmap-nav-dot" aria-hidden="true"></span>CTF &amp; Practice</a>
            <a class="roadmap-subnav-link phase-specializations" href="#specializations"><span class="roadmap-nav-dot" aria-hidden="true"></span>Specializations</a>
            <a class="roadmap-subnav-link phase-certifications" href="#certifications"><span class="roadmap-nav-dot" aria-hidden="true"></span>Certifications</a>
            <a class="roadmap-subnav-link phase-olympiads" href="#olympiads"><span class="roadmap-nav-dot" aria-hidden="true"></span>Olympiads</a>
          </nav>
        </div>
      </div>
      <div class="roadmap-map" aria-label="Cybersecurity roadmap progression">
        <div class="roadmap-map-column">
          <a class="roadmap-map-node roadmap-map-link" href="#foundations">
            <strong class="roadmap-stage-name">Start Here</strong>
            <p class="roadmap-stage-note">Begin at the absolute beginner level. Build routine first, then remove confusion one layer at a time.</p>
          </a>
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-map-node roadmap-map-link is-foundation" href="#foundations">
            <strong class="roadmap-stage-name">Core Foundations</strong>
            <p class="roadmap-stage-note">Linux, networking, Python, and web basics. This is the true beginner layer.</p>
          </a>
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-map-node roadmap-map-link is-platforms" href="#platforms">
            <strong class="roadmap-stage-name">Learning Platforms</strong>
            <p class="roadmap-stage-note">Use guided platforms in order. Start simple, then move into harder labs only when the earlier layer feels stable.</p>
          </a>
        </div>
        <div class="roadmap-platform-rail" aria-label="Platform difficulty progression">
          <article class="roadmap-platform-lane is-foundation">
            <span class="roadmap-platform-lane-label">Beginner</span>
            <h3>Guided first reps</h3>
            <p>Use these first if you are still learning how systems, terminals, and basic exploitation fit together.</p>
            <ul class="roadmap-platform-lane-list">
              <li>TryHackMe foundation paths</li>
              <li>OverTheWire: Bandit and early wargames</li>
            </ul>
          </article>
          <article class="roadmap-platform-lane is-platforms">
            <span class="roadmap-platform-lane-label">Build method</span>
            <h3>Structured skill-building</h3>
            <p>This is where you start thinking more cleanly: repeatable process, web workflow, and category familiarity.</p>
            <ul class="roadmap-platform-lane-list">
              <li>PortSwigger Web Security Academy</li>
              <li>PicoCTF and CTFlearn</li>
            </ul>
          </article>
          <article class="roadmap-platform-lane is-practice">
            <span class="roadmap-platform-lane-label">Professional step-up</span>
            <h3>Independent labs</h3>
            <p>These make more sense once you can work with less guidance and write down your own methodology.</p>
            <ul class="roadmap-platform-lane-list">
              <li>HTB Academy modules</li>
              <li>Hack The Box machines and labs</li>
            </ul>
          </article>
        </div>
        <div class="roadmap-map-column">
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-map-node roadmap-map-link is-practice" href="#practice">
            <strong class="roadmap-stage-name">Practice and CTF</strong>
            <p class="roadmap-stage-note">This is the intermediate layer. Solve challenges repeatedly, write notes, and stop depending on step-by-step guidance.</p>
          </a>
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-map-node roadmap-map-link is-track" href="#specializations">
            <strong class="roadmap-stage-name">Choose a Direction</strong>
            <p class="roadmap-stage-note">Once labs stop feeling random, choose a main track and start working in a more professional way.</p>
          </a>
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-map-node roadmap-map-link is-advanced" href="#certifications">
            <strong class="roadmap-stage-name">Certify and Compete</strong>
            <p class="roadmap-stage-note">Only after the earlier layers are stable: certifications, olympiads, and serious competition discipline.</p>
          </a>
        </div>
        <div class="roadmap-map-branch" aria-label="Advanced directions">
          <a class="roadmap-map-branch-card roadmap-map-link" href="#specializations">
            <h3>Web Security</h3>
            <p>Fastest practical track for students who want clean vulnerability workflow and real application logic.</p>
          </a>
          <a class="roadmap-map-branch-card roadmap-map-link" href="#specializations">
            <h3>Pentesting</h3>
            <p>More independent offensive path built on enumeration, exploitation, persistence, and reporting.</p>
          </a>
          <a class="roadmap-map-branch-card roadmap-map-link" href="#specializations">
            <h3>Blue Team</h3>
            <p>Monitoring, detection engineering, alert triage, and incident-response thinking.</p>
          </a>
          <a class="roadmap-map-branch-card roadmap-map-link" href="#specializations">
            <h3>Reverse / Malware</h3>
            <p>Lower-level path for students who want binaries, debugging, and deeper analysis discipline.</p>
          </a>
        </div>
      </div>
    </section>

    <section id="foundations" class="section-divider roadmap-block roadmap-foundations">
      <div class="roadmap-block-head">
        <h2 class="roadmap-block-title">Foundations</h2>
        <p class="roadmap-block-copy">
          Before serious security work, you need stable fundamentals. This is the layer everything
          else depends on.
        </p>
      </div>

      <div class="roadmap-layout-two">
        <article class="roadmap-panel">
          <h3 class="roadmap-panel-title">Suggested Learning Path</h3>
          <div class="roadmap-timeline">
            <article class="roadmap-timeline-item">
              <span class="roadmap-timeline-dot" aria-hidden="true"></span>
              <span class="roadmap-timeline-meta">2-4 weeks</span>
              <h3>Linux Basics</h3>
              <p>Terminal navigation, permissions, processes, logs, file systems, and basic Bash.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Shell</span>
                <span class="roadmap-tag">Permissions</span>
                <span class="roadmap-tag">Logs</span>
              </div>
            </article>
            <article class="roadmap-timeline-item">
              <span class="roadmap-timeline-dot" aria-hidden="true"></span>
              <span class="roadmap-timeline-meta">3-5 weeks</span>
              <h3>Networking Basics</h3>
              <p>TCP/IP, ports, DNS, HTTP/S, subnetting, packet flow, and service behavior.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">TCP/IP</span>
                <span class="roadmap-tag">DNS</span>
                <span class="roadmap-tag">HTTP</span>
              </div>
            </article>
            <article class="roadmap-timeline-item">
              <span class="roadmap-timeline-dot" aria-hidden="true"></span>
              <span class="roadmap-timeline-meta">4-6 weeks</span>
              <h3>Python and Scripting</h3>
              <p>Automation, parsing output, handling files, and building simple security helpers.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Python</span>
                <span class="roadmap-tag">Requests</span>
                <span class="roadmap-tag">Automation</span>
              </div>
            </article>
            <article class="roadmap-timeline-item">
              <span class="roadmap-timeline-dot" aria-hidden="true"></span>
              <span class="roadmap-timeline-meta">2-3 weeks</span>
              <h3>Web Basics</h3>
              <p>Requests, sessions, forms, cookies, auth flows, and how dynamic apps behave.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Sessions</span>
                <span class="roadmap-tag">Cookies</span>
                <span class="roadmap-tag">Forms</span>
              </div>
            </article>
          </div>
        </article>

        <article class="roadmap-panel">
          <h3 class="roadmap-panel-title">Free Resource Stack</h3>
          <div class="roadmap-card-grid is-stack">
            <article class="roadmap-card">
              <span class="roadmap-card-mark">OT</span>
              <p class="roadmap-card-meta">Linux practice</p>
              <h3 class="roadmap-card-title">OverTheWire: Bandit</h3>
              <p>Strong first stop for terminal confidence and command-line problem solving.</p>
              <a class="roadmap-card-link" href="https://overthewire.org/wargames/" target="_blank" rel="noopener noreferrer">overthewire.org</a>
            </article>
            <article class="roadmap-card">
              <span class="roadmap-card-mark">CS</span>
              <p class="roadmap-card-meta">Computer science base</p>
              <h3 class="roadmap-card-title">CS50</h3>
              <p>Useful for programming discipline, systems thinking, and general technical maturity.</p>
              <a class="roadmap-card-link" href="https://cs50.harvard.edu/" target="_blank" rel="noopener noreferrer">cs50.harvard.edu</a>
            </article>
            <article class="roadmap-card">
              <span class="roadmap-card-mark">NA</span>
              <p class="roadmap-card-meta">Networking base</p>
              <h3 class="roadmap-card-title">Cisco NetAcad</h3>
              <p>Good structured networking material, especially if you need protocol clarity.</p>
              <a class="roadmap-card-link" href="https://www.netacad.com/" target="_blank" rel="noopener noreferrer">netacad.com</a>
            </article>
          </div>
        </article>
      </div>
    </section>

    <section id="platforms" class="section-divider roadmap-block roadmap-platforms">
      <div class="roadmap-block-head">
        <h2 class="roadmap-block-title">Learning Platforms</h2>
        <p class="roadmap-block-copy">
          Once fundamentals are stable, move into structured hands-on training. Use guided platforms
          first, then transition into harder and less forgiving labs.
        </p>
      </div>

      <div class="roadmap-platform-board">
        <section class="roadmap-platform-column is-beginner">
          <div class="roadmap-platform-column-head">
            <span class="roadmap-platform-column-meta">Beginner first</span>
            <h3>Start where guidance is strong</h3>
            <p class="roadmap-platform-column-copy">Use these first if you still need clarity, repetition, and a lower-friction environment.</p>
          </div>
          <div class="roadmap-card-grid is-stack">
            <article class="roadmap-card">
              <a class="roadmap-card-mark roadmap-card-mark-link" href="https://tryhackme.com/" target="_blank" rel="noopener noreferrer" aria-label="Open TryHackMe">
                <img class="roadmap-card-mark-logo" src="/assets/roadmap/tryhackme.svg" alt="TryHackMe logo">
              </a>
              <p class="roadmap-card-meta">Guided start</p>
              <h3 class="roadmap-card-title">TryHackMe</h3>
              <p>Best starting point for absolute beginners. Follow foundation and junior paths first.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Beginner</span>
                <span class="roadmap-tag">Browser labs</span>
              </div>
              <a class="roadmap-card-link" href="https://tryhackme.com/" target="_blank" rel="noopener noreferrer">tryhackme.com</a>
            </article>
            <article class="roadmap-card">
              <span class="roadmap-card-mark">OW</span>
              <p class="roadmap-card-meta">Command line</p>
              <h3 class="roadmap-card-title">OverTheWire</h3>
              <p>Good for terminal discipline, progression through constraints, and clean problem solving.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Wargames</span>
                <span class="roadmap-tag">Linux</span>
              </div>
              <a class="roadmap-card-link" href="https://overthewire.org/wargames/" target="_blank" rel="noopener noreferrer">overthewire.org</a>
            </article>
          </div>
        </section>

        <section class="roadmap-platform-column is-guided">
          <div class="roadmap-platform-column-head">
            <span class="roadmap-platform-column-meta">Build method</span>
            <h3>Move into category-focused work</h3>
            <p class="roadmap-platform-column-copy">Use these once the basics stop feeling random and you want cleaner security workflow.</p>
          </div>
          <div class="roadmap-card-grid is-stack">
            <article class="roadmap-card">
              <a class="roadmap-card-mark roadmap-card-mark-link" href="https://portswigger.net/web-security" target="_blank" rel="noopener noreferrer" aria-label="Open PortSwigger Web Security Academy">
                <img class="roadmap-card-mark-logo" src="/assets/roadmap/portswigger.png" alt="PortSwigger logo">
              </a>
              <p class="roadmap-card-meta">Web security</p>
              <h3 class="roadmap-card-title">PortSwigger Academy</h3>
              <p>One of the best web security training resources for methodology and lab realism.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Web</span>
                <span class="roadmap-tag">Free</span>
              </div>
              <a class="roadmap-card-link" href="https://portswigger.net/web-security" target="_blank" rel="noopener noreferrer">portswigger.net</a>
            </article>
            <article class="roadmap-card">
              <span class="roadmap-card-mark">PC</span>
              <p class="roadmap-card-meta">Challenge repetition</p>
              <h3 class="roadmap-card-title">PicoCTF and CTFlearn</h3>
              <p>Useful for solving many smaller challenges and building confidence across categories.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Beginner CTF</span>
                <span class="roadmap-tag">Practice</span>
              </div>
              <a class="roadmap-card-link" href="https://picoctf.org/" target="_blank" rel="noopener noreferrer">picoctf.org</a>
            </article>
          </div>
        </section>

        <section class="roadmap-platform-column is-advanced">
          <div class="roadmap-platform-column-head">
            <span class="roadmap-platform-column-meta">Professional step-up</span>
            <h3>Work with less guidance</h3>
            <p class="roadmap-platform-column-copy">Only move here after you can solve, document, and troubleshoot with more independence.</p>
          </div>
          <div class="roadmap-card-grid is-stack">
            <article class="roadmap-card">
              <a class="roadmap-card-mark roadmap-card-mark-link" href="https://academy.hackthebox.com/" target="_blank" rel="noopener noreferrer" aria-label="Open HTB Academy">
                <img class="roadmap-card-mark-logo" src="/assets/roadmap/hackthebox.png" alt="Hack The Box logo">
              </a>
              <p class="roadmap-card-meta">Structured depth</p>
              <h3 class="roadmap-card-title">HTB Academy</h3>
              <p>Strong technical modules for Linux, networking, web, and offensive/defensive workflows.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Modules</span>
                <span class="roadmap-tag">Intermediate</span>
              </div>
              <a class="roadmap-card-link" href="https://academy.hackthebox.com/" target="_blank" rel="noopener noreferrer">academy.hackthebox.com</a>
            </article>
            <article class="roadmap-card">
              <a class="roadmap-card-mark roadmap-card-mark-link" href="https://www.hackthebox.com/" target="_blank" rel="noopener noreferrer" aria-label="Open Hack The Box">
                <img class="roadmap-card-mark-logo" src="/assets/roadmap/hackthebox.png" alt="Hack The Box logo">
              </a>
              <p class="roadmap-card-meta">Realistic labs</p>
              <h3 class="roadmap-card-title">Hack The Box</h3>
              <p>Use once you can work more independently and handle incomplete information.</p>
              <div class="roadmap-tag-row">
                <span class="roadmap-tag">Machines</span>
                <span class="roadmap-tag">Labs</span>
              </div>
              <a class="roadmap-card-link" href="https://www.hackthebox.com/" target="_blank" rel="noopener noreferrer">hackthebox.com</a>
            </article>
          </div>
        </section>
      </div>
    </section>

    <section id="practice" class="section-divider roadmap-block roadmap-practice">
      <div class="roadmap-block-head">
        <h2 class="roadmap-block-title">CTF &amp; Practice</h2>
        <p class="roadmap-block-copy">
          This is where knowledge becomes skill. Move from guided learning into repeated challenge
          solving, competition rhythm, and stronger category depth.
        </p>
      </div>

      <div class="roadmap-practice-map">
        <div class="roadmap-practice-column">
          <a class="roadmap-practice-node" href="https://ctftime.org/" target="_blank" rel="noopener noreferrer">
            <span class="roadmap-card-mark">CT</span>
            <div>
              <span class="roadmap-practice-meta">Find events and formats</span>
              <h3>CTFtime</h3>
              <p>Use it to track competition calendars, event styles, and where to practice under time pressure.</p>
            </div>
          </a>
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-practice-node" href="https://picoctf.org/" target="_blank" rel="noopener noreferrer">
            <span class="roadmap-card-mark">PI</span>
            <div>
              <span class="roadmap-practice-meta">Beginner repetitions</span>
              <h3>PicoCTF and CTFlearn</h3>
              <p>Start here when you need many smaller challenges to build category familiarity and confidence.</p>
            </div>
          </a>
          <div class="roadmap-map-connector" aria-hidden="true"></div>
          <a class="roadmap-practice-node" href="https://cryptohack.org/" target="_blank" rel="noopener noreferrer">
            <span class="roadmap-card-mark">HC</span>
            <div>
              <span class="roadmap-practice-meta">Focused category drills</span>
              <h3>CryptoHack and targeted practice</h3>
              <p>Use specialized drills once you want deeper repetitions in one category instead of broad beginner coverage.</p>
            </div>
          </a>
        </div>

        <div class="roadmap-practice-branch" aria-label="CTF categories">
          <article class="roadmap-practice-branch-card">
            <h3>Web</h3>
            <p>Request handling, auth flaws, injection, file abuse, and application logic.</p>
          </article>
          <article class="roadmap-practice-branch-card">
            <h3>Reverse</h3>
            <p>Static analysis, debugging, assembly basics, and binary understanding.</p>
          </article>
          <article class="roadmap-practice-branch-card">
            <h3>Crypto</h3>
            <p>Encodings, hashes, RSA, AES, protocol reasoning, and implementation mistakes.</p>
          </article>
          <article class="roadmap-practice-branch-card">
            <h3>Forensics</h3>
            <p>Pcaps, artifacts, image analysis, memory hints, and reconstruction work.</p>
          </article>
          <article class="roadmap-practice-branch-card">
            <h3>Pwn</h3>
            <p>Memory corruption, protections, and lower-level exploitation chains.</p>
          </article>
        </div>

        <p class="roadmap-practice-note">
          The point of this stage is repetition. Solve, fail, write notes, solve again, and let
          categories become familiar instead of random.
        </p>
      </div>
    </section>

    <section id="specializations" class="section-divider roadmap-block roadmap-specializations">
      <div class="roadmap-block-head">
        <h2 class="roadmap-block-title">Specializations</h2>
        <p class="roadmap-block-copy">
          Cybersecurity is broad. After enough practice, choose one primary focus and one secondary
          focus so your progress stays deliberate.
        </p>
      </div>

      <div class="roadmap-card-grid">
        <article class="roadmap-card">
          <span class="roadmap-card-mark">RT</span>
          <p class="roadmap-card-meta">Offensive security</p>
          <h3 class="roadmap-card-title">Red Team / Pentesting</h3>
          <p>Enumeration, exploitation workflows, scoping discipline, and professional reporting.</p>
          <div class="roadmap-tag-row">
            <span class="roadmap-tag">Recon</span>
            <span class="roadmap-tag">Burp</span>
            <span class="roadmap-tag">Reporting</span>
          </div>
        </article>
        <article class="roadmap-card">
          <span class="roadmap-card-mark">BT</span>
          <p class="roadmap-card-meta">Detection and response</p>
          <h3 class="roadmap-card-title">Blue Team / SOC</h3>
          <p>Monitoring, triage, alert analysis, and structured incident-response habits.</p>
          <div class="roadmap-tag-row">
            <span class="roadmap-tag">SIEM</span>
            <span class="roadmap-tag">Logs</span>
            <span class="roadmap-tag">IR</span>
          </div>
        </article>
        <article class="roadmap-card">
          <span class="roadmap-card-mark">WS</span>
          <p class="roadmap-card-meta">Application security</p>
          <h3 class="roadmap-card-title">Web Security</h3>
          <p>One of the most practical paths for students: auth bugs, APIs, sessions, and logic flaws.</p>
          <div class="roadmap-tag-row">
            <span class="roadmap-tag">OWASP</span>
            <span class="roadmap-tag">APIs</span>
            <span class="roadmap-tag">Burp</span>
          </div>
        </article>
        <article class="roadmap-card">
          <span class="roadmap-card-mark">RE</span>
          <p class="roadmap-card-meta">Low-level analysis</p>
          <h3 class="roadmap-card-title">Reverse Engineering / Malware</h3>
          <p>Binary analysis, debugging, assembly awareness, and malware behavior tracing.</p>
          <div class="roadmap-tag-row">
            <span class="roadmap-tag">Ghidra</span>
            <span class="roadmap-tag">x64dbg</span>
            <span class="roadmap-tag">IDA</span>
          </div>
        </article>
        <article class="roadmap-card">
          <span class="roadmap-card-mark">CS</span>
          <p class="roadmap-card-meta">Optional track</p>
          <h3 class="roadmap-card-title">Cloud Security</h3>
          <p>Best added after networking and systems are strong enough to reason about cloud attack surface.</p>
          <div class="roadmap-tag-row">
            <span class="roadmap-tag">IAM</span>
            <span class="roadmap-tag">Config</span>
            <span class="roadmap-tag">AWS</span>
          </div>
        </article>
      </div>
    </section>

    <section id="certifications" class="section-divider roadmap-block roadmap-certifications">
      <div class="roadmap-block-head">
        <h2 class="roadmap-block-title">Certifications</h2>
        <p class="roadmap-block-copy">
          Certifications matter after you already have hands-on skill. They should confirm real ability,
          not replace it.
        </p>
      </div>

      <div class="roadmap-cert-visual">
        <div class="roadmap-cert-visual-head">
          <p class="roadmap-cert-source">
            <a href="https://pauljerimy.com/security-certification-roadmap/" target="_blank" rel="noopener noreferrer">Paul Jerimy Security Certification Roadmap</a>
          </p>
        </div>
        <img class="roadmap-cert-image" src="/assets/roadmap/security-certification-roadmap-reference.png" alt="Security certification roadmap reference image">
      </div>
    </section>

    <section id="olympiads" class="section-divider roadmap-block roadmap-olympiads">
      <div class="roadmap-block-head">
        <h2 class="roadmap-block-title">Olympiads</h2>
        <p class="roadmap-block-copy">
          The final stage of the roadmap is performance under structure. Aegis Lab prepares weekly
          for the Cybersecurity Olympiad and related technical competitions.
        </p>
      </div>

      <div class="roadmap-table-wrap" role="region" aria-label="Technical competition dates">
        <table class="roadmap-table">
          <thead>
            <tr>
              <th>Competition</th>
              <th>Regional / Local Stage</th>
              <th>National Stage</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr class="is-featured">
              <td>Cybersecurity Olympiad (ONSC)</td>
              <td>24 April 2026</td>
              <td>15-17 May 2026</td>
              <td><span class="roadmap-status roadmap-status-upcoming">Upcoming</span></td>
            </tr>
            <tr>
              <td>National Informatics Olympiad - Middle School</td>
              <td>2 March 2026</td>
              <td>22-26 March 2026</td>
              <td><span class="roadmap-status roadmap-status-closed">Closed</span></td>
            </tr>
            <tr>
              <td>National Informatics Olympiad - High School</td>
              <td>2 March 2026</td>
              <td>26-30 March 2026</td>
              <td><span class="roadmap-status roadmap-status-closed">Closed</span></td>
            </tr>
            <tr>
              <td>Applied Informatics Olympiad (AcadNet)</td>
              <td>21 March 2026</td>
              <td>9-12 May 2026</td>
              <td><span class="roadmap-status roadmap-status-upcoming">Upcoming</span></td>
            </tr>
            <tr>
              <td>National Artificial Intelligence Olympiad (ONIA)</td>
              <td>14 March 2026</td>
              <td>17-20 April 2026</td>
              <td><span class="roadmap-status roadmap-status-upcoming">Upcoming</span></td>
            </tr>
            <tr>
              <td>Information and Communication Technology Olympiad (TIC)</td>
              <td>22 March 2026</td>
              <td>26-29 May 2026</td>
              <td><span class="roadmap-status roadmap-status-upcoming">Upcoming</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <article class="roadmap-panel">
        <h3 class="roadmap-panel-title">How We Prepare</h3>
        <div class="roadmap-prep-grid">
          <article class="roadmap-prep-card">
            <span class="roadmap-prep-mark">PP</span>
            <h3>Frequent Topics</h3>
            <p>Web vulnerabilities, cryptography basics, Linux forensics, and networking fundamentals.</p>
          </article>
          <article class="roadmap-prep-card">
            <span class="roadmap-prep-mark">TL</span>
            <h3>Core Tooling</h3>
            <p>Burp Suite, Wireshark, Ghidra, CyberChef, Nmap, and a stable Linux lab environment.</p>
          </article>
          <article class="roadmap-prep-card">
            <span class="roadmap-prep-mark">CT</span>
            <h3>CTF Repetition</h3>
            <p>Weekly challenge solving, review sessions, and competition-style practice before official stages.</p>
          </article>
        </div>
      </article>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
  <script>
    (function () {
      const links = Array.from(document.querySelectorAll(".roadmap-subnav-link"));
      const sections = links
        .map((link) => document.querySelector(link.getAttribute("href")))
        .filter(Boolean);

      if (!links.length || !sections.length) {
        return;
      }

      const setActive = (id) => {
        links.forEach((link) => {
          const isActive = link.getAttribute("href") === "#" + id;
          link.classList.toggle("is-active", isActive);
        });
      };

      links.forEach((link) => {
        link.addEventListener("click", (event) => {
          const targetId = link.getAttribute("href");
          const target = targetId ? document.querySelector(targetId) : null;

          if (!target) {
            return;
          }

          event.preventDefault();
          setActive(target.id);
          target.scrollIntoView({ behavior: "smooth", block: "start" });
        });
      });

      const observer = new IntersectionObserver(
        (entries) => {
          const visible = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

          if (visible) {
            setActive(visible.target.id);
          }
        },
        {
          rootMargin: "-22% 0px -54% 0px",
          threshold: [0.2, 0.38, 0.62],
        }
      );

      sections.forEach((section) => observer.observe(section));
      setActive("foundations");
    })();
  </script>
</body>
</html>

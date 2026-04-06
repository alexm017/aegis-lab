<?php
require_once __DIR__ . '/lib/site_data.php';
$siteContent = zs_get_site_content();
$schoolAddress = trim((string)($siteContent['school_address'] ?? ''));
if ($schoolAddress === '') {
  $schoolAddress = 'Your High School Address, Your City';
}

$mapUrl = trim((string)($siteContent['google_maps_url'] ?? ''));
if ($mapUrl === '') {
  $mapUrl = 'https://www.google.com/maps?q=' . rawurlencode($schoolAddress);
}
$mapUrl = zs_safe_http_url($mapUrl, 'https://www.google.com/maps?q=' . rawurlencode($schoolAddress));

$mapEmbedUrl = trim((string)($siteContent['google_maps_embed_url'] ?? ''));
if ($mapEmbedUrl === '') {
  $mapEmbedUrl = 'https://www.google.com/maps?q=' . rawurlencode($schoolAddress) . '&output=embed';
}
$mapEmbedUrl = zs_safe_http_url($mapEmbedUrl, 'https://www.google.com/maps?q=' . rawurlencode($schoolAddress) . '&output=embed');

$emailAddress = trim((string)($siteContent['contact_email'] ?? 'contact@aegislab.ro'));
if ($emailAddress === '') {
  $emailAddress = 'contact@aegislab.ro';
}
$emailHref = zs_safe_mailto_href($emailAddress);

$discordUrl = trim((string)($siteContent['discord_url'] ?? 'https://discord.gg/9bjk4FeRZS'));
if ($discordUrl === '') {
  $discordUrl = 'https://discord.gg/9bjk4FeRZS';
}
$discordUrl = zs_safe_http_url($discordUrl, 'https://discord.gg/9bjk4FeRZS');

$youtubeUrl = trim((string)($siteContent['youtube_url'] ?? 'https://www.youtube.com/@AegisLabTeam'));
if ($youtubeUrl === '') {
  $youtubeUrl = 'https://www.youtube.com/@AegisLabTeam';
}
$youtubeUrl = zs_safe_http_url($youtubeUrl, 'https://www.youtube.com/@AegisLabTeam');

$instagramUrl = trim((string)($siteContent['instagram_url'] ?? 'https://www.instagram.com/aegislab_team/'));
if ($instagramUrl === '') {
  $instagramUrl = 'https://www.instagram.com/aegislab_team/';
}
$instagramUrl = zs_safe_http_url($instagramUrl, 'https://www.instagram.com/aegislab_team/');

$linkedinUrl = trim((string)($siteContent['linkedin_url'] ?? 'https://www.linkedin.com/company/aegis-labteam/'));
if ($linkedinUrl === '') {
  $linkedinUrl = 'https://www.linkedin.com/company/aegis-labteam/';
}
$linkedinUrl = zs_safe_http_url($linkedinUrl, 'https://www.linkedin.com/company/aegis-labteam/');

$ctfUrl = zs_safe_http_url((string)($siteContent['ctf_url'] ?? ''), 'https://ctf.aegislab.ro/');

$canonicalBase = 'https://aegislab.ro';
$canonicalUrl = $canonicalBase . '/';
$pageTitle = 'Aegis Lab Cybersecurity Team - High School Cybersecurity Team';
$pageDescription = 'Aegis Lab is a high school cybersecurity team focused on practical offensive and defensive security training, competitions, weekly meetings, and real-world security workflows.';
$ogImage = $canonicalBase . '/Aegis.png';

$sameAs = [];
foreach ([$discordUrl, $youtubeUrl, $instagramUrl, $linkedinUrl] as $socialUrl) {
  if ($socialUrl !== '' && preg_match('/^https?:\\/\\//i', $socialUrl)) {
    $sameAs[] = $socialUrl;
  }
}

$homeStructuredData = [
  '@context' => 'https://schema.org',
  '@graph' => [
    [
      '@type' => 'WebSite',
      '@id' => $canonicalBase . '/#website',
      'url' => $canonicalUrl,
      'name' => 'Aegis Lab Cybersecurity Team',
      'alternateName' => ['Aegis Lab', 'Aegis Lab Romania'],
    ],
    [
      '@type' => 'Organization',
      '@id' => $canonicalBase . '/#organization',
      'name' => 'Aegis Lab Cybersecurity Team',
      'alternateName' => ['Aegis Lab', 'Aegis Lab Romania'],
      'url' => $canonicalUrl,
      'logo' => $ogImage,
      'description' => $pageDescription,
      'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'Strada 1 Decembrie 1918 7',
        'addressLocality' => 'Petroșani',
        'addressCountry' => 'RO',
      ],
    ],
  ],
];

if ($sameAs !== []) {
  $homeStructuredData['@graph'][1]['sameAs'] = $sameAs;
}

if ($emailAddress !== '') {
  $homeStructuredData['@graph'][1]['contactPoint'] = [
    [
      '@type' => 'ContactPoint',
      'contactType' => 'general inquiries',
      'email' => $emailAddress,
    ],
  ];
}

$homeStructuredDataJson = json_encode(
  $homeStructuredData,
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($homeStructuredDataJson)) {
  $homeStructuredDataJson = '{}';
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
  <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Jersey+10&family=Micro+5&family=Press+Start+2P&family=Silkscreen:wght@400;700&family=Space+Grotesk:wght@400;500;700&family=VT323&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles.css?v=20260407sponsors14">
  <script type="application/ld+json"><?= $homeStructuredDataJson ?></script>
</head>
<body class="home-page">
  <header class="site-header">
    <a class="site-brand-link mobile-home-brand" href="/" aria-label="Go to Aegis Lab home page">
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
      <a href="#home-details">About</a>
    </nav>
  </header>

  <main class="home-main">
    <section class="main-hero">
      <div class="hero-layout">
        <div class="hero-copy">
          <h1 class="hero-team-name" data-typing="Aegis Lab"></h1>
          <p class="hero-subtitle"><?= zs_escape($siteContent['hero_subtitle']) ?></p>
        </div>
        <div class="hero-logo-wrap">
          <img class="hero-logo" src="/Aegis.png" alt="Aegis Lab team logo">
        </div>
      </div>
      <a class="scroll-hint" href="#home-details" aria-label="Scroll down to team information">↓ Scroll Down</a>
    </section>

    <section id="home-details" class="home-details">
      <div class="home-details-inner">
        <h2>More About Aegis Lab</h2>
        <p>
          <?= zs_escape($siteContent['home_intro']) ?>
        </p>
        <p class="home-details-weekly-note">We have a team meeting every week.</p>

        <section id="how-to-join" class="section-divider home-join-section">
          <h2>How to Join</h2>
          <p>
            Apply to Aegis Lab, our high school cybersecurity team. Share your background,
            interests, and availability so we can organize training groups, weekly sessions,
            and competition preparation.
          </p>
          <p>
            To join the team you can <a class="text-link is-underlined" href="https://forms.gle/D8Yv7RE3ZJp8Uc6g9" target="_blank" rel="noopener noreferrer">Apply here</a>.
            (For international users <a class="text-link is-underlined-muted" href="https://forms.gle/5dq9svpiGNDMUCxp8" target="_blank" rel="noopener noreferrer">Click here</a>)
          </p>
          <p>No prior competition experience is required.</p>
        </section>

        <section id="contact" class="section-divider">
          <h2>Contact</h2>
          <p>If you have any questions don't hesitate to contact our team</p>
          <div class="contact-links" aria-label="Contact links">
            <a class="contact-link-btn" href="<?= zs_escape($discordUrl) ?>" target="_blank" rel="noopener noreferrer">
              <span class="contact-link-icon" aria-hidden="true">
                <img class="contact-link-icon-image" src="/assets/icons/discord.svg" alt="">
              </span>
              <span>Discord</span>
            </a>
            <a class="contact-link-btn" href="<?= zs_escape($emailHref) ?>">
              <span class="contact-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                  <rect x="2.5" y="5.1" width="19" height="13.8" rx="2.2"></rect>
                  <path class="icon-cutout" d="M3.7 7.3L12 13.2L20.3 7.3L20.3 8.9L12 14.6L3.7 8.9Z"></path>
                </svg>
              </span>
              <span>Email</span>
            </a>
            <a class="contact-link-btn" href="<?= zs_escape($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer">
              <span class="contact-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                  <rect x="2.2" y="5.7" width="19.6" height="12.6" rx="3.6"></rect>
                  <path class="icon-cutout" d="M10 9.2L15.9 12L10 14.8Z"></path>
                </svg>
              </span>
              <span>YouTube</span>
            </a>
            <a class="contact-link-btn" href="<?= zs_escape($instagramUrl) ?>" target="_blank" rel="noopener noreferrer">
              <span class="contact-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                  <rect x="3.2" y="3.2" width="17.6" height="17.6" rx="4.4"></rect>
                  <circle class="icon-cutout" cx="12" cy="12" r="3.5"></circle>
                  <circle class="icon-cutout" cx="16.8" cy="7.4" r="1.2"></circle>
                </svg>
              </span>
              <span>Instagram</span>
            </a>
            <a class="contact-link-btn" href="<?= zs_escape($linkedinUrl) ?>" target="_blank" rel="noopener noreferrer">
              <span class="contact-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                  <rect x="3.1" y="3.1" width="17.8" height="17.8" rx="2.7"></rect>
                  <circle class="icon-cutout" cx="7.95" cy="7.7" r="1.25"></circle>
                  <rect class="icon-cutout" x="6.8" y="10" width="2.3" height="7"></rect>
                  <path class="icon-cutout" d="M11.3 17V10h2.2v1.1c.6-.8 1.5-1.3 2.6-1.3 1.9 0 3.1 1.3 3.1 3.6V17h-2.3v-3.1c0-1.1-.5-1.8-1.5-1.8-1 0-1.6.8-1.6 2V17z"></path>
                </svg>
              </span>
              <span>LinkedIn</span>
            </a>
          </div>
        </section>

        <section id="location" class="section-divider">
          <h2>Location</h2>
          <p><strong>Address:</strong> <?= zs_escape($schoolAddress) ?></p>
          <p><strong>Google Maps:</strong> <a class="text-link" href="<?= zs_escape($mapUrl) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a></p>
          <div class="location-map-wrap">
            <iframe class="location-map" src="<?= zs_escape($mapEmbedUrl) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="High school location on Google Maps" allowfullscreen></iframe>
          </div>
        </section>

        <section id="sponsors" class="section-divider sponsors-section">
          <h2>Our Partners</h2>
          <p class="sponsors-subtitle">Extended by those who believe in the work</p>
          <div class="sponsors-list">
            <div class="sponsor-card">
              <img class="sponsor-logo" src="/assets/sponsors/eminescu_sponsor_final.png" alt="Colegiul Național Mihai Eminescu sponsor logo">
            </div>
          </div>
        </section>
      </div>
    </section>
  </main>

  <footer>
    <p>Aegis Lab 2026</p>
  </footer>

  <script src="/main.js?v=20260329vp1"></script>
</body>
</html>

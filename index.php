<?php
$jsonFile = __DIR__ . '/CSV to JSON.json';
$games = [];
if (file_exists($jsonFile)) {
    $raw = file_get_contents($jsonFile);
    $games = json_decode($raw, true) ?? [];
}

// Collect unique filter values
$ratings     = [];
$platforms   = [];
$years       = [];
$descriptors = [];

foreach ($games as $game) {
    $r = trim($game['rating'] ?? '');
    if ($r !== '' && !in_array($r, $ratings, true)) {
        $ratings[] = $r;
    }

    // Platforms can be comma-separated; normalize common variants
    $rawPlatforms = explode(',', $game['platform'] ?? '');
    foreach ($rawPlatforms as $p) {
        $p = trim($p);
        // Normalize "P C" -> "PC"
        $p = preg_replace('/^P\s+C$/i', 'PC', $p);
        if ($p !== '' && !in_array($p, $platforms, true)) {
            $platforms[] = $p;
        }
    }

    $y = (int)($game['release_year'] ?? 0);
    if ($y > 0 && !in_array($y, $years, true)) {
        $years[] = $y;
    }

    // Descriptors / content classification
    foreach (explode(',', $game['descriptor'] ?? '') as $d) {
        $d = trim($d);
        if ($d !== '' && !in_array($d, $descriptors, true)) {
            $descriptors[] = $d;
        }
    }
}

// Sort
$ratingOrder = ['3+', '13+', '15+', '18+', 'RC'];
usort($ratings, function($a, $b) use ($ratingOrder) {
    $ai = array_search($a, $ratingOrder);
    $bi = array_search($b, $ratingOrder);
    $ai = $ai === false ? 99 : $ai;
    $bi = $bi === false ? 99 : $bi;
    return $ai <=> $bi;
});
sort($platforms);
rsort($years);
sort($descriptors);

// Encode for JS
$gamesJson = json_encode($games, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="assets/semicolon.ico">
<title>IGRS — Indonesian Game Rating System</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:        #0c0c10;
    --surface:   #13131a;
    --surface2:  #1c1c27;
    --border:    #2a2a3d;
    --accent:    #7c6eff;
    --accent2:   #a78bfa;
    --text:      #e8e8f0;
    --muted:     #8888aa;
    --danger:    #ff5c5c;
    --warn:      #ffb545;
    --ok:        #4caf87;
    --info:      #60a5fa;
    --radius:    12px;
    --radius-sm: 7px;
    --transition: 0.2s ease;
  }

  html { scroll-behavior: smooth; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    font-size: 15px;
    line-height: 1.6;
    min-height: 100vh;
  }

  /* ── NAV ── */
  nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(12,12,16,.85);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
  }

  .nav-brand {
    display: flex;
    align-items: center;
    gap: .55rem;
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: .03em;
    color: var(--text);
    text-decoration: none;
    min-width: 0;
  }

  .nav-brand-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .nav-brand .logo-img {
    height: 28px;
    width: auto;
    display: block;
  }

  .nav-actions { display: flex; gap: .75rem; align-items: center; }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem 1rem;
    border-radius: var(--radius-sm);
    border: 1px solid transparent;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    white-space: nowrap;
  }

  .btn-ghost {
    background: transparent;
    border-color: var(--border);
    color: var(--muted);
  }
  .btn-ghost:hover { border-color: var(--accent); color: var(--accent); }

  .btn-primary {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }
  .btn-primary:hover { background: var(--accent2); border-color: var(--accent2); filter: brightness(1.08); }

  /* ── HERO ── */
  .hero {
    padding: 5rem 1.5rem 3.5rem;
    text-align: center;
    background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(124,110,255,.12) 0%, transparent 70%);
  }

  .hero-label {
    display: inline-block;
    background: rgba(124,110,255,.15);
    border: 1px solid rgba(124,110,255,.35);
    color: var(--accent2);
    font-size: .73rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    padding: .3rem .8rem;
    border-radius: 999px;
    margin-bottom: 1.2rem;
  }

  .hero h1 {
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 800;
    line-height: 1.15;
    background: linear-gradient(135deg, #ffffff 30%, var(--accent2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: .8rem;
  }

  .hero p {
    color: var(--muted);
    max-width: 520px;
    margin: 0 auto 2rem;
    font-size: 1rem;
  }

  .hero-stats {
    display: flex;
    justify-content: center;
    gap: 2.5rem;
    flex-wrap: wrap;
  }

  .stat { text-align: center; }
  .stat-num { font-size: 1.6rem; font-weight: 800; color: var(--text); }
  .stat-lbl { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }

  /* ── RATING GUIDE (Gamer Access) ── */
  .gamer-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    margin: 0 1.5rem 2.5rem;
    max-width: 1100px;
    margin-left: auto;
    margin-right: auto;
  }

  .section-heading {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--accent2);
    margin-bottom: 1.2rem;
  }

  .section-heading::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
  }

  .rating-guide-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
  }

  .rg-card {
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 1.4rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .75rem;
    transition: var(--transition);
    cursor: pointer;
  }

  .rg-card:hover { border-color: var(--accent); transform: translateY(-2px); }
  .rg-card.active-filter { border-color: var(--accent); background: rgba(124,110,255,.1); }

  .rg-badge { width: 64px; height: 64px; object-fit: contain; }
  .rg-name  { font-size: .82rem; font-weight: 700; color: var(--text); }
  .rg-desc  { font-size: .75rem; color: var(--muted); line-height: 1.55; }

  /* ── FILTER BAR ── */
  .filter-bar {
    max-width: 1100px;
    margin: 0 auto 1.5rem;
    padding: 0 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    align-items: center;
  }

  .search-wrap {
    flex: 1;
    min-width: 220px;
    position: relative;
  }

  .search-wrap svg {
    position: absolute;
    left: .85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    pointer-events: none;
  }

  .search-input {
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    color: var(--text);
    padding: .6rem .75rem .6rem 2.4rem;
    font-size: .85rem;
    outline: none;
    transition: var(--transition);
  }
  .search-input::placeholder { color: var(--muted); }
  .search-input:focus { border-color: var(--accent); }

  .select-filter {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    color: var(--text);
    padding: .6rem .9rem;
    font-size: .83rem;
    outline: none;
    cursor: pointer;
    transition: var(--transition);
    min-width: 130px;
  }
  .select-filter:focus { border-color: var(--accent); }
  .select-filter option { background: var(--surface2); }

  .filter-count {
    font-size: .8rem;
    color: var(--muted);
    white-space: nowrap;
  }

  /* ── GAME GRID ── */
  .game-grid {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem 4rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 1rem;
  }

  .game-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .65rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
  }

  .game-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: var(--radius);
    opacity: 0;
    transition: var(--transition);
    background: radial-gradient(circle at 0 0, rgba(124,110,255,.08), transparent 70%);
  }

  .game-card:hover { border-color: rgba(124,110,255,.45); transform: translateY(-3px); box-shadow: 0 12px 40px rgba(0,0,0,.35); }
  .game-card:hover::before { opacity: 1; }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .5rem;
  }

  .card-title {
    font-size: .95rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1.3;
  }

  .rating-badge {
    flex-shrink: 0;
    height: 36px;
    width: auto;
    object-fit: contain;
  }

  .rating-badge-fallback {
    flex-shrink: 0;
    font-size: .72rem;
    font-weight: 800;
    padding: .25rem .55rem;
    border-radius: 5px;
    letter-spacing: .04em;
    white-space: nowrap;
  }

  .rating-3  { background: rgba(76,175,135,.2);  color: #4caf87; border: 1px solid rgba(76,175,135,.4); }
  .rating-13 { background: rgba(96,165,250,.2);  color: #60a5fa; border: 1px solid rgba(96,165,250,.4); }
  .rating-15 { background: rgba(255,181,69,.2);  color: #ffb545;  border: 1px solid rgba(255,181,69,.4); }
  .rating-18 { background: rgba(255,92,92,.2);   color: #ff5c5c;  border: 1px solid rgba(255,92,92,.4); }
  .rating-rc { background: rgba(180,0,0,.25);     color: #ff2d2d;  border: 1px solid rgba(200,0,0,.5); }
  .rating-na { background: rgba(136,136,170,.15); color: var(--muted); border: 1px solid var(--border); }

  .card-about {
    font-size: .8rem;
    color: var(--muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: auto;
  }

  .meta-tag {
    font-size: .7rem;
    background: var(--surface2);
    border: 1px solid var(--border);
    color: var(--muted);
    padding: .18rem .5rem;
    border-radius: 4px;
    white-space: nowrap;
  }

  .meta-tag.year { color: var(--accent2); border-color: rgba(124,110,255,.3); background: rgba(124,110,255,.07); }

  .descriptor-row {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  .desc-tag {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .65rem;
    padding: .2rem .45rem;
    background: rgba(255,92,92,.06);
    border: 1px solid rgba(255,92,92,.18);
    color: #ff8c8c;
    border-radius: 4px;
    white-space: nowrap;
  }

  .desc-tag img {
    width: 14px;
    height: 14px;
    object-fit: contain;
    flex-shrink: 0;
  }

  /* ── EMPTY STATE ── */
  .empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 1rem;
    color: var(--muted);
  }
  .empty-state svg { opacity: .3; margin-bottom: 1rem; }
  .empty-state p { font-size: 1rem; }

  /* ── FOOTER ── */
  footer {
    border-top: 1px solid var(--border);
    padding: 1.75rem 1.5rem;
    text-align: center;
    color: var(--muted);
    font-size: .78rem;
  }

  footer a { color: var(--accent2); text-decoration: none; }
  footer a:hover { text-decoration: underline; }

  /* ── DISCLAIMER ── */
  .disclaimer-bar {
    background: rgba(255,181,69,.08);
    border-bottom: 1px solid rgba(255,181,69,.25);
    padding: .6rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .65rem;
    font-size: .78rem;
    color: #d4a030;
    flex-wrap: wrap;
    text-align: center;
  }

  .disclaimer-bar svg { flex-shrink: 0; opacity: .85; }

  .disclaimer-bar a {
    color: #ffb545;
    text-decoration: underline;
    text-underline-offset: 2px;
  }
  .disclaimer-bar a:hover { color: #ffd080; }

  .disclaimer-close {
    background: none;
    border: none;
    color: #d4a030;
    cursor: pointer;
    padding: 0 0 0 .25rem;
    opacity: .6;
    font-size: 1rem;
    line-height: 1;
    flex-shrink: 0;
  }
  .disclaimer-close:hover { opacity: 1; }

  /* ── ABOUT IGRS ── */
  .about-section {
    max-width: 1100px;
    margin: 0 auto 2.5rem;
    padding: 0 1.5rem;
  }

  .about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  @media (max-width: 760px) {
    .about-grid { grid-template-columns: 1fr; }
  }

  .about-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
  }

  .about-card.full-width {
    grid-column: 1 / -1;
  }

  .about-card h3 {
    font-size: .95rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: .75rem;
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .about-card h3 .icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: rgba(124,110,255,.15);
    border: 1px solid rgba(124,110,255,.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .about-card p, .about-card li {
    font-size: .82rem;
    color: var(--muted);
    line-height: 1.65;
  }

  .about-card ol {
    padding-left: 1.2rem;
    display: flex;
    flex-direction: column;
    gap: .4rem;
  }

  .about-card ol li::marker {
    color: var(--accent2);
    font-weight: 700;
  }

  .about-link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin-top: .9rem;
    font-size: .78rem;
    color: var(--accent2);
    text-decoration: none;
    border: 1px solid rgba(124,110,255,.3);
    border-radius: 5px;
    padding: .35rem .75rem;
    transition: var(--transition);
  }
  .about-link:hover { background: rgba(124,110,255,.1); border-color: var(--accent); }

  .process-steps {
    display: flex;
    flex-direction: column;
    gap: .55rem;
  }

  .process-step {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
  }

  .step-num {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(124,110,255,.2);
    border: 1px solid rgba(124,110,255,.4);
    color: var(--accent2);
    font-size: .7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: .15rem;
  }

  .step-text {
    font-size: .81rem;
    color: var(--muted);
    line-height: 1.5;
  }

  /* ── RESPONSIVE ── */
  @media (max-width: 600px) {
    nav { padding: 0 1rem; }
    .nav-brand-text { display: none; }
    .nav-actions .btn-ghost:first-child { display: none; }
    .hero { padding: 3rem 1rem 2.5rem; }
    .about-section { padding: 0 1rem; }
    .gamer-section { margin: 0 1rem 2rem; padding: 1.25rem; }
    .filter-bar { padding: 0 1rem; }
    .game-grid { padding: 0 1rem 3rem; }
  }
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a class="nav-brand" href="#">
    <img class="logo-img" src="assets/logo white IGRS.webp" alt="IGRS">
    <span class="nav-brand-text">Indonesian Game Rating System</span>
  </a>
  <div class="nav-actions">
    <a class="btn btn-ghost" href="#about-igrs">Tentang IGRS</a>
    <a class="btn btn-ghost" href="#gamer-guide">Panduan Rating</a>
    <a class="btn btn-primary" href="#games">Cari Game</a>
  </div>
</nav>

<!-- DISCLAIMER -->
<div class="disclaimer-bar" id="disclaimerBar">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  <span>
    <strong>Ini bukan situs resmi IGRS.</strong>
    Halaman ini dibuat untuk keperluan informasi. Data rating game bersumber dari situs resmi
    <a href="https://igrs.id" target="_blank" rel="noopener noreferrer">igrs.id</a>.
  </span>
  <button class="disclaimer-close" onclick="document.getElementById('disclaimerBar').style.display='none'" aria-label="Tutup">&times;</button>
</div>

<!-- HERO -->
<section class="hero">
  <span class="hero-label">Sistem Rating Game Indonesia</span>
  <h1>Temukan Rating<br>Game Favoritmu</h1>
  <p>Database lengkap rating konten game Indonesia. Cek kelayakan usia sebelum bermain.</p>
  <div class="hero-stats">
    <div class="stat">
      <div class="stat-num" id="totalGames">—</div>
      <div class="stat-lbl">Total Game</div>
    </div>
    <div class="stat">
      <div class="stat-num" id="totalPublishers">—</div>
      <div class="stat-lbl">Publisher</div>
    </div>
    <div class="stat">
      <div class="stat-num" id="totalPlatforms">—</div>
      <div class="stat-lbl">Platform</div>
    </div>
  </div>
</section>

<!-- ABOUT IGRS -->
<section id="about-igrs" class="about-section">
  <div class="section-heading" style="margin-bottom:1.2rem">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
    Tentang IGRS
  </div>

  <div class="about-grid">

    <!-- What is IGRS -->
    <div class="about-card full-width">
      <h3>
        <span class="icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.2"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/><path d="M12 8v4l3 3"/></svg>
        </span>
        Indonesia Game Rating System (IGRS)
      </h3>
      <p>IGRS adalah layanan publik yang diselenggarakan oleh <strong style="color:var(--text)">Kementerian Komunikasi dan Digital (Komdigi)</strong> sebagai bagian dari komitmen pemerintah dalam mewujudkan sistem elektronik dan transaksi yang aman, andal, dan bertanggung jawab — khususnya untuk game. Klasifikasi game dilakukan sesuai dengan <strong style="color:var(--text)">Peraturan Menteri Kominfo No. 2 Tahun 2024</strong> tentang Klasifikasi Game.</p>
      <p style="margin-top:.65rem">Layanan ini bertujuan memberikan klasifikasi usia dan konten bagi game yang beredar di Indonesia, dengan mempertimbangkan norma sosial dan budaya serta kepatuhan terhadap peraturan perundang-undangan. Melalui sistem klasifikasi yang transparan dan akuntabel, IGRS menjadi upaya strategis negara untuk menyeimbangkan perkembangan industri kreatif digital dan kebutuhan melindungi masyarakat dari potensi dampak negatif konten game.</p>
      <a class="about-link" href="https://s.id/usermanualIGRS" target="_blank" rel="noopener noreferrer">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        User Manual IGRS
      </a>
    </div>

    <!-- Scope of Service -->
    <div class="about-card">
      <h3>
        <span class="icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        Proses Bisnis Utama
      </h3>
      <div class="process-steps">
        <div class="process-step"><span class="step-num">1</span><span class="step-text">Pembuatan akun klasifikasi oleh publisher/developer game</span></div>
        <div class="process-step"><span class="step-num">2</span><span class="step-text">Verifikasi data dan kelengkapan dokumen</span></div>
        <div class="process-step"><span class="step-num">3</span><span class="step-text">Uji kepatuhan konten game terhadap regulasi</span></div>
        <div class="process-step"><span class="step-num">4</span><span class="step-text">Penanganan pengaduan masyarakat</span></div>
        <div class="process-step"><span class="step-num">5</span><span class="step-text">Konsultasi klasifikasi game</span></div>
      </div>
    </div>

    <!-- Classification Process -->
    <div class="about-card">
      <h3>
        <span class="icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </span>
        Alur Klasifikasi Game
      </h3>
      <div class="process-steps">
        <div class="process-step"><span class="step-num">1</span><span class="step-text">Publisher/developer mendaftar akun di website IGRS</span></div>
        <div class="process-step"><span class="step-num">2</span><span class="step-text">Melakukan self-assessment terhadap game</span></div>
        <div class="process-step"><span class="step-num">3</span><span class="step-text">Menunggu hasil verifikasi dari IGRS</span></div>
        <div class="process-step"><span class="step-num">4</span><span class="step-text">Dapat mengajukan banding terhadap hasil verifikasi</span></div>
        <div class="process-step"><span class="step-num">5</span><span class="step-text">Mengunduh sertifikat klasifikasi</span></div>
      </div>
    </div>

  </div>
</section>

<!-- GAMER GUIDE / GAMMER ACCESS -->
<div id="gamer-guide" class="gamer-section">
  <div class="section-heading">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M12 8v4l3 3"/></svg>
    Panduan Rating — Akses Gamer
  </div>
  <div class="rating-guide-grid">
    <div class="rg-card" onclick="filterByRating('3+')" data-rg="3+">
      <img class="rg-badge" src="assets/3+.png" alt="3+">
      <div class="rg-name">3 Tahun ke Atas</div>
      <div class="rg-desc">Tidak menampilkan rokok, alkohol, narkoba, kekerasan, darah, mutilasi, kanibalisme, bahasa kasar, humor dewasa, pornografi, perjudian, horor, maupun fitur interaksi online.</div>
    </div>
    <div class="rg-card" onclick="filterByRating('7+')" data-rg="7+">
      <img class="rg-badge" src="assets/7+.png" alt="7+">
      <div class="rg-name">7 Tahun ke Atas</div>
      <div class="rg-desc">Tidak menampilkan rokok, alkohol, narkoba, kekerasan, mutilasi, kanibalisme, atau elemen darah tidak realistis; bebas bahasa kasar, humor dewasa, pornografi, perjudian, horor, dan interaksi online.</div>
    </div>
    <div class="rg-card" onclick="filterByRating('13+')" data-rg="13+">
      <img class="rg-badge" src="assets/13+.png" alt="13+">
      <div class="rg-name">13 Tahun ke Atas</div>
      <div class="rg-desc">Tanpa rokok, alkohol, narkoba, mutilasi atau kanibalisme terhadap manusia, humor seksual, pornografi, perjudian, atau horor ekstrem. Boleh ada elemen darah, kekerasan animasi terbatas, dan percakapan online berfilter.</div>
    </div>
    <div class="rg-card" onclick="filterByRating('15+')" data-rg="15+">
      <img class="rg-badge" src="assets/15+.png" alt="15+">
      <div class="rg-name">15 Tahun ke Atas</div>
      <div class="rg-desc">Tanpa rokok, alkohol, narkoba, mutilasi, kanibalisme, pornografi, perjudian, atau horor ekstrem. Boleh ada kekerasan animasi terbatas, interaksi online berfilter, elemen darah, dan humor dewasa non-seksual.</div>
    </div>
    <div class="rg-card" onclick="filterByRating('18+')" data-rg="18+">
      <img class="rg-badge" src="assets/18+.png" alt="18+">
      <div class="rg-name">18 Tahun ke Atas</div>
      <div class="rg-desc">Tanpa pornografi, namun boleh menampilkan rokok/alkohol/narkoba, kekerasan animasi, darah, mutilasi, kanibalisme, humor dewasa, karakter manusiawi tanpa organ seksual eksplisit, aktivitas judi non-uang nyata, horor, dan percakapan online.</div>
    </div>
    <div class="rg-card" onclick="filterByRating('RC')" data-rg="RC">
      <img class="rg-badge" src="assets/rc.png" alt="RC">
      <div class="rg-name">Refused Classification</div>
      <div class="rg-desc">Konten yang dilarang beredar: mengandung pornografi; perjudian berbasis uang nyata/aset digital yang dapat ditukar; atau melanggar hukum dan peraturan perundang-undangan Indonesia.</div>
    </div>
    <div class="rg-card" onclick="filterByRating('')" data-rg="">
      <span class="rg-badge" style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900;color:var(--muted);border:1px dashed var(--border);border-radius:8px;">All</span>
      <div class="rg-name">Tampilkan Semua</div>
      <div class="rg-desc">Hapus filter rating dan lihat semua game.</div>
    </div>
  </div>
</div>

<!-- FILTER BAR -->
<div id="games" class="filter-bar">
  <div class="search-wrap">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="search-input" id="searchInput" type="search" placeholder="Cari judul atau publisher…" oninput="applyFilters()">
  </div>

  <select class="select-filter" id="ratingFilter" onchange="syncRatingGuide(); applyFilters()">
    <option value="">Semua Rating</option>
    <?php foreach ($ratings as $r): ?>
      <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
    <?php endforeach; ?>
  </select>

  <select class="select-filter" id="platformFilter" onchange="applyFilters()">
    <option value="">Semua Platform</option>
    <?php foreach ($platforms as $p): ?>
      <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
    <?php endforeach; ?>
  </select>

  <select class="select-filter" id="descriptorFilter" onchange="applyFilters()">
    <option value="">Semua Konten</option>
    <?php foreach ($descriptors as $d): ?>
      <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
    <?php endforeach; ?>
  </select>

  <select class="select-filter" id="yearFilter" onchange="applyFilters()">
    <option value="">Semua Tahun</option>
    <?php foreach ($years as $y): ?>
      <option value="<?= (int)$y ?>"><?= (int)$y ?></option>
    <?php endforeach; ?>
  </select>

  <span class="filter-count" id="filterCount"></span>
</div>

<!-- GAME GRID -->
<div class="game-grid" id="gameGrid"></div>

<!-- FOOTER -->
<footer>
  <p>© <?= date('Y') ?> IGRS — Indonesian Game Rating System &nbsp;·&nbsp; <a href="#">igrs.id</a></p>
</footer>

<script>
const GAMES = <?= $gamesJson ?>;

function normalizePlatform(p) {
  return p.trim().replace(/^P\s+C$/i, 'PC');
}

function getPlatforms(game) {
  return (game.platform || '').split(',').map(normalizePlatform).filter(Boolean);
}

const RATING_IMG = {
  '3+':  'assets/3+.png',
  '7+':  'assets/7+.png',
  '13+': 'assets/13+.png',
  '15+': 'assets/15+.png',
  '18+': 'assets/18+.png',
  'RC':  'assets/rc.png',
};

const DESCRIPTOR_IMG = {
  'Blood':                'assets/bloods.png',
  'Character Appearance': 'assets/character_appereance.jpeg',
  'Drugs':               'assets/drugs.png',
  'Gambling':            'assets/gambling.png',
  'Horror':              'assets/horror.png',
  'Language':            'assets/language.png',
  'Online Interactions': 'assets/online_interaction.png',
  'Sexuality/Pornography': 'assets/sexuality.png',
  'Violence':            'assets/violence.png',
};

function buildCard(game) {
  const rl   = game.rating || '';
  const platforms = getPlatforms(game);
  const descs = (game.descriptor || '').split(',').map(d => d.trim()).filter(Boolean);

  const platHtml = platforms.slice(0, 3).map(p =>
    `<span class="meta-tag">${escHtml(p)}</span>`
  ).join('');

  const yearHtml = game.release_year
    ? `<span class="meta-tag year">${game.release_year}</span>` : '';

  const descHtml = descs.map(d => {
    const img = DESCRIPTOR_IMG[d]
      ? `<img src="${DESCRIPTOR_IMG[d]}" alt="">`
      : '';
    return `<span class="desc-tag">${img}${escHtml(d)}</span>`;
  }).join('');

  const ratingHtml = RATING_IMG[rl]
    ? `<img class="rating-badge" src="${RATING_IMG[rl]}" alt="${escHtml(rl)}" title="Rating ${escHtml(rl)}">`
    : (rl ? `<span class="rating-badge-fallback rating-na">${escHtml(rl)}</span>` : '');

  return `
    <div class="game-card">
      <div class="card-header">
        <span class="card-title">${escHtml(game.title)}</span>
        ${ratingHtml}
      </div>
      <p class="card-about">${escHtml(game.about || '—')}</p>
      ${descHtml ? `<div class="descriptor-row">${descHtml}</div>` : ''}
      <div class="card-meta">
        ${platHtml}
        ${yearHtml}
        <span class="meta-tag">${escHtml(game.publisher || '—')}</span>
      </div>
    </div>`;
}

function escHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

function applyFilters() {
  const search     = document.getElementById('searchInput').value.toLowerCase();
  const rating     = document.getElementById('ratingFilter').value;
  const platform   = document.getElementById('platformFilter').value;
  const descriptor = document.getElementById('descriptorFilter').value;
  const year       = document.getElementById('yearFilter').value;
  const grid       = document.getElementById('gameGrid');

  const filtered = GAMES.filter(g => {
    if (rating && g.rating !== rating) return false;
    if (year && String(g.release_year) !== year) return false;
    if (platform && !getPlatforms(g).some(p => p.toLowerCase() === platform.toLowerCase())) return false;
    if (descriptor) {
      const descs = (g.descriptor || '').split(',').map(d => d.trim());
      if (!descs.includes(descriptor)) return false;
    }
    if (search) {
      const hay = (g.title + ' ' + g.publisher + ' ' + g.about).toLowerCase();
      if (!hay.includes(search)) return false;
    }
    return true;
  });

  document.getElementById('filterCount').textContent =
    `${filtered.length} game ditemukan`;

  if (filtered.length === 0) {
    grid.innerHTML = `
      <div class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          <path d="M8 11h6M11 8v6" opacity=".4"/>
        </svg>
        <p>Tidak ada game yang cocok dengan filter yang dipilih.</p>
      </div>`;
    return;
  }

  grid.innerHTML = filtered.map(buildCard).join('');
}

function syncRatingGuide() {
  const val = document.getElementById('ratingFilter').value;
  document.querySelectorAll('.rg-card').forEach(c => {
    c.classList.toggle('active-filter', c.dataset.rg === val);
  });
}

function filterByRating(r) {
  const sel = document.getElementById('ratingFilter');
  sel.value = r;
  syncRatingGuide();
  applyFilters();
  document.getElementById('games').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Stats
(function initStats() {
  const publishers = new Set();
  const platforms  = new Set();
  GAMES.forEach(g => {
    publishers.add(g.publisher);
    getPlatforms(g).forEach(p => platforms.add(p));
  });
  document.getElementById('totalGames').textContent     = GAMES.length;
  document.getElementById('totalPublishers').textContent = publishers.size;
  document.getElementById('totalPlatforms').textContent  = platforms.size;
})();

// Initial render
applyFilters();
</script>
</body>
</html>

$test = new IndexTest();
$test->runTests();
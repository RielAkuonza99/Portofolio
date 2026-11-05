<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<?php require __DIR__ . '/includes/public_meta.php'; $M = public_meta(); ?>
<meta charset="UTF-8">
  
  <?php if ($M['gsv'] !== ''): ?><meta name="google-site-verification" content="<?= e($M['gsv']) ?>" /><?php endif; ?>
  
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($M['title']) ?></title>
<meta name="description" content="<?= e($M['description']) ?>">
<meta property="og:title" content="<?= e($M['og_title']) ?>">
<meta property="og:description" content="<?= e($M['og_description']) ?>">
<meta name="theme-color" content="<?= e($M['theme_color']) ?>">
<link rel="icon" href="<?= e($M['favicon']) ?>"<?= $M['favicon_type'] ? ' type="' . e($M['favicon_type']) . '"' : '' ?>>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=IBM+Plex+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/animations.css">
<link rel="stylesheet" href="css/responsive.css">
<script>document.documentElement.classList.add("loading");try{var t=localStorage.getItem("theme");if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body>
<div id="loader" role="status" aria-live="polite"><div class="ld-in"><p class="ld-title">Personal HUB</p><div class="ld-track" role="progressbar" aria-label="Loading portfolio" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i id="ldBar"></i></div><p class="ld-meta"><b id="ldPct">0%</b><b id="ldMsg">Loading…</b></p></div></div>
<div id="cursor" aria-hidden="true"></div>
<a class="skip" href="#main">Skip to content</a>

<header id="nav">
  <div class="wrap nav-in">
    <a href="#home" class="logo" data-bind="logo">Riel.</a>
    <nav id="menu" aria-label="Main">
      <a href="#/home">Home</a><a href="#/about">About</a><a href="#/skills">Skills</a>
      <a href="#/projects">Projects</a><a href="#/journey">Journey</a><a href="#/contact">Contact</a>
    </nav>
    <button id="themeBtn" class="icon-btn" aria-label="Toggle dark mode"><span class="ico-theme"></span></button>
    <button id="burger" class="icon-btn" aria-label="Open menu" aria-expanded="false" aria-controls="menu"><span class="bars"></span></button>
  </div>
</header>

<main id="main" tabindex="-1">
<div class="page" data-page="home">
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <h1 data-bind="title"></h1>
      <p class="role"><span id="role"></span><span class="caret" aria-hidden="true"></span></p>
      <p class="lead" data-bind="tagline"></p>
      <div class="actions">
        <button class="btn primary" id="exploreBtn" type="button">Explore Portfolio</button>
        <a class="btn" id="cvBtn" href="#">Download CV</a>
      </div>
      <ul class="socials" id="heroSocials" aria-label="Social links"></ul>
    </div>
    <figure class="portrait"><img data-bind-src="photo" alt="Profile photo of Riel Akuonza" width="420" height="500"></figure>
  </div>
</section>
<section class="sec"><div class="wrap reveal"><h2>About me</h2><p class="lead" data-bind="about"></p><div class="more-wrap"><a class="btn" href="#/about">Lihat selengkapnya</a></div></div></section>
<section class="sec alt"><div class="wrap reveal"><h2>Skills</h2><div class="skill-grid" id="pvSkills"></div><div class="more-wrap"><a class="btn" href="#/skills">Lihat selengkapnya</a></div></div></section>
<section class="sec"><div class="wrap reveal"><h2>Projects</h2><div class="proj-grid" id="pvProjects"></div><div class="more-wrap"><a class="btn" href="#/projects">Lihat selengkapnya</a></div></div></section>
<section class="sec alt"><div class="wrap reveal"><h2>Journey</h2><ol class="timeline" id="pvTimeline"></ol><div class="more-wrap"><a class="btn" href="#/journey">Lihat selengkapnya</a></div></div></section>
<section class="sec"><div class="wrap reveal"><h2>Contact</h2><p class="lead">Have an idea, an internship, or a collaboration in mind? Send a message.</p><ul class="links" id="pvLinks"></ul><div class="more-wrap"><a class="btn" href="#/contact">Lihat selengkapnya</a></div></div></section>
</div>

<div class="page" data-page="about" hidden>
<section class="sec">
  <div class="wrap about-grid reveal">
    <figure class="portrait small"><img data-bind-src="photo" alt="" loading="lazy" width="420" height="500"></figure>
    <div>
      <h2>About me</h2>
      <p class="lead" data-bind="about"></p>
      <dl class="bio" id="bio"></dl>
      <h3>Focus areas</h3><ul class="chips" id="focus"></ul>
      <h3>Interests</h3><ul class="chips" id="interests"></ul>
      <h3>Self-development goals</h3><ul class="goals" id="goals"></ul>
    </div>
  </div>
</section>
</div>

<div class="page" data-page="skills" hidden>
<section class="sec alt">
  <div class="wrap reveal">
    <h2>Skills</h2>
    <p class="sub">Levels are self-assessed estimates and may change as I learn.</p>
    <div class="filters" id="skillFilters" role="group" aria-label="Skill categories"></div>
    <div class="skill-grid" id="skillGrid"></div>
  </div>
</section>
</div>

<div class="page" data-page="projects" hidden>
<section class="sec">
  <div class="wrap reveal">
    <h2>Projects</h2>
    <div class="toolbar">
      <div class="filters" id="projFilters" role="group" aria-label="Project categories"></div>
      <form id="projSearchForm" class="search-form" role="search"><input id="projSearch" type="search" placeholder="Cari nama atau kategori" aria-label="Cari proyek"><button class="btn sm" type="submit">Cari</button></form>
    </div>
    <div class="proj-grid" id="projGrid" aria-live="polite"></div>
    <div id="projEmpty" class="empty" hidden><p>No projects match your search.</p><button class="btn" id="projReset">Clear filters</button></div>
  </div>
</section>
</div>

<div class="page" data-page="journey" hidden>
<section class="sec alt">
  <div class="wrap reveal">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="#/home">Home</a><span>Journey</span></nav>
    <h2>Journey</h2>
    <ol class="timeline" id="timeline"></ol>
    <article class="journey-detail" id="journeyDetail" hidden></article>
  </div>
</section>
</div>

<div class="page" data-page="contact" hidden>
<section class="sec">
  <div class="wrap contact-grid reveal">
    <div>
      <h2>Contact</h2>
      <p class="lead">Have an idea, an internship, or a collaboration in mind? Send a message.</p>
      <ul class="links" id="contactLinks"></ul>
    </div>
    <form id="form" novalidate>
      <label>Name<input name="name" type="text" autocomplete="name" required minlength="2" maxlength="120"><small class="err" aria-live="polite"></small></label>
      <label>Email<input name="email" type="email" autocomplete="email" required maxlength="190"><small class="err" aria-live="polite"></small></label>
      <label>Message<textarea name="message" rows="5" required minlength="10" maxlength="5000"></textarea><small class="err" aria-live="polite"></small></label>
      <button class="btn primary" type="submit" id="submitBtn"><span>Send message</span></button>
      <p class="note">Your message is sent to my inbox. If sending fails, your email app opens instead.</p>
    </form>
  </div>
</section>
</div>
</main>

<footer>
  <div class="wrap foot">
    <p>© <span id="year"></span> <span data-bind="name"></span></p>
    <ul class="socials" id="footSocials" aria-label="Social links"></ul>
    <button class="btn" id="toTop">Back to top</button>
  </div>
</footer>

<dialog id="modal" aria-labelledby="mTitle">
  <button class="icon-btn close" id="mClose" aria-label="Close">✕</button>
  <div class="media" id="mMedia"></div>
  <h3 id="mTitle"></h3><p id="mDesc"></p><ul class="chips" id="mTech"></ul>
  <div class="actions" id="mLinks"></div>
</dialog>
<dialog id="explore" aria-label="Explore portfolio">
  <button class="icon-btn close" id="xClose" aria-label="Close">✕</button>
  <h3>Explore</h3>
  <input id="xInput" type="search" placeholder="Search pages, projects, skills, journey" aria-label="Search the portfolio" autocomplete="off">
  <ul class="xlist" id="xList" role="listbox"></ul>
  <p class="note">Tip: press Ctrl+K (or ⌘K) anywhere to open this.</p>
</dialog>
<div id="toasts" role="status" aria-live="polite"></div>

<script src="js/icons.js"></script>
<script src="js/theme.js"></script>
<script src="js/navigation.js"></script>
<script src="js/projects.js"></script>
<script src="js/explore.js"></script>
<script src="js/interactions.js"></script>
<script src="js/main.js"></script>
</body>
</html>

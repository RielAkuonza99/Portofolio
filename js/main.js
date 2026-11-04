var SITE, SKILLS, SKILL_CATEGORIES, PROJECTS, JOURNEY; // filled from data/data.json
const trackVisit = () => {
  if (location.protocol === "file:") return;
  fetch("api/public/visit.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ path: location.hash || "/" }), keepalive: true }).catch(() => {});
};
trackVisit(); addEventListener("hashchange", trackVisit);
(async () => {
  const $ = id => document.getElementById(id);
  const L = UI.loader; L.start();
  let d;
  const load = async url => { const r = await fetch(url, { cache: "no-cache" }); if (!r.ok) throw new Error(r.status); return r.json(); };
  try {
    d = await load("api/public/content.php"); // primary source: PHP + MySQL
    if (!d || !d.site) throw new Error("bad payload");
  } catch (e) {
    try { d = await load("data/data.json"); } // archived fallback if the backend is down or empty
    catch (e2) { return L.fail("Cannot load content. Open the site through a web host (PHP), not as a plain file."); }
  }
  ({ site: SITE, skills: SKILLS, skillCategories: SKILL_CATEGORIES, projects: PROJECTS, journey: JOURNEY } = d);
  L.progress(45, "Building pages…");

  const li = (el, text) => { const x = document.createElement("li"); x.textContent = text; el.append(x); };
  const links = (el, items) => items.forEach(s => {
    const x = document.createElement("li"), a = document.createElement("a");
    a.innerHTML = Icons.svg(s.id) + "<span></span>"; a.lastChild.textContent = s.label;
    a.href = s.url; if (!s.url.startsWith("mailto:")) { a.target = "_blank"; a.rel = "noopener noreferrer"; }
    x.append(a); el.append(x);
  });
  const timeline = (el, list) => list.forEach(j => {
    const x = document.createElement("li");
    x.innerHTML = "<time></time><div><h3><a></a></h3><p></p></div>";
    x.querySelector("time").textContent = j.year;
    const title = x.querySelector("h3 a"); title.textContent = j.title; title.href = "#/journey/" + (j.slug || encodeURIComponent(j.year + "-" + j.title));
    x.querySelector("p").textContent = j.text;
    el.append(x);
  });

  document.querySelectorAll("[data-bind]").forEach(el => el.textContent = SITE[el.dataset.bind]);
  document.querySelectorAll("[data-bind-src]").forEach(el => el.src = SITE[el.dataset.bindSrc]);
  $("year").textContent = new Date().getFullYear();
  const contact = [{ id: "mail", label: SITE.email, url: "mailto:" + SITE.email }, ...SITE.socials];
  links($("heroSocials"), SITE.socials); links($("footSocials"), SITE.socials);
  links($("contactLinks"), contact); links($("pvLinks"), contact);
  SITE.bio.forEach(([k, v]) => { const x = document.createElement("div"); x.innerHTML = "<dt></dt><dd></dd>"; x.children[0].textContent = k; x.children[1].textContent = v; $("bio").append(x); });
  SITE.roles.forEach(t => li($("focus"), t)); SITE.interests.forEach(t => li($("interests"), t)); SITE.goals.forEach(t => li($("goals"), t));
  timeline($("timeline"), JOURNEY); timeline($("pvTimeline"), JOURNEY.slice(-3));
  const renderJourneyDetail = () => {
    const match = location.hash.match(/^#\/journey\/([^/?]+)/); const detail = $("journeyDetail"); if (!match) { detail.hidden = true; return; }
    const slug = decodeURIComponent(match[1]); const item = JOURNEY.find(j => (j.slug || "") === slug || slug === j.year + "-" + j.title.toLowerCase().replace(/[^a-z0-9]+/g, "-")); if (!item) { detail.hidden = true; return; }
    detail.hidden = false; detail.innerHTML = "<nav class='breadcrumb'><a href='#/home'>Home</a><span>Journey</span><strong></strong></nav><p class='muted'></p><h2></h2><div class='journey-copy'></div>";
    detail.querySelector("strong").textContent = item.year + " " + item.title; detail.querySelector(".muted").textContent = item.year; detail.querySelector("h2").textContent = item.title;
    detail.querySelector(".journey-copy").innerHTML = item.html || item.text; if (item.link) { const a=document.createElement("a"); a.href=item.link; a.target="_blank"; a.rel="noopener noreferrer"; a.className="btn sm"; a.textContent="Buka dokumentasi"; detail.append(a); }
  };
  addEventListener("hashchange", renderJourneyDetail); renderJourneyDetail();

  Theme.init(); Nav.init(); Projects.init(); Explore.init(); UI.init();
  L.progress(80, "Loading fonts…");
  await Promise.race([document.fonts ? document.fonts.ready : 0, new Promise(r => setTimeout(r, 2500))]);
  L.progress(92, "Almost ready…");
  if (document.readyState !== "complete") await Promise.race([new Promise(r => addEventListener("load", r)), new Promise(r => setTimeout(r, 3000))]);
  L.progress(100, "Ready");
})();

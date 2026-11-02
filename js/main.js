var SITE, SKILLS, SKILL_CATEGORIES, PROJECTS, JOURNEY; // filled from data/data.json
(async () => {
  const $ = id => document.getElementById(id);
  const L = UI.loader; L.start();
  let d;
  try {
    const r = await fetch("data/data.json", { cache: "no-cache" });
    if (!r.ok) throw new Error(r.status);
    d = await r.json();
  } catch (e) {
    return L.fail("Cannot load data/data.json. Open the site with Live Server or a web host, not as a plain file.");
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
    x.innerHTML = "<time></time><div><h3></h3><p></p></div>";
    x.querySelector("time").textContent = j.year; x.querySelector("h3").textContent = j.title; x.querySelector("p").textContent = j.text;
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

  Theme.init(); Nav.init(); Projects.init(); Explore.init(); UI.init();
  L.progress(80, "Loading fonts…");
  await Promise.race([document.fonts ? document.fonts.ready : 0, new Promise(r => setTimeout(r, 2500))]);
  L.progress(92, "Almost ready…");
  if (document.readyState !== "complete") await Promise.race([new Promise(r => addEventListener("load", r)), new Promise(r => setTimeout(r, 3000))]);
  L.progress(100, "Ready");
})();

# Personal HUB — Riel Akuonza

Multi-page personal portfolio in plain HTML, CSS and JavaScript. No frameworks, no build step, no backend.

## Run
Content is loaded from `data/data.json`, so browsers block it when `index.html` is opened as a plain file. Use **Live Server** in VS Code (or any static host). If the data cannot load, the loading screen shows an error.

## Structure
- `index.html` (all pages), `404.html`
- `data/data.json` — all editable content; `data/cv/` — CV file for the Download CV button
- `css/` — `style.css`, `responsive.css`, `animations.css`
- `js/` — `main.js` (loads data, renders), `navigation.js` (page router), `projects.js`, `explore.js`, `interactions.js` (loader, typing, form), `icons.js`, `theme.js`
- `assets/images/` — placeholders and your images
- `docs/customization.md`

## Pages
Home shows a few items per category with "Lihat selengkapnya". Each menu item has its own page (`#/about`, `#/skills`, `#/projects`, `#/journey`, `#/contact`) with a fade transition between pages.

## Edit content (`data/data.json`)
- **site:** title, roles, tagline, email, photo, `cv`, bio, interests, goals, socials (`id`: instagram, github, tiktok, x).
- **CV:** replace `data/cv/Riel-Akuonza-CV.pdf` (currently a placeholder) or change `site.cv`.
- **projects:** `type` sets the media ratio: `web` 16:9, `design` 4:5, `app` 9:16. `image` and optional `video` paths. The box never resizes; images are cropped to fit, videos are letterboxed.
- **skills / journey:** edit the lists. Colors: variables at the top of `css/style.css`.

## Explore Portfolio
The button (or Ctrl/⌘+K) opens a search across pages, projects, skills and journey. Pick a result to jump there.

## Contact form
Opens the visitor's email app with `mailto:`. For real delivery, use a service such as Formspree and replace the `mailto` block in `js/interactions.js`.

## Deploy
GitHub Pages: push, then Settings → Pages → `main` root. Vercel: import repo, preset "Other", no build command.

# Customization guide

All content is in `data/data.json`.

## Add a project
Append to `projects`:
```json
{ "id": 7, "name": "My App", "type": "app", "cat": "App", "desc": "One line.", "detail": "Longer text for the popup.",
  "tech": ["Flutter"], "image": "assets/images/my-app.png", "video": "assets/video/my-app.mp4",
  "github": "https://github.com/you/my-app", "demo": "" }
```
`type`: `web` (16:9), `design` (4:5), `app` (9:16). Leave `video`, `github` or `demo` empty to hide them.

## Skills
Add a pair to `skillCategories`, then use its key as `cat` in `skills`.

## Accent color
Edit `--accent` and `--accent-ink` in `css/style.css` (light and dark).

## Social icons
`id` must be `instagram`, `github`, `tiktok` or `x`. Other ids show a generic link icon (add more in `js/icons.js`).

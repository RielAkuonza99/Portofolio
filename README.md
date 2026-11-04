# Portfolio — Riel Akuonza (PHP Native + MySQL + private admin)

Public frontend unchanged (HTML/CSS/JS); content now comes from MySQL via `api/public/content.php`.
`data/data.json` is kept as migration source and automatic fallback.

## Setup (InfinityFree, no shell/Composer needed)
1. **Database**: create a MySQL DB in the InfinityFree panel. In phpMyAdmin import `database/schema.sql`, then `database/seed.sql` (idempotent) — or skip the seed and use Admin → Tools → *Impor data.json*.
2. **Config**: copy `.env.example` to `config/.env` (File Manager/FTP, never in Git). Fill `DB_*`, and generate `APP_KEY` (>=32 chars) and `SETUP_KEY` (>=24 chars) with e.g. `openssl rand -hex 32`. Then **open `https://YOURDOMAIN/config/.env` and confirm it is denied (403/404)**.
3. **Upload** the files to `htdocs/` (manually, or Actions → *Deploy to InfinityFree (manual)* after adding secrets `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`). `database/`, `.env`, `*.sql`, `*.md` are excluded from deploy.
4. **Admin enrollment** (once): open `/admin/setup.php`, enter SETUP_KEY, choose your own username + password (>=12 chars), scan the QR with Google Authenticator, enter the code, **save the 10 recovery codes** (shown once). After activation `setup.php` returns 404.
5. Log in at `/admin/` (no link exists on the public site). Remove `SETUP_KEY` from `config/.env` afterwards if you like.
6. Providers (all optional; status shown in Overview/Tools): `IMGBB_API_KEY`, `BREVO_*`, `MEDIAFIRE_*`. Streamable = paste URL only (its API has no upload).

## Admin session and form behavior
Each successful login rotates the PHP session ID after the password check and again after the second factor. The admin session expires after the configured idle and absolute limits (`SESSION_IDLE_MIN` and `SESSION_MAX_HOURS`). Admin forms validate values on the server as well as in the browser; logout, deletes, publishing changes, credential changes, and external uploads show a confirmation. Form submissions display a loading state while the request is processed.

## Lost authenticator
Use a recovery code at the TOTP step. If none left: in phpMyAdmin run `DELETE FROM admins;` then repeat step 4 (needs SETUP_KEY in config). There is no other bypass.

## Backup / rollback
Admin → Tools → *Unduh backup konten (JSON)*, and phpMyAdmin → Export for a full dump. Rollback code = re-run the deploy of a previous commit; data lives in MySQL and external providers, not in deployed files.

## Provider notes
- ImgBB: `POST api.imgbb.com/1/upload` (server-side only). No documented delete API: the encrypted `delete_url` is viewable in Media.
- Streamable: official API is read-only (oEmbed); videos are uploaded manually at streamable.com; embed = `streamable.com/o/{id}`.
- MediaFire: session auth per official docs; upload/poll/links calls are **untested with real credentials**. Manual link mode always works.
- Brevo: `POST /v3/smtp/email`; contact messages and login notices. Sender must be verified in Brevo.

## Dependencies
None via Composer. Local QR: `admin/assets/qrcode.js` (qrcode-generator 2.0.4, MIT, runs in the browser). TOTP is implemented in `includes/totp.php` (RFC 6238).

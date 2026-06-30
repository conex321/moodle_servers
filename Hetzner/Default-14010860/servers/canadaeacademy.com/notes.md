# canadaeacademy.com — Canada-E-Academy

| Field | Value |
|---|---|
| Hetzner server | Canada-E-Academy (id 145396564) |
| IPv4 | 5.161.222.147 |
| Type / location | CPX21 / ash-dc1 (Ashburn, VA) |
| Created | 2026-06-26 |
| Status | running; Moodle live on raw IP |
| Linked domain | canadaeacademy.com (also `.ca`, `.org` registered in Hostinger) |
| SSH key | `emcs-moodle` |

## Status / TODO
- Moodle stack is running but reachable only by IP (redirects to `/login/index.php`).
- **DNS:** point `canadaeacademy.com` (and `www`) at `5.161.222.147` in Hostinger.
- **SSL:** issue Let's Encrypt cert once DNS resolves.
- Follow `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md` phases 4–9.

Build config to be added here once the instance is finalised (mirror
`../app.canadaemcs.com/`).

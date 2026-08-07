---
paths:
  - 'app/Actions/{ExportPortfolioBackup,RestorePortfolioBackup}.php'
---

# Actions

## Portfolio backup restore scope
Backups are a portable CMS-content transfer only: restore site settings, projects, experiences and pivots, skills, and referenced public media. Preserve archive/media validation, transactional replacement, and rollback cleanup; never include users, analytics, config, sessions, jobs, or unrelated files.

## Backup archive and request-body size limits
The restore archive is capped at 100 MB; the web server's request-body limit must be at least 128 MB to allow the multipart upload overhead. A fresh installation needs migrations run and one administrator account created before that admin can upload a backup — restore is admin-authenticated only, there's no bootstrap path via backup.

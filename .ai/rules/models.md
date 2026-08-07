---
paths:
  - 'app/Models/{SiteSetting,Project,Experience,Skill}.php'
---

# Models

## Writing portfolio CMS content via Tinker
Only create or update portfolio content with Tinker when the user explicitly asks for that content change and has supplied or approved it. Never invent personal names, contact details, work history, project links, or credentials. Inspect the model, Filament form, and schema first; use Eloquent models for the write and `database-query` to verify after.

Required fields: SiteSetting (singleton) needs name, professional title, hero copy, about/contact copy, email, social links, `site_locale`, `is_indexable`, SEO metadata — profile image, sharing image, CV are optional. Project needs title, summary, rich body, an existing public-disk image path, technologies, sort order, publishing state — source/live URLs optional. Experience needs company, position, start date, location, description, technologies, sort order, publishing state — end date and project relationships optional; create projects before attaching them. Skill needs a unique name, sort order, publishing state.

Store media under the existing public-disk paths: `site/profile-images`, `site/seo`, `site/resumes`, `projects`. Never write raw `appearance` JSON — build it through `SiteSetting::resolveAppearance()` and check `SiteSetting::appearanceContrastFailures()` before persisting custom colors, matching what the Filament form and backup restore already enforce. Do not run `migrate:fresh --seed` unless the user explicitly approves deleting all local CMS content.

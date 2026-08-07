---
paths:
  - 'resources/views/**'
---

# Views

## Hide links and metadata when values are absent
Public portfolio views must omit project source buttons when source_url is null. Do not emit a canonical production URL until site_url is populated, and keep Open Graph tags conditional so they disappear when no image is configured.

## Link to the stable CV route
Public CV links must use the named cv.show route (/cv), never a storage URL. The route streams the currently configured PDF from the managed site/resumes directory, so Filament replacements do not change the public link.

## Use generic placeholders for the default media
The seeded profile and project images are neutral SVG placeholders. Keep OG/Twitter image tags conditional so clearing the setting omits them; site_url remains null and canonical/og:url must remain absent.

## Responsive variants are optional
Public picture markup may include responsive sources for seeded media when available. Filament uploads and the generic SVG placeholders must continue rendering through their original fallback paths.

## Derive metadata URLs from deployment configuration
Canonical, Open Graph URL, structured-data URL, social media URL, and sitemap origins must come from config('app.url'), which is backed by APP_URL. Never read or persist an environment URL in SiteSetting or portfolio backups. Omit URL-dependent metadata and keep indexing ineffective when APP_URL is not a valid HTTP or HTTPS origin.

## Hide links and metadata when values are absent
Public portfolio views must omit project source buttons when source_url is null. Emit canonical and Open Graph URLs only when config('app.url') is a valid HTTP or HTTPS origin, and keep Open Graph tags conditional so they disappear when no sharing image is configured.

## APP_URL supersedes stored site URL guidance
This rule supersedes earlier references to SiteSetting.site_url. The site_url field no longer exists. Treat config('app.url') as the only canonical origin, while keeping Open Graph and Twitter image tags conditional on an uploaded sharing image.

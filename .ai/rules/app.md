---
paths:
  - 'app/**'
---

# App

## Reuse SiteSetting URL guards, don't re-inline them
SiteSetting::isWebUrl() and SiteSetting::isSafeSocialLinkUrl() are the single source of truth for link validation. isSafeSocialLinkUrl covers the approved http(s)/mailto/tel schemes and is shared by the Filament social_links rule and the backup restore validator, so both trust boundaries stay in sync. PortfolioMetadata uses isWebUrl for the APP_URL origin check and for schema.org sameAs (web links only). Pure static helpers on the model is the house pattern here, alongside resolveAppearance and appearanceContrastFailures.

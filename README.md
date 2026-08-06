# Portfolio Builder

A Laravel and Filament portfolio website with a CMS at `/admin`. It starts with neutral placeholder content so you can replace every visible detail with your own work, then move that content to a fresh installation with a portable backup.

<p align="center">
  <img width="1904"  height="4211" alt="Portfolio homepage preview" src="https://github.com/user-attachments/assets/1fa9745b-176f-476c-a653-7a54632a1fc2" />
</p>

## What it does

Portfolio Builder turns your professional profile, projects, experience, skills, links, SEO metadata, and optional CV into an editable public portfolio. Filament provides the private CMS, while the Laravel frontend displays only the records you choose to publish and orders them using `sort_order`.

It is intended as a reusable starting point: update content in the CMS and make the public design your own without embedding personal data in the codebase.

## Requirements

- PHP 8.3 or later
- Composer
- Node.js and npm

## Setup

1. Install PHP dependencies and create your environment file:

    ```bash
    composer install
    cp .env.example .env
    php artisan key:generate
    ```

2. Create the SQLite database file if it does not already exist, then migrate and seed the application:

    ```bash
    touch database/database.sqlite
    php artisan migrate --seed
    php artisan storage:link
    ```

3. Install and build frontend assets:

    ```bash
    npm install
    npm run build
    ```

4. Start the application:

    ```bash
    composer run dev
    ```

    Visit the URL configured by `APP_URL` and append `/admin`. On the first visit, create the one administrator account; afterwards, `/admin` shows the normal sign-in screen. In this checkout, those URLs are `https://portofolio.test` and `https://portofolio.test/admin`.

## Backup and restore

Use **Admin → Backups** to download one ZIP archive containing the portfolio CMS content and every referenced profile image, project image, sharing image, and CV. Create a backup before replacing local content or starting a new installation.

To populate a fresh installation, complete the setup above, create an administrator account, then sign in and choose **Restore backup** from **Admin → Backups**. The restore replaces the site settings, projects, experience-project relationships, skills, and referenced media with the archive contents.

Administrator accounts, analytics, application configuration, and unrelated storage files are deliberately excluded. The restore archive is limited to 100 MB; configure the web server request-body limit to at least 128 MB to allow the multipart upload overhead.

## Default content

The seeders create generic, editable examples:

- One site settings record for `Your Name`
- One project, one experience, and three skills
- Neutral profile and project SVG placeholders
- No default CV; upload your PDF from the CMS

## CMS content checklist

Use the admin panel to replace the placeholders:

- **Site settings:** name, role, hero and about copy, contact email, social links, SEO fields, profile image, sharing image, and optional CV.
- **Projects:** title, short summary, rich description, image, technologies, publishing status, display order, and optional source/live URLs.
- **Experience:** company, role, dates, location, description, technologies, publishing status, display order, and optional related projects.
- **Skills:** name, publishing status, and display order.

Only published projects, experiences, and skills appear on the public page. Lower `sort_order` values appear first.

## Customize the public homepage

You may completely redesign [`resources/views/home.blade.php`](resources/views/home.blade.php): use any layout, components, typography, colors, or visual style that fits the portfolio owner.

Keep the CMS data contract intact by rendering the variables provided by `HomeController` rather than hardcoding portfolio content:

- `$siteSetting` — the singleton profile, hero, contact, social, SEO, and optional CV data.
- `$projects` — published projects in display order, with their published related experiences.
- `$experiences` — published experiences in display order, with their published related projects.
- `$skills` — published skills in display order.

Do not query the database from Blade. Keep filtering and ordering in the controller, and use the supplied variables so edits in `/admin` continue to appear on the public site.

## Testing

```bash
php artisan test --compact tests/Feature
```

## License

This project is licensed under the [GNU General Public License v3.0](LICENSE).

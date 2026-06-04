# Mageaustralia_Carousel

CMS-driven carousel widget for Maho: manage carousels and slides in the admin, drop them anywhere on the storefront via a widget, and automatically keep recently-uploaded images optimized as WebP on a daily cron.

## Requirements

- Maho 26.5+
- PHP 8.3+
- Intervention Image (pulled in via Composer)

## Installation

```bash
composer require mageaustralia/maho-module-carousel
composer dump-autoload -o
./maho setup:upgrade
./maho cache:flush
```

## Features

- **Admin carousels grid** - create, edit, and delete carousels under CMS > Carousel Builder.
- **Per-carousel slides management** - upload images, add title/subtitle/buttons, control text position, color, and overlay per slide; drag to reorder.
- **Flexible widget placement** - insert a `carousel/widget` block via the Widgets UI or directly in layout XML; reference a carousel by ID or by its text identifier.
- **Two frontend templates**:
  - `template/carousel/widget.phtml` - self-contained (plain HTML/CSS/vanilla JS, works on any Maho theme). Ships with `skin/frontend/base/default/css/carousel/carousel.css` and `skin/frontend/base/default/js/carousel/carousel.js`.
  - `docs/examples/widget-daisyui.phtml` - DaisyUI/Tailwind variant for modern themes; copy it into your theme's template directory to override.
- **Responsive images** - the widget renders a `<picture>` element that serves AVIF, then WebP, then the original as a fallback if those variants exist alongside the source file.
- **Daily image optimization cron** (`carousel_optimize_images`, runs at 03:00) - scans recently-modified JPG/PNG files in the `catalog/product`, `wysiwyg`, and `carousel` media directories and generates WebP (and optionally AVIF) versions using Intervention Image.

## Quick-start: adding a carousel to a page

1. Go to **CMS > Carousel Builder** in the admin, create a carousel, add slides, and note the carousel's **Identifier**.
2. Edit any CMS page or block, switch to the Insert Widget panel, choose **DaisyUI Carousel**, and select the carousel.
3. Save and flush the cache.

Alternatively, add it directly in layout XML:

```xml
<block type="carousel/widget" name="home.carousel" as="home_carousel">
    <action method="setIdentifier"><identifier>my-carousel</identifier></action>
</block>
```

## Database tables

| Table | Purpose |
|-------|---------|
| `custom_carousel` | Carousel records |
| `custom_carousel_slide` | Slide records (FK to `custom_carousel`) |

Table names are kept from the original `Custom_Carousel` module for drop-in compatibility.

## Admin menu

**CMS > Carousel Builder** (requires `admin/cms/carousel` ACL role).

## Cron

| Job | Schedule | What it does |
|-----|----------|-------------|
| `carousel_optimize_images` | Daily at 03:00 | Converts JPG/PNG modified in the last 24 h to WebP (skips files already optimized or smaller than 10 KB) |

## License

Open Software License 3.0 (OSL-3.0). See `LICENSE`.

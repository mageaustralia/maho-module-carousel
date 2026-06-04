# Mageaustralia_Carousel - Admin and Developer Manual

## Table of contents

1. [Overview](#overview)
2. [How it works end-to-end](#how-it-works-end-to-end)
3. [Installation](#installation)
4. [Database tables](#database-tables)
5. [Admin guide](#admin-guide)
   - [Navigating to the carousel manager](#navigating-to-the-carousel-manager)
   - [Creating a carousel](#creating-a-carousel)
   - [Carousel general settings](#carousel-general-settings)
   - [Carousel display settings](#carousel-display-settings)
   - [Managing slides](#managing-slides)
   - [Slide fields](#slide-fields)
   - [Reordering slides](#reordering-slides)
   - [Deleting a carousel](#deleting-a-carousel)
6. [Placing the carousel on the storefront](#placing-the-carousel-on-the-storefront)
   - [Via the Widgets UI (CMS pages and blocks)](#via-the-widgets-ui-cms-pages-and-blocks)
   - [Via layout XML](#via-layout-xml)
   - [Widget parameters](#widget-parameters)
7. [Frontend templates](#frontend-templates)
   - [Option B: self-contained base/default template](#option-b-self-contained-basedefault-template)
   - [Option A: DaisyUI/Tailwind override template](#option-a-daisyuitailwind-override-template)
   - [Overriding the template in your theme](#overriding-the-template-in-your-theme)
   - [Responsive image rendering](#responsive-image-rendering)
8. [Image optimization cron](#image-optimization-cron)
9. [Troubleshooting and FAQ](#troubleshooting-and-faq)

---

## Overview

`Mageaustralia_Carousel` is a CMS-driven image carousel module for Maho. It provides:

- An admin interface under **CMS > Carousel Builder** for managing carousels and their slides.
- A `carousel/widget` Maho widget that can be dropped into any CMS page, static block, or layout handle.
- A self-contained frontend (HTML/CSS/vanilla JS) that works on any Maho theme without any CSS framework dependency.
- An alternative DaisyUI/Tailwind template for modern headless-adjacent themes.
- A daily cron job that converts recently-uploaded media images to WebP using Intervention Image.

The module group/alias is `carousel`. Database tables are named `custom_carousel` and `custom_carousel_slide` to maintain drop-in compatibility with the `Custom_Carousel` module it was productized from.

---

## How it works end-to-end

```
Admin
  +-- CMS > Carousel Builder
        +-- Creates records in custom_carousel + custom_carousel_slide
        +-- Image uploads land in public/media/carousel/{carousel_id}/
        +-- At upload time: WebP + AVIF variants are generated alongside the original

Storefront
  +-- carousel/widget block resolves the carousel by ID or identifier
  +-- Loads only enabled slides, ordered by sort_order ASC
  +-- Renders <picture> with AVIF source, WebP source, original fallback
  +-- Self-contained template: skin CSS + JS loaded via layout/carousel.xml (default layout handle)
  +-- DaisyUI template: relies on the theme's own Tailwind/DaisyUI build

Cron (daily 03:00)
  +-- Scans media/catalog/product, media/wysiwyg, media/carousel for JPGs/PNGs modified in last 24 h
  +-- Generates .webp variants (skips files < 10 KB or already converted)
  +-- Logs to var/log/image_optimizer.log
```

---

## Installation

### Composer

```bash
composer require mageaustralia/maho-module-carousel
composer dump-autoload -o
./maho setup:upgrade
./maho cache:flush
```

`setup:upgrade` runs `install-1.0.0.php` (creates the two tables) and, if upgrading from 1.0.0 to 1.0.1, `upgrade-1.0.0-1.0.1.php` (adds `text_width` and `text_alignment` columns to the slides table).

The module is enabled automatically via `app/etc/modules/Mageaustralia_Carousel.xml` (installed by Composer's `extra.map`).

### Verify installation

After flushing the cache, navigate to **Admin > CMS**. You should see a **Carousel Builder** menu item. If it is missing, check that the `Mageaustralia_Carousel` entry exists in `app/etc/modules/` and that your admin role has the `admin/cms/carousel` ACL resource granted.

---

## Database tables

### `custom_carousel`

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `carousel_id` | INT UNSIGNED PK | auto | |
| `title` | VARCHAR(255) | | Required |
| `identifier` | VARCHAR(100) UNIQUE | | Slug; used to load a carousel by name |
| `status` | SMALLINT | 1 | 1 = Enabled, 0 = Disabled |
| `carousel_type` | VARCHAR(50) | `standard` | Label only; does not affect rendering |
| `height` | VARCHAR(20) | NULL | CSS value e.g. `500px`, `60vh` |
| `show_navigation` | SMALLINT | 1 | Show prev/next arrow buttons |
| `show_dots` | SMALLINT | 1 | Show dot indicators |
| `autoplay` | SMALLINT | 0 | Enable auto-advance |
| `autoplay_speed` | INT | 5000 | Milliseconds between slides |
| `slides_to_show` | SMALLINT | 1 | Visible slides on desktop |
| `slides_to_show_mobile` | SMALLINT | 1 | Visible slides on mobile |
| `created_at` | TIMESTAMP | NOW() | |
| `updated_at` | TIMESTAMP | NOW() ON UPDATE | |

### `custom_carousel_slide`

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `slide_id` | INT UNSIGNED PK | auto | |
| `carousel_id` | INT UNSIGNED FK | | Cascades on delete |
| `title` | VARCHAR(255) | NULL | Headline text overlaid on the image |
| `subtitle` | VARCHAR(255) | NULL | Secondary text overlaid on the image |
| `image` | VARCHAR(500) | | Relative path from `media/carousel/` |
| `image_mobile` | VARCHAR(500) | NULL | Optional separate mobile image |
| `link_url` | VARCHAR(500) | NULL | Destination if slide is clicked |
| `link_target` | VARCHAR(20) | `_self` | `_self` or `_blank` |
| `button_text` | VARCHAR(100) | NULL | Primary CTA button label |
| `button_style` | VARCHAR(50) | `btn-primary` | CSS class for the button |
| `text_position` | VARCHAR(50) | `center-center` | Vertical-horizontal grid position |
| `text_color` | VARCHAR(20) | `text-white` | CSS color or class |
| `text_width` | VARCHAR(50) | NULL | Max-width of text block e.g. `50%`, `500px` |
| `text_alignment` | VARCHAR(20) | `center` | `left`, `center`, or `right` |
| `overlay_opacity` | SMALLINT | 0 | Black overlay 0-100 (percentage) |
| `sort_order` | INT | 0 | Lower = first |
| `status` | SMALLINT | 1 | 1 = Enabled, 0 = Disabled |
| `custom_html` | TEXT | NULL | Full custom HTML; replaces image + text when set |

---

## Admin guide

### Navigating to the carousel manager

Log into the Maho admin panel and go to **CMS > Carousel Builder**.

The grid shows all carousels with their ID, title, identifier, type, status, and creation date. Use the **Edit** and **Delete** row actions, or the checkboxes and the mass-action dropdown for bulk operations.

### Creating a carousel

Click **Add New Carousel**. The edit form has two tabs: **General Information** and **Slides**.

### Carousel general settings

| Field | Description |
|-------|-------------|
| **Title** | Internal name shown in the admin grid. Required. |
| **Identifier** | Unique text slug (e.g. `home-hero`). Required. Use only lowercase letters, numbers, and hyphens. Auto-generated from the title if left blank on first save. Reference this in widgets or layout XML instead of the numeric ID so it survives database re-imports. |
| **Status** | Enabled / Disabled. Disabled carousels render nothing on the storefront. |
| **Type** | Label only (Standard / Hero Banner / Product Carousel / Testimonials). Does not change rendering behavior; useful for filtering in future extensions. |
| **Height** | CSS height applied to each slide as a CSS custom property (`--mhc-h`). Accepts any valid CSS length: `500px`, `60vh`, `auto`. Leave blank for natural image height. |

### Carousel display settings

| Field | Description |
|-------|-------------|
| **Show Navigation Arrows** | Yes/No. Prev/next arrow buttons. Only rendered when the carousel has more than one enabled slide. |
| **Show Dots** | Yes/No. Dot indicators below the carousel. Only rendered when there is more than one enabled slide. |
| **Autoplay** | Yes/No. Auto-advance slides. |
| **Autoplay Speed (ms)** | Interval between slides in milliseconds when autoplay is on. Default `5000` (5 seconds). Autoplay pauses when the user hovers over the carousel. |
| **Slides to Show (Desktop)** | Number of slides visible at once on desktop. |
| **Slides to Show (Mobile)** | Number of slides visible at once on mobile. |

Save the carousel before adding slides. The **Slides** tab becomes interactive once a carousel ID exists.

### Managing slides

Open the **Slides** tab. Existing slides are listed with their image preview, title, and sort order. Use the **Add Slide** button to open the slide editor panel. Each slide is saved independently via AJAX.

### Slide fields

| Field | Description |
|-------|-------------|
| **Image** | Upload a JPG, JPEG, GIF, PNG, or WebP file. On upload, the module generates WebP and AVIF variants alongside the original in `public/media/carousel/{carousel_id}/`. |
| **Mobile Image** | Optional separate image served on narrow viewports. Falls back to the main image if not set. |
| **Title** | Headline text rendered over the image. Escaped on output. |
| **Subtitle** | Secondary text rendered over the image. |
| **Link URL** | URL the slide navigates to if clicked. |
| **Link Target** | `_self` (same tab) or `_blank` (new tab). |
| **Button Text** | Label for the primary CTA button. |
| **Button Style** | CSS class applied to the button element. Options are `btn-primary`, `btn-secondary`, `btn-accent`, `btn-ghost`, `btn-outline`, `btn-link`. These map to DaisyUI button classes in the DaisyUI template; in the self-contained template the base button style (`mhc__btn`) is always applied regardless of this field. |
| **Text Position** | Where the text block is anchored on the image. A grid of nine positions: top-left, top-center, top-right, center-left, center (default), center-right, bottom-left, bottom-center, bottom-right. |
| **Text Alignment** | Alignment of text within the text block: left, center, or right. |
| **Text Width** | Max-width of the text wrapper. Accepts CSS values: `50%`, `400px`. Capped at `calc(100% - 4rem)` to prevent overflow. |
| **Text Color** | Color applied to title and subtitle text. In the self-contained template this is a raw CSS color value (e.g. `#ffffff`, `white`). In the DaisyUI template it is treated as a Tailwind text-color class (e.g. `text-white`, `text-black`). |
| **Overlay Opacity** | Integer 0-100. A semi-transparent black layer placed over the image to improve text legibility. `0` = no overlay. `50` = 50% black. |
| **Sort Order** | Lower numbers appear first. |
| **Status** | Enabled / Disabled. Disabled slides are excluded from the storefront collection. |
| **Custom HTML** | Optional full HTML content. When set, the image, overlay, and text overlay are replaced entirely by this HTML. Processed through the CMS template filter (supports `{{block}}` and `{{store url}}` directives). |

### Reordering slides

Drag and drop slides within the Slides tab. The new order is persisted via AJAX to `updateSlideOrder`.

### Deleting a carousel

Use the **Delete** button on the edit page or the **Delete** row action in the grid. Deleting a carousel cascades to all its slides (foreign key `CASCADE`). Uploaded image files in `public/media/carousel/{carousel_id}/` are cleaned up when individual slides are deleted via the slide delete action, but not automatically on carousel-level deletion; remove the directory manually if needed.

---

## Placing the carousel on the storefront

### Via the Widgets UI (CMS pages and blocks)

1. Edit a CMS page or static block.
2. In the WYSIWYG editor toolbar, click **Insert Widget**.
3. Choose **DaisyUI Carousel** from the widget type list.
4. Fill in the widget parameters (see below).
5. Click **Insert Widget**, then save the page/block and flush the cache.

### Via layout XML

Add the block to any layout handle in your theme or module:

```xml
<block type="carousel/widget" name="home.carousel" as="home_carousel">
    <action method="setCarouselId"><carousel_id>3</carousel_id></action>
</block>
```

Or reference by identifier (preferred - survives re-imports):

```xml
<block type="carousel/widget" name="home.carousel" as="home_carousel">
    <action method="setIdentifier"><identifier>home-hero</identifier></action>
</block>
```

Then render it in the parent template with `<?= $this->getChildHtml('home_carousel') ?>`.

### Widget parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `carousel_id` | Select | Choose a carousel from the dropdown. Loads by numeric ID. |
| `identifier` | Text | Alternative to `carousel_id`. The carousel's text identifier. If both are set, `carousel_id` takes precedence. |
| `template` | Select | Which `.phtml` template to use. `carousel/widget.phtml` (default) or `carousel/hero.phtml`. |

---

## Frontend templates

### Option B: self-contained base/default template

**File:** `app/design/frontend/base/default/template/carousel/widget.phtml`

This is the default template used by the `carousel/widget` block. It has no dependency on any CSS framework. It uses BEM-style class names prefixed with `mhc` (Maho Carousel).

**CSS and JS** are loaded via `app/design/frontend/base/default/layout/carousel.xml` which adds them to every page under the `<default>` layout handle:

- `skin/frontend/base/default/css/carousel/carousel.css`
- `skin/frontend/base/default/js/carousel/carousel.js`

The JavaScript is a self-contained IIFE (no jQuery, no Prototype.js). It initializes all `.mhc[data-mhc]` elements on `DOMContentLoaded`, handling:
- Prev/next navigation
- Dot indicator clicks
- Autoplay with pause-on-hover

Key CSS custom property: `--mhc-h` sets the slide height (set via the carousel's **Height** field).

The template produces a `<picture>` element per slide with AVIF and WebP `<source>` elements before the fallback `<img>`, so browsers that support modern formats get the smaller file automatically.

### Option A: DaisyUI/Tailwind override template

**File:** `docs/examples/widget-daisyui.phtml`

This is an example template for themes built with Tailwind CSS and DaisyUI. It uses DaisyUI's `carousel` component classes and Tailwind utility classes for positioning and typography.

It calls the same block helper methods (`getCarousel()`, `getSlides()`, `getImageSources()`, `getTextPositionClasses()`) plus two additional helpers that exist in newer widget builds: `getTextAlignmentClass()` and `getSafeTextColor()` / `getSafeButtonStyle()`. If your theme's version of the block does not expose those methods, adjust accordingly.

Navigation in this template is handled by inline vanilla JS at the bottom of the file (scrolls `.carousel-item` into view). Dot navigation uses DaisyUI's anchor-hash approach.

### Overriding the template in your theme

To use a custom template, copy `widget.phtml` (or `widget-daisyui.phtml`) to your theme:

```
app/design/frontend/{package}/{theme}/template/carousel/widget.phtml
```

Maho's theme fallback system will prefer the theme-level file over `base/default`. No module config change is needed.

Alternatively, pass the `template` parameter when inserting the widget (see [Widget parameters](#widget-parameters)).

### Responsive image rendering

`Block_Widget::getImageSources()` calls `Model_Image::getResponsiveImageSources()`, which checks the `media/carousel/` directory for co-located AVIF and WebP variants of the slide image and returns an array of `[srcset, type]` entries. The template wraps these in `<source>` elements inside a `<picture>`. If no modern-format files exist for a given image, only the original `<img>` fallback is rendered.

The variants are generated at upload time by `controllers/Adminhtml/CarouselController::_processImageUpload()` (using `Model_Image::processCarouselImage()`) and on a rolling basis by the daily cron.

---

## Image optimization cron

**Job name:** `carousel_optimize_images`
**Default schedule:** `0 3 * * *` (daily at 03:00)
**Log file:** `var/log/image_optimizer.log`

### What it does

On each run the cron:

1. Builds a list of JPG/JPEG/PNG files modified in the last 24 hours across three media directories:
   - `media/catalog/product`
   - `media/wysiwyg`
   - `media/carousel`
2. Groups those files by their parent directory to avoid redundant passes.
3. For each parent directory, calls `Model_ImageOptimizer::optimizeDirectory()` with these settings:
   - Format: `webp` (AVIF is not generated by the cron by default)
   - Quality: 85
   - Max dimensions: 2400x2400 (images larger than this are scaled down before encoding)
   - Skips files smaller than 10 KB
   - Skips files for which a `.webp` already exists (use `force: true` in custom calls to override)
   - If the resulting WebP is larger than the original, it is discarded
4. The cron has a hard runtime cap of 120 seconds to prevent zombie runs.
5. After processing, clears the Maho `image` cache tag.

### Image processing backend

`Model_ImageOptimizer` uses Intervention Image (v3). It prefers the Imagick driver if the `imagick` PHP extension is loaded; otherwise falls back to GD. Imagick is recommended for better WebP quality and AVIF support.

### Running manually

To trigger the optimization outside of cron (e.g., after a bulk image import):

```bash
./maho cron:run --group carousel_optimize_images
```

Or call the model directly from a script:

```php
$optimizer = Mage::getModel('carousel/imageOptimizer');
$stats = $optimizer->optimizeDirectory(
    Mage::getBaseDir('media') . '/catalog/product',
    ['force' => true, 'quality' => 80]
);
var_dump($stats);
// ['processed' => 142, 'skipped' => 23, 'errors' => 0, 'space_saved' => 8421376]
```

### Finding large images

`Model_ImageOptimizer::findLargeImages()` returns a sorted list of images above a size threshold (default 512 KB). Useful for auditing before a bulk conversion:

```php
$optimizer = Mage::getModel('carousel/imageOptimizer');
$large = $optimizer->findLargeImages(Mage::getBaseDir('media') . '/catalog/product', 1048576); // > 1 MB
```

---

## Troubleshooting and FAQ

**The Carousel Builder menu item does not appear in the admin.**

Check that `app/etc/modules/Mageaustralia_Carousel.xml` exists (it is installed by Composer). Flush the config cache. Confirm your admin role includes the `admin/cms/carousel` ACL resource.

**The carousel does not appear on the storefront.**

- Check that the carousel's **Status** is Enabled.
- Check that at least one slide is Enabled and has an image.
- If using the widget identifier, verify it matches exactly (case-sensitive).
- Flush the full cache.

**The CSS and JS are not loading on the frontend.**

The self-contained template relies on `layout/carousel.xml` injecting `skin_css` and `skin_js` items into the `<head>` block. If your theme does not merge from `base/default` layout, copy the layout update into your own theme's layout XML.

**WebP images are not being generated on upload.**

The server's GD or Imagick extension must support WebP. Check with `php -r "print_r(gd_info());"` for GD or `php -r "print_r((new Imagick)->queryFormats('WEBP'));"` for Imagick. If neither supports WebP, upgrade the extension or install `libwebp`.

**The cron does not seem to be running.**

Check `var/log/cron.log` and `var/log/image_optimizer.log`. Confirm Maho's cron is active (`./maho cron:run` should execute without error). The cron will only process images modified in the last 24 hours; if files are older, call `optimizeDirectory()` with `force: true` directly.

**AVIF images are not generated.**

AVIF support requires Imagick compiled with libheif, or PHP 8.1+ GD with `imageavif()`. By default, only WebP is targeted by the cron. AVIF generation is opt-in via the `formats` option when calling `optimizeDirectory()` directly, or is generated at upload time if the server supports it.

**I want to use a custom template per widget instance.**

Pass the `template` parameter when inserting the widget via the Widgets UI or set it in layout XML:

```xml
<action method="setTemplate"><template>carousel/hero.phtml</template></action>
```

**How do I reference a carousel from a CMS block or WYSIWYG?**

Use the Widgets UI to insert `{{widget type="carousel/widget" carousel_id="3"}}`. Using the identifier is more portable: `{{widget type="carousel/widget" identifier="home-hero"}}`.

**Can slides have completely custom HTML instead of an image?**

Yes. Fill in the **Custom HTML** field on a slide. When present, the module renders that content instead of the image/overlay/text stack. The HTML is processed through Maho's CMS template filter, so `{{store url=""}}` and `{{block type="..."}}` directives work.

**The text position and color fields look like DaisyUI utility classes. Do they work in the base/default template?**

The self-contained template translates `text_position` (e.g. `center-left`) into flex CSS values and `text_color` into a raw CSS color. If you store Tailwind class names like `text-white` in the color field, the self-contained template handles `white`/`black` keywords and falls through to using the stored value as a literal CSS color for all other values. For full Tailwind compatibility, use the DaisyUI override template.

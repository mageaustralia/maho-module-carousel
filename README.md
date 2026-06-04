# Mageaustralia_Carousel

A CMS-driven carousel for Maho: manage carousels and their slides from the
admin, drop them anywhere via a widget, and keep recently-uploaded images
optimized (WebP) on a daily cron.

## Requirements

- Maho 26.5+
- PHP 8.3+

## Installation

    composer require mageaustralia/maho-module-carousel
    composer dump-autoload -o

## Features

- Admin: Carousels grid + per-carousel Slides management.
- Frontend: a `carousel/widget` widget (insert via Widgets or layout).
- Cron `carousel_optimize_images` (daily): converts recently-modified images in
  the media tree to WebP via Intervention Image.

## Notes

- Model/block/helper group alias is `carousel`; tables are `custom_carousel`
  and `custom_carousel_slide` (kept for drop-in compatibility with the original
  Custom_Carousel module this was productized from).
- The bundled `base/default` widget template is a fallback; themes may override
  `template/carousel/widget.phtml`.

## License

OSL-3.0

<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class Mageaustralia_Carousel_Model_ImageOptimizer extends Mage_Core_Model_Abstract
{
    protected ImageManager $imageManager;
    protected array $processedImages = [];
    /** @var array{processed: int, skipped: int, errors: int, space_saved: int} */
    protected array $stats = [
        'processed' => 0,
        'skipped' => 0,
        'errors' => 0,
        'space_saved' => 0,
    ];

    public function __construct()
    {
        parent::__construct();

        // Use Imagick if available for better quality
        try {
            if (extension_loaded('imagick')) {
                $this->imageManager = new ImageManager(new ImagickDriver());
            } else {
                $this->imageManager = new ImageManager(new GdDriver());
            }
        } catch (Exception) {
            $this->imageManager = new ImageManager(new GdDriver());
        }
    }

    /**
     * Optimize images in a directory recursively
     *
     * @param array<string, mixed> $options
     * @return array{processed: int, skipped: int, errors: int, space_saved: int}
     */
    public function optimizeDirectory(string $directory, array $options = []): array
    {
        $options = array_merge([
            'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
            'formats' => ['webp'],  // Can add 'avif' if your server supports it
            'quality' => 85,
            'max_width' => 2400,
            'max_height' => 2400,
            'skip_smaller_than' => 10240, // Skip files smaller than 10KB
            'recursive' => true,
            'dry_run' => false,
            'force' => false,  // Re-process even if WebP exists
        ], $options);

        $this->log("Starting optimization of: $directory");
        $this->_processDirectory($directory, $options);

        return $this->stats;
    }

    /**
     * Process all images in a directory
     *
     * @param array<string, mixed> $options
     */
    protected function _processDirectory(string $directory, array $options): void
    {
        if (!is_dir($directory)) {
            $this->log("Directory not found: $directory");
            return;
        }

        $iterator = $options['recursive']
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS,
            ))
            : new DirectoryIterator($directory);

        foreach ($iterator as $file) {
            // Never follow symlinks out of the media tree we were asked to walk.
            if ($file->isLink()) {
                continue;
            }
            if ($file->isFile()) {
                $ext = strtolower(pathinfo((string) $file->getPathname(), PATHINFO_EXTENSION));

                if (in_array($ext, $options['extensions'])) {
                    $this->_processImage($file->getPathname(), $options);
                }
            }
        }
    }

    /**
     * Process a single image
     *
     * @param array<string, mixed> $options
     */
    protected function _processImage(string $imagePath, array $options): void
    {
        try {
            // Skip if file is too small
            $fileSize = filesize($imagePath);
            if ($fileSize < $options['skip_smaller_than']) {
                $this->log("Skipping (too small): $imagePath");
                $this->stats['skipped']++;
                return;
            }

            $pathInfo = pathinfo((string) $imagePath);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = strtolower($pathInfo['extension']);

            // Check if already processed
            $webpPath = $directory . DS . $filename . '.webp';
            $avifPath = $directory . DS . $filename . '.avif';

            if (!$options['force']) {
                $hasWebp = in_array('webp', $options['formats']) && file_exists($webpPath);
                $hasAvif = in_array('avif', $options['formats']) && file_exists($avifPath);

                if ($hasWebp || $hasAvif) {
                    $this->log("Already optimized: $imagePath");
                    $this->stats['skipped']++;
                    return;
                }
            }

            if ($options['dry_run']) {
                $this->log("Would optimize: $imagePath (" . $this->formatBytes($fileSize) . ')');
                $this->stats['processed']++;
                return;
            }

            // Load and process image
            $image = $this->imageManager->decodePath($imagePath);

            // Get original dimensions
            $width = $image->width();
            $height = $image->height();

            // Resize if too large
            if ($width > $options['max_width'] || $height > $options['max_height']) {
                $image->scale(
                    width: $options['max_width'],
                    height: $options['max_height'],
                );
                $this->log("Resized: $imagePath from {$width}x{$height}");
            }

            $originalSize = $fileSize;
            $newSize = 0;

            // Generate WebP
            if (in_array('webp', $options['formats']) && $this->supportsWebP()) {
                $image->save($webpPath, quality: $options['quality']);
                $webpSize = filesize($webpPath);
                $newSize = $webpSize;

                // If WebP is larger than original, remove it
                if ($webpSize >= $originalSize) {
                    unlink($webpPath);
                    $this->log("WebP larger than original, skipped: $imagePath");
                } else {
                    $saved = $originalSize - $webpSize;
                    $percent = round(($saved / $originalSize) * 100);
                    $this->log("Created WebP: $webpPath (saved {$percent}% / " . $this->formatBytes($saved) . ')');
                    $this->stats['space_saved'] += $saved;
                }
            }

            // Generate AVIF (if supported)
            if (in_array('avif', $options['formats']) && $this->supportsAvif()) {
                $image->save($avifPath, quality: $options['quality'] - 10);
                $avifSize = filesize($avifPath);

                // If AVIF is larger than original or WebP, remove it
                if ($avifSize >= $originalSize || ($newSize > 0 && $avifSize >= $newSize)) {
                    unlink($avifPath);
                    $this->log("AVIF larger than original/WebP, skipped: $imagePath");
                } else {
                    $saved = $originalSize - $avifSize;
                    $percent = round(($saved / $originalSize) * 100);
                    $this->log("Created AVIF: $avifPath (saved {$percent}% / " . $this->formatBytes($saved) . ')');
                    $this->stats['space_saved'] += $saved;
                }
            }

            // Optimize original (if PNG or JPG)
            if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
                $optimizedPath = $directory . DS . $filename . '_optimized.' . $extension;
                $image->save($optimizedPath, quality: $options['quality']);

                $optimizedSize = filesize($optimizedPath);
                if ($optimizedSize < $originalSize * 0.9) { // Only replace if 10% smaller
                    rename($optimizedPath, $imagePath);
                    $saved = $originalSize - $optimizedSize;
                    $percent = round(($saved / $originalSize) * 100);
                    $this->log("Optimized original: $imagePath (saved {$percent}% / " . $this->formatBytes($saved) . ')');
                    $this->stats['space_saved'] += $saved;
                } else {
                    unlink($optimizedPath);
                }
            }

            $this->stats['processed']++;

        } catch (Exception $e) {
            $this->log("Error processing $imagePath: " . $e->getMessage());
            $this->stats['errors']++;
        }
    }

    /**
     * Optimize catalog product images
     *
     * @param array<string, mixed> $options
     * @return array{processed: int, skipped: int, errors: int, space_saved: int}
     */
    public function optimizeCatalogImages(array $options = []): array
    {
        $mediaPath = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product';
        return $this->optimizeDirectory($mediaPath, $options);
    }

    /**
     * Optimize CMS/WYSIWYG images
     *
     * @param array<string, mixed> $options
     * @return array{processed: int, skipped: int, errors: int, space_saved: int}
     */
    public function optimizeWysiwygImages(array $options = []): array
    {
        $mediaPath = Mage::getBaseDir('media') . DS . 'wysiwyg';
        return $this->optimizeDirectory($mediaPath, $options);
    }

    /**
     * Optimize theme images
     *
     * @param array<string, mixed> $options
     * @return array{processed: int, skipped: int, errors: int, space_saved: int}
     */
    public function optimizeThemeImages(?string $theme = null, array $options = []): array
    {
        if (!$theme) {
            $theme = 'custom/daisyui';
        }

        $skinPath = Mage::getBaseDir('skin') . DS . 'frontend' . DS . $theme . DS . 'images';
        return $this->optimizeDirectory($skinPath, $options);
    }

    /**
     * Get list of large images
     *
     * @return list<array{path: string, size: int|false, size_formatted: string, extension: string}>
     */
    public function findLargeImages(string $directory, int $minSize = 524288): array // 512KB default
    {
        $largeImages = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if ($file->isLink()) {
                continue;
            }
            if ($file->isFile()) {
                $ext = strtolower(pathinfo((string) $file->getPathname(), PATHINFO_EXTENSION));

                if (in_array($ext, $extensions)) {
                    $size = filesize($file->getPathname());
                    if ($size >= $minSize) {
                        $largeImages[] = [
                            'path' => $file->getPathname(),
                            'size' => $size,
                            'size_formatted' => $this->formatBytes($size),
                            'extension' => $ext,
                        ];
                    }
                }
            }
        }

        // Sort by size descending
        usort($largeImages, fn($a, $b) => $b['size'] - $a['size']);

        return $largeImages;
    }

    /**
     * Check WebP support
     */
    protected function supportsWebP(): bool
    {
        if (extension_loaded('imagick')) {
            $imagick = new Imagick();
            return in_array('WEBP', $imagick->queryFormats());
        }
        return function_exists('imagewebp');
    }

    /**
     * Check AVIF support
     */
    protected function supportsAvif(): bool
    {
        if (extension_loaded('imagick')) {
            $imagick = new Imagick();
            return in_array('AVIF', $imagick->queryFormats());
        }
        return function_exists('imageavif');
    }

    /**
     * Format bytes to human readable
     */
    public function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Log message
     */
    protected function log(string $message): void
    {
        if (php_sapi_name() === 'cli') {
            echo date('[Y-m-d H:i:s] ') . $message . "\n";
        }
        Mage::log($message, null, 'image_optimizer.log');
    }
}

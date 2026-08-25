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

class Mageaustralia_Carousel_Model_Image extends Mage_Core_Model_Abstract
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        parent::__construct();

        // Use Imagick if available, otherwise fallback to GD
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
     * Process uploaded carousel image with modern format conversion
     *
     * @param string $sourceFile Path to uploaded file
     * @param string $destinationDir Directory to save processed images
     * @param array $options Processing options
     * @return array Paths to generated images
     */
    public function processCarouselImage(string $sourceFile, string $destinationDir, array $options = []): array
    {
        $results = [];

        // Default options
        $options = array_merge([
            'width' => null,
            'height' => null,
            'quality' => 85,
            'formats' => ['webp', 'avif', 'original'],
            'keepOriginal' => true,
            'generateThumbnail' => true,
            'thumbnailWidth' => 300,
            'thumbnailHeight' => 200,
        ], $options);

        // Create destination directory if it doesn't exist
        $mediaPath = Mage::getBaseDir('media') . DS . 'carousel' . DS . $destinationDir;
        if (!is_dir($mediaPath)) {
            mkdir($mediaPath, 0775, true);
        }

        // Get original file info
        $pathInfo = pathinfo($sourceFile);
        $baseName = $pathInfo['filename'];
        $originalExt = strtolower($pathInfo['extension']);

        // Load the image
        $image = $this->imageManager->decodePath($sourceFile);

        // Resize if dimensions specified
        if ($options['width'] || $options['height']) {
            $image->scale(
                width: $options['width'],
                height: $options['height'],
            );
        }

        // Generate modern formats
        foreach ($options['formats'] as $format) {
            $fileName = $baseName;

            switch ($format) {
                case 'webp':
                    if ($this->supportsWebP()) {
                        $webpFile = $mediaPath . DS . $fileName . '.webp';
                        $image->save($webpFile, quality: $options['quality']);
                        $results['webp'] = $destinationDir . '/' . $fileName . '.webp';
                    }
                    break;

                case 'avif':
                    if ($this->supportsAvif()) {
                        $avifFile = $mediaPath . DS . $fileName . '.avif';
                        $image->save($avifFile, quality: $options['quality'] - 10); // AVIF works better with slightly lower quality
                        $results['avif'] = $destinationDir . '/' . $fileName . '.avif';
                    }
                    break;

                case 'original':
                    if ($options['keepOriginal']) {
                        // Save optimized version of original format
                        $optimizedFile = $mediaPath . DS . $fileName . '.' . $originalExt;
                        $image->save($optimizedFile, quality: $options['quality']);
                        $results['original'] = $destinationDir . '/' . $fileName . '.' . $originalExt;
                    }
                    break;
            }
        }

        // Generate thumbnail if requested
        if ($options['generateThumbnail']) {
            $thumbImage = $this->imageManager->decodePath($sourceFile);
            $thumbImage->cover(
                $options['thumbnailWidth'],
                $options['thumbnailHeight'],
            );

            $thumbFile = $mediaPath . DS . $baseName . '_thumb.webp';
            $thumbImage->save($thumbFile, quality: 80);
            $results['thumbnail'] = $destinationDir . '/' . $baseName . '_thumb.webp';
        }

        return $results;
    }

    /**
     * Get optimal image source for browser
     * Returns array of sources for picture element
     */
    public function getResponsiveImageSources(string $imagePath): array
    {
        $sources = [];
        $mediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'carousel/';
        $mediaPath = Mage::getBaseDir('media') . DS . 'carousel' . DS;

        $pathInfo = pathinfo((string) $imagePath);
        $baseName = $pathInfo['filename'];
        $dir = $pathInfo['dirname'];

        // Check for AVIF version
        $avifPath = $dir . '/' . $baseName . '.avif';
        if (file_exists($mediaPath . str_replace('/', DS, $avifPath))) {
            $sources[] = [
                'srcset' => $mediaUrl . $avifPath,
                'type' => 'image/avif',
            ];
        }

        // Check for WebP version
        $webpPath = $dir . '/' . $baseName . '.webp';
        if (file_exists($mediaPath . str_replace('/', DS, $webpPath))) {
            $sources[] = [
                'srcset' => $mediaUrl . $webpPath,
                'type' => 'image/webp',
            ];
        }

        // Original as fallback
        $sources[] = [
            'src' => $mediaUrl . $imagePath,
            'type' => $this->getMimeType($imagePath),
        ];

        return $sources;
    }

    /**
     * Check if WebP is supported
     */
    protected function supportsWebP(): bool
    {
        if (extension_loaded('imagick')) {
            $imagick = new Imagick();
            return in_array('WEBP', $imagick->queryFormats());
        }

        // GD support for WebP
        return function_exists('imagewebp');
    }

    /**
     * Check if AVIF is supported
     */
    protected function supportsAvif(): bool
    {
        if (extension_loaded('imagick')) {
            $imagick = new Imagick();
            return in_array('AVIF', $imagick->queryFormats());
        }

        // GD support for AVIF (PHP 8.1+)
        return function_exists('imageavif');
    }

    /**
     * Get MIME type for image
     */
    protected function getMimeType(string $imagePath): string
    {
        $ext = strtolower(pathinfo((string) $imagePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
        ];

        return $mimeTypes[$ext] ?? 'image/jpeg';
    }
}

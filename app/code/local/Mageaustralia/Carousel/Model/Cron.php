<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mageaustralia_Carousel_Model_Cron
{
    /**
     * Cron job to optimize recently uploaded images
     * Run daily to convert new images to WebP
     */
    public function optimizeRecentImages(): void
    {
        try {
            $optimizer = Mage::getModel('carousel/imageOptimizer');

            // Get images modified in last 24 hours
            $since = time() - (24 * 3600);
            $deadline = time() + 120; // hard runtime cap so the cron cannot zombie
            $doneDirs = [];

            $directories = [
                Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product',
                Mage::getBaseDir('media') . DS . 'wysiwyg',
                Mage::getBaseDir('media') . DS . 'carousel',
            ];

            $totalProcessed = 0;
            $totalSaved = 0;

            foreach ($directories as $dir) {
                if (!is_dir($dir) || time() > $deadline) {
                    continue;
                }

                $files = $this->findRecentImages($dir, $since, $deadline);

                foreach ($files as $file) {
                    if (time() > $deadline) {
                        break 2;
                    }
                    $targetDir = dirname((string) $file);
                    if (isset($doneDirs[$targetDir])) {
                        continue;
                    }
                    $doneDirs[$targetDir] = true;
                    $options = [
                        'formats' => ['webp'],
                        'quality' => 85,
                        'max_width' => 2400,
                        'max_height' => 2400,
                        'force' => false,
                    ];

                    $stats = $optimizer->optimizeDirectory($targetDir, array_merge($options, [
                        'recursive' => false,
                    ]));

                    $totalProcessed += $stats['processed'];
                    $totalSaved += $stats['space_saved'];
                }
            }

            if ($totalProcessed > 0) {
                Mage::log("Image optimization cron: Processed {$totalProcessed} images, saved " .
                    $this->formatBytes($totalSaved), null, 'image_optimizer.log');

                // Clear image cache
                Mage::app()->cleanCache(['image']);
            }

        } catch (\Throwable $e) {
            Mage::logException($e);
        }
    }

    /**
     * Find images modified since given timestamp
     */
    protected function findRecentImages(string $directory, int $since, int $deadline = 0): array
    {
        // Native, bounded mtime filter (the find -newermt concept, in PHP - no
        // shelling out). PHP has to walk the tree, so the $deadline cap stops it
        // before it can run long enough to zombie the cron.
        $recentFiles = [];
        $extensions = ['jpg', 'jpeg', 'png'];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($deadline && time() > $deadline) {
                break;
            }
            if ($file->isFile()) {
                $ext = strtolower(pathinfo((string) $file->getPathname(), PATHINFO_EXTENSION));
                if (in_array($ext, $extensions, true) && $file->getMTime() >= $since) {
                    $recentFiles[] = $file->getPathname();
                }
            }
        }
        return $recentFiles;
    }

    /**
     * Format bytes
     */
    protected function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

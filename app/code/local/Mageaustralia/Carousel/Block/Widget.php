<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mageaustralia_Carousel_Block_Widget extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    #[\Override]
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('carousel/widget.phtml');
    }

    /**
     * Get carousel model
     */
    public function getCarousel(): ?Mageaustralia_Carousel_Model_Carousel
    {
        if (!$this->hasData('carousel_model')) {
            $carousel = null;

            if ($this->getCarouselId()) {
                $carousel = Mage::getModel('carousel/carousel')->load($this->getCarouselId());
            } elseif ($this->getIdentifier()) {
                $carousel = Mage::getModel('carousel/carousel')->loadByIdentifier($this->getIdentifier());
            }

            if ($carousel && $carousel->getId() && $carousel->getStatus()) {
                $this->setData('carousel_model', $carousel);
            }
        }

        return $this->getData('carousel_model');
    }

    /**
     * Get carousel slides
     *
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract|array<mixed>
     */
    public function getSlides(): Mage_Core_Model_Resource_Db_Collection_Abstract|array
    {
        if ($carousel = $this->getCarousel()) {
            return $carousel->getSlides();
        }

        return [];
    }

    /**
     * Get image sources for picture element
     *
     * @return array<int, array<string, string>>
     */
    public function getImageSources(Maho\DataObject $slide): array
    {
        $imageModel = Mage::getModel('carousel/image');
        return $imageModel->getResponsiveImageSources($slide->getImage());
    }

    /**
     * Get carousel unique ID for JavaScript
     */
    public function getCarouselHtmlId(): string
    {
        return 'carousel-' . ($this->getCarousel() ? $this->getCarousel()->getId() : uniqid());
    }

    /**
     * Get carousel configuration for JavaScript
     */
    public function getCarouselConfig(): string
    {
        $carousel = $this->getCarousel();
        if (!$carousel) {
            return json_encode([]);
        }

        $config = [
            'autoplay' => (bool) $carousel->getAutoplay(),
            'autoplaySpeed' => (int) $carousel->getAutoplaySpeed(),
            'showNavigation' => (bool) $carousel->getShowNavigation(),
            'showDots' => (bool) $carousel->getShowDots(),
            'slidesToShow' => (int) $carousel->getSlidesToShow(),
            'slidesToShowMobile' => (int) $carousel->getSlidesToShowMobile(),
            'loop' => true,
        ];

        return json_encode($config);
    }

    /**
     * Get text position classes
     */
    public function getTextPositionClasses(string $position): string
    {
        $classes = [
            'top-left' => 'top-0 left-0 items-start justify-start text-left',
            'top-center' => 'top-0 left-0 right-0 items-start justify-center text-center',
            'top-right' => 'top-0 right-0 items-start justify-end text-right',
            'center-left' => 'top-1/2 left-0 -translate-y-1/2 items-center justify-start text-left',
            'center-center' => 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 items-center justify-center text-center',
            'center-right' => 'top-1/2 right-0 -translate-y-1/2 items-center justify-end text-right',
            'bottom-left' => 'bottom-0 left-0 items-end justify-start text-left',
            'bottom-center' => 'bottom-0 left-0 right-0 items-end justify-center text-center',
            'bottom-right' => 'bottom-0 right-0 items-end justify-end text-right',
        ];

        return $classes[$position] ?? $classes['center-center'];
    }

    /**
     * Get text alignment classes
     */
    public function getTextAlignmentClasses(string $alignment): string
    {
        $classes = [
            'left' => 'items-start',
            'center' => 'items-center',
            'right' => 'items-end',
        ];

        return $classes[$alignment] ?? $classes['center'];
    }

    /**
     * Process button text for markdown links
     * Converts ### to the slide's link URL
     */
    public function processButtonText(string $buttonText, string $linkUrl): string
    {
        if (empty($buttonText) || empty($linkUrl)) {
            return $buttonText;
        }

        // Replace ### with the actual link URL
        return str_replace('###', $linkUrl, $buttonText);
    }
}

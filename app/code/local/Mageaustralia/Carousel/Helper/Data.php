<?php

class Mageaustralia_Carousel_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get carousel types
     */
    public function getCarouselTypes(): array
    {
        return [
            'standard' => $this->__('Standard'),
            'hero' => $this->__('Hero Banner'),
            'product' => $this->__('Product Carousel'),
            'testimonial' => $this->__('Testimonials'),
        ];
    }

    /**
     * Get text positions
     */
    public function getTextPositions(): array
    {
        return [
            'top-left' => $this->__('Top Left'),
            'top-center' => $this->__('Top Center'),
            'top-right' => $this->__('Top Right'),
            'center-left' => $this->__('Center Left'),
            'center-center' => $this->__('Center'),
            'center-right' => $this->__('Center Right'),
            'bottom-left' => $this->__('Bottom Left'),
            'bottom-center' => $this->__('Bottom Center'),
            'bottom-right' => $this->__('Bottom Right'),
        ];
    }

    /**
     * Get button styles
     */
    public function getButtonStyles(): array
    {
        return [
            'btn-primary' => $this->__('Primary'),
            'btn-secondary' => $this->__('Secondary'),
            'btn-accent' => $this->__('Accent'),
            'btn-ghost' => $this->__('Ghost'),
            'btn-outline' => $this->__('Outline'),
            'btn-link' => $this->__('Link'),
        ];
    }

    /**
     * Get text colors
     */
    public function getTextColors(): array
    {
        return [
            'text-white' => $this->__('White'),
            'text-black' => $this->__('Black'),
            'text-primary' => $this->__('Primary'),
            'text-secondary' => $this->__('Secondary'),
            'text-accent' => $this->__('Accent'),
        ];
    }
}

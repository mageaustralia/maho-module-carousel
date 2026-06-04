<?php

class Mageaustralia_Carousel_Model_Slide extends Mage_Core_Model_Abstract
{
    protected function _construct(): void
    {
        $this->_init('carousel/slide');
    }

    /**
     * Get image URL
     */
    public function getImageUrl(): string
    {
        if ($this->getImage()) {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'carousel/' . $this->getImage();
        }
        return '';
    }

    /**
     * Get mobile image URL
     */
    public function getMobileImageUrl(): string
    {
        if ($this->getImageMobile()) {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'carousel/' . $this->getImageMobile();
        }
        return $this->getImageUrl(); // Fallback to regular image
    }
}

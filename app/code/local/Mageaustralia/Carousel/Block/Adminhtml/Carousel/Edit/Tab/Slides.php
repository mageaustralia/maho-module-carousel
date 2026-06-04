<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mageaustralia_Carousel_Block_Adminhtml_Carousel_Edit_Tab_Slides extends Mage_Adminhtml_Block_Widget
{
    #[\Override]
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('carousel/slides.phtml');
    }

    public function getCarousel(): ?Mageaustralia_Carousel_Model_Carousel
    {
        return Mage::registry('current_carousel');
    }

    public function getSlides(): Mage_Core_Model_Resource_Db_Collection_Abstract|array
    {
        $carousel = $this->getCarousel();
        if ($carousel && $carousel->getId()) {
            return Mage::getModel('carousel/slide')->getCollection()
                ->addFieldToFilter('carousel_id', $carousel->getId())
                ->setOrder('sort_order', 'ASC');
        }
        return [];
    }

    public function getUploadUrl(): string
    {
        return $this->getUrl('*/*/uploadSlide', ['carousel_id' => $this->getCarousel()->getId()]);
    }

    public function getSaveSlideUrl(): string
    {
        return $this->getUrl('*/*/saveSlide', ['carousel_id' => $this->getCarousel()->getId()]);
    }

    public function getDeleteSlideUrl(): string
    {
        return $this->getUrl('*/*/deleteSlide', ['carousel_id' => $this->getCarousel()->getId()]);
    }

    public function getUpdateOrderUrl(): string
    {
        return $this->getUrl('*/*/updateSlideOrder', ['carousel_id' => $this->getCarousel()->getId()]);
    }

    public function getMediaUrl(): string
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
    }
}

<?php

class Mageaustralia_Carousel_Model_Carousel extends Mage_Core_Model_Abstract
{
    protected function _construct(): void
    {
        $this->_init('carousel/carousel');
    }

    /**
     * Get slides collection
     */
    public function getSlides(): Mage_Core_Model_Resource_Db_Collection_Abstract
    {
        $collection = Mage::getModel('carousel/slide')->getCollection()
            ->addFieldToFilter('carousel_id', $this->getId())
            ->addFieldToFilter('status', 1)
            ->setOrder('sort_order', 'ASC');

        return $collection;
    }

    /**
     * Get carousel by identifier
     */
    public function loadByIdentifier(string $identifier): static
    {
        $this->_getResource()->loadByIdentifier($this, $identifier);
        return $this;
    }
}

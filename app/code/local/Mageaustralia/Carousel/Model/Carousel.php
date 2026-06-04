<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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

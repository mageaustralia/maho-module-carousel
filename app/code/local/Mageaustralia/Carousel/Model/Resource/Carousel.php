<?php

class Mageaustralia_Carousel_Model_Resource_Carousel extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct(): void
    {
        $this->_init('carousel/carousel', 'carousel_id');
    }

    /**
     * Load carousel by identifier
     */
    public function loadByIdentifier(Mageaustralia_Carousel_Model_Carousel $carousel, string $identifier): static
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('identifier = ?', $identifier);

        $carouselId = $adapter->fetchOne($select);
        if ($carouselId) {
            $carousel->load($carouselId);
        }
        return $this;
    }
}

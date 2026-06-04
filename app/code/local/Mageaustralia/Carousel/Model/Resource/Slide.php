<?php

class Mageaustralia_Carousel_Model_Resource_Slide extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct(): void
    {
        $this->_init('carousel/slide', 'slide_id');
    }
}

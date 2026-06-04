<?php

class Mageaustralia_Carousel_Model_Resource_Slide_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('carousel/slide');
    }
}

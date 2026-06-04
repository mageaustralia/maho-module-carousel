<?php

class Mageaustralia_Carousel_Block_Adminhtml_Carousel extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'carousel';
        $this->_controller = 'adminhtml_carousel';
        $this->_headerText = Mage::helper('carousel')->__('Manage Carousels');
        $this->_addButtonLabel = Mage::helper('carousel')->__('Add New Carousel');
        parent::__construct();
    }
}

<?php

class Mageaustralia_Carousel_Block_Adminhtml_Carousel_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('carousel_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('carousel')->__('Carousel Information'));
    }

    #[\Override]
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', [
            'label' => Mage::helper('carousel')->__('General Information'),
            'title' => Mage::helper('carousel')->__('General Information'),
            'content' => $this->getLayout()->createBlock('carousel/adminhtml_carousel_edit_tab_form')->toHtml(),
        ]);

        $this->addTab('slides_section', [
            'label' => Mage::helper('carousel')->__('Slides'),
            'title' => Mage::helper('carousel')->__('Slides'),
            'content' => $this->getLayout()->createBlock('carousel/adminhtml_carousel_edit_tab_slides')->toHtml(),
        ]);

        return parent::_beforeToHtml();
    }
}

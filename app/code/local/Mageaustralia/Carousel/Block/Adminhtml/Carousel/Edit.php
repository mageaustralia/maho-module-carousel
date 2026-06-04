<?php

class Mageaustralia_Carousel_Block_Adminhtml_Carousel_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'carousel';
        $this->_controller = 'adminhtml_carousel';

        $this->_updateButton('save', 'label', Mage::helper('carousel')->__('Save Carousel'));
        $this->_updateButton('delete', 'label', Mage::helper('carousel')->__('Delete Carousel'));

        $this->_addButton('saveandcontinue', [
            'label' => Mage::helper('adminhtml')->__('Save and Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ], -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    #[\Override]
    public function getHeaderText()
    {
        if (Mage::registry('current_carousel') && Mage::registry('current_carousel')->getId()) {
            return Mage::helper('carousel')->__(
                "Edit Carousel '%s'",
                $this->escapeHtml(Mage::registry('current_carousel')->getTitle()),
            );
        } else {
            return Mage::helper('carousel')->__('Add Carousel');
        }
    }
}

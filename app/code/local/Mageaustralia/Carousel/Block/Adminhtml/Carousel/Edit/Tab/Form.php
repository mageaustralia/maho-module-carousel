<?php

class Mageaustralia_Carousel_Block_Adminhtml_Carousel_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    #[\Override]
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $carousel = Mage::registry('current_carousel');

        $fieldset = $form->addFieldset('carousel_form', [
            'legend' => Mage::helper('carousel')->__('General Information'),
        ]);

        if ($carousel && $carousel->getId()) {
            $fieldset->addField('carousel_id', 'hidden', [
                'name' => 'carousel_id',
            ]);
        }

        $fieldset->addField('title', 'text', [
            'label' => Mage::helper('carousel')->__('Title'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'title',
        ]);

        $fieldset->addField('identifier', 'text', [
            'label' => Mage::helper('carousel')->__('Identifier'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'identifier',
            'note' => Mage::helper('carousel')->__('Unique identifier for this carousel. Use only lowercase letters, numbers, and hyphens.'),
        ]);

        $fieldset->addField('status', 'select', [
            'label' => Mage::helper('carousel')->__('Status'),
            'name' => 'status',
            'values' => [
                ['value' => 1, 'label' => Mage::helper('carousel')->__('Enabled')],
                ['value' => 0, 'label' => Mage::helper('carousel')->__('Disabled')],
            ],
        ]);

        $fieldset->addField('carousel_type', 'select', [
            'label' => Mage::helper('carousel')->__('Type'),
            'name' => 'carousel_type',
            'values' => Mage::helper('carousel')->getCarouselTypes(),
        ]);

        $fieldset->addField('height', 'text', [
            'label' => Mage::helper('carousel')->__('Height'),
            'name' => 'height',
            'note' => Mage::helper('carousel')->__('e.g., 500px, 60vh, auto'),
        ]);

        // Display Settings
        $displayFieldset = $form->addFieldset('display_settings', [
            'legend' => Mage::helper('carousel')->__('Display Settings'),
        ]);

        $displayFieldset->addField('show_navigation', 'select', [
            'label' => Mage::helper('carousel')->__('Show Navigation Arrows'),
            'name' => 'show_navigation',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
        ]);

        $displayFieldset->addField('show_dots', 'select', [
            'label' => Mage::helper('carousel')->__('Show Dots'),
            'name' => 'show_dots',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
        ]);

        $displayFieldset->addField('autoplay', 'select', [
            'label' => Mage::helper('carousel')->__('Autoplay'),
            'name' => 'autoplay',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
        ]);

        $displayFieldset->addField('autoplay_speed', 'text', [
            'label' => Mage::helper('carousel')->__('Autoplay Speed (ms)'),
            'name' => 'autoplay_speed',
            'note' => Mage::helper('carousel')->__('Time between slides in milliseconds (e.g., 5000 = 5 seconds)'),
        ]);

        $displayFieldset->addField('slides_to_show', 'text', [
            'label' => Mage::helper('carousel')->__('Slides to Show (Desktop)'),
            'name' => 'slides_to_show',
            'note' => Mage::helper('carousel')->__('Number of slides visible at once on desktop'),
        ]);

        $displayFieldset->addField('slides_to_show_mobile', 'text', [
            'label' => Mage::helper('carousel')->__('Slides to Show (Mobile)'),
            'name' => 'slides_to_show_mobile',
            'note' => Mage::helper('carousel')->__('Number of slides visible at once on mobile'),
        ]);

        if ($carousel) {
            $form->setValues($carousel->getData());
        }

        return parent::_prepareForm();
    }
}

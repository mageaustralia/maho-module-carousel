<?php

class Mageaustralia_Carousel_Model_Source_Carousel
{
    public function toOptionArray(): array
    {
        $options = [
            ['value' => '', 'label' => Mage::helper('carousel')->__('-- Please Select --')],
        ];

        $collection = Mage::getModel('carousel/carousel')->getCollection()
            ->addFieldToFilter('status', 1)
            ->setOrder('title', 'ASC');

        foreach ($collection as $carousel) {
            $options[] = [
                'value' => $carousel->getId(),
                'label' => $carousel->getTitle() . ' (' . $carousel->getIdentifier() . ')',
            ];
        }

        return $options;
    }
}

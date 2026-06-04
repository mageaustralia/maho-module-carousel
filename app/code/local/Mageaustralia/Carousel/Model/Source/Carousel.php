<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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

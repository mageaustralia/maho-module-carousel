<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mageaustralia_Carousel_Block_Adminhtml_Carousel_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('carouselGrid');
        $this->setDefaultSort('carousel_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    #[\Override]
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('carousel/carousel')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    #[\Override]
    protected function _prepareColumns()
    {
        $this->addColumn('carousel_id', [
            'header' => Mage::helper('carousel')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'carousel_id',
        ]);

        $this->addColumn('title', [
            'header' => Mage::helper('carousel')->__('Title'),
            'align' => 'left',
            'index' => 'title',
        ]);

        $this->addColumn('identifier', [
            'header' => Mage::helper('carousel')->__('Identifier'),
            'align' => 'left',
            'index' => 'identifier',
        ]);

        $this->addColumn('carousel_type', [
            'header' => Mage::helper('carousel')->__('Type'),
            'align' => 'left',
            'index' => 'carousel_type',
            'type' => 'options',
            'options' => Mage::helper('carousel')->getCarouselTypes(),
        ]);

        $this->addColumn('status', [
            'header' => Mage::helper('carousel')->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'status',
            'type' => 'options',
            'options' => [
                1 => 'Enabled',
                0 => 'Disabled',
            ],
        ]);

        $this->addColumn('created_at', [
            'header' => Mage::helper('carousel')->__('Created'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'date',
            'default' => '--',
            'index' => 'created_at',
        ]);

        $this->addColumn('action', [
            'header' => Mage::helper('carousel')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => [
                [
                    'caption' => Mage::helper('carousel')->__('Edit'),
                    'url' => ['base' => '*/*/edit'],
                    'field' => 'id',
                ],
                [
                    'caption' => Mage::helper('carousel')->__('Delete'),
                    'url' => [
                        'base' => '*/*/delete',
                        'params' => ['form_key' => Mage::getSingleton('core/session')->getFormKey()],
                    ],
                    'field' => 'id',
                    'confirm' => Mage::helper('carousel')->__('Are you sure you want to delete this carousel?'),
                ],
            ],
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ]);

        return parent::_prepareColumns();
    }

    #[\Override]
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('carousel_id');
        $this->getMassactionBlock()->setFormFieldName('carousel');

        $this->getMassactionBlock()->addItem('delete', [
            'label' => Mage::helper('carousel')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('carousel')->__('Are you sure?'),
        ]);

        $statuses = [
            1 => 'Enabled',
            0 => 'Disabled',
        ];

        $this->getMassactionBlock()->addItem('status', [
            'label' => Mage::helper('carousel')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', ['_current' => true]),
            'additional' => [
                'visibility' => [
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('carousel')->__('Status'),
                    'values' => $statuses,
                ],
            ],
        ]);

        return $this;
    }

    #[\Override]
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }
}

<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();
$tableName = $installer->getTable('carousel/slide');

// Add text_width field
if (!$connection->tableColumnExists($tableName, 'text_width')) {
    $connection->addColumn(
        $tableName,
        'text_width',
        [
            'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
            'length' => 50,
            'nullable' => true,
            'comment' => 'Text Wrapper Width (e.g., 250px, 25%, 500px)',
            'after' => 'text_color',
        ],
    );
}

// Add text_alignment field
if (!$connection->tableColumnExists($tableName, 'text_alignment')) {
    $connection->addColumn(
        $tableName,
        'text_alignment',
        [
            'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
            'length' => 20,
            'nullable' => false,
            'default' => 'center',
            'comment' => 'Text Block Alignment (left, center, right)',
            'after' => 'text_width',
        ],
    );
}

$installer->endSetup();

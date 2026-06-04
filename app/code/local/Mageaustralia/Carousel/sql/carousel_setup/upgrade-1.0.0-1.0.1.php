<?php

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

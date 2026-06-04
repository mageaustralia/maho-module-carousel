<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

// Check if carousel table exists before creating
if (!$connection->isTableExists($installer->getTable('carousel/carousel'))) {
    // Create carousel table
    $table = $connection
        ->newTable($installer->getTable('carousel/carousel'))
    ->addColumn('carousel_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ], 'Carousel ID')
    ->addColumn('title', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [
        'nullable' => false,
    ], 'Title')
    ->addColumn('identifier', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, [
        'nullable' => false,
    ], 'Identifier')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '1',
    ], 'Status')
    ->addColumn('show_navigation', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '1',
    ], 'Show Navigation Arrows')
    ->addColumn('show_dots', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '1',
    ], 'Show Dots')
    ->addColumn('autoplay', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '0',
    ], 'Autoplay')
    ->addColumn('autoplay_speed', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'nullable' => false,
        'default' => '5000',
    ], 'Autoplay Speed (ms)')
    ->addColumn('slides_to_show', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '1',
    ], 'Slides to Show')
    ->addColumn('slides_to_show_mobile', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '1',
    ], 'Slides to Show (Mobile)')
    ->addColumn('carousel_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, [
        'nullable' => false,
        'default' => 'standard',
    ], 'Carousel Type')
    ->addColumn('height', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, [
        'nullable' => true,
    ], 'Carousel Height')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
        'nullable' => false,
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT,
    ], 'Created At')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [
        'nullable' => false,
        'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE,
    ], 'Updated At')
    ->addIndex(
        $installer->getIdxName('carousel/carousel', ['identifier']),
        ['identifier'],
        ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE],
    )
    ->setComment('Carousel Table');

    $connection->createTable($table);
}

// Check if slides table exists before creating
if (!$connection->isTableExists($installer->getTable('carousel/slide'))) {
    // Create carousel slides table
    $table = $connection
        ->newTable($installer->getTable('carousel/slide'))
    ->addColumn('slide_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ], 'Slide ID')
    ->addColumn('carousel_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned' => true,
        'nullable' => false,
    ], 'Carousel ID')
    ->addColumn('title', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [
        'nullable' => true,
    ], 'Title')
    ->addColumn('subtitle', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [
        'nullable' => true,
    ], 'Subtitle')
    ->addColumn('image', Varien_Db_Ddl_Table::TYPE_VARCHAR, 500, [
        'nullable' => false,
    ], 'Image Path')
    ->addColumn('image_mobile', Varien_Db_Ddl_Table::TYPE_VARCHAR, 500, [
        'nullable' => true,
    ], 'Mobile Image Path')
    ->addColumn('link_url', Varien_Db_Ddl_Table::TYPE_VARCHAR, 500, [
        'nullable' => true,
    ], 'Link URL')
    ->addColumn('link_target', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, [
        'nullable' => false,
        'default' => '_self',
    ], 'Link Target')
    ->addColumn('button_text', Varien_Db_Ddl_Table::TYPE_VARCHAR, 100, [
        'nullable' => true,
    ], 'Button Text')
    ->addColumn('button_style', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, [
        'nullable' => false,
        'default' => 'btn-primary',
    ], 'Button Style')
    ->addColumn('text_position', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, [
        'nullable' => false,
        'default' => 'center-center',
    ], 'Text Position')
    ->addColumn('text_color', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, [
        'nullable' => false,
        'default' => 'text-white',
    ], 'Text Color')
    ->addColumn('overlay_opacity', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '0',
    ], 'Overlay Opacity')
    ->addColumn('sort_order', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'nullable' => false,
        'default' => '0',
    ], 'Sort Order')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'nullable' => false,
        'default' => '1',
    ], 'Status')
    ->addColumn('custom_html', Varien_Db_Ddl_Table::TYPE_TEXT, null, [
        'nullable' => true,
    ], 'Custom HTML')
    ->addIndex(
        $installer->getIdxName('carousel/slide', ['carousel_id']),
        ['carousel_id'],
    )
    ->addIndex(
        $installer->getIdxName('carousel/slide', ['sort_order']),
        ['sort_order'],
    )
    ->addForeignKey(
        $installer->getFkName('carousel/slide', 'carousel_id', 'carousel/carousel', 'carousel_id'),
        'carousel_id',
        $installer->getTable('carousel/carousel'),
        'carousel_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Carousel Slides Table');

    $connection->createTable($table);
}

$installer->endSetup();

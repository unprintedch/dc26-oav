<?php
declare(strict_types=1);

add_action('init', function (): void {

    register_block_style('core/list', [
        'name'  => 'check',
        'label' => __('Liste avec coches', 'dc26-oav'),
    ]);

    register_block_style('core/heading', [
        'name'  => 'badge-title',
        'label' => __('Badge titre', 'dc26-oav'),
    ]);

    register_block_style('core/details', [
        'name'  => 'big-details',
        'label' => __('Grand accordéon', 'dc26-oav'),
    ]);
});

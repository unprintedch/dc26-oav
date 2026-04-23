const { registerBlockVariation } = wp.blocks;
const { __ } = wp.i18n;
const { domReady } = wp;

domReady(() => {
    registerBlockVariation('core/accordion', {
        name: 'dc26-tabs-vertical',
        title: __('Tabs (vertical)', 'dc26-oav'),
        icon: 'index-card',
        scope: ['inserter'],
        attributes: {
            className: 'is-style-dc26-tabs-vertical',
            autoclose: true,
            showIcon: false,
        },
        innerBlocks: [
            [
                'core/accordion-item',
                { openByDefault: true },
                [
                    ['core/accordion-heading', { title: __('Sur place', 'dc26-oav') }],
                    [
                        'core/accordion-panel',
                        {},
                        [['core/paragraph', { placeholder: __('Contenu...', 'dc26-oav') }]],
                    ],
                ],
            ],
            [
                'core/accordion-item',
                {},
                [
                    ['core/accordion-heading', { title: __('Téléphone', 'dc26-oav') }],
                    [
                        'core/accordion-panel',
                        {},
                        [['core/paragraph', { placeholder: __('Contenu...', 'dc26-oav') }]],
                    ],
                ],
            ],
            [
                'core/accordion-item',
                {},
                [
                    ['core/accordion-heading', { title: __('En ligne', 'dc26-oav') }],
                    [
                        'core/accordion-panel',
                        {},
                        [['core/paragraph', { placeholder: __('Contenu...', 'dc26-oav') }]],
                    ],
                ],
            ],
        ],
    });
});

<?php

return [
    [
        'label' => 'Dashboard',
        'icon' => 'bx bx-home-alt',
        'route' => 'dashboard',
    ],

    [
        'label' => 'Comercial',
        'icon' => 'bx bx-briefcase-alt-2',
        'children' => [
            [
                'label' => 'Clientes',
                'icon' => 'bx bx-user',
                'route' => 'customers.index',
                'permission' => 'customers.view',
                'active' => [
                    'customers.index',
                    'customers.create',
                    'customers.show',
                    'customers.edit',
                ],
            ],
            [
                'label' => 'Orçamentos',
                'icon' => 'bx bx-file',
                'route' => 'budgets.index',
                'permission' => 'budgets.view',
                'active' => [
                    'budgets.index',
                    'budgets.create',
                    'budgets.show',
                    'budgets.edit',
                    'budgets.pdf',
                ],
            ],
        ],
    ],

    [
        'label' => 'Operação',
        'icon' => 'bx bx-building-house',
        'children' => [
            [
                'label' => 'Obras',
                'icon' => 'bx bx-hard-hat',
                'route' => 'jobs.index',
                'permission' => 'jobs.view',
                'active' => [
                    'jobs.index',
                ],
            ],
            [
                'label' => 'Stock',
                'icon' => 'bx bx-package',
                'route' => 'stock.index',
                'permission' => 'stock.view',
                'active' => [
                    'stock.index',
                ],
            ],
        ],
    ],

    [
        'label' => 'Catálogo',
        'icon' => 'bx bx-box',
        'children' => [
            [
                'label' => 'Artigos',
                'icon' => 'bx bx-cube',
                'route' => 'items.index',
                'permission' => 'items.view',
                'active' => [
                    'items.index',
                    'items.create',
                    'items.show',
                    'items.edit',
                ],
            ],
            [
                'label' => 'Famílias de Artigos',
                'icon' => 'bx bx-category',
                'route' => 'item-families.index',
                'permission' => 'settings.manage',
                'active' => [
                    'item-families.index',
                    'item-families.create',
                    'item-families.edit',
                ],
            ],
            [
                'label' => 'Marcas',
                'icon' => 'bx bx-purchase-tag',
                'route' => 'brands.index',
                'permission' => 'settings.manage',
                'active' => [
                    'brands.index',
                    'brands.create',
                    'brands.edit',
                ],
            ],
            [
                'label' => 'Unidades',
                'icon' => 'bx bx-ruler',
                'route' => 'units.index',
                'permission' => 'settings.manage',
                'active' => [
                    'units.index',
                    'units.create',
                    'units.edit',
                ],
            ],
            [
                'label' => 'Taxas de IVA',
                'icon' => 'bx bx-receipt',
                'route' => 'tax-rates.index',
                'permission' => 'settings.manage',
                'active' => [
                    'tax-rates.index',
                    'tax-rates.create',
                    'tax-rates.edit',
                ],
            ],
            [
                'label' => 'Motivos de Isenção',
                'icon' => 'bx bx-shield-quarter',
                'route' => 'tax-exemption-reasons.index',
                'permission' => 'settings.manage',
                'active' => [
                    'tax-exemption-reasons.index',
                    'tax-exemption-reasons.create',
                    'tax-exemption-reasons.edit',
                ],
            ],
        ],
    ],

    [
        'label' => 'Sistema',
        'icon' => 'bx bx-cog',
        'children' => [
            [
                'label' => 'Utilizadores',
                'icon' => 'bx bx-group',
                'route' => 'users.index',
                'permission' => 'users.view',
                'active' => [
                    'users.index',
                ],
            ],
            [
                'label' => 'Dados da Empresa',
                'icon' => 'bx bx-buildings',
                'route' => 'company-profile.show',
                'permission' => 'settings.manage',
                'active' => [
                    'company-profile.show',
                    'company-profile.edit',
                ],
            ],
        ],
    ],
];

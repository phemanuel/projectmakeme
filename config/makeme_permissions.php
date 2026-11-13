<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PROJECT MAKEME MODULE PERMISSIONS
    |--------------------------------------------------------------------------
    |
    | These permissions appear in the Roles & Permissions
    | management interface.
    |
    */

    'modules' => [

        'makeme.dashboard' => [
            'label' => 'Overview',
            'description' => 'Access the administrative dashboard.',
            'actions' => [
                'view' => 'View Dashboard',
            ],
        ],

        'makeme.participants' => [
            'label' => 'Participants',
            'description' => 'Manage participants, imports and invitations.',
            'actions' => [
                'view' => 'View Participants',
                'create' => 'Create Participants',
                'edit' => 'Edit Participants',
                'delete' => 'Delete Participants',
                'import' => 'Import Participants',
                'export' => 'Export Participants',
                'invite' => 'Send Invitations',
            ],
        ],

        'makeme.products' => [
            'label' => 'Products & Stock',
            'description' => 'Manage products, inventory and stock levels.',
            'actions' => [
                'view' => 'View Products',
                'create' => 'Create Products',
                'edit' => 'Edit Products',
                'delete' => 'Archive Products',
                'stock.adjust' => 'Adjust Stock',
            ],
        ],

        'makeme.wallets' => [
            'label' => 'Wallets',
            'description' => 'Access wallets and financial information.',
            'actions' => [
                'view' => 'View Wallets',
                'adjust' => 'Adjust Wallet Balances',
            ],
        ],

        'makeme.orders' => [
            'label' => 'Orders',
            'description' => 'Manage customer orders.',
            'actions' => [
                'view' => 'View Orders',
                'create' => 'Create Orders',
                'edit' => 'Edit Orders',
                'update-status' => 'Update Order Status',
            ],
        ],

        'makeme.reports' => [
            'label' => 'Reports',
            'description' => 'View and export reports.',
            'actions' => [
                'view' => 'View Reports',
                'export' => 'Export Reports',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | PROTECTED ADMINISTRATIVE PERMISSIONS
    |--------------------------------------------------------------------------
    |
    | These permissions belong exclusively to Super Admin.
    | They are not exposed in the editable permissions matrix.
    |
    */

    'protected_modules' => [

        'admin.users' => [
            'view',
            'create',
            'edit',
            'delete',
        ],

        'admin.roles' => [
            'view',
            'create',
            'edit',
            'delete',
        ],

    ],

];

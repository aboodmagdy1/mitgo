<?php

return [

    'actions' => [

        'delete' => [
            'label' => 'Delete',
        ],

        'edit' => [
            'label' => 'Edit',
        ],

        'view' => [
            'label' => 'View',
        ],

        'create' => [
            'label' => 'Create',
        ],

        'duplicate' => [
            'label' => 'Duplicate',
        ],

        'restore' => [
            'label' => 'Restore',
        ],

        'force_delete' => [
            'label' => 'Force Delete',
        ],

    ],

    'bulk_actions' => [

        'delete' => [
            'label' => 'Delete Selected',
        ],

        'restore' => [
            'label' => 'Restore Selected',
        ],

        'force_delete' => [
            'label' => 'Force Delete Selected',
        ],

    ],

    'empty_state' => [
        'heading' => 'No records found',
        'description' => 'Start by creating a new record.',
    ],

    'filters' => [
        'label' => 'Filters',
        'actions' => [
            'apply' => 'Apply',
            'reset' => 'Reset',
        ],
    ],

    'search' => [
        'label' => 'Search',
        'placeholder' => 'Search records...',
    ],

    'selection_indicator' => [
        'selected_count' => '{1} record selected|[2,*] :count records selected',
        'actions' => [
            'select_all' => 'Select all',
            'deselect_all' => 'Deselect all',
        ],
    ],

];

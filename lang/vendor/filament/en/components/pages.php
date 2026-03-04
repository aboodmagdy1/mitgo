<?php

return [

    'actions' => [
        'create' => [
            'label' => 'Create',
            'modal' => [
                'heading' => 'Create',
                'actions' => [
                    'create' => [
                        'label' => 'Create',
                    ],
                ],
            ],
        ],
    ],

    'pages' => [
        'index' => [
            'title' => 'Records',
        ],
        'create' => [
            'title' => 'Create',
        ],
        'edit' => [
            'title' => 'Edit',
        ],
        'view' => [
            'title' => 'View',
        ],
    ],

    'messages' => [
        'created' => 'Record created successfully',
        'updated' => 'Record updated successfully',
        'deleted' => 'Record deleted successfully',
        'restored' => 'Record restored successfully',
        'force_deleted' => 'Record force deleted successfully',
    ],

];

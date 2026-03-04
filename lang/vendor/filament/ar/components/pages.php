<?php

return [

    'actions' => [

        'create' => [
            'label' => 'إنشاء',
            'modal' => [
                'heading' => 'إنشاء :label',
                'actions' => [
                    'create' => 'إنشاء',
                    'create_and_create_another' => 'إنشاء وإنشاء آخر',
                ],
            ],
        ],

        'edit' => [
            'label' => 'تعديل',
            'modal' => [
                'heading' => 'تعديل :label',
                'actions' => [
                    'save' => 'حفظ',
                    'save_and_continue_editing' => 'حفظ والاستمرار في التعديل',
                ],
            ],
        ],

        'delete' => [
            'label' => 'حذف',
            'modal' => [
                'heading' => 'حذف :label',
                'description' => 'هل أنت متأكد من حذف هذا السجل؟ لا يمكن التراجع عن هذا الإجراء.',
                'actions' => [
                    'delete' => 'حذف',
                    'cancel' => 'إلغاء',
                ],
            ],
        ],

        'view' => [
            'label' => 'عرض',
        ],

    ],

    'messages' => [
        'created' => 'تم إنشاء :label بنجاح',
        'updated' => 'تم تحديث :label بنجاح',
        'deleted' => 'تم حذف :label بنجاح',
        'deleted_multiple' => 'تم حذف :count سجلات بنجاح',
        'restored' => 'تم استعادة :label بنجاح',
        'restored_multiple' => 'تم استعادة :count سجلات بنجاح',
        'error' => 'حدث خطأ أثناء تنفيذ العملية',
    ],

    'empty_state' => [
        'heading' => 'لا توجد :label',
        'description' => 'ابدأ بإنشاء :label جديد.',
        'actions' => [
            'create' => 'إنشاء :label',
        ],
    ],

];

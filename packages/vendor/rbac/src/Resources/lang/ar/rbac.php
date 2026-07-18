<?php

return [
    'titles' => [
        'roles_permissions' => 'الأدوار والصلاحيات',
        'permissions' => 'الصلاحيات',
        'new_role' => 'دور جديد',
        'edit_role' => 'تعديل الدور',
    ],

    'breadcrumbs' => [
        'admin' => 'الإدارة',
        'roles_permissions' => 'الأدوار والصلاحيات',
        'permissions' => 'الصلاحيات',
        'new_role' => 'دور جديد',
        'edit_role' => 'تعديل',
    ],

    'headings' => [
        'roles_permissions' => 'الأدوار والصلاحيات',
        'permissions_available' => 'الصلاحيات المتاحة',
        'new_role' => 'دور جديد',
        'edit_role' => 'تعديل الدور',
        'quick_permissions_selection' => 'اختيار سريع للصلاحيات',
        'role_identity' => 'هوية الدور',
        'permissions_summary' => 'ملخص الصلاحيات',
        'information' => 'المعلومات',
        'quick_actions' => 'إجراءات',
    ],

    'subtitles' => [
        'roles_index' => 'حدّد أدوار فريقك وحقوق وصولها.',
        'permissions_index' => 'مرجع لجميع صلاحيات النظام، مُنظَّمة حسب الوحدة.',
        'new_role' => 'حدّد دورًا وحقوق وصوله.',
        'instant_sync' => 'عدّل الصلاحيات مباشرة هنا، ويتم حفظها فورًا.',
        'role_active_help' => 'يمكن للأعضاء الذين يملكون هذا الدور تسجيل الدخول.',
        'system_role_warning' => 'هذا دور نظامي. يمكن تعديل الصلاحيات فقط.',
    ],

    'stats' => [
        'total_roles' => 'إجمالي الأدوار',
        'custom_roles' => 'الأدوار المخصّصة',
        'total_permissions' => 'الصلاحيات المتاحة',
        'members_without_role' => 'الأعضاء بدون دور',
    ],

    'table' => [
        'roles' => 'الأدوار',
        'role' => 'الدور',
        'description' => 'الوصف',
        'permissions' => 'الصلاحيات',
        'members' => 'الأعضاء',
        'type' => 'النوع',
        'actions' => 'إجراءات',
        'display' => 'عرض من :from إلى :to من أصل :total دور',
    ],

    'filters' => [
        'search_role' => 'البحث عن دور...',
        'search_permission' => 'البحث عن صلاحية...',
    ],

    'buttons' => [
        'view_permissions' => 'عرض الصلاحيات',
        'view_roles' => 'عرض الأدوار',
        'new_role' => 'دور جديد',
        'back' => 'رجوع',
        'select_all' => 'تحديد الكل',
        'deselect_all' => 'إلغاء تحديد الكل',
        'enable_all' => 'تفعيل الكل',
        'disable_all' => 'تعطيل الكل',
        'save_changes' => 'حفظ التغييرات',
        'save_permissions' => 'حفظ التغييرات',
        'create_role' => 'إنشاء الدور',
        'cancel' => 'إلغاء',
        'view' => 'عرض',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'see_all' => 'عرض الكل',
    ],

    'labels' => [
        'selected_permissions' => ':count صلاحية محددة',
        'enabled_permissions' => ':count / :total صلاحية مفعّلة',
        'slug_auto' => 'سيتم إنشاء المُعرّف تلقائيًا.',
        'internal_slug' => 'المُعرّف الداخلي',
        'role_name' => 'اسم الدور',
        'role_name_placeholder' => 'مثال: محاسب، مندوب مبيعات...',
        'description_placeholder' => 'صف مسؤوليات هذا الدور...',
        'identification_color' => 'لون التعريف',
        'custom_color' => 'لون مخصّص',
        'preview' => 'معاينة',
        'active_role' => 'دور مفعّل',
        'none_selected' => 'لم يتم تحديد أي صلاحية',
        'total' => 'الإجمالي :count صلاحية',
        'system' => 'نظامي',
        'custom' => 'مخصّص',
        'active' => 'مفعّل',
        'inactive' => 'غير مفعّل',
        'allowed' => 'مسموح',
        'denied' => 'مرفوض',
        'created_on' => 'أُنشئ في',
        'color' => 'اللون',
        'status' => 'الحالة',
        'type' => 'النوع',
        'members' => 'الأعضاء (:count)',
        'other_members' => '+ :count عضو آخر',
        'no_member' => 'لا يوجد عضو بهذا الدور',
        'no_role' => 'لا يوجد دور',
        'create_first_role' => 'أنشئ أول دور مخصّص لك.',
        'no_role_for_permission' => 'لا يوجد دور',
        'system_role_readonly' => 'دور نظامي غير قابل للتعديل',
    ],

    'badges' => [
        'system' => 'نظامي',
        'default' => 'افتراضي',
        'custom' => 'مخصّص',
    ],

    'messages' => [
        'role_created' => 'تم إنشاء الدور «:label» بنجاح.',
        'role_updated' => 'تم تحديث الدور.',
        'role_deleted' => 'تم حذف الدور.',
        'permissions_synced' => 'تمت مزامنة الصلاحيات.',
        'role_assigned' => 'تم تعيين الدور «:label» إلى :user.',
        'load_roles_failed' => 'تعذّر تحميل الأدوار.',
        'saved_permissions' => ':count صلاحية مفعّلة على هذا الدور.',
        'save_failed' => 'تعذّر الحفظ.',
        'validation_errors' => 'أخطاء في التحقق.',
    ],

    'confirmations' => [
        'delete_role_title' => 'حذف الدور «:label»؟',
        'delete_role_message' => 'ستتم إزالة هذا الدور من جميع الأعضاء الذين يملكونه.',
    ],

    'toasts' => [
        'error' => 'خطأ',
        'deleted' => 'تم الحذف',
        'role_created' => 'تم إنشاء الدور!',
        'role_updated' => 'تم تحديث الدور!',
        'permissions_saved' => 'تم حفظ الصلاحيات!',
    ],

    'errors' => [
        'assign_owner_forbidden' => 'يمكن لمالك المستأجر فقط تعيين دور المالك.',
        'unauthorized_role_access' => 'وصول غير مصرّح به إلى هذا الدور.',
        'system_role_locked' => 'لا يمكن تعديل الأدوار النظامية.',
        'system_role_delete_forbidden' => 'لا يمكن حذف دور نظامي.',
        'default_role_delete_forbidden' => 'يُعاد إنشاء هذا الدور الافتراضي تلقائيًا ولا يمكن حذفه. يمكنك تعديله أو تعطيله.',
        'role_assigned_users' => 'هذا الدور مُعيَّن لمستخدمين. أعد تعيينهم قبل الحذف.',
        'role_not_active_tenant' => 'الدور لا يطابق المستأجر النشط.',
        'role_not_found_tenant' => 'تعذّر العثور على الدور المحدد لهذا المستأجر.',
    ],

    'validation' => [
        'label_required' => 'اسم الدور مطلوب.',
        'label_max' => 'لا يمكن أن يتجاوز الاسم 100 حرف.',
        'color_regex' => 'يجب أن يكون اللون رمزًا سداسيًا عشريًا صحيحًا (مثال: ‎#2563eb).',
        'permission_exists' => 'إحدى الصلاحيات المحددة غير صحيحة.',
    ],
];

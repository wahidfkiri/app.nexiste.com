<?php

return [
    'common' => [
        'success' => 'نجاح',
        'error' => 'خطأ',
        'validation' => 'التحقق',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'حالة OAuth لا تطابق الجلسة الحالية.',
        'extension_inactive' => 'إضافة Google Drive غير مفعّلة لهذا المستأجر. فعّلها أولاً من المتجر.',
        'storage_missing' => 'جداول Google Drive غير موجودة. نفّذ الترحيلات: php artisan migrate',
        'client_id_missing' => 'GOOGLE_DRIVE_CLIENT_ID مفقود.',
        'invalid_oauth_state' => 'حالة OAuth غير صحيحة.',
        'session_expired' => 'انتهت جلسة Google Drive أو أُلغيت. أعد الاتصال بحساب Google الخاص بك.',
        'list_files' => 'تعذّر سرد الملفات: :message',
        'file_type_not_allowed' => 'نوع الملف غير مسموح به: :mime',
        'file_too_large' => 'الملف كبير جدًا.',
        'not_connected' => 'Google Drive غير متصل لهذا المستأجر.',
    ],

    'success' => [
        'connected' => 'تم الاتصال بـ Google Drive بنجاح.',
        'disconnected' => 'تم قطع الاتصال بـ Google Drive.',
        'folder_created' => 'تم إنشاء المجلد بنجاح.',
        'file_uploaded' => 'تم رفع الملف بنجاح.',
        'file_renamed' => 'تمت إعادة تسمية الملف.',
        'file_moved' => 'تم نقل الملف.',
        'file_copied' => 'تم نسخ الملف.',
        'file_deleted' => 'تم حذف الملف.',
        'file_restored' => 'تمت استعادة الملف.',
        'trash_emptied' => 'تم إفراغ سلة المهملات.',
        'file_shared' => 'تمت مشاركة الملف.',
    ],

    'validation' => [
        'folder_name_required' => 'اسم المجلد مطلوب.',
        'folder_name_string' => 'يجب أن يكون اسم المجلد نصًا.',
        'folder_name_max' => 'يجب ألا يتجاوز اسم المجلد 500 حرف.',
        'parent_id_string' => 'مرجع المجلد الأصل غير صحيح.',
        'parent_id_max' => 'مرجع المجلد الأصل طويل جدًا.',
        'file_required' => 'يُرجى اختيار ملف.',
        'file_invalid' => 'الملف المحدد غير صحيح.',
        'file_max' => 'يتجاوز الملف الحد المسموح به وهو 100 ميغابايت.',
    ],
];

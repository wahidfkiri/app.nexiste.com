<?php

return [
    'common' => [
        'success' => 'نجاح',
        'error' => 'خطأ',
        'validation' => 'التحقق',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'حالة OAuth غير صحيحة للجلسة الحالية.',
        'extension_inactive' => 'Google Docs غير مفعّل لهذا المستأجر. فعّل التطبيق من المتجر.',
        'storage_missing' => 'جداول Google Docs غير موجودة. نفّذ: php artisan migrate',
        'client_id_missing' => 'GOOGLE_DOCX_CLIENT_ID مفقود.',
        'invalid_oauth_state' => 'حالة OAuth غير صحيحة.',
        'session_expired' => 'انتهت جلسة Google Docs أو أُلغيت. أعد الاتصال بحساب Google الخاص بك.',
        'not_connected' => 'Google Docs غير متصل لهذا المستأجر.',
        'document_id_missing' => 'معرّف Google Docs مفقود.',
        'document_id_invalid' => 'معرّف Google Docs غير صحيح.',
        'document_url_invalid' => 'رابط Google Docs غير صحيح. استخدم رابط المستند أو معرّفه.',
        'document_not_found' => 'تعذّر العثور على مستند Google Docs',
        'permission_denied' => 'تم رفض الوصول إلى مستند Google Docs. شارك المستند مع الحساب المتصل ثم حاول مرة أخرى.',
        'unexpected' => 'خطأ غير متوقع في Google Docs.',
    ],

    'success' => [
        'connected' => 'تم الاتصال بـ Google Docs بنجاح.',
        'disconnected' => 'تم قطع الاتصال بـ Google Docs.',
        'document_created' => 'تم إنشاء المستند بنجاح.',
        'document_renamed' => 'تمت إعادة تسمية المستند.',
        'document_duplicated' => 'تم تكرار المستند.',
        'document_deleted' => 'تم حذف المستند.',
        'text_appended' => 'تمت إضافة النص إلى المستند.',
        'replace_done' => 'اكتمل الاستبدال.',
    ],

    'validation' => [
        'title_required' => 'العنوان مطلوب.',
        'title_string' => 'يجب أن يكون العنوان نصًا.',
        'title_max' => 'يجب ألا يتجاوز العنوان 500 حرف.',
        'content_string' => 'المحتوى غير صحيح.',
        'content_max' => 'المحتوى طويل جدًا.',
        'text_required' => 'النص المراد إضافته مطلوب.',
        'text_string' => 'النص المراد إضافته غير صحيح.',
        'text_max' => 'النص المراد إضافته طويل جدًا.',
        'search_required' => 'النص المراد البحث عنه مطلوب.',
        'search_string' => 'النص المراد البحث عنه غير صحيح.',
        'search_max' => 'النص المراد البحث عنه طويل جدًا.',
        'replace_string' => 'نص الاستبدال غير صحيح.',
        'replace_max' => 'نص الاستبدال طويل جدًا.',
        'format_in' => 'صيغة التصدير غير صحيحة.',
    ],
];

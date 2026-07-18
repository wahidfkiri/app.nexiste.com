<?php

return [
    'success' => [
        'room_created' => 'تم إنشاء الغرفة بنجاح.',
        'room_updated' => 'تم تحديث الغرفة.',
        'room_archived' => 'تمت أرشفة الغرفة.',
        'message_sent' => 'تم إرسال الرسالة.',
        'message_deleted' => 'تم حذف الرسالة.',
    ],

    'errors' => [
        'storage_missing' => 'جداول روبوت الدردشة غير موجودة. نفّذ: php artisan migrate',
        'room_name_required' => 'اسم الغرفة مطلوب.',
        'room_name_exists' => 'توجد غرفة بهذا الاسم بالفعل.',
        'default_room_delete_forbidden' => 'لا يمكن حذف الغرفة الافتراضية.',
        'room_select' => 'اختر غرفة.',
        'room_required' => 'الغرفة مطلوبة.',
        'message_empty' => 'الرسالة فارغة.',
        'message_delete_forbidden' => 'لا يحق لك حذف هذه الرسالة.',
        'room_not_found' => 'تعذّر العثور على الغرفة.',
        'room_access_denied' => 'تم رفض الوصول إلى هذه الغرفة.',
        'room_invalid' => 'غرفة غير صالحة.',
        'room_manage_forbidden' => 'ليس لديك صلاحية تعديل هذه الغرفة.',
    ],

    'validation' => [
        'room_name_required' => 'اسم الغرفة مطلوب.',
        'room_name_max' => 'اسم الغرفة طويل جدًا.',
        'icon_regex' => 'صيغة الأيقونة غير صحيحة.',
        'color_regex' => 'لون الغرفة غير صحيح.',
        'member_exists' => 'أحد الأعضاء المحددين غير صحيح.',
        'message_empty_with_file_hint' => 'الرسالة فارغة. أضف نصًا أو ملفًا.',
        'room_required' => 'الغرفة مطلوبة.',
        'room_exists' => 'الغرفة المحددة غير صحيحة.',
        'text_max' => 'الرسالة طويلة جدًا.',
        'file_invalid' => 'الملف المرفوع غير صحيح.',
        'files_max' => 'يمكنك إرسال ما يصل إلى 6 ملفات لكل رسالة.',
        'file_size_max' => 'يتجاوز الملف الحد الأقصى المسموح به للحجم.',
        'file_mime' => 'نوع الملف غير مسموح به.',
        'file_extension' => 'امتداد الملف غير مسموح به.',
    ],

    'defaults' => [
        'general_description' => 'الغرفة العامة لشركتك.',
        'user' => 'مستخدم',
        'file' => 'ملف',
    ],
];

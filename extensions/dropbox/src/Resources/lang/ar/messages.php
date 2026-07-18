<?php

return [
    'common' => [
        'success' => 'نجاح',
        'error' => 'خطأ',
        'validation' => 'التحقق',
    ],

    'errors' => [
        'oauth_session_mismatch' => 'جلسة OAuth الخاصة بـ Dropbox لا تطابق جلستك الحالية.',
        'extension_inactive' => 'إضافة Dropbox غير مفعّلة لهذا المستأجر. فعّلها من المتجر.',
        'storage_missing' => 'جداول Dropbox غير موجودة. نفّذ: php artisan migrate',
        'client_id_missing' => 'DROPBOX_CLIENT_ID مفقود.',
        'invalid_oauth_state' => 'حالة OAuth الخاصة بـ Dropbox غير صحيحة.',
        'missing_access_token' => 'لم يُرجع Dropbox أي رمز وصول.',
        'file_type_not_allowed' => 'نوع الملف غير مسموح به: :mime',
        'file_too_large' => 'الملف كبير جدًا.',
        'trash_file_not_found' => 'تعذّر العثور على ملف Dropbox في سلة المهملات.',
        'trash_revision_missing' => 'مراجعة Dropbox مفقودة لاستعادة هذا الملف.',
        'download_failed' => 'تعذّر تنزيل ملف Dropbox هذا.',
        'not_connected' => 'Dropbox غير متصل لهذا المستأجر.',
        'refresh_token_missing' => 'يتطلب Dropbox إعادة اتصال: رمز التحديث مفقود.',
        'session_expired' => 'انتهت جلسة Dropbox أو أُلغيت. أعد الاتصال بـ Dropbox.',
        'refresh_failed' => 'تعذّر تحديث رمز Dropbox.',
        'auth_finalize_failed' => 'تعذّر إتمام مصادقة Dropbox.',
        'resolve_path_failed' => 'تعذّر تحديد مسار الملف في Dropbox.',
        'invalid_name' => 'اسم Dropbox غير صحيح.',
    ],

    'success' => [
        'connected' => 'أصبح Dropbox متصلاً الآن بمساحتك.',
        'disconnected' => 'تم قطع الاتصال بـ Dropbox.',
        'folder_created' => 'تم إنشاء مجلد Dropbox بنجاح.',
        'files_uploaded' => 'تم رفع الملفات إلى Dropbox بنجاح.',
        'file_uploaded' => 'تم رفع الملف إلى Dropbox بنجاح.',
        'item_renamed' => 'تمت إعادة تسمية العنصر.',
        'item_moved' => 'تم نقل العنصر.',
        'item_copied' => 'تم نسخ العنصر.',
        'item_deleted' => 'تم حذف العنصر.',
        'item_restored' => 'تمت استعادة العنصر.',
        'trash_emptied' => 'تم إفراغ سلة مهملات Dropbox.',
        'share_link_created' => 'تم إنشاء رابط المشاركة.',
    ],

    'validation' => [
        'folder_name_required' => 'اسم المجلد مطلوب.',
        'folder_name_string' => 'يجب أن يكون اسم المجلد نصًا.',
        'folder_name_max' => 'يجب ألا يتجاوز اسم المجلد 255 حرفًا.',
        'parent_id_string' => 'مرجع المجلد الأصل غير صحيح.',
        'parent_id_max' => 'مرجع المجلد الأصل طويل جدًا.',
        'files_required' => 'يُرجى اختيار ملف واحد على الأقل.',
        'files_array' => 'صيغة الملفات المراد استيرادها غير صحيحة.',
        'file_required' => 'أحد الملفات المحددة غير صحيح.',
        'file_invalid' => 'أحد العناصر المحددة ليس ملفًا صحيحًا.',
        'file_max' => 'أحد الملفات يتجاوز الحد المسموح به وهو 100 ميغابايت.',
    ],
];

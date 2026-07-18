<?php

return [
    'breadcrumb' => [
        'applications' => 'التطبيقات',
    ],

    'common' => [
        'success' => 'نجاح',
        'error' => 'خطأ',
        'validation' => 'التحقق',
        'unknown' => 'غير معروف',
        'never' => 'أبدًا',
        'none' => 'لا شيء',
        'no_title' => '(بدون عنوان)',
        'no_data_title' => 'لا توجد بيانات',
        'dash' => '-',
    ],

    'page' => [
        'title' => 'Google Meet',
        'subtitle' => 'جدول وأدر اجتماعات Meet باستخدام Google OAuth.',
    ],

    'actions' => [
        'migration_required' => 'الترحيل مطلوب',
        'activate_marketplace' => 'التفعيل من المتجر',
        'sync' => 'مزامنة',
        'new_meeting' => 'اجتماع جديد',
        'disconnect' => 'قطع الاتصال',
        'connect_google_meet' => 'ربط Google Meet',
        'connect' => 'اتصال',
        'open_marketplace' => 'فتح المتجر',
        'open_app' => 'فتح التطبيق',
        'explore_apps' => 'استكشاف التطبيقات',
        'cancel' => 'إلغاء',
        'save' => 'حفظ',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'reset' => 'إعادة تعيين',
        'join_meet' => 'الانضمام إلى Meet',
    ],

    'storage' => [
        'title' => 'مطلوب ترحيل قاعدة البيانات',
        'description' => 'جداول Google Meet غير موجودة. نفّذ الترحيل قبل استخدام هذه الوحدة.',
        'command' => 'php artisan migrate',
    ],

    'extension' => [
        'title' => 'الإضافة غير مفعّلة',
        'description' => 'Google Meet متاح على المنصة لكنه لم يُفعّل بعد لهذا المستأجر. فعّل التطبيق من المتجر.',
    ],

    'connection' => [
        'title' => 'اتصال Google Meet',
        'description' => 'لم يربط هذا المستأجر Google Meet بعد. ابدأ مصادقة OAuth لمزامنة اجتماعاتك وإدارتها.',
    ],

    'stats' => [
        'calendars' => 'التقاويم',
        'today' => 'اليوم',
        'next_7_days' => 'الـ 7 أيام القادمة',
        'this_month' => 'هذا الشهر',
        'active_links' => 'الروابط النشطة',
    ],

    'account' => [
        'title' => 'الحساب المتصل',
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'connected_at' => 'متصل منذ',
        'last_sync' => 'آخر مزامنة',
    ],

    'calendars' => [
        'title' => 'التقاويم',
        'primary' => 'الرئيسي',
        'no_calendars_title' => 'لا يوجد تقويم',
        'no_calendars_desc' => 'ابدأ مزامنة بعد الاتصال.',
    ],

    'table' => [
        'meetings' => 'اجتماعات Meet',
        'count_results' => ':count نتيجة',
        'pagination_showing' => 'عرض من :from إلى :to من أصل :total اجتماع',
        'empty_filtered' => 'لم يتم العثور على اجتماعات لعوامل التصفية المحددة.',
    ],

    'columns' => [
        'meeting' => 'الاجتماع',
        'calendar' => 'التقويم',
        'start' => 'البداية',
        'end' => 'النهاية',
        'status' => 'الحالة',
        'actions' => 'إجراءات',
    ],

    'filters' => [
        'search' => 'البحث في العنوان، الوصف، المنظّم...',
        'from' => 'من',
        'to' => 'إلى',
    ],

    'modal' => [
        'create_meeting' => 'اجتماع جديد',
        'edit_meeting' => 'تعديل الاجتماع',
        'subtitle' => 'يتم حفظ البيانات في Google Calendar مع رابط Meet.',
    ],

    'form' => [
        'title' => 'العنوان',
        'start' => 'البداية',
        'end' => 'النهاية',
        'location' => 'المكان',
        'location_placeholder' => 'مكتب، مكالمة فيديو، إلخ.',
        'visibility' => 'الظهور',
        'notifications' => 'الإشعارات',
        'attendees' => 'الحضور (`,` أو مفتاح Tab للتأكيد)',
        'attendees_placeholder' => 'أضف بريد حضور إلكتروني...',
        'auto_meet_link' => 'إنشاء رابط Google Meet تلقائيًا',
        'description' => 'الوصف',
    ],

    'visibility' => [
        'default' => 'افتراضي',
        'public' => 'عام',
        'private' => 'خاص',
        'confidential' => 'سري',
    ],

    'notifications' => [
        'all' => 'الكل',
        'external_only' => 'الخارجيون',
        'none' => 'لا شيء',
    ],

    'badges' => [
        'meet_link' => 'رابط Meet',
        'no_link' => 'بدون رابط',
    ],

    'tooltips' => [
        'join_meet' => 'الانضمام إلى Meet',
        'open_calendar_module' => 'الفتح في وحدة Google Calendar لدينا',
        'install_calendar' => 'تثبيت Google Calendar من المتجر',
    ],

    'status' => [
        'confirmed' => 'مؤكّد',
        'tentative' => 'مبدئي',
        'cancelled' => 'ملغى',
        'unknown' => 'غير معروف',
    ],

    'confirm' => [
        'disconnect_title' => 'قطع الاتصال بـ Google Meet؟',
        'disconnect_message' => 'سيتم حذف رموز OAuth لهذا المستأجر.',
        'disconnect_button' => 'قطع الاتصال',
        'delete_title' => 'حذف هذا الاجتماع؟',
        'delete_message' => 'سيتم حذف الاجتماع «:title» من Google Calendar.',
        'delete_button' => 'حذف',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'حالة OAuth لا تطابق الجلسة الحالية.',
        'extension_inactive' => 'Google Meet غير مفعّل لهذا المستأجر. فعّله من المتجر.',
        'storage_missing' => 'جداول Google Meet غير موجودة. نفّذ: php artisan migrate',
        'client_id_missing' => 'GOOGLE_MEET_CLIENT_ID مفقود.',
        'invalid_oauth_state' => 'حالة OAuth غير صحيحة.',
        'not_connected' => 'Google Meet غير متصل لهذا المستأجر.',
        'session_expired' => 'انتهت جلسة Google Meet أو أُلغيت. أعد الاتصال بحساب Google الخاص بك.',
        'calendar_missing' => 'التقويم المحدد غير موجود لهذا المستأجر.',
        'no_calendar_selected' => 'لم يتم اختيار أي تقويم.',
        'end_after_start' => 'يجب أن يكون تاريخ نهاية الاجتماع بعد تاريخ البداية.',
        'event_id_missing' => 'معرّف حدث Google Meet مفقود.',
        'google_session_invalid' => 'جلسة Google غير صحيحة أو منتهية. أعد الاتصال بـ Google Meet.',
        'google_event_not_found' => 'تعذّر العثور على الاجتماع في Google Calendar',
        'google_permission_denied' => 'رفض Google الطلب. تحقق من نطاقات OAuth وصلاحيات الحساب.',
        'google_access_blocked' => 'تم حظر وصول Google. تحقق من إعدادات OAuth وروابط إعادة التوجيه.',
        'unexpected' => 'خطأ غير متوقع في Google Meet.',
        'load_calendars' => 'تعذّر تحميل التقاويم.',
        'select_calendar' => 'تعذّر اختيار هذا التقويم.',
        'load_meetings' => 'تعذّر تحميل الاجتماعات.',
        'sync' => 'فشلت المزامنة.',
        'disconnect' => 'تعذّر قطع الاتصال بـ Google Meet.',
        'delete' => 'تعذّر حذف الاجتماع.',
        'save' => 'تعذّر حفظ الاجتماع.',
        'validation' => 'يُرجى تصحيح أخطاء النموذج.',
        'invalid_email_title' => 'بريد إلكتروني غير صحيح',
        'invalid_email_message' => '«:email» ليس بريدًا إلكترونيًا صحيحًا.',
    ],

    'success' => [
        'connected' => 'تم الاتصال بـ Google Meet بنجاح.',
        'disconnected' => 'تم قطع الاتصال بـ Google Meet.',
        'calendar_selected' => 'تم اختيار التقويم بنجاح.',
        'calendar_selected_short' => 'تم اختيار التقويم.',
        'sync_count' => 'تمت مزامنة :count اجتماع.',
        'sync' => 'اكتملت المزامنة.',
        'meeting_created' => 'تم إنشاء اجتماع Google Meet بنجاح.',
        'meeting_updated' => 'تم تحديث الاجتماع بنجاح.',
        'meeting_deleted' => 'تم حذف الاجتماع.',
        'disconnected_title' => 'تم قطع الاتصال',
        'disconnected_message' => 'تم قطع الاتصال بـ Google Meet.',
        'deleted_title' => 'تم الحذف',
        'deleted_message' => 'تم حذف الاجتماع.',
        'saved' => 'تم حفظ الاجتماع.',
    ],

    'validation' => [
        'calendar_required' => 'يُرجى اختيار تقويم.',
        'calendar' => 'يُرجى اختيار تقويم.',
        'summary_required' => 'عنوان الاجتماع مطلوب.',
        'summary_min' => 'يجب أن يحتوي العنوان على حرفين على الأقل.',
        'title_required' => 'العنوان مطلوب.',
        'start_required' => 'تاريخ البداية مطلوب.',
        'end_required' => 'تاريخ النهاية مطلوب.',
        'end_after' => 'يجب أن يكون تاريخ النهاية بعد تاريخ البداية.',
        'end_after_start' => 'يجب أن يكون تاريخ النهاية بعد تاريخ البداية.',
        'send_updates_in' => 'قيمة الإشعار غير صحيحة.',
        'attendees_invalid' => 'واحد أو أكثر من بريد الحضور الإلكتروني غير صحيح.',
    ],
];

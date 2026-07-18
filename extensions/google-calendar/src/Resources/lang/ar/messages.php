<?php

return [
    'breadcrumb' => [
        'applications' => 'التطبيقات',
    ],

    'common' => [
        'success' => 'نجاح',
        'error' => 'خطأ',
        'validation' => 'التحقق',
        'none' => 'لا شيء',
        'no_title' => '(بدون عنوان)',
        'no_data_title' => 'لا توجد بيانات',
        'no_data_message' => 'لا توجد بيانات متاحة.',
        'all_day' => 'طوال اليوم',
        'no_events' => 'لا توجد أحداث',
        'more' => 'إضافي',
    ],

    'page' => [
        'title' => 'Google Calendar',
        'subtitle' => 'زامن تقاويمك وأدر أحداث المستأجر باستخدام Google OAuth.',
    ],

    'actions' => [
        'migration_required' => 'الترحيل مطلوب',
        'activate_marketplace' => 'التفعيل من المتجر',
        'sync' => 'مزامنة',
        'new_event' => 'حدث جديد',
        'disconnect' => 'قطع الاتصال',
        'connect_google' => 'ربط Google Calendar',
        'cancel' => 'إلغاء',
        'close' => 'إغلاق',
        'save_event' => 'حفظ',
        'open_google' => 'فتح في Google',
        'edit' => 'تعديل',
        'delete' => 'حذف',
    ],

    'storage' => [
        'title' => 'مطلوب ترحيل قاعدة البيانات',
        'description' => 'جداول Google Calendar غير موجودة. نفّذ الترحيل قبل استخدام هذه الوحدة.',
        'command' => 'php artisan migrate',
    ],

    'extension' => [
        'title' => 'الإضافة غير مفعّلة',
        'description' => 'تم تثبيت Google Calendar على المنصة لكنه لم يُفعّل بعد لهذا المستأجر. فعّله من المتجر لاستخدام OAuth ومزامنة الأحداث.',
        'open_app_page' => 'فتح صفحة التطبيق',
        'browse_apps' => 'تصفّح التطبيقات',
    ],

    'connection' => [
        'title' => 'اتصال Google Calendar',
        'description' => 'لم يربط هذا المستأجر Google Calendar بعد. ابدأ مصادقة OAuth لتفعيل المزامنة واختيار التقويم والإدارة الكاملة للأحداث.',
        'connect_now' => 'اتصل الآن',
        'open_marketplace' => 'فتح المتجر',
        'oauth_cancelled' => 'تم إلغاء مصادقة Google Calendar أو رفضها.',
    ],

    'stats' => [
        'calendars' => 'التقاويم',
        'events_today' => 'أحداث اليوم',
        'this_month' => 'هذا الشهر',
        'next_30_days' => 'الـ 30 يومًا القادمة',
        'holidays_year' => 'العطلات (السنة)',
    ],

    'account' => [
        'title' => 'الحساب المتصل',
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'connected' => 'متصل',
        'last_sync' => 'آخر مزامنة',
        'unknown' => 'غير معروف',
        'never' => 'أبدًا',
    ],

    'calendars' => [
        'title' => 'التقاويم',
        'primary' => 'الرئيسي',
        'no_calendars_title' => 'لا يوجد تقويم',
        'no_calendars_desc' => 'ابدأ مزامنة بعد ربط Google Calendar.',
    ],

    'table' => [
        'events' => 'الأحداث',
        'count_results' => ':count نتيجة',
        'pagination_showing' => 'عرض من :from إلى :to من أصل :total حدث',
        'empty_filtered' => 'لم يتم العثور على أحداث لعوامل التصفية المحددة.',
    ],

    'columns' => [
        'title' => 'العنوان',
        'calendar' => 'التقويم',
        'start' => 'البداية',
        'end' => 'النهاية',
        'status' => 'الحالة',
        'actions' => 'إجراءات',
    ],

    'filters' => [
        'search' => 'البحث في العنوان، الوصف، المكان...',
        'from' => 'من',
        'to' => 'إلى',
        'include_holidays' => 'تضمين العطلات',
        'reset' => 'إعادة تعيين',
    ],

    'views' => [
        'aria' => 'وضع عرض التقويم',
        'month' => 'شهر',
        'week' => 'أسبوع',
        'day' => 'يوم',
        'year' => 'سنة',
        'list' => 'قائمة',
    ],

    'period' => [
        'previous' => 'الفترة السابقة',
        'today' => 'اليوم',
        'next' => 'الفترة التالية',
    ],

    'modal' => [
        'create_event' => 'إنشاء حدث',
        'edit_event' => 'تعديل حدث',
        'subtitle' => 'يتم حفظ البيانات على Google Calendar ومزامنتها محليًا.',
        'detail_title' => 'تفاصيل الحدث',
        'detail_subtitle' => 'راجع المعلومات قبل التعديل أو الحذف.',
    ],

    'detail' => [
        'when' => 'متى',
        'location' => 'المكان',
        'client' => 'العميل',
        'source' => 'المصدر',
        'visibility' => 'الظهور',
        'updated_at' => 'التحديث',
        'attendees' => 'الحضور',
        'description' => 'الوصف',
        'empty' => 'غير محدد',
        'no_attendees' => 'لا يوجد حضور',
        'no_description' => 'لا يوجد وصف.',
        'client_optional' => 'العميل (اختياري)',
        'client_module_missing' => 'وحدة العملاء غير مثبّتة.',
        'install_client_module' => 'تثبيت وحدة العملاء',
    ],

    'form' => [
        'title' => 'العنوان',
        'start' => 'البداية',
        'end' => 'النهاية',
        'location' => 'المكان',
        'visibility' => 'الظهور',
        'reminder' => 'التذكير (دقيقة)',
        'reminder_placeholder' => '10',
        'attendees' => 'الحضور (بريد إلكتروني مفصول بفواصل)',
        'attendees_placeholder' => 'john@company.com, jane@company.com',
        'description' => 'الوصف',
    ],

    'visibility' => [
        'default' => 'افتراضي',
        'public' => 'عام',
        'private' => 'خاص',
        'confidential' => 'سري',
    ],

    'status' => [
        'confirmed' => 'مؤكّد',
        'tentative' => 'مبدئي',
        'cancelled' => 'ملغى',
        'unknown' => 'غير معروف',
    ],

    'badges' => [
        'holiday' => 'عطلة',
    ],

    'validation' => [
        'calendar' => 'يُرجى اختيار تقويم.',
        'title_required' => 'العنوان مطلوب.',
        'start_required' => 'تاريخ البداية مطلوب.',
        'end_required' => 'تاريخ النهاية مطلوب.',
        'end_after_start' => 'يجب أن يكون تاريخ النهاية بعد تاريخ البداية.',
        'attendees' => 'واحد أو أكثر من بريد الحضور الإلكتروني غير صحيح.',
        'source_type' => 'نوع المصدر غير صحيح.',
    ],

    'errors' => [
        'load_calendars' => 'تعذّر تحميل التقاويم.',
        'select_calendar' => 'تعذّر اختيار هذا التقويم.',
        'load_events' => 'تعذّر تحميل الأحداث.',
        'sync' => 'فشلت المزامنة.',
        'disconnect' => 'تعذّر قطع الاتصال بـ Google Calendar.',
        'delete' => 'تعذّر حذف هذا الحدث.',
        'save' => 'تعذّر حفظ هذا الحدث.',
        'validation' => 'يُرجى تصحيح أخطاء النموذج.',
        'client_id_missing' => 'GOOGLE_CALENDAR_CLIENT_ID مفقود.',
        'invalid_oauth_state' => 'حالة OAuth غير صحيحة.',
        'oauth_credentials_missing' => 'بيانات اعتماد OAuth الخاصة بـ Google Calendar مفقودة.',
        'oauth_code_exchange' => 'تعذّر تبادل رمز التفويض: :message',
        'not_connected' => 'Google Calendar غير متصل لهذا المستأجر.',
        'calendar_missing' => 'التقويم المحدد غير موجود لهذا المستأجر.',
        'no_calendar_selected' => 'لم يتم اختيار أي تقويم.',
        'no_google_calendar_available' => 'لا يوجد تقويم Google متاح. افتح Google Calendar في CRM وزامن تقاويمك.',
        'refresh_token_missing' => 'رمز التحديث مفقود. أعد الاتصال بحساب Google الخاص بك.',
        'session_expired' => 'انتهت جلسة Google Calendar أو أُلغيت. أعد الاتصال بحساب Google الخاص بك.',
        'refresh_access_token' => 'تعذّر تحديث رمز الوصول: :details',
        'api' => 'خطأ في واجهة Google Calendar البرمجية: :message',
        'client_not_found' => 'تعذّر العثور على العميل لهذا المستأجر.',
        'google_event_id_missing' => 'معرّف حدث Google مفقود.',
        'storage_missing' => 'جداول Google Calendar غير موجودة. نفّذ: php artisan migrate',
        'extension_inactive' => 'Google Calendar غير مفعّل لهذا المستأجر. فعّله من المتجر.',
        'oauth_state_mismatch' => 'حالة OAuth لا تطابق الجلسة الحالية.',
    ],

    'success' => [
        'calendar_selected' => 'تم اختيار التقويم.',
        'sync' => 'اكتملت المزامنة.',
        'connected' => 'تم الاتصال بـ Google Calendar بنجاح.',
        'disconnected' => 'تم قطع الاتصال بـ Google Calendar.',
        'selected_calendar' => 'تم اختيار التقويم بنجاح.',
        'synced_count' => 'تمت مزامنة :count حدث.',
        'event_created' => 'تم إنشاء الحدث بنجاح.',
        'event_updated' => 'تم تحديث الحدث بنجاح.',
        'event_deleted' => 'تم حذف الحدث.',
        'disconnected_title' => 'تم قطع الاتصال',
        'disconnected_message' => 'تم قطع الاتصال بـ Google Calendar.',
        'deleted_title' => 'تم الحذف',
        'deleted_message' => 'تم حذف الحدث.',
        'saved' => 'تم حفظ الحدث.',
    ],

    'confirm' => [
        'disconnect_title' => 'قطع الاتصال بـ Google Calendar؟',
        'disconnect_message' => 'سيتم حذف رموز OAuth لهذا المستأجر.',
        'disconnect_button' => 'قطع الاتصال',
        'delete_title' => 'حذف هذا الحدث؟',
        'delete_message' => 'سيتم حذف الحدث «:title» من Google Calendar.',
        'delete_button' => 'حذف',
    ],

    'mode' => [
        'no_events_title' => 'لا توجد أحداث',
        'no_events_message' => 'لم يتم العثور على أحداث في هذه الفترة.',
        'load_error_title' => 'خطأ في التحميل',
    ],
];

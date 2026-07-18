<?php

return [
    'page_title' => 'لوحة التحكم',
    'welcome' => 'مرحبًا، :name',
    'session_expired' => 'انتهت الجلسة',
    'access_denied' => 'ليس لديك صلاحية الوصول إلى هذه المساحة.',

    'meta' => [
        'title' => 'لوحة التحكم',
        'subtitle' => 'رؤية تنفيذية سريعة وقابلة للتنفيذ لنشاطك ووحداتك وتكاملاتك.',
        'tenant_fallback' => 'مساحة CRM',
        'member' => 'عضو',
    ],

    'actions' => [
        'fallback' => 'إجراء',
        'new_client' => 'عميل جديد',
        'new_invoice' => 'فاتورة جديدة',
        'projects' => 'المشاريع',
        'applications' => 'التطبيقات',
        'settings' => 'الإعدادات',
    ],

    'command' => [
        'modules' => 'الوحدات',
        'modules_active' => ':count مفعّلة',
        'currency' => 'العملة',
        'integrations' => 'التكاملات',
        'date' => 'التاريخ',
    ],

    'signals' => [
        'aria_label' => 'المؤشرات الرئيسية',
        'fallback_label' => 'مؤشر',
        'revenue_month' => 'إيرادات الشهر',
        'payments' => 'المقبوضات',
        'clients' => 'العملاء',
        'clients_hint' => '+:count هذا الشهر',
        'priorities' => 'الأولويات',
        'priorities_hint' => 'المهام والمخزون والفواتير المطلوب معالجتها',
        'integrations' => 'التكاملات',
        'integrations_hint' => ':count بحاجة لإعادة الاتصال',
    ],

    'finance' => [
        'kicker' => 'الأداء',
        'title' => 'مالية الشهر',
        'view_invoices' => 'عرض الفواتير',
        'issued_revenue' => 'الإيرادات المصدرة',
        'collected' => 'المحصّل',
        'pending' => 'المتبقي المستحق',
    ],

    'modules' => [
        'kicker' => 'مساحة العمل',
        'title' => 'الوحدات المفعّلة',
        'fallback_label' => 'وحدة',
        'empty_title' => 'لا توجد وحدة مرئية',
        'empty_description' => 'قم بتفعيل التطبيقات أو تحقق من صلاحيات الدور.',
        'clients' => [
            'name' => 'العملاء',
            'label' => 'CRM',
            'caption' => ':count جدد هذا الشهر',
        ],
        'invoice' => [
            'name' => 'الفوترة',
            'label' => 'المالية',
            'caption' => ':count فواتير مفتوحة',
        ],
        'projects' => [
            'name' => 'المشاريع',
            'label' => 'التسليم',
            'caption' => ':count مهام عاجلة',
        ],
        'stock' => [
            'name' => 'المخزون',
            'label' => 'العمليات',
            'caption' => ':count عناصر حرجة',
        ],
    ],

    'focus' => [
        'kicker' => 'للمعالجة',
        'title' => 'الأولويات التشغيلية',
        'fallback_kind' => 'أولوية',
        'fallback_title' => 'إجراء',
        'empty_title' => 'كل شيء هادئ',
        'empty_description' => 'لا توجد أولوية حرجة في الوقت الحالي.',
        'open_invoice' => 'فاتورة مفتوحة',
        'invoice_fallback' => 'فاتورة',
        'missing_client' => 'العميل غير محدد',
        'upcoming_task' => 'مهمة قريبة',
        'missing_project' => 'المشروع غير محدد',
        'critical_stock' => 'مخزون حرج',
        'article_fallback' => 'عنصر بدون اسم',
        'missing_sku' => 'بدون SKU',
    ],

    'activity' => [
        'kicker' => 'الجدول الزمني',
        'title' => 'النشاط الأخير',
        'fallback_event' => 'حدث',
        'empty_title' => 'لا يوجد نشاط',
        'empty_description' => 'ستُدرج أحداث المساحة هنا.',
        'client_created' => 'تمت إضافة عميل',
        'client_fallback' => 'عميل بدون اسم',
        'invoice_created' => 'تم إنشاء فاتورة',
        'invoice_fallback' => 'فاتورة',
        'project_updated' => 'تم تحديث المشروع',
        'project_update_fallback' => 'تحديث',
        'draft_resume' => 'مسودة للاستئناف',
        'draft_description' => ':type غير مكتمل',
        'app_active' => 'تطبيق مفعّل',
        'app_fallback' => 'تطبيق',
    ],

    'charts' => [
        'finance' => [
            'invoices' => 'الفواتير',
            'payments' => 'المقبوضات',
        ],
        'projects' => [
            'todo' => 'قيد الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'review' => 'قيد المراجعة',
            'done' => 'مكتملة',
        ],
        'stock' => [
            'critical' => 'حرج',
            'healthy' => 'سليم',
        ],
        'integrations' => [
            'connected' => 'متصلة',
            'attention' => 'بحاجة لإعادة الاتصال',
            'installed' => 'بحاجة للإعداد',
        ],
    ],

    'integrations' => [
        'states' => [
            'installed' => 'بحاجة للإعداد',
            'internal' => 'داخلي',
            'connected' => 'متصلة',
            'attention' => 'بحاجة لإعادة الاتصال',
        ],
        'resources' => [
            'pages' => 'صفحات',
            'boards' => 'لوحات',
            'files' => 'ملفات',
            'events' => 'أحداث',
            'spreadsheets' => 'جداول بيانات',
            'documents' => 'مستندات',
            'messages' => 'رسائل',
            'meetings' => 'اجتماعات',
        ],
        'names' => [
            'notion' => 'Notion',
            'trello' => 'Trello',
            'drive' => 'Drive',
            'dropbox' => 'Dropbox',
            'calendar' => 'Calendar',
            'sheets' => 'Sheets',
            'docs' => 'Docs',
            'gmail' => 'Gmail',
            'meet' => 'Meet',
            'slack' => 'Slack',
            'chatbot' => 'Chatbot',
        ],
    ],

    'trend' => [
        'stable' => 'مستقر هذا الشهر',
        'new_growth' => '+100% مقارنة بالشهر الماضي',
        'vs_previous' => ':percent% مقارنة بالشهر الماضي',
    ],
];

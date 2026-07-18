<?php

return [
    'success' => [
        'connected' => 'تم الاتصال بمساحة Notion بنجاح.',
        'disconnected' => 'تم قطع الاتصال بمساحة Notion.',
        'page_created' => 'تم إنشاء صفحة Notion بنجاح.',
        'link_created' => 'تم ربط صفحة Notion بـ CRM.',
        'link_updated' => 'تم تحديث رابط CRM.',
        'link_deleted' => 'تم حذف رابط CRM.',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'حالة OAuth غير صحيحة لهذه الجلسة.',
        'permission_insufficient' => 'صلاحية غير كافية: :permission',
        'storage_missing' => 'جداول مساحة Notion غير موجودة. نفّذ: php artisan migrate',
        'extension_inactive' => 'مساحة Notion غير مفعّلة لهذا المستأجر. فعّلها من المتجر.',
        'clients_module_missing' => 'وحدة العملاء غير متاحة.',
        'client_invalid' => 'عميل غير صحيح لهذا المستأجر.',
        'projects_module_missing' => 'وحدة المشاريع غير متاحة.',
        'project_invalid' => 'مشروع غير صحيح لهذا المستأجر.',
        'client_id_missing' => 'NOTION_WORKSPACE_CLIENT_ID مفقود.',
        'oauth_state_invalid' => 'حالة OAuth الخاصة بـ Notion غير صحيحة.',
        'not_connected' => 'مساحة Notion غير متصلة لهذا المستأجر.',
        'page_title_required' => 'عنوان صفحة Notion مطلوب.',
        'oauth_finalize_failed' => 'تعذّر إتمام الاتصال بـ Notion: :message',
        'session_expired' => 'انتهت جلسة Notion أو أُلغيت. أعد الاتصال بمساحة Notion الخاصة بك.',
        'session_refresh_failed' => 'تعذّر تحديث جلسة Notion: :message',
        'api_error' => 'واجهة Notion البرمجية: :message',
        'oauth_credentials_missing' => 'بيانات اعتماد OAuth الخاصة بـ Notion مفقودة.',
    ],

    'defaults' => [
        'workspace_name' => 'مساحة Notion',
        'untitled' => 'بدون عنوان',
        'child_page' => 'صفحة فرعية',
    ],
];

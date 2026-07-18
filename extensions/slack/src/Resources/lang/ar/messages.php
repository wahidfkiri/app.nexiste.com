<?php

return [
    'success' => [
        'connected' => 'تم الاتصال بـ Slack بنجاح.',
        'disconnected' => 'تم قطع الاتصال بـ Slack.',
        'channel_selected' => 'تم اختيار القناة.',
        'message_sent' => 'تم إرسال الرسالة.',
        'sync_done' => 'تمت مزامنة :channels قناة، واستيراد :messages رسالة.',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'حالة OAuth لا تطابق الجلسة الحالية.',
        'extension_inactive' => 'Slack غير مفعّل لهذا المستأجر. فعّله من المتجر.',
        'storage_missing' => 'جداول Slack غير موجودة. نفّذ: php artisan migrate',
        'client_id_missing' => 'SLACK_CLIENT_ID مفقود.',
        'oauth_state_invalid' => 'حالة OAuth غير صحيحة.',
        'oauth_state_expired' => 'انتهت صلاحية حالة OAuth. أعد بدء الاتصال بـ Slack.',
        'oauth_credentials_missing' => 'بيانات اعتماد OAuth الخاصة بـ Slack مفقودة.',
        'oauth_request_failed' => 'فشل طلب OAuth الخاص بـ Slack: HTTP :status',
        'oauth_exchange_failed' => 'فشل تبادل OAuth الخاص بـ Slack.',
        'bot_token_missing' => 'لم يُرجع OAuth رمز البوت الخاص بـ Slack.',
        'not_connected' => 'Slack غير متصل لهذا المستأجر.',
        'bot_token_missing_reconnect' => 'رمز البوت الخاص بـ Slack مفقود. أعد الاتصال بمساحة Slack الخاصة بك.',
        'channel_not_found' => 'قناة Slack المحددة غير موجودة.',
        'channel_not_selected' => 'لم يتم اختيار أي قناة Slack.',
        'channel_required' => 'قناة Slack مطلوبة.',
        'message_required' => 'نص الرسالة مطلوب.',
        'api_failed' => 'فشلت واجهة Slack البرمجية :endpoint: HTTP :status',
        'api_failed_generic' => 'فشلت واجهة Slack البرمجية :endpoint.',
        'redirect_uri_invalid_format' => 'يجب أن يكون SLACK_REDIRECT_URI رابط ويب http/https. تتطلب الروابط غير الويب PKCE وهي غير مدعومة هنا.',
        'redirect_uri_invalid' => 'رابط إعادة توجيه OAuth غير صحيح. استخدم رابط ويب http/https (مثال: http://127.0.0.1:8000/extensions/slack/oauth/callback).',
        'redirect_uri_localhost_bot_scopes' => 'يرفض Slack هذا الاتصال لأن التطبيق يستخدم localhost كرابط إعادة توجيه مع نطاقات البوت. منذ تغييرات PKCE في Slack، يُعامَل localhost كإعادة توجيه لسطح المكتب في هذه الحالة. استخدم رابط ويب غير localhost لـ SLACK_REDIRECT_URI (مثال: https://crm.test/extensions/slack/oauth/callback) وأضفه أيضًا إلى روابط إعادة التوجيه في تطبيق Slack الخاص بك، أو عطّل PKCE إذا كان يجب أن تبقى على localhost.',
        'client_id_google_detected' => 'يبدو أن SLACK_CLIENT_ID معرّف Google. استخدم معرّف العميل الخاص بتطبيق Slack الخاص بك (api.slack.com/apps).',
    ],

    'common' => [
        'me' => 'أنا',
        'bot' => 'بوت',
        'user' => 'مستخدم',
        'api_error_prefix' => 'خطأ واجهة Slack البرمجية:',
    ],
];

<?php

return [
    'title'    => 'إدارة الفريق',
    'subtitle' => 'إدارة الأعضاء والأدوار والصلاحيات',

    'fields' => [
        'name'           => 'الاسم الكامل',
        'email'          => 'البريد الإلكتروني',
        'phone'          => 'الهاتف',
        'role'           => 'الدور',
        'status'         => 'الحالة',
        'job_title'      => 'المسمى / الوظيفة',
        'department'     => 'القسم',
        'last_login'     => 'آخر تسجيل دخول',
        'created_at'     => 'عضو منذ',
        'invited_by'     => 'تمت دعوته بواسطة',
    ],

    'roles' => [
        'owner'   => 'المالك',
        'admin'   => 'مدير',
        'manager' => 'مسؤول',
        'user'    => 'مستخدم',
        'viewer'  => 'مشاهد',
    ],

    'statuses' => [
        'active'   => 'مفعّل',
        'inactive' => 'غير مفعّل',
        'invited'  => 'مدعو',
        'suspended'=> 'موقوف',
    ],

    'actions' => [
        'invite'   => 'إنشاء عضو',
        'edit'     => 'تعديل',
        'delete'   => 'حذف',
        'suspend'  => 'إيقاف',
        'activate' => 'تفعيل',
        'resend'   => 'إعادة إرسال الدعوة',
        'revoke'   => 'إلغاء',
        'export'   => 'تصدير',
    ],

    'messages' => [
        'invited'          => 'تم إرسال الدعوة بنجاح.',
        'updated'          => 'تم تحديث العضو بنجاح.',
        'deleted'          => 'تم حذف العضو بنجاح.',
        'suspended'        => 'تم إيقاف العضو.',
        'activated'        => 'تم تفعيل العضو.',
        'invitation_resent'=> 'تمت إعادة إرسال الدعوة.',
        'invitation_revoked'=> 'تم إلغاء الدعوة.',
        'cannot_delete_owner' => 'لا يمكن حذف المالك.',
        'cannot_delete_self'  => 'لا يمكنك حذف نفسك.',
    ],
];

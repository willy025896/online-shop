<?php

return [
    'title' => '通知',
    'empty' => '目前沒有通知。',
    'mark_all_read' => '全部標為已讀',
    'view_all' => '查看全部',
    'unread' => '未讀',
    'read' => '已讀',
    'all' => '全部',
    'just_now' => '剛剛',

    'order' => [
        'paid' => [
            'title' => '訂單已付款',
            'body' => '訂單 #:number 已完成付款。',
        ],
        'status_changed' => [
            'title' => '訂單狀態更新',
            'body' => '訂單 #:number 已變更為 :status。',
        ],
        'cancellation_requested' => [
            'title' => '取消請求',
            'body' => '買家請求取消訂單 #:number。',
        ],
        'cancellation_approved' => [
            'title' => '取消已核准',
            'body' => '您的訂單 #:number 取消請求已核准。',
        ],
        'cancellation_rejected' => [
            'title' => '取消已拒絕',
            'body' => '您的訂單 #:number 取消請求已被拒絕。',
        ],
        'cancelled_by_seller' => [
            'title' => '訂單已被賣家取消',
            'body' => '訂單 #:number 已被賣家取消。',
        ],
        'status' => [
            'pending' => '待處理',
            'paid' => '已付款',
            'processing' => '處理中',
            'shipped' => '已出貨',
            'completed' => '已完成',
            'cancelled' => '已取消',
        ],
    ],

    'shop' => [
        'approved' => [
            'title' => '賣場已通過審核',
            'body' => '您的賣場「:name」已通過審核。',
        ],
        'suspended' => [
            'title' => '賣場已被停權',
            'body' => '您的賣場「:name」已被停權。',
        ],
    ],
];

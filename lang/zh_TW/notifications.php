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
        'return_requested' => [
            'title' => '退貨申請',
            'body' => '買家申請退貨訂單 #:number。',
        ],
        'return_approved' => [
            'title' => '退貨已核准',
            'body' => '您的訂單 #:number 退貨申請已核准。',
        ],
        'return_rejected' => [
            'title' => '退貨已拒絕',
            'body' => '您的訂單 #:number 退貨申請已被拒絕。',
        ],
        'payout_completed' => [
            'title' => '撥款已完成',
            'body' => '已撥款 $:amount 給您。',
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

    'review' => [
        'cooling_started' => [
            'title' => '評價即將公開',
            'body' => '訂單 #:number 雙方已完成評價，評論將於 :time 公開。',
        ],
        'released' => [
            'title' => '評價已公開',
            'body' => '訂單 #:number 的評論已正式公開。',
        ],
        'cooling_reset' => [
            'title' => '對方修改評論，冷靜期已重置',
            'body' => '訂單 #:number 對方修改了評論，冷靜期已重置，您可以對應調整自己的評論。',
        ],
        'seller_replied' => [
            'title' => '賣家回覆了您的評論',
            'body' => '賣家已回覆您對「:product」的評論。',
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

    'message' => [
        'new' => [
            'title' => ':name 傳送了新訊息給您',
            'attachment' => '[附件]',
        ],
    ],
];

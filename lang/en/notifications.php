<?php

return [
    'title' => 'Notifications',
    'empty' => 'No notifications yet.',
    'mark_all_read' => 'Mark all as read',
    'view_all' => 'View all',
    'unread' => 'Unread',
    'read' => 'Read',
    'all' => 'All',
    'just_now' => 'just now',

    'order' => [
        'paid' => [
            'title' => 'Order paid',
            'body' => 'Order #:number has been paid.',
        ],
        'status_changed' => [
            'title' => 'Order status updated',
            'body' => 'Order #:number is now :status.',
        ],
        'cancellation_requested' => [
            'title' => 'Cancellation requested',
            'body' => 'Buyer requested to cancel order #:number.',
        ],
        'cancellation_approved' => [
            'title' => 'Cancellation approved',
            'body' => 'Your cancellation request for order #:number was approved.',
        ],
        'cancellation_rejected' => [
            'title' => 'Cancellation rejected',
            'body' => 'Your cancellation request for order #:number was rejected.',
        ],
        'cancelled_by_seller' => [
            'title' => 'Order cancelled by seller',
            'body' => 'Order #:number was cancelled by the seller.',
        ],
        'status' => [
            'pending' => 'pending',
            'paid' => 'paid',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ],
    ],

    'shop' => [
        'approved' => [
            'title' => 'Shop approved',
            'body' => 'Your shop ":name" has been approved.',
        ],
        'suspended' => [
            'title' => 'Shop suspended',
            'body' => 'Your shop ":name" has been suspended.',
        ],
    ],
];

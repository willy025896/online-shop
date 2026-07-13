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
        'return_requested' => [
            'title' => 'Return requested',
            'body' => 'Buyer requested a return for order #:number.',
        ],
        'return_approved' => [
            'title' => 'Return approved',
            'body' => 'Your return request for order #:number was approved. Refund amount: $:amount.',
        ],
        'return_rejected' => [
            'title' => 'Return rejected',
            'body' => 'Your return request for order #:number was rejected.',
        ],
        'payout_completed' => [
            'title' => 'Payout completed',
            'body' => 'You were paid out $:amount.',
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

    'review' => [
        'cooling_started' => [
            'title' => 'Review period closing soon',
            'body' => 'Both parties have submitted reviews for order #:number. Reviews will be published at :time.',
        ],
        'released' => [
            'title' => 'Review published',
            'body' => 'Reviews for order #:number are now public.',
        ],
        'cooling_reset' => [
            'title' => 'Review modified — cooling period reset',
            'body' => 'The other party modified their review for order #:number. The cooling period has been reset and you may revise your review accordingly.',
        ],
        'seller_replied' => [
            'title' => 'Seller replied to your review',
            'body' => 'The seller has replied to your review for ":product".',
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

    'message' => [
        'new' => [
            'title' => ':name sent you a new message',
            'attachment' => '[Attachment]',
        ],
    ],

    'wishlist' => [
        'price_drop' => [
            'title' => 'Price drop on a wishlisted item',
            'body' => '":product" dropped from $:old to $:new.',
        ],
        'back_in_stock' => [
            'title' => 'Back in stock',
            'body' => '":product" is back in stock.',
        ],
    ],

    'mail' => [
        'view_details' => 'View Details',
    ],
];

<?php
/**
 * 使用者相關
 */

 return [
    /**
     * common
     */
    'action' => [
        'save' => '儲存',
        'saved' => '已儲存',
    ],

    /**
     * confirm-password
     */
    'secureArea' => 'Secure Area',
    'confirmPasswordHint' => 'This is a secure area of the application. Please confirm your password before continuing.',
    'password' => 'Password',
    'confirm' => 'Confirm',

    /**
     * profile
     */
    'profile' => [
        /**
         * information
         */
        'information' => [
            'title' => 'Profile Information',
            'description' => 'Update your account\'s profile information and email address.',
            'photo' => [
                'title' => 'Photo',
                'new' => 'Select A New Photo',
                'remove' => 'Remove Photo'
            ],
            'name' => 'Name',
            'email' => [
                'title' => 'Email',
                'unverified' => 'Your email address is unverified.',
                'resend' => 'Click here to re-send the verification email.',
                'sent' => 'A new verification link has been sent to your email address.'
            ]
        ],

        /**
         * update password
         */
        'password' => [
            'title' => 'Update Password',
            'description' => 'Ensure your account is using a long, random password to stay secure.',
            'current' => 'Current Password',
            'new' => 'New Password',
            'confirm' => 'Confirm Password',
        ],

        /**
         * two-factor
         */
        'twoFactor' => [
            'title' => 'Two Factor Authentication',
            'description' => 'Add additional security to your account using two factor authentication.',
            'hint' => 'When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.',
            'enabled' => [
                'title' => 'You have enabled two factor authentication.',
                'hint' => 'Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application or enter the setup key.'
            ],
            'disabled' => [
                'title' => 'You have not enabled two factor authentication.'
            ],
            'enabling' => [
                'title' => 'Finish enabling two factor authentication.',
                'hint' => 'To finish enabling two factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.',
                'code' => 'Code'
            ],
            'recovery' => [
                'hint' => 'Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.',
                'regenerate' => 'Regenerate Recovery Codes',
                'show' => 'Show Recovery Codes'
            ],
            'setupKey' => 'Setup Key',
            'confirm' => 'Confirm',
            'cancel' => 'Cancel',
            'disable' => 'Disable',
            'enable' => 'Enable'
        ],

        /**
         * session
         */
        'session' => [
            'title' => 'Browser Sessions',
            'description' => 'Manage and log out your active sessions on other browsers and devices.',
            'content' => 'If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.',
            'logout' => 'Log Out Other Browser Sessions'
        ],

        /**
         * delete
         */
        'delete' => [
            'title' => 'Delete Account',
            'description' => 'Permanently delete your account.',
            'content' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
            'delete' => 'Delete Account'
        ]
    ],
 ];

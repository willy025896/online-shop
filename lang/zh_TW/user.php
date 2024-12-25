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
    'secureArea' => '安全性頁面',
    'confirmPasswordHint' => '安全性頁面，請先輸入您的密碼。',
    'password' => '密碼',
    'confirm' => '確認',

    /**
     * profile
     */
    'profile' => [
        /**
         * information
         */
        'information' => [
            'title' => '基本資訊',
            'description' => '更新帳戶基本資訊與電子信箱地址。',
            'photo' => [
                'title' => '照片',
                'new' => '選擇新照片',
                'remove' => '刪除照片'
            ],
            'name' => '名稱',
            'email'=> [
                'title' => '電子信箱',
                'unverified' => '您的電子信向尚未驗證。',
                'resend' => '點這裡重新發送驗證信件。',
                'sent' => '新的驗證信已發送至您的電子信箱。'
            ]
        ],

        /**
         * update password
         */
        'password' => [
            'title' => '更新密碼',
            'description' => '確保您的密碼長度夠長且隨機來維持安全性',
            'current' => '當前密碼',
            'new' => '新密碼',
            'confirm' => '確認新密碼',
        ],

        /**
         * two-factor
         */
        'twoFactor' => [
            'title' => '兩階段驗證',
            'description' => '使用兩階段驗證來為您的帳戶添加額外的安全性。',
            'hint' => '當啟用兩階段驗證時，您在驗證過程中將需要輸入一個安全且隨機的驗證碼。您可以從手機的 Google Authenticator 應用程式中獲取此驗證碼。',
            'enabled' => [
                'title' => '已啟用兩階段驗證。',
                'hint' => '兩階段驗證已啟用。請使用手機上的驗證應用程式掃描以下 QR 碼，或手動輸入設定金鑰。'
            ],
            'disabled' => [
                'title' => '尚未啟用兩階段驗證'
            ],
            'enabling' => [
                'title' => '完成啟用兩階段驗證',
                'hint' => '要完成啟用兩階段驗證，請使用手機上的驗證應用程式掃描以下 QR 碼，或輸入設定金鑰，然後輸入生成的 OTP 驗證碼。',
                'code' => '驗證碼'
            ],
            'recovery' => [
                'hint' => '將這些復原碼儲存在安全的密碼管理工具中。如果您的兩階段驗證裝置遺失，這些復原碼可用於重新獲取帳戶的訪問權限。',
                'regenerate' => '重新產生復原碼',
                'show' => '顯示復原碼'
            ],
            'setupKey' => '設定金鑰',
            'confirm' => '確認',
            'cancel' => '取消',
            'disable' => '停用',
            'enable' => '啟用'
        ],

        /**
         * session
         */
        'session' => [
            'title' => '瀏覽器登入紀錄',
            'description' => '管理並登出您在其他瀏覽器和裝置上的活躍紀錄。',
            'content' => '如果有需要，您可以登出所有裝置上的其他瀏覽器。以下列出部分近期的登入的瀏覽器，但此列表可能並不完整。如果您覺得帳戶已遭到入侵，建議您同時更新密碼。',
            'logout' => '登出其他瀏覽器'
        ],

        /**
         * delete
         */
        'delete' => [
            'title' => '刪除帳戶',
            'description' => '永久刪除您的帳戶。',
            'content' => '一旦您的帳戶被刪除，其所有資源和數據將被永久刪除。在刪除帳戶之前，請下載您希望保留的任何數據或訊息。',
        ]
    ],
 ];

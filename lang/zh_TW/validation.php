<?php

return [

    /*
    |----------------------------------------------------------------------
    | 驗證錯誤訊息
    |----------------------------------------------------------------------
    |
    | 以下語言行包含了驗證器類別使用的默認錯誤訊息。一些規則有多個版本，
    | 例如大小規則。可以在這裡隨意調整每個訊息。
    |
    */

    'accepted' => ':attribute 欄位必須被接受。',
    'accepted_if' => '當 :other 為 :value 時，:attribute 欄位必須被接受。',
    'active_url' => ':attribute 欄位必須是有效的 URL。',
    'after' => ':attribute 欄位必須是 :date 之後的日期。',
    'after_or_equal' => ':attribute 欄位必須是 :date 之後或相等的日期。',
    'alpha' => ':attribute 欄位只能包含字母。',
    'alpha_dash' => ':attribute 欄位只能包含字母、數字、破折號和底線。',
    'alpha_num' => ':attribute 欄位只能包含字母和數字。',
    'array' => ':attribute 欄位必須是陣列。',
    'ascii' => ':attribute 欄位只能包含單字節的字母數字字符和符號。',
    'before' => ':attribute 欄位必須是 :date 之前的日期。',
    'before_or_equal' => ':attribute 欄位必須是 :date 之前或相等的日期。',
    'between' => [
        'array' => ':attribute 欄位必須有介於 :min 和 :max 項目。',
        'file' => ':attribute 欄位必須介於 :min 和 :max kb之間。',
        'numeric' => ':attribute 欄位必須介於 :min 和 :max 之間。',
        'string' => ':attribute 欄位必須介於 :min 和 :max 字元之間。',
    ],
    'boolean' => ':attribute 欄位必須是 true 或 false。',
    'can' => ':attribute 欄位包含未授權的值。',
    'confirmed' => ':attribute與:attribute確認不一樣。',
    'contains' => ':attribute 欄位缺少必須的值。',
    'current_password' => '密碼不正確。',
    'date' => ':attribute 欄位必須是有效的日期。',
    'date_equals' => ':attribute 欄位必須是與 :date 相等的日期。',
    'date_format' => ':attribute 欄位必須符合 :format 格式。',
    'decimal' => ':attribute 欄位必須有 :decimal 位小數。',
    'declined' => ':attribute 欄位必須被拒絕。',
    'declined_if' => '當 :other 為 :value 時，:attribute 欄位必須被拒絕。',
    'different' => ':attribute 欄位和 :other 必須不同。',
    'digits' => ':attribute 欄位必須是 :digits 位數字。',
    'digits_between' => ':attribute 欄位必須介於 :min 和 :max 位數字之間。',
    'dimensions' => ':attribute 欄位的圖片尺寸無效。',
    'distinct' => ':attribute 欄位有重複的值。',
    'doesnt_end_with' => ':attribute 欄位不得以以下之一結尾：:values。',
    'doesnt_start_with' => ':attribute 欄位不得以以下之一開頭：:values。',
    'email' => ':attribute 欄位必須是有效的電子郵件地址。',
    'ends_with' => ':attribute 欄位必須以以下之一結尾：:values。',
    'enum' => '選擇的 :attribute 無效。',
    'exists' => '選擇的 :attribute 無效。',
    'extensions' => ':attribute 欄位必須擁有以下副檔名之一：:values。',
    'file' => ':attribute 欄位必須是檔案。',
    'filled' => ':attribute 欄位必須有值。',
    'gt' => [
        'array' => ':attribute 欄位必須有超過 :value 項目。',
        'file' => ':attribute 欄位必須大於 :value kb。',
        'numeric' => ':attribute 欄位必須大於 :value。',
        'string' => ':attribute 欄位必須大於 :value 字元。',
    ],
    'gte' => [
        'array' => ':attribute 欄位必須有 :value 項目或更多。',
        'file' => ':attribute 欄位必須大於或等於 :value kb。',
        'numeric' => ':attribute 欄位必須大於或等於 :value。',
        'string' => ':attribute 欄位必須大於或等於 :value 字元。',
    ],
    'hex_color' => ':attribute 欄位必須是有效的十六進位顏色。',
    'image' => ':attribute 欄位必須是圖片。',
    'in' => '選擇的 :attribute 無效。',
    'in_array' => ':attribute 欄位必須存在於 :other 中。',
    'integer' => ':attribute 欄位必須是整數。',
    'ip' => ':attribute 欄位必須是有效的 IP 地址。',
    'ipv4' => ':attribute 欄位必須是有效的 IPv4 地址。',
    'ipv6' => ':attribute 欄位必須是有效的 IPv6 地址。',
    'json' => ':attribute 欄位必須是有效的 JSON 字串。',
    'list' => ':attribute 欄位必須是清單。',
    'lowercase' => ':attribute 欄位必須是小寫。',
    'lt' => [
        'array' => ':attribute 欄位必須少於 :value 項目。',
        'file' => ':attribute 欄位必須小於 :value kb。',
        'numeric' => ':attribute 欄位必須小於 :value。',
        'string' => ':attribute 欄位必須小於 :value 字元。',
    ],
    'lte' => [
        'array' => ':attribute 欄位不得超過 :value 項目。',
        'file' => ':attribute 欄位必須小於或等於 :value kb。',
        'numeric' => ':attribute 欄位必須小於或等於 :value。',
        'string' => ':attribute 欄位必須小於或等於 :value 字元。',
    ],
    'mac_address' => ':attribute 欄位必須是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 欄位不得超過 :max 項目。',
        'file' => ':attribute 欄位不得超過 :max kb。',
        'numeric' => ':attribute 欄位不得超過 :max。',
        'string' => ':attribute 欄位不得超過 :max 字元。',
    ],
    'max_digits' => ':attribute 欄位不得超過 :max 位數字。',
    'mimes' => ':attribute 欄位必須是以下類型的檔案：:values。',
    'mimetypes' => ':attribute 欄位必須是以下類型的檔案：:values。',
    'min' => [
        'array' => ':attribute 必須至少有 :min 項目。',
        'file' => ':attribute 必須至少有 :min kb。',
        'numeric' => ':attribute 必須至少為 :min。',
        'string' => ':attribute 必須至少有 :min 個字元。',
    ],
    'min_digits' => ':attribute 欄位必須至少有 :min 位數字。',
    'missing' => ':attribute 欄位必須空白。',
    'missing_if' => '當 :other 為 :value 時，:attribute 欄位必須空白。',
    'missing_unless' => '除非 :other 是 :value，否則 :attribute 欄位必須空白。',
    'missing_with' => '當 :values 存在時，:attribute 欄位必須空白。',
    'missing_with_all' => '當 :values 都存在時，:attribute 欄位必須空白。',
    'multiple_of' => ':attribute 欄位必須是 :value 的倍數。',
    'not_in' => '選擇的 :attribute 無效。',
    'not_regex' => ':attribute 欄位格式無效。',
    'numeric' => ':attribute 欄位必須是數字。',
    'password' => [
        'letters' => ':attribute 欄位必須包含至少一個字母。',
        'mixed' => ':attribute 欄位必須包含至少一個大寫字母和一個小寫字母。',
        'numbers' => ':attribute 欄位必須包含至少一個數字。',
        'symbols' => ':attribute 欄位必須包含至少一個符號。',
        'uncompromised' => '提供的 :attribute 欄位出現在資料外洩中。請選擇另一個 :attribute。',
    ],
    'present' => ':attribute 欄位必須存在。',
    'prohibited' => ':attribute 欄位是禁止的。',
    'prohibited_if' => '當 :other 為 :value 時，:attribute 欄位是禁止的。',
    'prohibited_unless' => '除非 :other 是 :value，否則 :attribute 欄位是禁止的。',
    'prohibits' => ':attribute 欄位禁止包含 :other。',
    'regex' => ':attribute 欄位格式無效。',
    'required' => ':attribute 欄位為必填。',
    'required_array_keys' => ':attribute 欄位必須包含以下項目：:values。',
    'required_if' => '當 :other 為 :value 時，:attribute 欄位為必填。',
    'required_if_accepted' => '當 :other 被接受時，:attribute 欄位為必填。',
    'required_unless' => '除非 :other 是 :value，否則 :attribute 欄位為必填。',
    'required_with' => '當 :values 存在時，:attribute 欄位為必填。',
    'required_with_all' => '當 :values 都存在時，:attribute 欄位為必填。',
    'required_without' => '當 :values 不存在時，:attribute 欄位為必填。',
    'required_without_all' => '當 :values 都不存在時，:attribute 欄位為必填。',
    'same' => ':attribute 欄位和 :other 必須相同。',
    'size' => [
        'array' => ':attribute 欄位必須包含 :size 項目。',
        'file' => ':attribute 欄位必須是 :size kb。',
        'numeric' => ':attribute 欄位必須是 :size。',
        'string' => ':attribute 欄位必須是 :size 字元。',
    ],
    'starts_with' => ':attribute 欄位必須以以下之一開頭：:values。',
    'string' => ':attribute 欄位必須是字串。',
    'timezone' => ':attribute 欄位必須是有效的時區。',
    'unique' => ':attribute 欄位已被使用。',
    'uploaded' => ':attribute 欄位上傳失敗。',
    'url' => ':attribute 欄位必須是有效的 URL。',
    'uuid' => ':attribute 欄位必須是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | 自訂規則 - 錯誤訊息
    |--------------------------------------------------------------------------
    |
    | 這裡可以定義自訂規則的多語系，格式如下方註解。
    |
    */

    // 'custom' => [
    //     'attribute-name' => [
    //         'rule-name' => 'custom-message',
    //     ],
    // ],

    /*
    |--------------------------------------------------------------------------
    | 自訂欄位名稱
    |--------------------------------------------------------------------------
    |
    | 可以將驗證欄位的名稱替換成可讀性較高的文字。
    |
    */

    'attributes' => [
        'email' => '電子信箱',
        'password' => '密碼',
        'name' => '姓名',
    ],
];

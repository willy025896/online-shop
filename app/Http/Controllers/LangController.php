<?php

/**
 * 語言
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Lang;

class LangController extends Controller
{
    /**
     * 取得前端套件語言包
     *
     * @param  string  $name  套件名稱
     */
    public function getComponents(string $name): string
    {
        $lang = Lang::get('components');

        return json_encode($lang[$name] ?? []);
    }
}

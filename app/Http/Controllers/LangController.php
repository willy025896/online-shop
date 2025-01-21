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
     * @param String $name 套件名稱
     * @return String
     */
    public function getComponents(String $name): String
    {
        $lang = Lang::get('components');

        return json_encode($lang[$name] ?? []);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function toggle(Request $request)
    {
        $theme = $request->session()->get('theme', 'light');
        $request->session()->put('theme', $theme === 'light' ? 'dark' : 'light');

        return back();
    }
}

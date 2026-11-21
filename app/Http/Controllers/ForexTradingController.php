<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ForexTradingController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('forex', [
            'user' => $request->user(),
        ]);
    }
}

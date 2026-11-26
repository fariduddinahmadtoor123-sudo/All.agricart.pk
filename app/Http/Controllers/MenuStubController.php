<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuStubController extends Controller
{
    public function __invoke(Request $request): View
    {
        $title = (string) ($request->route()?->defaults['deskTitle'] ?? 'Desk');

        return view('menu-stub', [
            'user' => $request->user(),
            'title' => $title,
        ]);
    }
}

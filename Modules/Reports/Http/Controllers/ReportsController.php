<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('section', [
            'user' => $request->user(),
            'title' => 'Reports',
        ]);
    }
}

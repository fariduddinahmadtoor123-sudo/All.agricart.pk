<?php

namespace Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('section', [
            'user' => $request->user(),
            'title' => 'Contacts',
        ]);
    }
}

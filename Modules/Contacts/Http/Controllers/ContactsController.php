<?php

namespace Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactsController extends Controller
{
    public function overview(Request $request): View
    {
        return view('contacts.overview', [
            'user' => $request->user(),
            'cards' => [
                [
                    'label' => 'Suppliers',
                    'route' => 'contacts.suppliers',
                    'count' => 0,
                    'hint' => 'Businesses you buy from',
                ],
                [
                    'label' => 'Customers',
                    'route' => 'contacts.customers',
                    'count' => 0,
                    'hint' => 'Offline / local buyers',
                ],
                [
                    'label' => 'Online customers',
                    'route' => 'contacts.online-customers',
                    'count' => 0,
                    'hint' => 'Store login buyers',
                ],
                [
                    'label' => 'Carriers',
                    'route' => 'contacts.carriers',
                    'count' => 0,
                    'hint' => 'Transport and courier companies',
                ],
                [
                    'label' => 'Message templates',
                    'route' => 'contacts.message-templates',
                    'count' => 0,
                    'hint' => 'Saved WhatsApp / SMS text',
                ],
            ],
        ]);
    }

    public function suppliers(Request $request): View
    {
        return view('contacts.suppliers', [
            'user' => $request->user(),
            'supplierTypes' => config('supplier_types', []),
        ]);
    }

    public function customers(Request $request): View
    {
        return view('contacts.customers', [
            'user' => $request->user(),
        ]);
    }

    public function carriers(Request $request): View
    {
        return view('contacts.carriers', [
            'user' => $request->user(),
            'transportTypes' => config('transport_types', []),
        ]);
    }

    public function messageTemplates(Request $request): View
    {
        return view('contacts.message-templates', [
            'user' => $request->user(),
        ]);
    }
}

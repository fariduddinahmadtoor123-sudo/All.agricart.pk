<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolesController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('roles', [
            'user' => $request->user(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('roles.create', [
            'user' => $request->user(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::query()->create($request->validated());

        return redirect()->route('roles');
    }
}

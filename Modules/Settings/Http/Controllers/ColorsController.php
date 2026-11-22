<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ColorScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Settings\Http\Requests\StoreColorSchemeRequest;

class ColorsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('colors', [
            'user' => $request->user(),
            'schemes' => ColorScheme::query()->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('colors.create', [
            'user' => $request->user(),
        ]);
    }

    public function store(StoreColorSchemeRequest $request): RedirectResponse
    {
        ColorScheme::query()->create($request->validated());

        return redirect()->route('colors');
    }

    public function edit(Request $request, ColorScheme $colorScheme): View
    {
        return view('colors.edit', [
            'user' => $request->user(),
            'scheme' => $colorScheme,
        ]);
    }

    public function update(StoreColorSchemeRequest $request, ColorScheme $colorScheme): RedirectResponse
    {
        $colorScheme->update($request->validated());

        return redirect()->route('colors');
    }

    public function destroy(ColorScheme $colorScheme): RedirectResponse
    {
        $colorScheme->delete();

        return redirect()->route('colors');
    }
}

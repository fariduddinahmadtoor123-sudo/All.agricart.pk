<?php

namespace Modules\Docs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Docs\Http\Requests\StoreNoteRequest;

class NotesController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('notes', [
            'user' => $request->user(),
            'notes' => Note::query()->orderBy('title')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('notes.create', [
            'user' => $request->user(),
        ]);
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        Note::query()->create($request->validated());

        return redirect()->route('notes');
    }

    public function edit(Request $request, Note $note): View
    {
        return view('notes.edit', [
            'user' => $request->user(),
            'note' => $note,
        ]);
    }

    public function update(StoreNoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($request->validated());

        return redirect()->route('notes');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return redirect()->route('notes');
    }
}

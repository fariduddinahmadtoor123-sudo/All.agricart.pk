<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PrintProfile;
use App\Support\Printing\BarcodeRenderer;
use App\Support\Printing\PrintProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Settings\Http\Requests\StorePrintProfileRequest;
use Modules\Settings\Http\Requests\UpdatePrintProfileRequest;

class PrintingController extends Controller
{
    public function __invoke(Request $request, PrintProfileService $profiles): View
    {
        $all = $profiles->all();

        return view('settings.printing', [
            'user' => $request->user(),
            'documents' => $all->where('category', PrintProfile::CATEGORY_DOCUMENT)->values(),
            'receipts' => $all->where('category', PrintProfile::CATEGORY_RECEIPT)->values(),
            'labels' => $all->where('category', PrintProfile::CATEGORY_LABEL)->values(),
        ]);
    }

    public function store(StorePrintProfileRequest $request, PrintProfileService $profiles): RedirectResponse
    {
        $profiles->create($request->validated());

        return redirect()->route('settings.printing')->with('status', 'Print size saved.');
    }

    public function update(UpdatePrintProfileRequest $request, PrintProfile $printProfile, PrintProfileService $profiles): RedirectResponse
    {
        $profiles->update($printProfile, $request->validated());

        return redirect()->route('settings.printing')->with('status', 'Print size updated.');
    }

    public function destroy(PrintProfile $printProfile, PrintProfileService $profiles): RedirectResponse
    {
        $profiles->delete($printProfile);

        return redirect()->route('settings.printing')->with('status', 'Print size removed.');
    }

    public function setDefault(PrintProfile $printProfile, PrintProfileService $profiles): RedirectResponse
    {
        $profiles->setDefault($printProfile);

        return redirect()->route('settings.printing')->with('status', 'Default print size set.');
    }

    public function preview(PrintProfile $printProfile): View
    {
        return view('print.preview', [
            'profile' => $printProfile,
            'sampleTitle' => 'Desk print preview',
            'sampleBody' => 'This page uses the selected print size. Later, invoices, ledgers, and lists will print through the same profiles.',
        ]);
    }

    public function barcodePreview(PrintProfile $printProfile, BarcodeRenderer $barcodes): View
    {
        abort_unless($printProfile->category === PrintProfile::CATEGORY_LABEL, 404);

        $code = 'DESK-'.str_pad((string) $printProfile->id, 6, '0', STR_PAD_LEFT);

        return view('print.barcode-preview', [
            'profile' => $printProfile,
            'code' => $code,
            'barcodeSvg' => $barcodes->svg($code, 1.8, max(28.0, (float) $printProfile->height_mm * 1.2)),
        ]);
    }
}

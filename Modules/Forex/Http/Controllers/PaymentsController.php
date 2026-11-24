<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Forex\Http\Requests\RejectPaymentRequest;
use Modules\Forex\Http\Requests\StorePaymentRequest;
use Modules\Forex\Models\ClientAccount;
use Modules\Forex\Models\Payment;
use Modules\Forex\Support\Payments;

class PaymentsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $view = $request->string('status')->toString();

        if (! in_array($view, ['history', 'rejected'], true)) {
            $view = 'pending';
        }

        $status = match ($view) {
            'history' => Payment::STATUS_PAID,
            'rejected' => Payment::STATUS_REJECTED,
            default => Payment::STATUS_PENDING,
        };

        $payments = Payment::query()
            ->with(['clientAccount.user'])
            ->where('status', $status)
            ->when(
                $view === 'history',
                fn ($query) => $query->latest('paid_at'),
                fn ($query) => $query->latest('id'),
            )
            ->get();

        return view('forex.payments', [
            'user' => $request->user(),
            'view' => $view,
            'payments' => $payments,
            'accounts' => ClientAccount::query()->with('user')->orderBy('number')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('forex.payments.create', [
            'user' => $request->user(),
            'accounts' => ClientAccount::query()->with('user')->orderBy('number')->get(),
        ]);
    }

    public function store(StorePaymentRequest $request, Payments $payments): RedirectResponse
    {
        $account = ClientAccount::query()->findOrFail($request->integer('client_account_id'));

        try {
            $payments->record($account, $request->safe()->only(['type', 'amount', 'reference']), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('forex.payments', ['status' => 'pending'])
            ->with('status', 'Request saved. It waits until you approve it.');
    }

    public function approve(Request $request, Payment $payment, Payments $payments): RedirectResponse
    {
        try {
            $payments->markPaid($payment, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('forex.payments', ['status' => 'pending'])
            ->with('status', 'Approved. It is now in History and on the customer ledger.');
    }

    public function reject(RejectPaymentRequest $request, Payment $payment, Payments $payments): RedirectResponse
    {
        try {
            $payments->reject($payment, $request->string('reason')->toString(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['reason' => $exception->getMessage()]);
        }

        return redirect()
            ->route('forex.payments', ['status' => 'pending'])
            ->with('status', 'Rejected. The reason is saved for this customer.');
    }
}

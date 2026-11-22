<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ForexTradingController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('forex.market-watch');
    }
}

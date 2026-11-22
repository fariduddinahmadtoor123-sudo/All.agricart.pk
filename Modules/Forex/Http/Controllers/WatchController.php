<?php

namespace Modules\Forex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forex\Support\MarketWatch;

class WatchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('forex.watch', [
            'user' => $user,
            'quotes' => MarketWatch::gateTicks($user),
            'feedOnline' => MarketWatch::onlineFeed($user) !== null,
        ]);
    }
}

<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiLogsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $logs = AiLog::query()
            ->latest('id')
            ->limit(100)
            ->get();

        return view('settings.ai-logs', [
            'user' => $request->user(),
            'logs' => $logs,
        ]);
    }
}

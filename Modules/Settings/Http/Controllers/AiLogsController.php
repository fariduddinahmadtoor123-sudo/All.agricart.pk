<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AiLogsController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('settings.ai')->withFragment('activity');
    }
}

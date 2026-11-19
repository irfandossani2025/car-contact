<?php

namespace App\Http\Controllers;

use App\Services\QrCodeService;
use App\Services\TelegramNotifier;
use App\Services\WebPushNotifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, QrCodeService $qr, WebPushNotifier $push, TelegramNotifier $telegram): View
    {
        $user = $request->user();
        $vehicles = $user->vehicles()->withCount('alerts')->orderBy('id')->get();
        $alerts = $user->alerts()->with('vehicle')->latest('alerts.created_at')->limit(10)->get();

        return view('dashboard', [
            'user' => $user,
            'vehicles' => $vehicles,
            'qrSvgs' => $vehicles->mapWithKeys(fn ($v) => [$v->id => $qr->svg($v->scanUrl())]),
            'alerts' => $alerts,
            'pushEnabled' => $push->isConfigured(),
            'pushCount' => $user->pushSubscriptions()->count(),
            'telegramEnabled' => $telegram->isConfigured(),
        ]);
    }
}

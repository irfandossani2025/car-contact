<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsAppSettingsController extends Controller
{
    /** Opens WhatsApp with "CONNECT <code>" pre-filled; the webhook links the sender's number. */
    public function connect(Request $request, WhatsAppNotifier $wa): RedirectResponse
    {
        abort_unless($wa->isConfigured(), 404);

        $code = strtoupper(Str::random(8));
        $request->user()->forceFill(['whatsapp_link_code' => $code])->save();

        return redirect()->away($wa->connectUrl($code));
    }

    public function toggle(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->whatsapp_number, 422);
        $user->update(['notify_whatsapp' => ! $user->notify_whatsapp]);

        return back()->with('status', $user->notify_whatsapp ? 'WhatsApp alerts resumed.' : 'WhatsApp alerts paused.');
    }

    public function test(Request $request, WhatsAppNotifier $wa): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canReceiveWhatsApp(), 422);

        // Uses the approved template so it works even outside the 24-hour chat window.
        $ok = $wa->sendTemplate($user->whatsapp_number, ['Test car', 'This is a test alert', 'Alerts are working']);

        return back()->with('status', $ok ? 'Test WhatsApp message sent.' : 'The test could not be sent. Check that the message template is approved.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['whatsapp_number' => null, 'whatsapp_link_code' => null, 'notify_whatsapp' => false])->save();

        return back()->with('status', 'WhatsApp disconnected.');
    }
}

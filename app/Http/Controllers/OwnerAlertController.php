<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\BlockedSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Pages the car owner opens from a notification. They use signed links,
 * so the owner can reply in one tap without logging in.
 */
class OwnerAlertController extends Controller
{
    public function show(Alert $alert): View
    {
        $alert->load('vehicle');
        $alert->markSeen();

        return view('owner.alert', [
            'alert' => $alert,
            'replies' => Alert::REPLIES,
            'blockUrl' => URL::temporarySignedRoute('owner.alert.block', now()->addDays(7), ['alert' => $alert->id]),
        ]);
    }

    /** Short link from SMS: /o/{token} -> the signed owner page. */
    public function short(string $token): RedirectResponse
    {
        $alert = Alert::where('owner_token', $token)->firstOrFail();
        abort_if($alert->created_at->lt(now()->subDays(7)), 410);

        return redirect($alert->ownerUrl());
    }

    public function reply(Request $request, Alert $alert, string $reply): RedirectResponse|JsonResponse
    {
        abort_unless(array_key_exists($reply, Alert::REPLIES), 404);
        $alert->setReply($reply);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect($alert->ownerUrl())->with('status', 'Reply sent: “'.Alert::REPLIES[$reply].'”');
    }

    public function block(Alert $alert): RedirectResponse
    {
        $alert->load('vehicle');
        BlockedSender::firstOrCreate([
            'user_id' => $alert->vehicle->user_id,
            'sender_hash' => $alert->sender_hash,
        ]);

        return redirect($alert->ownerUrl())->with('status', 'This sender is now blocked from alerting you.');
    }
}

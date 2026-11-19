<?php

namespace App\Http\Controllers;

use App\Services\SmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Owner adds a phone number, verifies it with a one-time SMS code, and turns SMS alerts on/off. */
class SmsSettingsController extends Controller
{
    public function savePhone(Request $request, SmsNotifier $sms): RedirectResponse
    {
        abort_unless($sms->isConfigured(), 404);

        $phone = SmsNotifier::normalizePhone($request->input('phone'));
        if (! $phone) {
            return back()->withErrors(['phone' => 'Enter your mobile number with country code, e.g. +971501234567.']);
        }

        $user = $request->user();
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'phone' => $phone,
            'phone_verified_at' => null,
            'notify_sms' => false,
            'phone_verify_code' => Hash::make($code),
            'phone_verify_expires_at' => now()->addMinutes(10),
            'phone_verify_attempts' => 0,
        ])->save();

        if (! $sms->sendVerificationCode($phone, $code)) {
            return back()->withErrors(['phone' => 'We could not send an SMS to that number. Check it and try again.']);
        }

        return back()->with('status', 'We sent a 6-digit code to '.$user->maskedPhone().'. Enter it below.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        if (! $user->phone_verify_code || ! $user->phone_verify_expires_at || $user->phone_verify_expires_at->isPast()) {
            return back()->withErrors(['code' => 'That code has expired. Send a new one.']);
        }
        if ($user->phone_verify_attempts >= 5) {
            return back()->withErrors(['code' => 'Too many wrong attempts. Send a new code.']);
        }
        if (! Hash::check($request->input('code'), $user->phone_verify_code)) {
            $user->increment('phone_verify_attempts');

            return back()->withErrors(['code' => 'That code is not right. Please try again.']);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'notify_sms' => true,
            'phone_verify_code' => null,
            'phone_verify_expires_at' => null,
            'phone_verify_attempts' => 0,
        ])->save();

        return back()->with('status', 'Phone verified. SMS alerts are on.');
    }

    public function toggle(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->phone_verified_at, 422);
        $user->update(['notify_sms' => ! $user->notify_sms]);

        return back()->with('status', $user->notify_sms ? 'SMS alerts turned on.' : 'SMS alerts turned off.');
    }

    public function test(Request $request, SmsNotifier $sms): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canReceiveSms(), 422);
        $ok = $sms->send($user->phone, config('app.name').': SMS alerts are working. You will get a text when someone scans your car.');

        return back()->with('status', $ok ? 'Test SMS sent.' : 'The test SMS could not be sent. Please try again later.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'phone' => null, 'phone_verified_at' => null, 'notify_sms' => false,
            'phone_verify_code' => null, 'phone_verify_expires_at' => null, 'phone_verify_attempts' => 0,
        ])->save();

        return back()->with('status', 'Phone number removed.');
    }
}

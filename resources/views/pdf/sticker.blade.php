<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0.45in; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #111827; margin: 0; }
    .page-break { page-break-after: always; }
    .cut { border: 1.5px dashed #9ca3af; border-radius: 14px; padding: 8px; }
    .sticker { background: #111827; color: #fff; border-radius: 10px; text-align: center; }
    .big { padding: 22px 20px 18px; }
    .big h1 { font-size: 30px; margin: 0; line-height: 1.15; color: #FBBF24; }
    .big .sub { font-size: 15px; margin: 6px 0 14px; color: #e5e7eb; }
    .qr-box { background: #fff; border-radius: 10px; display: inline-block; padding: 10px; }
    .big .qr { width: 3.3in; height: 3.3in; }
    .big .url { font-size: 13px; margin-top: 12px; color: #d1d5db; }
    .big .code { font-size: 22px; letter-spacing: 4px; font-weight: bold; color: #fff; margin-top: 2px; }
    .big .privacy { font-size: 11px; margin-top: 10px; color: #9ca3af; }
    .small { padding: 12px 10px; }
    .small h2 { font-size: 15px; margin: 0 0 8px; color: #FBBF24; }
    .small .qr { width: 1.55in; height: 1.55in; }
    .small .qr-box { padding: 6px; }
    .small .code { font-size: 12px; letter-spacing: 2px; margin-top: 6px; color: #fff; }
    .hint { font-size: 10px; color: #6b7280; text-align: center; margin-top: 4px; }
    h3 { font-size: 22px; margin: 0 0 4px; }
    .steps { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .steps td { vertical-align: top; font-size: 13px; line-height: 1.45; }
    .num { width: 34px; }
    .num span { display: inline-block; width: 26px; height: 22px; padding-top: 4px; line-height: 16px; border-radius: 13px; background: #FBBF24; color: #111827; text-align: center; font-weight: bold; }
    .box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-top: 12px; font-size: 12px; line-height: 1.5; }
    .muted { color: #6b7280; }
</style>
</head>
<body>

{{-- ============ PAGE 1: stickers ============ --}}
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:62%; padding-right:10px; vertical-align:top;">
            <div class="cut">
                <div class="sticker big">
                    <h1>Blocking you?<br>Scan to alert me.</h1>
                    <div class="sub">Point your phone camera at the code</div>
                    <div class="qr-box"><img class="qr" src="{{ $qr }}"></div>
                    <div class="url">No camera? Go to {{ $shortUrl }}</div>
                    <div class="code">{{ $vehicle->displayCode() }}</div>
                    <div class="privacy">Free &amp; anonymous. No app needed. Owner's number stays private.</div>
                </div>
            </div>
            <div class="hint">Main sticker: inside the windshield, facing out (driver's side, lower corner)</div>
        </td>
        <td style="width:38%; vertical-align:top;">
            @for ($i = 0; $i < 2; $i++)
                <div class="cut" style="{{ $i ? 'margin-top:14px;' : '' }}">
                    <div class="sticker small">
                        <h2>Need me to move?<br>Scan me</h2>
                        <div class="qr-box"><img class="qr" src="{{ $qr }}"></div>
                        <div class="code">{{ $vehicle->displayCode() }}</div>
                    </div>
                </div>
                <div class="hint">{{ $i ? 'Spare' : 'Rear or side window' }}</div>
            @endfor
        </td>
    </tr>
</table>

<div class="box" style="margin-top:18px;">
    <strong>Printing tips:</strong> print at <strong>100% / Actual size</strong> (not "fit to page").
    Use sticker paper, or print on regular paper and tape it inside the glass facing out.
    Clear packing tape over the front helps against sun and rain.
    <span class="muted">Car: {{ $vehicle->name }}</span>
</div>

<div class="page-break"></div>

{{-- ============ PAGE 2: instructions for the owner ============ --}}
<h3>Your Contact Scan sticker: how it works</h3>
<p class="muted" style="margin:0 0 6px; font-size:13px;">Keep this page. It explains setup and what happens when someone scans your car.</p>

<table class="steps">
    <tr><td class="num"><span>1</span></td><td><strong>Stick it on.</strong> Cut out the big sticker and place it inside your windshield, facing out, where it's easy to see from outside, like the lower driver's-side corner. Make sure it doesn't block your view. Put a small one on a side or rear window if you like.</td></tr>
    <tr><td class="num"><span>2</span></td><td><strong>Turn on instant alerts. This is important.</strong> Log in to your dashboard on your phone and turn on at least one:<br>
        &bull; <strong>Phone notifications:</strong> tap "Turn on for this device". On iPhone, first tap Share &rarr; <em>Add to Home Screen</em>, open it from there, then turn it on.<br>
        &bull; <strong>Telegram:</strong> tap "Connect Telegram", then <em>Start</em>. Works great on any phone.<br>
        &bull; <strong>Email</strong> is always sent as a backup.</td></tr>
    <tr><td class="num"><span>3</span></td><td><strong>Test it.</strong> Scan your own sticker with your phone camera and send a test alert. You should get it within seconds.</td></tr>
    <tr><td class="num"><span>4</span></td><td><strong>When someone scans it,</strong> they pick a reason ("Blocking my car", "Lights are on", etc.) and can add a short note. You're notified right away.</td></tr>
    <tr><td class="num"><span>5</span></td><td><strong>Reply in one tap,</strong> e.g. "On my way, about 5 minutes" or "Car has been moved". They see your reply on their screen. Nobody ever sees your phone number or email.</td></tr>
</table>

<div class="box">
    <strong>Your sticker</strong><br>
    Car: {{ $vehicle->name }}{{ $vehicle->plate ? ' · '.$vehicle->plate : '' }}<br>
    Code: <strong>{{ $vehicle->displayCode() }}</strong> &nbsp;·&nbsp; Link: {{ $shortUrl }}<br>
    Dashboard: {{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}/dashboard
</div>

<div class="box">
    <strong>Good to know</strong><br>
    &bull; <strong>Selling your car or lost the sticker?</strong> In the dashboard, tap "New QR code". The old sticker stops working immediately.<br>
    &bull; <strong>Going away?</strong> Tap "Pause alerts". People who scan will see that alerts are paused.<br>
    &bull; <strong>Getting unwanted alerts?</strong> Open the alert and tap "Block this sender". Repeated alerts are also limited automatically.<br>
    &bull; Plate and colour are shown to the person scanning only if you add them, so they know they have the right car.
</div>

</body>
</html>

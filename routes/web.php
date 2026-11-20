<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OwnerAlertController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SmsSettingsController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WhatsAppSettingsController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// --- Public: the person who scans the QR code ---
Route::get('/t/{code}', [ScanController::class, 'show'])->name('scan.show');
Route::post('/t/{code}', [ScanController::class, 'send'])->middleware('throttle:20,1')->name('scan.send');
Route::get('/s/{token}', [ScanController::class, 'status'])->name('scan.status');
Route::get('/s/{token}/poll', [ScanController::class, 'poll'])->middleware('throttle:60,1')->name('scan.poll');
Route::post('/find', function (\Illuminate\Http\Request $request) {
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->input('code')));

    return redirect()->route('scan.show', $code ?: 'X');
})->name('scan.find');

// --- Owner one-tap pages (signed links from push / Telegram / email) ---
Route::middleware('signed')->group(function () {
    Route::get('/a/{alert}', [OwnerAlertController::class, 'show'])->name('owner.alert');
    Route::post('/a/{alert}/reply/{reply}', [OwnerAlertController::class, 'reply'])->name('owner.alert.reply');
    Route::post('/a/{alert}/block', [OwnerAlertController::class, 'block'])->name('owner.alert.block');
});

Route::get('/o/{token}', [OwnerAlertController::class, 'short'])->middleware('throttle:30,1')->name('owner.short');

// --- Telegram bot webhook ---
Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');

// --- WhatsApp (Meta Cloud API) webhook ---
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'receive'])->name('whatsapp.webhook');

// --- Owner dashboard ---
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::post('/vehicles/{vehicle}/toggle', [VehicleController::class, 'toggle'])->name('vehicles.toggle');
    Route::post('/vehicles/{vehicle}/regenerate', [VehicleController::class, 'regenerate'])->name('vehicles.regenerate');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::get('/vehicles/{vehicle}/sticker.pdf', [VehicleController::class, 'sticker'])->name('vehicles.sticker');

    Route::post('/notifications/push', [NotificationController::class, 'subscribePush'])->name('push.subscribe');
    Route::delete('/notifications/push', [NotificationController::class, 'unsubscribePush'])->name('push.unsubscribe');
    Route::post('/notifications/push/test', [NotificationController::class, 'testPush'])->name('push.test');
    Route::post('/notifications/push/remove-all', [NotificationController::class, 'removeAllPush'])->name('push.removeAll');
    Route::get('/notifications/telegram/connect', [NotificationController::class, 'connectTelegram'])->name('telegram.connect');
    Route::post('/notifications/telegram/disconnect', [NotificationController::class, 'disconnectTelegram'])->name('telegram.disconnect');
    Route::post('/notifications/sms/phone', [SmsSettingsController::class, 'savePhone'])->middleware('throttle:6,1')->name('sms.phone');
    Route::post('/notifications/sms/verify', [SmsSettingsController::class, 'verify'])->middleware('throttle:10,1')->name('sms.verify');
    Route::post('/notifications/sms/toggle', [SmsSettingsController::class, 'toggle'])->name('sms.toggle');
    Route::post('/notifications/sms/test', [SmsSettingsController::class, 'test'])->middleware('throttle:3,10')->name('sms.test');
    Route::post('/notifications/sms/remove', [SmsSettingsController::class, 'remove'])->name('sms.remove');
    Route::get('/notifications/whatsapp/connect', [WhatsAppSettingsController::class, 'connect'])->name('whatsapp.connect');
    Route::post('/notifications/whatsapp/toggle', [WhatsAppSettingsController::class, 'toggle'])->name('whatsapp.toggle');
    Route::post('/notifications/whatsapp/test', [WhatsAppSettingsController::class, 'test'])->middleware('throttle:3,10')->name('whatsapp.test');
    Route::post('/notifications/whatsapp/disconnect', [WhatsAppSettingsController::class, 'disconnect'])->name('whatsapp.disconnect');
    Route::post('/notifications/email', [NotificationController::class, 'updateEmail'])->name('email.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

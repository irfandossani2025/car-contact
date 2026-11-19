<?php

namespace Tests\Feature;

use App\Mail\CarAlertMail;
use App\Models\Alert;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_car_with_qr_code(): void
    {
        $this->post('/register', [
            'name' => 'Sam', 'email' => 'sam@example.com', 'car_name' => 'Grey Civic',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $vehicle = User::first()->vehicles()->first();
        $this->assertSame('Grey Civic', $vehicle->name);
        $this->assertSame(8, strlen($vehicle->tag_code));

        $this->get('/dashboard')->assertOk()->assertSee('Grey Civic')->assertSee('<svg', false);
    }

    public function test_sticker_pdf_downloads(): void
    {
        $user = User::factory()->create();
        $vehicle = $user->vehicles()->create(['name' => 'Car']);

        $res = $this->actingAs($user)->get(route('vehicles.sticker', $vehicle));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_other_users_cannot_access_car(): void
    {
        $vehicle = User::factory()->create()->vehicles()->create(['name' => 'Car']);
        $this->actingAs(User::factory()->create())->get(route('vehicles.sticker', $vehicle))->assertNotFound();
    }

    public function test_scan_sends_alert_and_owner_can_reply(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $vehicle = $user->vehicles()->create(['name' => 'Car', 'plate' => 'ABC 123']);

        $this->get('/t/'.strtolower($vehicle->tag_code))->assertOk()->assertSee('ABC 123');

        $res = $this->post('/t/'.$vehicle->tag_code, ['reason' => 'blocking', 'message' => 'Need to leave']);
        $alert = Alert::first();
        $res->assertRedirect($alert->statusUrl());
        Mail::assertSent(CarAlertMail::class);

        $this->getJson(route('scan.poll', $alert->public_token))->assertJson(['seen' => false, 'reply' => null]);

        // Owner opens signed link -> seen; replies.
        $this->get($alert->ownerUrl())->assertOk()->assertSee('Need to leave');
        $this->post($alert->ownerReplyUrl('coming_5'))->assertRedirect();

        $this->getJson(route('scan.poll', $alert->public_token))
            ->assertJson(['seen' => true, 'reply' => 'coming_5']);
    }

    public function test_owner_links_require_signature(): void
    {
        $vehicle = User::factory()->create()->vehicles()->create(['name' => 'Car']);
        $alert = $vehicle->alerts()->create(['reason' => 'lights', 'sender_hash' => 'x']);

        $this->get('/a/'.$alert->id)->assertForbidden();
        $this->post('/a/'.$alert->id.'/reply/moved')->assertForbidden();
    }

    public function test_cooldown_and_block_and_pause(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $vehicle = $user->vehicles()->create(['name' => 'Car']);

        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'lights']);
        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'lights']); // same sender inside cooldown -> redirected to status
        $this->assertSame(1, Alert::count());

        $alert = Alert::first();
        $this->post(\URL::temporarySignedRoute('owner.alert.block', now()->addDay(), ['alert' => $alert->id]));
        $this->travel(5)->minutes();
        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'lights'])->assertSessionHasErrors('reason');
        $this->assertSame(1, Alert::count());

        $vehicle->update(['is_active' => false]);
        $this->get('/t/'.$vehicle->tag_code)->assertSee('Alerts are paused');
        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'lights'])->assertForbidden();
    }

    public function test_telegram_link_and_reply(): void
    {
        config([
            'contactscan.telegram.bot_token' => 'T', 'contactscan.telegram.bot_username' => 'bot',
            'contactscan.telegram.webhook_secret' => 'sec',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Mail::fake();

        $user = User::factory()->create();
        $vehicle = $user->vehicles()->create(['name' => 'Car']);

        $this->actingAs($user)->get(route('telegram.connect'))->assertRedirectContains('t.me/bot?start=');
        $token = $user->fresh()->telegram_link_token;

        $this->postJson('/telegram/webhook', ['message' => ['chat' => ['id' => 555], 'text' => '/start '.$token]])->assertForbidden();
        $this->postJson('/telegram/webhook', ['message' => ['chat' => ['id' => 555], 'text' => '/start '.$token]], ['X-Telegram-Bot-Api-Secret-Token' => 'sec'])->assertOk();
        $this->assertSame('555', $user->fresh()->telegram_chat_id);

        auth()->logout();
        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'blocking']);
        $alert = Alert::first();
        $this->assertTrue($alert->delivery['telegram']);

        $this->postJson('/telegram/webhook', ['callback_query' => [
            'id' => 'q', 'data' => "r:{$alert->id}:moved", 'message' => ['chat' => ['id' => 555], 'message_id' => 1],
        ]], ['X-Telegram-Bot-Api-Secret-Token' => 'sec'])->assertOk();
        $this->assertSame('moved', $alert->fresh()->reply);

        // A different chat cannot reply to someone else's alert.
        $this->postJson('/telegram/webhook', ['callback_query' => [
            'id' => 'q', 'data' => "r:{$alert->id}:cant", 'message' => ['chat' => ['id' => 999], 'message_id' => 1],
        ]], ['X-Telegram-Bot-Api-Secret-Token' => 'sec'])->assertOk();
        $this->assertSame('moved', $alert->fresh()->reply);
    }

    public function test_push_subscription_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'BKey', 'auth' => 'auth'],
        ])->assertOk();
        $this->assertSame(1, $user->pushSubscriptions()->count());
    }
}

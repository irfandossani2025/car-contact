<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'contactscan.whatsapp.access_token' => 'tok',
            'contactscan.whatsapp.phone_number_id' => '111',
            'contactscan.whatsapp.business_number' => '+15550001111',
            'contactscan.whatsapp.app_secret' => 'appsecret',
            'contactscan.whatsapp.verify_token' => 'vtok',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]])]);
        Mail::fake();
    }

    private function webhook(array $message)
    {
        $body = json_encode(['entry' => [['changes' => [['value' => ['messages' => [$message]]]]]]]);

        return $this->call('POST', '/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'appsecret'),
        ], $body);
    }

    public function test_webhook_verification(): void
    {
        $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=vtok&hub.challenge=12345')->assertOk()->assertSee('12345');
        $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345')->assertForbidden();
    }

    public function test_bad_signature_rejected(): void
    {
        $this->call('POST', '/whatsapp/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=bad'], '{}')
            ->assertForbidden();
    }

    public function test_connect_alert_and_button_reply(): void
    {
        $user = User::factory()->create(['notify_email' => false]);
        $vehicle = $user->vehicles()->create(['name' => 'Grey Civic']);

        $res = $this->actingAs($user)->get(route('whatsapp.connect'));
        $code = $user->fresh()->whatsapp_link_code;
        $res->assertRedirect('https://wa.me/15550001111?text=CONNECT%20'.$code);

        $this->webhook(['from' => '971501234567', 'type' => 'text', 'text' => ['body' => 'connect '.strtolower($code)]])->assertOk();
        $user->refresh();
        $this->assertSame('+971501234567', $user->whatsapp_number);
        $this->assertTrue($user->canReceiveWhatsApp());

        auth()->logout();
        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'blocking', 'message' => "Need\nto leave"]);
        $alert = Alert::first();
        $this->assertTrue($alert->delivery['whatsapp']);
        $this->assertFalse($alert->delivery['email']);

        Http::assertSent(function (Request $r) use ($alert) {
            if (($r['type'] ?? null) !== 'template') {
                return false;
            }
            $body = $r['template']['components'][0]['parameters'];

            return $r->url() === 'https://graph.facebook.com/v23.0/111/messages'
                && $r['to'] === '971501234567'
                && $r['template']['name'] === 'car_alert'
                && $body[0]['text'] === 'Grey Civic'
                && $body[2]['text'] === 'Need to leave'
                && $r['template']['components'][1]['parameters'][0]['payload'] === "r:{$alert->id}:coming_5";
        });

        // Owner taps "Car moved" (button index 1).
        $this->webhook(['from' => '971501234567', 'type' => 'button', 'button' => ['payload' => "r:{$alert->id}:moved", 'text' => 'Car moved']])->assertOk();
        $this->assertSame('moved', $alert->fresh()->reply);

        // Someone else's number cannot reply.
        $this->webhook(['from' => '971509999999', 'type' => 'button', 'button' => ['payload' => "r:{$alert->id}:cant"]]);
        $this->assertSame('moved', $alert->fresh()->reply);

        // STOP disconnects.
        $this->webhook(['from' => '971501234567', 'type' => 'text', 'text' => ['body' => 'STOP']]);
        $this->assertNull($user->fresh()->whatsapp_number);
    }

    public function test_invalid_connect_code_does_not_link(): void
    {
        $this->webhook(['from' => '971501234567', 'type' => 'text', 'text' => ['body' => 'CONNECT ZZZZZZZZ']])->assertOk();
        $this->assertSame(0, User::whereNotNull('whatsapp_number')->count());
    }
}

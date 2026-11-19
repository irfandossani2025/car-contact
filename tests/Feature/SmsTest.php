<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use App\Services\SmsNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['contactscan.sms.token_id' => 'tid', 'contactscan.sms.token_secret' => 'tsec']);
        Http::fake(['api.bulksms.com/*' => Http::response([['id' => '1']], 201)]);
        Mail::fake();
    }

    private function sentBodies(): array
    {
        return collect(Http::recorded())->map(fn ($r) => $r[0]->data()['body'] ?? null)->filter()->values()->all();
    }

    public function test_phone_verification_flow(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sms.phone'), ['phone' => '+971 50 123 4567'])->assertSessionHasNoErrors();
        $this->assertSame('+971501234567', $user->fresh()->phone);

        Http::assertSent(function (Request $r) {
            return str_contains($r->url(), 'api.bulksms.com/v1/messages')
                && $r->header('Authorization')[0] === 'Basic '.base64_encode('tid:tsec')
                && $r['to'] === '+971501234567';
        });

        preg_match('/code: (\d{6})/', $this->sentBodies()[0], $m);

        $this->post(route('sms.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('sms.verify'), ['code' => $m[1]])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->canReceiveSms());
    }

    public function test_invalid_phone_rejected(): void
    {
        $this->actingAs(User::factory()->create())->post(route('sms.phone'), ['phone' => '12'])->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_alert_sends_one_segment_sms_with_short_link(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['phone' => '+971501234567', 'phone_verified_at' => now(), 'notify_sms' => true, 'notify_email' => false])->save();
        $vehicle = $user->vehicles()->create(['name' => 'Grey Honda Civic']);

        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'blocking']);
        $alert = Alert::first();

        $this->assertTrue($alert->delivery['sms']);
        $this->assertFalse($alert->delivery['email']); // SMS reached the owner, so no fallback email
        $body = $this->sentBodies()[0];
        $this->assertStringContainsString('/o/'.$alert->owner_token, $body);
        $this->assertLessThanOrEqual(160, strlen($body));

        // Short link takes the owner to the signed reply page.
        $this->get('/o/'.$alert->owner_token)->assertRedirectContains('/a/'.$alert->id.'?expires=');
        $this->get('/o/nonexistent00')->assertNotFound();
    }

    public function test_unverified_phone_gets_no_sms(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['phone' => '+971501234567', 'notify_sms' => true])->save();
        $vehicle = $user->vehicles()->create(['name' => 'Car']);

        $this->post('/t/'.$vehicle->tag_code, ['reason' => 'lights']);
        Http::assertNothingSent();
        $this->assertTrue(Alert::first()->delivery['email']);
    }

    public function test_daily_cap(): void
    {
        config(['contactscan.sms.daily_limit_per_user' => 1]);
        $user = User::factory()->create();
        $user->forceFill(['phone' => '+971501234567', 'phone_verified_at' => now(), 'notify_sms' => true])->save();
        $vehicle = $user->vehicles()->create(['name' => 'Car']);
        $sms = app(SmsNotifier::class);

        $a1 = $vehicle->alerts()->create(['reason' => 'lights', 'sender_hash' => 'a']);
        $a2 = $vehicle->alerts()->create(['reason' => 'lights', 'sender_hash' => 'b']);
        $this->assertTrue($sms->sendAlert($user, $a1->load('vehicle')));
        $this->assertFalse($sms->sendAlert($user, $a2->load('vehicle')));
    }

    public function test_basic_auth_string_and_phone_normalising(): void
    {
        config(['contactscan.sms.token_id' => null, 'contactscan.sms.token_secret' => null, 'contactscan.sms.basic_auth' => 'abc123==']);
        $this->assertTrue(app(SmsNotifier::class)->send('+971501234567', 'hi'));
        Http::assertSent(fn (Request $r) => $r->header('Authorization')[0] === 'Basic abc123==');

        $this->assertSame('+971501234567', SmsNotifier::normalizePhone('00971 50-123-4567'));
        config(['contactscan.sms.default_country_code' => '971']);
        $this->assertSame('+971501234567', SmsNotifier::normalizePhone('0501234567'));
    }
}

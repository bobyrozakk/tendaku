<?php

namespace Tests\Feature\Notification;

use App\Services\FonnteWhatsAppService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteWhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.token' => 'token-uji-123',
            'services.fonnte.base_url' => 'https://api.fonnte.com',
            'services.fonnte.retry_times' => 1,
            'services.fonnte.retry_sleep_ms' => 1,
        ]);
    }

    public function test_it_sends_message_with_normalized_target_and_raw_token(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        $service = app(FonnteWhatsAppService::class);

        $this->assertTrue($service->send('081234567890', 'Halo, pengingat pengembalian.'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->header('Authorization')[0] === 'token-uji-123'
                && $request['target'] === '6281234567890'
                && $request['message'] === 'Halo, pengingat pengembalian.'
                && $request['countryCode'] === '62';
        });
    }

    public function test_it_returns_false_without_sending_request_for_invalid_phone_number(): void
    {
        Http::fake();

        $service = app(FonnteWhatsAppService::class);

        $this->assertFalse($service->send('12345', 'Halo'));
        $this->assertCount(0, Http::recorded());
    }

    public function test_it_returns_false_when_token_is_not_configured(): void
    {
        config(['services.fonnte.token' => null]);

        Http::fake();

        $service = app(FonnteWhatsAppService::class);

        $this->assertFalse($service->send('081234567890', 'Halo'));
        $this->assertCount(0, Http::recorded());
    }

    public function test_it_returns_false_when_api_responds_http_error(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'invalid token'], 401),
        ]);

        $service = app(FonnteWhatsAppService::class);

        $this->assertFalse($service->send('081234567890', 'Halo'));
    }

    public function test_it_returns_false_when_fonnte_rejects_the_request(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'token invalid'], 200),
        ]);

        $service = app(FonnteWhatsAppService::class);

        $this->assertFalse($service->send('081234567890', 'Halo'));
    }

    public function test_it_returns_false_on_connection_failure_without_throwing(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::failedConnection(),
        ]);

        $service = app(FonnteWhatsAppService::class);

        $this->assertFalse($service->send('081234567890', 'Halo'));
    }
}

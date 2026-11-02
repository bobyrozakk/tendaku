<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SettlementManager;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\CreatesRevenueFixtures;
use Tests\TestCase;

class SettlementIrisPayoutTest extends TestCase
{
    use CreatesRevenueFixtures;
    use RefreshDatabase;

    private const IRIS_BASE_URL = 'https://app.sandbox.midtrans.com/iris/api/v1';

    private User $superAdmin;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-26 10:00:00');

        $this->vendor = $this->createRevenueVendor('Vendor Iris');
        $this->superAdmin = $this->createRevenueUser('super_admin');

        config()->set('services.midtrans_iris.creator_key', 'iris-creator-key');
        config()->set('services.midtrans_iris.approver_key', 'iris-approver-key');
        config()->set('services.midtrans_iris.base_url', self::IRIS_BASE_URL);
        config()->set('services.midtrans_iris.is_production', false);
        config()->set('services.midtrans_iris.connect_timeout', 1);
        config()->set('services.midtrans_iris.timeout', 1);

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_superadmin_can_create_approve_and_complete_an_iris_payout(): void
    {
        $settlement = $this->createApprovedSettlement();

        Http::fake([
            self::IRIS_BASE_URL.'/payouts' => Http::response([
                'payouts' => [[
                    'status' => 'queued',
                    'reference_no' => 'IRIS-REF-001',
                ]],
            ], 201),
            self::IRIS_BASE_URL.'/payouts/approve' => Http::response(['status' => 'ok'], 202),
            self::IRIS_BASE_URL.'/payouts/IRIS-REF-001' => Http::response([
                'reference_no' => 'IRIS-REF-001',
                'status' => 'completed',
                'amount' => '950000.00',
                'beneficiary_name' => 'Toko Iris',
                'beneficiary_account' => '1234567890',
                'bank' => 'Bank Central Asia ( BCA )',
            ]),
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->assertSet('showIrisModal', true)
            ->set('irisOtp', '123456')
            ->call('startIrisPayout')
            ->assertHasNoErrors()
            ->assertSet('showIrisModal', false)
            ->assertSee('sudah ditandai lunas');

        $settlement->refresh();

        $this->assertSame('paid', $settlement->status);
        $this->assertSame('IRIS-REF-001', $settlement->iris_reference_no);
        $this->assertSame('completed', $settlement->iris_status);
        $this->assertSame('bca', $settlement->iris_bank_code);
        $this->assertSame('IRIS-REF-001', $settlement->reference_number);
        $this->assertNotNull($settlement->paid_at);
        $this->assertNotNull($settlement->iris_completed_at);
        $this->assertSame(route('settlements.payout-proof', $settlement), $settlement->proof_url);

        $this->assertDatabaseHas('vendor_invoices', [
            'settlement_id' => $settlement->id,
            'status' => 'paid',
        ]);

        $createRequest = collect(Http::recorded())
            ->first(fn (array $pair): bool => $pair[0]->method() === 'POST'
                && $pair[0]->url() === self::IRIS_BASE_URL.'/payouts');

        $this->assertNotNull($createRequest);
        $this->assertTrue($createRequest[0]->hasHeader(
            'Authorization',
            'Basic '.base64_encode('iris-creator-key:'),
        ));
        $this->assertTrue($createRequest[0]->hasHeader(
            'X-Idempotency-Key',
            'TDK-SETTLEMENT-'.$settlement->id,
        ));

        $payload = $createRequest[0]->data()['payouts'][0];

        $this->assertSame('Toko Iris', $payload['beneficiary_name']);
        $this->assertSame('1234567890', $payload['beneficiary_account']);
        $this->assertSame('bca', $payload['beneficiary_bank']);
        $this->assertSame('950000.00', $payload['amount']);
        $this->assertSame(
            'Settlement '.$settlement->id.' periode 20261019 20261025',
            $payload['notes'],
        );

        $approveRequest = collect(Http::recorded())
            ->first(fn (array $pair): bool => $pair[0]->url() === self::IRIS_BASE_URL.'/payouts/approve');

        $this->assertNotNull($approveRequest);
        $this->assertTrue($approveRequest[0]->hasHeader(
            'Authorization',
            'Basic '.base64_encode('iris-approver-key:'),
        ));
        $this->assertSame(['IRIS-REF-001'], $approveRequest[0]->data()['reference_nos']);
        $this->assertSame('123456', $approveRequest[0]->data()['otp']);
    }

    public function test_non_completed_iris_status_does_not_mark_the_batch_paid(): void
    {
        $settlement = $this->createApprovedSettlement();
        $irisStatus = 'processed';

        Http::fake([
            self::IRIS_BASE_URL.'/payouts' => Http::response([
                'payouts' => [['status' => 'queued', 'reference_no' => 'IRIS-REF-002']],
            ], 201),
            self::IRIS_BASE_URL.'/payouts/approve' => Http::response(['status' => 'ok'], 202),
            self::IRIS_BASE_URL.'/payouts/IRIS-REF-002' => function () use (&$irisStatus) {
                return Http::response([
                    'reference_no' => 'IRIS-REF-002',
                    'status' => $irisStatus,
                ]);
            },
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->call('startIrisPayout')
            ->assertSet('showIrisModal', false)
            ->assertSee('Diproses bank');

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame('processed', $settlement->iris_status);
        $this->assertNull($settlement->paid_at);
        $this->assertNull($settlement->proof_url);
        $this->assertSame('issued', $settlement->invoices()->firstOrFail()->status);

        $irisStatus = 'completed';

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('syncIrisPayout', $settlement->id)
            ->assertSee('sudah ditandai lunas');

        $settlement->refresh();

        $this->assertSame('paid', $settlement->status);
        $this->assertSame('completed', $settlement->iris_status);
        $this->assertSame('paid', $settlement->invoices()->firstOrFail()->status);

        $createRequests = collect(Http::recorded())
            ->filter(fn (array $pair): bool => $pair[0]->method() === 'POST'
                && $pair[0]->url() === self::IRIS_BASE_URL.'/payouts');

        $this->assertCount(1, $createRequests);
    }

    public function test_iris_create_failure_keeps_the_settlement_approved(): void
    {
        $settlement = $this->createApprovedSettlement();

        Http::fake([
            self::IRIS_BASE_URL.'/payouts' => Http::response([
                'error_message' => 'Saldo payout tidak mencukupi',
            ], 400),
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->call('startIrisPayout')
            ->assertSet('showIrisModal', true)
            ->assertSee('Saldo payout tidak mencukupi');

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame('failed', $settlement->iris_status);
        $this->assertNull($settlement->iris_reference_no);
        $this->assertNull($settlement->paid_at);
        $this->assertStringContainsString('Saldo payout tidak mencukupi', (string) $settlement->iris_error);
        $this->assertSame('issued', $settlement->invoices()->firstOrFail()->status);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_keeps_payout_ambiguous_and_blocks_manual_payment(): void
    {
        $settlement = $this->createApprovedSettlement();

        Http::fake([
            self::IRIS_BASE_URL.'/payouts' => fn () => throw new ConnectionException('timeout'),
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->call('startIrisPayout')
            ->assertSet('showIrisModal', true)
            ->assertSee('Koneksi ke Midtrans Iris gagal');

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame('unknown', $settlement->iris_status);
        $this->assertNull($settlement->iris_reference_no);
        $this->assertNull($settlement->paid_at);
        $this->assertTrue($settlement->hasActiveIrisPayout());
        $this->assertNotNull($settlement->iris_idempotency_key);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openPayModal', $settlement->id)
            ->assertSet('showPayModal', false)
            ->assertSee('masih berjalan');

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openSettlement', $settlement->id)
            ->assertSee('Belum terkonfirmasi')
            ->assertSee('Belum ditandai lunas');
    }

    public function test_approval_can_be_retried_with_otp_without_creating_a_second_payout(): void
    {
        $settlement = $this->createApprovedSettlement();
        $approveAttempts = 0;

        Http::fake([
            self::IRIS_BASE_URL.'/payouts' => Http::response([
                'payouts' => [['status' => 'queued', 'reference_no' => 'IRIS-REF-OTP']],
            ], 201),
            self::IRIS_BASE_URL.'/payouts/approve' => function (ClientRequest $request) use (&$approveAttempts) {
                $approveAttempts++;

                if ($approveAttempts === 1) {
                    return Http::response(['error_message' => 'OTP is required'], 400);
                }

                return Http::response(['status' => 'ok'], 202);
            },
            self::IRIS_BASE_URL.'/payouts/IRIS-REF-OTP' => Http::response([
                'reference_no' => 'IRIS-REF-OTP',
                'status' => 'completed',
            ]),
        ]);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->call('startIrisPayout')
            ->assertSet('showIrisModal', true);

        $this->assertStringContainsString('OTP is required', (string) $component->get('irisError'));

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame('queued', $settlement->iris_status);
        $this->assertSame('IRIS-REF-OTP', $settlement->iris_reference_no);
        $this->assertNull($settlement->paid_at);

        $component
            ->set('irisOtp', '654321')
            ->call('startIrisPayout')
            ->assertSet('showIrisModal', false)
            ->assertSee('sudah ditandai lunas');

        $settlement->refresh();

        $this->assertSame('paid', $settlement->status);
        $this->assertSame('completed', $settlement->iris_status);

        $createRequests = collect(Http::recorded())
            ->filter(fn (array $pair): bool => $pair[0]->method() === 'POST'
                && $pair[0]->url() === self::IRIS_BASE_URL.'/payouts');

        $this->assertCount(1, $createRequests);
        $this->assertSame(2, $approveAttempts);
    }

    public function test_missing_bank_details_do_not_send_an_iris_request(): void
    {
        $settlement = $this->createApprovedSettlement([
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->call('startIrisPayout')
            ->assertSet('showIrisModal', true)
            ->assertSee('Rekening tujuan vendor belum lengkap');

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertNull($settlement->iris_status);
        $this->assertNull($settlement->iris_idempotency_key);
        $this->assertNull($settlement->paid_at);
        Http::assertNothingSent();
    }

    public function test_manual_payment_is_blocked_while_an_iris_payout_is_active(): void
    {
        $settlement = $this->createApprovedSettlement([
            'iris_reference_no' => 'IRIS-REF-ACTIVE',
            'iris_status' => 'queued',
            'iris_idempotency_key' => 'TDK-SETTLEMENT-ACTIVE',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openPayModal', $settlement->id)
            ->assertSet('showPayModal', false)
            ->assertSee('masih berjalan');

        $this->assertSame('approved', $settlement->refresh()->status);
    }

    public function test_failed_iris_status_does_not_mark_the_batch_paid(): void
    {
        $settlement = $this->createApprovedSettlement();

        Http::fake([
            self::IRIS_BASE_URL.'/payouts' => Http::response([
                'payouts' => [['status' => 'queued', 'reference_no' => 'IRIS-REF-FAILED']],
            ], 201),
            self::IRIS_BASE_URL.'/payouts/approve' => Http::response(['status' => 'ok'], 202),
            self::IRIS_BASE_URL.'/payouts/IRIS-REF-FAILED' => Http::response([
                'reference_no' => 'IRIS-REF-FAILED',
                'status' => 'failed',
                'error_details' => ['error_message' => 'Rekening tujuan tidak valid'],
            ]),
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SettlementManager::class)
            ->call('openIrisModal', $settlement->id)
            ->call('startIrisPayout')
            ->assertSee('Rekening tujuan tidak valid');

        $settlement->refresh();

        $this->assertSame('approved', $settlement->status);
        $this->assertSame('failed', $settlement->iris_status);
        $this->assertSame('issued', $settlement->invoices()->firstOrFail()->status);
        $this->assertNull($settlement->paid_at);
    }

    public function test_payout_proof_is_visible_to_superadmin_and_the_owning_vendor_only(): void
    {
        $settlement = $this->createApprovedSettlement([
            'iris_reference_no' => 'IRIS-REF-PROOF',
            'iris_status' => 'completed',
            'iris_completed_at' => now(),
        ]);

        app(SettlementService::class)->markPaid(
            $settlement,
            $this->superAdmin,
            'IRIS-REF-PROOF',
            route('settlements.payout-proof', $settlement),
            'Payout Midtrans Iris sandbox',
        );

        $this->actingAs($this->superAdmin)
            ->get(route('settlements.payout-proof', $settlement))
            ->assertOk()
            ->assertSee('Bukti Pencairan Vendor')
            ->assertSee('IRIS-REF-PROOF');

        $owner = $this->createRevenueUser('vendor_owner', $this->vendor);

        $this->actingAs($owner)
            ->get(route('settlements.payout-proof', $settlement))
            ->assertOk()
            ->assertSee('IRIS-REF-PROOF');

        $otherVendor = $this->createRevenueVendor('Vendor Lain');
        $otherOwner = $this->createRevenueUser('vendor_owner', $otherVendor);

        $this->actingAs($otherOwner)
            ->get(route('settlements.payout-proof', $settlement))
            ->assertNotFound();

        $customer = $this->createRevenueUser('customer');

        $this->actingAs($customer)
            ->get(route('settlements.payout-proof', $settlement))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createApprovedSettlement(array $attributes = []): Settlement
    {
        [, $payment] = $this->createPaidRevenuePayment($this->vendor, 1000000, [
            'paid_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        $this->createRevenueCommission($payment, 50000, [
            'base_amount' => 1000000,
            'recognized_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        $settlement = app(SettlementService::class)
            ->buildWeeklyDraft(Carbon::parse('2026-10-19'))
            ->firstOrFail();

        app(SettlementService::class)->approve($settlement, $this->superAdmin);

        $settlement->update(array_merge([
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Toko Iris',
        ], $attributes));

        return $settlement->refresh();
    }
}

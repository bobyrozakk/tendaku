<?php

namespace Tests\Feature;

use App\Livewire\Customer\CatalogIndex;
use App\Livewire\Customer\ProductBooking;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\CartService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class CatalogStockConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));

        DB::purge();
        config(['database.connections' => ['sqlite' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]]);
        $this->assertSame('', DB::select('PRAGMA database_list')[0]->file);
        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-11-16 10:00:00'));
        $this->createMemorySchema();
    }

    #[TestWith([0])]
    #[TestWith([1])]
    #[TestWith([4])]
    public function test_pages_show_the_same_stock_and_real_product_vendor(int $units): void
    {
        $item = $this->createProduct($units);
        $label = $units > 0 ? "Stok: {$units} Unit" : 'Tidak tersedia pada periode ini';

        $this->get(route('home'))->assertOk()->assertSeeText($item->name)
            ->assertSeeText($item->vendor->name)->assertSeeText($label)
            ->assertSeeText('17 Nov 2026')->assertSeeText('18 Nov 2026');
        $this->get(route('customer.catalog.index'))->assertOk()->assertSeeText($label)
            ->assertSeeText('17 Nov 2026')->assertSeeText('18 Nov 2026');
        $this->get(route('customer.product.show', $item->id))->assertOk()->assertSeeText($label);
    }

    public function test_full_reservation_keeps_another_schedule_accessible(): void
    {
        $item = $this->createProduct(2);
        $this->reserve($item, 2);

        $this->get(route('home'))->assertSeeText('Tidak tersedia pada periode ini');
        $this->get(route('customer.catalog.index'))->assertSeeText('Tidak tersedia pada periode ini')
            ->assertSeeText('Pilih Jadwal Lain');
        $component = Livewire::test(ProductBooking::class, ['product' => $item])
            ->assertSet('availableStock', 0)->assertSeeText('Tidak tersedia pada periode ini')
            ->assertSee('wire:click="chooseSchedule"', false)
            ->assertDontSee('wire:model.live="pickup_date"', false)
            ->assertDontSee('wire:model.live="duration_days"', false);
        $this->assertActionDisabled($component->html(), 'btn-checkout', false);
        $component->call('chooseSchedule')->assertHasNoErrors()
            ->assertRedirect(route('checkout.index', ['direct' => 1]));

        $this->get(route('checkout.index', ['direct' => 1]))->assertOk()
            ->assertViewHas('booking', fn (array $booking): bool => $booking['items'][0]['stock'] === 0
                && $booking['items'][0]['max_qty'] === 2 && $booking['items'][0]['qty'] === 1
                && in_array('2026-11-17', $booking['booked_dates'], true));
        $this->get(route('checkout.index', ['direct' => 1, 'pickup_date' => '2026-11-20', 'return_date' => '2026-11-22']))
            ->assertOk()->assertViewHas('booking', fn (array $booking): bool => $booking['items'][0]['stock'] === 2
                && $booking['pickup_date_iso'] === '2026-11-20' && $booking['rental_days'] === 2);
        $this->assertSame('2026-11-20', session(CartService::DIRECT_SESSION_KEY.'.pickup_date'));
        $this->assertSame('2026-11-22', session(CartService::DIRECT_SESSION_KEY.'.return_date'));
        $this->assertDatabaseCount('rentals', 1);
    }

    public function test_filtered_dates_survive_navigation_to_detail_and_checkout(): void
    {
        $item = $this->createProduct(1);
        $this->reserve($item, 1);
        $dates = ['start_date' => '2026-11-22', 'end_date' => '2026-11-25'];
        $detailUrl = route('customer.product.show', ['id' => $item->id] + $dates);

        $this->get(route('home', $dates))->assertSee($detailUrl)->assertSeeText('Stok: 1 Unit');
        $this->get(route('customer.catalog.index', $dates))->assertSee($detailUrl)->assertSeeText('Stok: 1 Unit');
        $this->get($detailUrl)->assertOk()->assertSeeText('Stok: 1 Unit');
        Livewire::test(ProductBooking::class, ['product' => $item, 'startDate' => $dates['start_date'], 'endDate' => $dates['end_date']])
            ->assertSet('duration_days', 3)->assertSet('availableStock', 1)
            ->call('checkoutNow')->assertHasNoErrors();
        $this->assertSame('2026-11-22', session(CartService::DIRECT_SESSION_KEY.'.pickup_date'));
        $this->assertSame('2026-11-25', session(CartService::DIRECT_SESSION_KEY.'.return_date'));
    }

    public function test_available_filter_excludes_reservations_before_pagination_and_isolates_vendors(): void
    {
        $available = $this->createProduct(1);
        $unavailable = $this->createProduct(2);
        $this->reserve($unavailable, 2);
        for ($index = 0; $index < 12; $index++) {
            $this->createProduct(0);
        }

        $component = Livewire::test(CatalogIndex::class)->set('onlyAvailable', true)
            ->assertViewHas('items', fn ($items): bool => $items->total() === 1 && $items->first()->id === $available->id)
            ->assertSeeText($available->name)->assertDontSeeText($unavailable->name);
        $component->set('startDate', '2026-11-22')->set('endDate', '2026-11-24')
            ->assertViewHas('items', fn ($items): bool => $items->total() === 2)
            ->assertSeeText($available->name)->assertSeeText($unavailable->name);
        $this->get(route('customer.product.show', $available->id))->assertSeeText('Stok: 1 Unit');
        Livewire::test(CatalogIndex::class)->set('vendorId', $unavailable->vendor_id)
            ->set('onlyAvailable', true)->assertViewHas('items', fn ($items): bool => $items->total() === 0);
    }

    public function test_catalog_and_detail_cart_actions_use_the_displayed_dates_and_stock(): void
    {
        $item = $this->createProduct(1);
        $this->reserve($item, 1);
        $customer = User::factory()->create();
        $this->actingAs($customer);

        Livewire::test(CatalogIndex::class)->call('addToCart', $item->id)
            ->assertDispatched('notify', fn ($event, $parameters): bool => ($parameters['type'] ?? $parameters[0]['type'] ?? null) === 'error');
        $this->assertNull(session(CartService::SESSION_KEY));
        Livewire::test(CatalogIndex::class)->set('startDate', '2026-11-22')->set('endDate', '2026-11-24')
            ->call('addToCart', $item->id)->assertHasNoErrors();
        $this->assertSame('2026-11-22', $customer->fresh()->cart['pickup_date']);
        $this->assertSame('2026-11-24', $customer->fresh()->cart['return_date']);
        $this->assertSame($item->vendor_id, $customer->fresh()->cart['vendor_id']);

        $component = Livewire::test(ProductBooking::class, ['product' => $item])->assertSet('availableStock', 0)
            ->assertSee('wire:click="chooseSchedule"', false);
        $this->assertActionDisabled($component->html(), 'btn-masuk-keranjang', true);
        $this->assertActionDisabled($component->html(), 'btn-sewa-sekarang', false);
        $savedCart = $customer->fresh()->cart;
        $component->call('chooseSchedule')->assertHasNoErrors()->assertRedirect(route('checkout.index', ['direct' => 1]));
        $this->assertSame($savedCart, $customer->fresh()->cart);
        $this->assertDatabaseCount('rentals', 1);
        $component->call('addToCart')->assertHasErrors('quantity');
        $component->set('pickup_date', '2026-11-22')->set('duration_days', 2)->call('addToCart')->assertHasNoErrors();
        $this->assertSame('2026-11-22', $customer->fresh()->cart['pickup_date']);
        $this->assertSame('2026-11-24', $customer->fresh()->cart['return_date']);
    }

    public function test_check_failures_block_cart_but_allow_schedule_selection(): void
    {
        $item = $this->createProduct(2);
        Exceptions::fake();
        $exception = new RuntimeException('Availability connection failed');
        $this->partialMock(AvailabilityService::class)->shouldReceive('checkAvailability')->andThrow($exception);

        $this->get(route('home'))->assertSeeText('Ketersediaan belum dapat diperiksa')->assertDontSeeText('Stok: 2 Unit');
        $this->get(route('customer.catalog.index'))->assertSeeText('Ketersediaan belum dapat diperiksa')
            ->assertSeeText('Pilih Jadwal Lain');
        Livewire::test(CatalogIndex::class)->set('onlyAvailable', true)
            ->assertSeeText('Ketersediaan belum dapat diperiksa')
            ->assertViewHas('items', fn ($items): bool => $items->total() === 0);
        $this->get(route('customer.product.show', $item->id))->assertOk()->assertSeeText('Ketersediaan belum dapat diperiksa');
        Livewire::test(ProductBooking::class, ['product' => $item])->assertSet('availableStock', null)
            ->call('checkoutNow')->assertHasErrors('quantity');
        $this->assertNull(session(CartService::DIRECT_SESSION_KEY));
        Livewire::test(ProductBooking::class, ['product' => $item])->call('chooseSchedule')
            ->assertHasNoErrors()->assertRedirect(route('checkout.index', ['direct' => 1]));
        $this->get(route('checkout.index', ['direct' => 1]))->assertOk()
            ->assertSeeText('Ketersediaan belum dapat diperiksa')
            ->assertViewHas('booking', fn (array $booking): bool => $booking['items'][0]['stock'] === null);
        app(CartService::class)->clearDirect();
        $this->actingAs(User::factory()->create());
        Livewire::test(CatalogIndex::class)->call('addToCart', $item->id);
        $this->assertNull(session(CartService::SESSION_KEY));
        Livewire::test(ProductBooking::class, ['product' => $item])->call('addToCart')->assertHasErrors('quantity');
        $this->assertNull(session(CartService::SESSION_KEY));
        Livewire::test(ProductBooking::class, ['product' => $item])->call('chooseSchedule')
            ->assertHasNoErrors()->assertRedirect(route('checkout.index', ['direct' => 1]));
        $this->assertNull(session(CartService::SESSION_KEY));
        $this->assertDatabaseCount('rentals', 0);
        Exceptions::assertReported(RuntimeException::class);
    }

    #[TestWith(['2026-11-15', '2026-11-17'])]
    #[TestWith(['2026-11-20', '2026-11-19'])]
    #[TestWith(['2026-02-30', '2026-11-19'])]
    #[TestWith(['invalid', '2026-11-19'])]
    #[TestWith(['2026-11-20', ''])]
    #[TestWith(['0', ''])]
    public function test_invalid_dates_never_fall_back_to_available_stock(string $start, string $end): void
    {
        $item = $this->createProduct(1);
        $dates = ['start_date' => $start, 'end_date' => $end];

        $this->get(route('home', $dates))->assertSeeText('Ketersediaan belum dapat diperiksa');
        $this->get(route('customer.catalog.index', $dates))->assertSeeText('Ketersediaan belum dapat diperiksa');
        $this->get(route('customer.product.show', ['id' => $item->id] + $dates))->assertSeeText('Ketersediaan belum dapat diperiksa');
    }

    public function test_invalid_reference_dates_can_be_replaced_on_the_booking_page(): void
    {
        $item = $this->createProduct(1);

        Livewire::test(ProductBooking::class, ['product' => $item, 'startDate' => 'invalid', 'endDate' => '2026-11-19'])
            ->assertSet('availableStock', null)->call('chooseSchedule')->assertHasNoErrors()
            ->assertRedirect(route('checkout.index', ['direct' => 1]));

        $this->assertSame('2026-11-17', session(CartService::DIRECT_SESSION_KEY.'.pickup_date'));
        $this->assertSame('2026-11-18', session(CartService::DIRECT_SESSION_KEY.'.return_date'));
        $this->get(route('checkout.index', ['direct' => 1]))->assertOk();
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_schedule_selection_preserves_a_member_cart_from_another_vendor(): void
    {
        $item = $this->createProduct(1);
        $this->reserve($item, 1);
        $otherItem = $this->createProduct(1);
        $customer = User::factory()->create();
        $this->actingAs($customer);
        Livewire::test(CatalogIndex::class)->call('addToCart', $otherItem->id);
        $savedCart = $customer->fresh()->cart;

        Livewire::test(ProductBooking::class, ['product' => $item])->call('chooseSchedule')
            ->assertHasNoErrors()->assertRedirect(route('checkout.index', ['direct' => 1]));

        $this->assertSame($savedCart, $customer->fresh()->cart);
        $this->assertSame($item->vendor_id, session(CartService::DIRECT_SESSION_KEY.'.vendor_id'));
        $this->assertSame($item->id, session(CartService::DIRECT_SESSION_KEY.'.master_item_id'));
        $this->assertDatabaseCount('rentals', 1);
    }

    public function test_schedule_selection_normalizes_quantity_without_reserving_stock(): void
    {
        $item = $this->createProduct(0);

        Livewire::test(ProductBooking::class, ['product' => $item])->set('quantity', 0)
            ->assertSet('quantity', 1)->call('chooseSchedule')->assertHasNoErrors();

        $this->assertSame(1, session(CartService::DIRECT_SESSION_KEY.'.quantity'));
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_unrentable_units_and_inactive_vendors_are_not_advertised_as_available(): void
    {
        $item = $this->createProduct(1);
        $item->units()->first()->update(['condition' => 'damaged']);
        $hidden = $this->createProduct(1);
        $hidden->vendor->update(['status' => 'suspended']);

        $this->get(route('home'))->assertSeeText('Tidak tersedia pada periode ini')->assertDontSeeText($hidden->name);
        $this->get(route('customer.catalog.index'))->assertSeeText('Tidak tersedia pada periode ini')->assertDontSeeText($hidden->name);
        $this->get(route('customer.product.show', $item->id))->assertSeeText('Tidak tersedia pada periode ini');
        $this->get(route('customer.product.show', $hidden->id))->assertNotFound();
    }

    private function createProduct(int $units): MasterItem
    {
        $vendor = Vendor::factory()->create();
        $category = ItemCategory::create(['vendor_id' => $vendor->id, 'name' => 'Tenda', 'slug' => 'tenda']);
        $item = MasterItem::create([
            'vendor_id' => $vendor->id, 'category_id' => $category->id,
            'name' => 'Tenda Vendor '.$vendor->id, 'slug' => 'tenda',
            'daily_rate' => 50000, 'is_active' => true,
        ]);
        for ($index = 0; $index < $units; $index++) {
            ItemUnit::create([
                'vendor_id' => $vendor->id, 'master_item_id' => $item->id,
                'unit_code' => 'UNIT-'.$index, 'status' => 'available', 'condition' => 'good',
            ]);
        }

        return $item;
    }

    private function reserve(MasterItem $item, int $quantity): void
    {
        $rental = Rental::factory()->create([
            'vendor_id' => $item->vendor_id, 'status' => 'confirmed',
            'pickup_date' => '2026-11-17', 'return_date' => '2026-11-18',
        ]);
        for ($index = 0; $index < $quantity; $index++) {
            RentalDetail::create([
                'rental_id' => $rental->id, 'master_item_id' => $item->id,
                'item_unit_id' => null, 'daily_rate_snapshot' => 50000, 'is_returned' => false,
            ]);
        }
    }

    private function assertActionDisabled(string $html, string $id, bool $disabled): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $button = $document->getElementById($id);
        $this->assertNotNull($button);
        $this->assertSame($disabled, $button->hasAttribute('disabled'));
    }

    /** Only an isolated in-memory schema is created; no migration or seeder is executed. */
    private function createMemorySchema(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            foreach (['name', 'slug', 'description', 'phone', 'email', 'address', 'city', 'logo_url', 'status'] as $column) {
                $table->string($column)->nullable();
            }
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->decimal('commission_rate')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->nullable();
            foreach (['name', 'email', 'phone', 'password', 'remember_token', 'role'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('cart')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('item_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('master_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('daily_rate');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('item_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('master_item_id');
            $table->string('unit_code');
            $table->string('status');
            $table->string('condition');
            $table->date('purchase_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('master_item_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('master_item_id');
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('rentals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('booking_code');
            $table->date('pickup_date');
            $table->date('return_date');
            $table->integer('rental_days');
            $table->decimal('subtotal');
            $table->decimal('grand_total');
            $table->string('status');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('rental_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rental_id');
            $table->unsignedBigInteger('master_item_id');
            $table->unsignedBigInteger('item_unit_id')->nullable();
            $table->decimal('daily_rate_snapshot');
            $table->boolean('is_returned')->default(false);
            $table->timestamps();
        });
    }
}

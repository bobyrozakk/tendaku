import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = ['components/location-picker.js', 'components/checkout-address.js', 'pages/customer/package-checkout.js']
    .map(path => fs.readFileSync(new URL('../../resources/js/' + path, import.meta.url), 'utf8')
        .replace(/^import .+;\r?\n/gm, '').replace(/^export /gm, '')).join('\n');
function page(fetch) {
    let factory;
    let timerId = 0;
    const timers = new Map();
    const watches = {};
    const redirects = [];
    vm.runInNewContext(source, {
        document: { addEventListener: (_, callback) => callback() },
        Alpine: { data: (_, callback) => { factory = callback; } },
        AbortController, fetch, URL,
        CustomEvent: class { constructor(type, options = {}) { this.type = type; this.detail = options.detail; } },
        FormData: class extends FormData { constructor() { super(); this.set('package_id', '999'); } },
        setTimeout: (callback, delay = 0) => { timers.set(++timerId, { callback, delay }); return timerId; },
        clearTimeout: (id) => timers.delete(id),
        window: {
            location: { origin: 'https://tendaku.test', set href(url) { redirects.push(url); } },
            matchMedia: () => ({ matches: true }),
            scrollTo() {},
            dispatchEvent() {},
        },
    });
    const component = factory({ packageId: 1, packagePrice: 300000, pickupMethod: 'delivery',
        pickupDate: '2090-01-01', pickupTime: '09:00', returnDate: '2090-01-03', returnTime: '09:00',
        durationDays: 2, subtotal: 600000, totalHours: 48, checkoutAttemptId: 'attempt-1',
        addresses: [{ id: 1, latitude: -7.9, longitude: 112.6 }, { id: 2, latitude: -7.8, longitude: 112.7 }], selectedAddressId: 1,
        shippingPreviewUrl: '/preview', storeAddressUrl: '/addresses', updateAddressUrl: '/addresses/__ADDRESS__', storeUrl: '/store', csrf: 'token', packageItems: [] });
    component.$nextTick = () => {};
    component.$watch = (key, callback) => { watches[key] = callback; };
    return {
        component, watches, redirects,
        run: async () => {
            const pending = [...timers.entries()].filter(([, timer]) => timer.delay < 12000);
            for (const [id] of pending) timers.delete(id);
            await Promise.all(pending.map(([, timer]) => timer.callback()));
        },
    };
}
function quote(overrides = {}) {
    return { success: true, package_id: 1, package_name: 'Paket Camping', package_price: 300000,
        items: [{ id: 10, name: 'Tenda', qty: 2 }, { id: 11, name: 'Kompor', qty: 3 }],
        total_weight_kg: 11, distance_km: 12.34, delivery_fee: 30680, subtotal: 600000,
        grand_total: 630680, rental_days: 2, total_hours: 48, shipping_context: 'a'.repeat(64), ...overrides };
}
function response(data, status = 200) {
    return { ok: status === 200, status, headers: { get: () => null }, json: async () => data };
}

test('delivery without address stays incomplete and cannot submit with a false zero total', () => {
    const { component } = page(() => { throw new Error('Unexpected request'); });
    component.selectedAddressId = null;
    component.scheduleShipping();
    assert.equal(component.shippingState, 'incomplete');
    assert.equal(component.deliveryFee, null);
    assert.equal(component.grandTotal, null);
    assert.equal(component.canSubmitBooking, false);
});

test('preview sends package identity and schedule and displays server weight and grand total', async () => {
    let payload;
    const { component, run } = page(async (url, options) => { assert.equal(url, '/preview'); payload = JSON.parse(options.body); return response(quote()); });
    component.init();
    await run();
    assert.equal(payload.checkout_type, 'package');
    assert.equal(payload.package_id, 1);
    assert.equal(payload.return_date, '2090-01-03');
    assert.equal('weight_kg' in payload, false);
    assert.equal(component.shipping.total_weight_kg, 11);
    assert.equal(component.grandTotal, 630680);
    assert.equal(component.canSubmitBooking, true);
});

test('older address responses are ignored even when abort is not honored by the transport', async () => {
    const pending = [];
    const { component, run } = page(() => new Promise((resolve) => pending.push(resolve)));
    component.scheduleShipping();
    const old = run();
    const oldController = component.shippingController;
    component.selectedAddressId = 2;
    component.scheduleShipping();
    const current = run();
    assert.equal(oldController.signal.aborted, true);
    pending[1](response(quote({ delivery_fee: 46000 })));
    await current;
    pending[0](response(quote({ delivery_fee: 30680 })));
    await old;
    assert.equal(component.deliveryFee, 46000);
});

test('new address saving selects it and the next preview uses its id', async () => {
    let payload;
    const { component, watches, run } = page(async (url, options) => {
        if (url === '/addresses') return response({ data: { ...JSON.parse(options.body), id: 7, is_default: true } });
        payload = JSON.parse(options.body);
        return response(quote());
    });
    component.init();
    component.showAddAddressModal = true;
    component.newAddressForm.phone_number = '081234567890';
    component.syncNewAddressCoordinates({ pickerId: component.addressPickerId, latitude: -7.8, longitude: 112.7 });
    await component.saveAddress();
    assert.equal(component.selectedAddressId, 7);
    assert.equal(component.showAddAddressModal, false);
    assert.equal(component.newAddressForm.latitude, null);
    watches.shippingKey();
    await run();
    assert.equal(payload.customer_address_id, 7);
});

test('pickup has zero shipping and no fictional distance', async () => {
    const { component, run } = page(async () => response(quote({ delivery_fee: 0, distance_km: null, grand_total: 600000 })));
    component.pickupMethod = 'self_pickup';
    component.scheduleShipping();
    await run();
    assert.equal(component.deliveryFee, 0);
    assert.equal(component.shipping.distance_km, null);
    assert.equal(component.grandTotal, 600000);
});

test('duration updates subtotal and invalidates preview without multiplying package weight', async () => {
    const { component, run } = page(async () => response(quote({ rental_days: 3, total_hours: 72, subtotal: 900000, grand_total: 930680 })));
    component.applyShipping(quote(), component.shippingKey);
    component.setDuration(3);
    assert.equal(component.subtotal, 900000);
    assert.equal(component.canSubmitBooking, false);
    component.scheduleShipping();
    await run();
    assert.equal(component.shipping.total_weight_kg, 11);
    assert.equal(component.grandTotal, 930680);
});

test('failed preview blocks booking and keeps delivery fee unknown', async () => {
    const { component, run } = page(async () => response({ success: false, message: 'Layanan jarak gagal' }, 503));
    component.scheduleShipping();
    await run();
    assert.equal(component.shippingState, 'loading');
    await run();
    assert.equal(component.shippingState, 'error');
    assert.equal(component.deliveryFee, null);
    assert.equal(component.canSubmitBooking, false);
});

test('409 refreshes package details and requires explicit approval before resubmit', async () => {
    const calls = [];
    const latest = quote({ package_price: 400000, subtotal: 800000, grand_total: 830680, shipping_context: 'b'.repeat(64), items: [{ id: 10, name: 'Tenda Baru', qty: 3 }] });
    const { component, redirects, run } = page(async (_, options) => {
        calls.push(options.body);
        return calls.length === 1 ? response({ message: 'Rincian berubah', shipping: latest }, 409) : response({ redirect_url: '/status/BOOKING' });
    });
    component.applyShipping(quote(), component.shippingKey);
    await component.submitBooking();
    assert.equal(component.grandTotal, 830680);
    assert.equal(component.packageItems[0].name, 'Tenda Baru');
    assert.equal(component.canSubmitBooking, false);
    await component.submitBooking();
    assert.equal(calls.length, 1);
    component.approveShippingChange();
    const submitting = component.submitBooking();
    await new Promise((resolve) => setImmediate(resolve));
    await run();
    await submitting;
    assert.equal(calls.length, 2);
    assert.equal(calls[1].get('expected_shipping_context'), 'b'.repeat(64));
    assert.equal(calls[1].get('package_id'), '1');
    assert.equal(calls[1].get('checkout_attempt_id'), 'attempt-1');
    assert.deepEqual(redirects, ['https://tendaku.test/status/BOOKING?new=1']);
});

test('repeated clicks while booking is pending send only one request', async () => {
    let calls = 0;
    let finish;
    const { component, run } = page(() => { calls++; return new Promise((resolve) => { finish = resolve; }); });
    component.applyShipping(quote(), component.shippingKey);
    const first = component.submitBooking();
    await component.submitBooking();
    assert.equal(calls, 1);
    finish(response({ redirect_url: '/status/BOOKING' }));
    await new Promise((resolve) => setImmediate(resolve));
    await run();
    await first;
});

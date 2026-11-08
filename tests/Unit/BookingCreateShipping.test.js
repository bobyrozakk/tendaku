import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../resources/js/pages/customer/booking-create.js', import.meta.url), 'utf8');
function page(fetch) {
    let factory;
    const timers = new Map();
    let nextTimer = 0;
    vm.runInNewContext(source, {
        document: { addEventListener: (_, callback) => callback() },
        Alpine: { data: (_, callback) => { factory = callback; } },
        AbortController, FormData, fetch,
        setTimeout: (callback) => { timers.set(++nextTimer, callback); return nextTimer; },
        clearTimeout: (id) => timers.delete(id), clearInterval() {},
        window: {},
    });
    const component = factory({
        items: [{ id: 1, qty: 2, max_qty: 4, price_per_day: 50000 }],
        pickupMethod: 'delivery', selectedAddressId: 1, addresses: [{ id: 1 }], isLoggedIn: true,
        pickupDate: '2090-01-01', returnDate: '2090-01-02', pickupTime: '09:00',
        minDate: '2026-01-01', initialDays: 1, discount: 0, dpPercentage: 50,
        shippingPreviewUrl: '/preview', storeUrl: '/store', csrf: 'token',
        needsName: false, needsPhone: false, needsNik: false,
    });
    component.$nextTick = () => {};
    component.$watch = (_, callback) => { component.inputChanged = callback; };
    component.scrollToError = () => {};
    component.playHandoff = async () => {};
    return { component, run: async () => { const pending = [...timers.values()]; timers.clear(); for (const callback of pending) await callback(); } };
}
function quote(overrides = {}) {
    return { success: true, status: 'ready', pickup_method: 'delivery', distance_km: 12.34,
        total_weight_kg: 6.5, delivery_fee: 26680, subtotal: 100000, grand_total: 126680,
        shipping_context: 'a'.repeat(64), items: [{ id: 1, qty: 2, price_per_day: 50000 }], ...overrides };
}
function response(data, status = 200) {
    return { ok: status === 200, status, headers: { get: () => 'application/json' }, json: async () => data };
}

test('delivery without address stays incomplete with unknown fee and disabled submit', () => {
    const { component } = page(() => { throw new Error('must not fetch'); });
    component.selectedAddressId = null;
    component.scheduleShipping();
    assert.equal(component.shippingState, 'incomplete');
    assert.equal(component.deliveryFee, null);
    assert.equal(component.grandTotal, null);
    assert.equal(component.canSubmitBooking, false);
    assert.equal(component.formatRupiah(null), '\u2014');
});

test('quantity and address changes invalidate the total immediately and debounce into one current request', async () => {
    const calls = [];
    const { component, run } = page(async (_, options) => { calls.push(JSON.parse(options.body)); return response(quote({ total_weight_kg: 9.75 })); });
    component.init();
    component.items[0].qty = 3;
    component.selectedAddressId = 2;
    assert.equal(component.canSubmitBooking, false);
    component.inputChanged();
    await run();
    assert.equal(calls.length, 1);
    assert.equal(calls[0].items[0].qty, 3);
    assert.equal(calls[0].customer_address_id, 2);
    assert.equal(component.totalWeightKg, 9.75);
    assert.equal(component.canSubmitBooking, true);
});

test('an older response cannot overwrite the newly selected address even if abort is ignored', async () => {
    const pending = [];
    const { component, run } = page(() => new Promise((resolve) => pending.push(resolve)));
    component.scheduleShipping();
    const oldRequest = run();
    const oldController = component.shippingController;
    component.selectedAddressId = 2;
    component.scheduleShipping();
    assert.equal(oldController.signal.aborted, true);
    const currentRequest = run();
    pending[1](response(quote({ delivery_fee: 40000 })));
    await currentRequest;
    pending[0](response(quote({ delivery_fee: 1000 })));
    await oldRequest;
    assert.equal(component.deliveryFee, 40000);
});

test('preview errors never become zero fee or a final total', async () => {
    const { component, run } = page(async () => response({ success: false, message: 'Rute tidak tersedia' }, 503));
    component.scheduleShipping();
    await run();
    assert.equal(component.shippingState, 'error');
    assert.equal(component.shippingMessage, 'Rute tidak tersedia');
    assert.equal(component.deliveryFee, null);
    assert.equal(component.grandTotal, null);
    assert.equal(component.canSubmitBooking, false);
});

test('switching to pickup clears delivery distance and uses zero shipping', async () => {
    const { component, run } = page(async () => response(quote({ pickup_method: 'self_pickup', distance_km: null, delivery_fee: 0 })));
    component.applyShipping(quote(), component.shippingInputKey);
    component.pickupMethod = 'self_pickup';
    component.scheduleShipping();
    await run();
    assert.equal(component.deliveryFee, 0);
    assert.equal(component.shipping.distance_km, null);
    assert.equal(component.grandTotal, 100000);
});

test('409 updates total but requires explicit approval and a separate submit', async () => {
    const calls = [];
    const latest = quote({ delivery_fee: 39020, shipping_context: 'b'.repeat(64) });
    const { component } = page(async (_, options) => {
        calls.push(options.body);
        return calls.length === 1 ? response({ message: 'Total berubah', shipping: latest }, 409) : response({ redirect_url: '/status' });
    });
    component.applyShipping(quote(), component.shippingInputKey);
    await component.submitBooking();
    assert.equal(component.deliveryFee, 39020);
    assert.equal(component.grandTotal, 139020);
    assert.equal(component.canSubmitBooking, false);
    await component.submitBooking();
    assert.equal(calls.length, 1);
    component.approveShippingChange();
    await component.submitBooking();
    assert.equal(calls.length, 2);
    assert.equal(calls[1].get('expected_delivery_fee'), '39020');
    assert.equal(calls[1].get('expected_shipping_context'), 'b'.repeat(64));
});

test('repeated clicks while submit is pending send only one booking request', async () => {
    let calls = 0;
    let finish;
    const { component } = page(() => { calls++; return new Promise((resolve) => { finish = resolve; }); });
    component.applyShipping(quote(), component.shippingInputKey);
    const first = component.submitBooking();
    await component.submitBooking();
    assert.equal(calls, 1);
    finish(response({ redirect_url: '/status' }));
    await first;
});

test('a changed product set requires reload instead of accepting unseen products', () => {
    const { component } = page(() => {});
    component.applyShipping(quote({ items: [{ id: 2, qty: 1, price_per_day: 10000 }] }), component.shippingInputKey);
    assert.equal(component.shippingState, 'error');
    assert.equal(component.shippingReloadRequired, true);
    assert.equal(component.canSubmitBooking, false);
});


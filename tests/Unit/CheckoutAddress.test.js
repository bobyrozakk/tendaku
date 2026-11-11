import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

function page(kind, fetch) {
    let factory;
    let timerId = 0;
    const timers = new Map();
    const pickerRefreshes = [];
    const window = { addEventListener() {}, refreshLocationPicker: (...args) => pickerRefreshes.push(args) };
    const context = {
        document: { addEventListener: (_, callback) => callback() },
        Alpine: { data: (_, callback) => { factory = callback; } },
        AbortController, FormData, fetch, window,
        setTimeout: callback => { timers.set(++timerId, callback); return timerId; },
        clearTimeout: id => timers.delete(id), clearInterval() {},
    };
    const source = ['components/location-picker.js', 'components/checkout-address.js', `pages/customer/${kind === 'package' ? 'package-checkout' : 'booking-create'}.js`]
        .map(path => fs.readFileSync(new URL('../../resources/js/' + path, import.meta.url), 'utf8')
            .replace(/^import .+;\r?\n/gm, '').replace(/^export /gm, '')).join('\n');
    vm.runInNewContext(source, context);
    window.refreshLocationPicker = (...args) => pickerRefreshes.push(args);
    window.resetLocationPicker = () => {};
    const legacy = {
        id: 7, label: 'Rumah Lama', recipient_name: 'Budi', phone_number: '081234567890',
        full_address: 'Jl. Lama No. 7', province: 'Jawa Timur', city: 'Malang', district: 'Klojen',
        village: 'Klojen', postal_code: '65111', notes: 'Pagar hitam', is_default: true,
        latitude: null, longitude: null,
    };
    const component = factory({
        ...(kind === 'package' ? { method: 'delivery' } : { pickupMethod: 'delivery' }),
        addresses: [legacy], selectedAddressId: 7, isLoggedIn: true,
        items: [{ id: 1, qty: 1, price_per_day: 50000 }], packageItems: [],
        packageId: 4, packagePrice: 50000, subtotal: 50000, durationDays: 1, initialDays: 1, discount: 0,
        pickupDate: '2090-01-01', pickupTime: '09:00', returnDate: '2090-01-02', returnTime: '09:00',
        shippingPreviewUrl: '/preview', storeAddressUrl: '/addresses', updateAddressUrl: '/addresses/__ADDRESS__', csrf: 'token',
    });
    component.$nextTick = callback => callback();
    component.$watch = () => {};
    return {
        component, legacy, pickerRefreshes,
        canSubmit: () => kind === 'package' ? component.canSubmit : component.canSubmitBooking,
        run: async () => {
            const pending = [...timers.values()]; timers.clear();
            for (const callback of pending) await callback();
        },
        createPicker: () => vm.runInNewContext('createLocationPickerState({ pickerId: "picker" })', context),
    };
}

const response = (data, status = 200) => ({ ok: status === 200, json: async () => data });
const quote = (fee = 20000) => ({
    success: true, package_id: 4, package_name: 'Paket', package_price: 50000,
    items: [{ id: 1, qty: 1, price_per_day: 50000 }], rental_days: 1, total_hours: 24,
    subtotal: 50000, delivery_fee: fee, grand_total: 50000 + fee, shipping_context: 'a'.repeat(64),
});

for (const kind of ['regular', 'package']) {
    test(`${kind}: default legacy address blocks delivery without requesting shipping or showing a zero fee`, async () => {
        const { component, canSubmit, run } = page(kind, () => assert.fail('must not fetch'));
        component.init();
        await run();
        assert.equal(component.needsAddressLocation(), true);
        assert.equal(component.shippingState, 'incomplete');
        assert.equal(component.deliveryFee, null);
        assert.equal(component.grandTotal, null);
        assert.equal(canSubmit(), false);
    });

    test(`${kind}: editing prefills the old address without mutating it and clears an unselected map`, () => {
        const { component, legacy, pickerRefreshes } = page(kind, () => {});
        component.openEditAddressModal();
        assert.equal(component.editingAddressId, 7);
        assert.equal(component.newAddressForm.full_address, legacy.full_address);
        assert.equal(component.newAddressForm.district, 'Klojen');
        assert.equal(component.newAddressForm.notes, 'Pagar hitam');
        assert.equal(component.newAddressForm.is_default, true);
        assert.equal(pickerRefreshes[0][1].latitude, null);
        component.newAddressForm.full_address = 'Alamat belum disimpan';
        component.closeAddAddressModal();
        assert.equal(legacy.full_address, 'Jl. Lama No. 7');
        assert.equal(legacy.latitude, null);
    });

    test(`${kind}: updating the same address id saves manual changes and requests a fresh shipping preview`, async () => {
        const calls = [];
        const { component, canSubmit, run } = page(kind, async (url, options) => {
            calls.push({ url, method: options.method, body: JSON.parse(options.body) });
            return url === '/addresses/7'
                ? response({ data: { ...JSON.parse(options.body), id: 7, formatted_address: 'Jl. Baru No. 8', delivery_snapshot: 'Budi - Jl. Baru No. 8' } })
                : response(quote());
        });
        component.init();
        component.openEditAddressModal();
        component.syncNewAddressCoordinates({ pickerId: component.addressPickerId, latitude: -7.95, longitude: 112.65 });
        component.newAddressForm.full_address = 'Jl. Baru No. 8';
        await component.saveAddress();
        assert.equal(component.selectedAddressId, 7);
        assert.equal(component.addresses.length, 1);
        assert.equal(component.addresses[0].full_address, 'Jl. Baru No. 8');
        assert.equal(component.addresses[0].latitude, -7.95);
        assert.equal(component.shippingState, 'loading');
        assert.equal(canSubmit(), false);
        await run();
        assert.equal(calls.length, 2);
        assert.equal(calls[0].method, 'PUT');
        assert.equal(calls[0].url, '/addresses/7');
        assert.equal(calls[0].body.is_default, true);
        assert.equal(calls[1].url, '/preview');
        assert.equal(calls[1].body.customer_address_id, 7);
        assert.equal(component.deliveryFee, 20000);
        assert.equal(component.grandTotal, 70000);
        assert.equal(canSubmit(), true);
        if (kind === 'regular') assert.equal(component.address, 'Budi - Jl. Baru No. 8');
    });

    test(`${kind}: missing or invalid coordinate pairs cannot save delivery, while zero coordinates are valid`, async () => {
        const { component } = page(kind, () => assert.fail('must not save invalid coordinates'));
        component.openEditAddressModal();
        for (const [latitude, longitude] of [[null, null], [-7.9, null], ['', 112.6], [91, 112.6], [-7.9, 181], [true, 112.6]]) {
            component.newAddressForm.latitude = latitude;
            component.newAddressForm.longitude = longitude;
            await component.saveAddress();
            assert.ok(component.newAddressErrors.latitude);
            assert.equal(component.showAddAddressModal, true);
        }
        assert.equal(component.addressHasCoordinates({ latitude: 0, longitude: 0 }), true);
    });

    test(`${kind}: denied or invalid updates keep the original address and modal for retry`, async () => {
        let status = 403;
        const { component, run } = page(kind, async () => response(
            status === 403 ? { message: 'Anda tidak memiliki hak akses ke alamat ini.' } : { errors: { full_address: ['Alamat wajib diisi.'] } }, status,
        ));
        component.openEditAddressModal();
        component.newAddressForm.latitude = -7.9;
        component.newAddressForm.longitude = 112.6;
        await component.saveAddress();
        assert.match(component.newAddressErrors.general, /hak akses/);
        assert.equal(component.addresses[0].latitude, null);
        assert.equal(component.showAddAddressModal, true);
        assert.equal(component.newAddressSubmitting, false);
        status = 422;
        await component.saveAddress();
        assert.equal(component.newAddressErrors.full_address[0], 'Alamat wajib diisi.');
        await run();
        assert.equal(component.shippingState, 'incomplete');
    });

    test(`${kind}: adding an address after cancelling an edit creates a new address and preserves the legacy one`, async () => {
        let request;
        const { component } = page(kind, async (url, options) => {
            request = { url, method: options.method };
            return response({ data: { ...JSON.parse(options.body), id: 8 } });
        });
        component.openEditAddressModal();
        component.newAddressForm.full_address = 'Perubahan dibatalkan';
        component.closeAddAddressModal();
        component.openAddAddressModal();
        assert.equal(component.editingAddressId, null);
        assert.equal(component.newAddressForm.full_address, '');
        component.syncNewAddressCoordinates({ latitude: -7.9, longitude: 112.6 });
        component.newAddressForm.full_address = 'Alamat baru';
        await component.saveAddress();
        assert.equal(request.url, '/addresses');
        assert.equal(request.method, 'POST');
        assert.equal(component.selectedAddressId, 8);
        assert.equal(component.addresses.length, 2);
        assert.equal(component.addresses.find(address => address.id === 7).full_address, 'Jl. Lama No. 7');
        assert.equal(component.addresses.find(address => address.id === 7).latitude, null);
    });

    test(`${kind}: repeated save clicks send one update and cannot close the modal during saving`, async () => {
        let calls = 0;
        let finish;
        const { component } = page(kind, () => {
            calls++;
            return new Promise(resolve => { finish = resolve; });
        });
        component.openEditAddressModal();
        component.newAddressForm.latitude = -7.9;
        component.newAddressForm.longitude = 112.6;
        const saving = component.saveAddress();
        await component.saveAddress();
        component.closeAddAddressModal();
        assert.equal(calls, 1);
        assert.equal(component.showAddAddressModal, true);
        finish(response({ data: { ...component.newAddressForm, id: 7 } }));
        await saving;
        assert.equal(component.showAddAddressModal, false);
    });

    test(`${kind}: self pickup still works with a legacy address without coordinates`, async () => {
        const { component, canSubmit, run } = page(kind, async () => response(quote(0)));
        if (kind === 'regular') component.pickupMethod = 'self_pickup';
        else component.method = 'self_pickup';
        component.init();
        await run();
        assert.equal(component.deliveryFee, 0);
        assert.equal(component.grandTotal, 50000);
        assert.equal(canSubmit(), true);
    });

    test(`${kind}: a late quote cannot overwrite shipping after coordinates change on the same address id`, async () => {
        const pending = [];
        const { component, run } = page(kind, (url, options) => url === '/preview'
            ? new Promise(resolve => pending.push(resolve))
            : response({ data: { ...JSON.parse(options.body), id: 7 } }));
        component.addresses[0].latitude = -7.9;
        component.addresses[0].longitude = 112.6;
        component.scheduleShipping();
        const oldQuote = run();
        const oldController = component.shippingController;
        component.openEditAddressModal();
        component.newAddressForm.latitude = -7.8;
        await component.saveAddress();
        assert.equal(oldController.signal.aborted, true);
        const newQuote = run();
        pending[1](response(quote(30000)));
        await newQuote;
        pending[0](response(quote(10000)));
        await oldQuote;
        assert.equal(component.deliveryFee, 30000);
    });
}

test('refreshing a reused Location Picker restores saved coordinates or clears a previous pin without emitting an address change', () => {
    const { createPicker } = page('regular', () => {});
    const picker = createPicker();
    picker.$el = { addEventListener() {} };
    picker.$nextTick = callback => callback();
    picker.initializeMap = () => {};
    picker.syncExternalState = () => assert.fail('hydration must not emit changes');
    picker.init();
    picker.boundRefreshHandler({ detail: { pickerId: 'picker', latitude: -7.9, longitude: 112.6 } });
    assert.equal(picker.selectedLatitude, -7.9);
    assert.equal(picker.selectedLongitude, 112.6);
    picker.boundRefreshHandler({ detail: { pickerId: 'picker', latitude: null, longitude: null } });
    assert.equal(picker.selectedLatitude, null);
    assert.equal(picker.selectedLongitude, null);
});

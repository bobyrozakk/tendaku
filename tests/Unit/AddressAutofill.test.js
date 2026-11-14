import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const templates = {
    profile: 'resources/views/livewire/profile/manage-addresses.blade.php',
    checkout: 'resources/views/customer/booking/partials/create/add-address-modal.blade.php',
    owner: 'resources/views/vendor/shipping/index.blade.php',
};
const address = {
    full_address: 'Jalan Ijen', province: 'Jawa Timur', city: 'Kota Malang',
    district: 'Klojen', village: 'Oro-oro Dowo', postal_code: '65119',
};
const response = (data, status = 200) => ({ ok: status === 200, status, json: async () => data });
const success = (data = address) => response({ success: true, data });

function fixture(kind, fetcher = async () => success(), initial = {}, initialCoordinates = {}) {
    const template = fs.readFileSync(new URL('../../' + templates[kind], import.meta.url), 'utf8');
    const bindings = new Map();
    for (const [tag] of template.matchAll(/<(?:input|textarea|x-text-input)\b[^>]*>/g)) {
        const field = tag.match(/data-address-field="([^"]+)"/)?.[1];
        const model = tag.match(/(?:wire:model|x-model|name)="(?:newAddressForm\.)?([^"]+)"/)?.[1];
        if (field) bindings.set(field, model);
    }

    const timers = new Map();
    const calls = [];
    const model = {};
    let timerId = 0;
    let factory;
    const source = ['location-picker.js', 'address-autofill.js'].map(file =>
        fs.readFileSync(new URL('../../resources/js/components/' + file, import.meta.url), 'utf8')
            .replace(/^import .+;\r?\n/gm, '').replace(/^export /gm, '')).join('\n');
    vm.runInNewContext(source, {
        document: { addEventListener() {} },
        window: { location: { origin: 'https://tendaku.test' }, Alpine: { data(name, callback) {
            if (name === 'addressAutofill') factory = callback;
        } } },
        AbortController, URL, Event,
        setTimeout(callback, delay) { timers.set(++timerId, { callback, delay }); return timerId; },
        clearTimeout: id => timers.delete(id),
        fetch: (url, options) => { calls.push({ url, options }); return fetcher(url, options); },
    });
    const component = factory({ url: '/locations/reverse-geocode', pickerId: 'picker' });
    const inputs = new Map([...bindings].map(([field, name]) => {
        const input = new EventTarget();
        input.dataset = { addressField: field };
        input.value = initial[name] ?? '';
        model[name] = input.value;
        for (const event of ['input', 'change']) {
            input.addEventListener(event, () => {
                model[name] = input.value;
                component.trackAddressEdit({ target: input });
            });
        }
        return [field, input];
    }));
    const coordinates = { latitude: { value: initialCoordinates.latitude ?? '' }, longitude: { value: initialCoordinates.longitude ?? '' } };
    component.$el = {
        isConnected: true,
        querySelectorAll: () => [...inputs.values()],
        querySelector: selector => coordinates[selector.includes('latitude') ? 'latitude' : 'longitude'],
    };
    component.init();
    component.syncAutofillContext(true, null);

    return {
        component, inputs, model, calls, timers, coordinates,
        pin(latitude = -7.9, longitude = 112.6, pickerId = 'picker') {
            coordinates.latitude.value = latitude;
            coordinates.longitude.value = longitude;
            component.scheduleAddressAutofill({ pickerId, latitude, longitude });
        },
        edit(field, value) {
            const input = inputs.get(field);
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        },
        run() {
            const pending = [...timers.values()];
            timers.clear();
            return Promise.all(pending.map(timer => timer.callback()));
        },
    };
}

for (const kind of ['profile', 'checkout', 'owner']) {
    test(`${kind}: autofills the existing form bindings only after a pin change`, async () => {
        const form = fixture(kind);
        await form.run();
        assert.equal(form.calls.length, 0);
        form.pin();
        assert.equal(form.calls.length, 0);
        assert.equal([...form.timers.values()][0].delay, 400);
        await form.run();
        assert.deepEqual(form.model, kind === 'owner' ? { address: 'Jalan Ijen', city: 'Kota Malang' } : address);
        assert.equal(form.calls.length, 1);
        const url = new URL(form.calls[0].url);
        assert.equal(url.origin, 'https://tendaku.test');
        assert.equal(url.pathname, '/locations/reverse-geocode');
        assert.deepEqual([...url.searchParams], [['latitude', '-7.9'], ['longitude', '112.6']]);
        assert.equal(form.calls[0].options.headers.Accept, 'application/json');
        assert.equal(form.calls[0].options.credentials, 'same-origin');
    });

    test(`${kind}: manual changes before and during a lookup survive subsequent pin changes`, async () => {
        let finish;
        const form = fixture(kind, () => new Promise(resolve => { finish = resolve; }));
        form.edit('full_address', 'Alamat dengan nomor rumah manual');
        form.pin();
        const pending = form.run();
        form.edit('city', 'Kota pilihan manual');
        finish(success());
        await pending;
        assert.equal(form.model[kind === 'owner' ? 'address' : 'full_address'], 'Alamat dengan nomor rumah manual');
        assert.equal(form.model.city, 'Kota pilihan manual');
        form.pin(-8, 113);
        const next = form.run();
        finish(success({ ...address, city: 'Kota Baru', full_address: 'Jalan Baru' }));
        await next;
        assert.equal(form.model[kind === 'owner' ? 'address' : 'full_address'], 'Alamat dengan nomor rumah manual');
        assert.equal(form.model.city, 'Kota pilihan manual');
    });

    test(`${kind}: reopening preserves saved fields and never starts a lookup`, async () => {
        const initial = kind === 'owner' ? { address: 'Alamat tersimpan', city: 'Kota tersimpan' }
            : { full_address: 'Alamat tersimpan', city: 'Kota tersimpan' };
        const form = fixture(kind, () => assert.fail('opening must not fetch'), initial, { latitude: -7.9, longitude: 112.6 });
        form.component.syncAutofillContext(false, null);
        form.component.syncAutofillContext(true, null);
        form.pin();
        await form.run();
        assert.equal(form.model[kind === 'owner' ? 'address' : 'full_address'], 'Alamat tersimpan');
        assert.equal(form.model.city, 'Kota tersimpan');
        assert.equal(form.calls.length, 0);
    });
}

test('rapid pin changes debounce into one request for the latest coordinates', async () => {
    const form = fixture('profile');
    form.pin(-7, 110);
    form.pin(-8, 111);
    form.pin(0, 0);
    assert.equal(form.timers.size, 1);
    await form.run();
    assert.equal(form.calls.length, 1);
    assert.equal(new URL(form.calls[0].url).searchParams.get('latitude'), '0');
    assert.equal(new URL(form.calls[0].url).searchParams.get('longitude'), '0');
});

test('a late response cannot overwrite the new pin even if fetch ignores abort', async () => {
    const pending = [];
    const form = fixture('checkout', () => new Promise(resolve => pending.push(resolve)));
    form.pin();
    const oldLookup = form.run();
    form.pin(-8, 113);
    assert.equal(form.calls[0].options.signal.aborted, true);
    const newLookup = form.run();
    pending[1](success({ ...address, full_address: 'Lokasi terbaru' }));
    await newLookup;
    pending[0](success({ ...address, full_address: 'Lokasi lama' }));
    await oldLookup;
    assert.equal(form.model.full_address, 'Lokasi terbaru');
});

test('a late JSON body cannot overwrite the latest address', async () => {
    let finishJson;
    let jsonStarted;
    const started = new Promise(resolve => { jsonStarted = resolve; });
    let calls = 0;
    const form = fixture('profile', async () => ++calls === 1
        ? { ok: true, status: 200, json: () => new Promise(resolve => { finishJson = resolve; jsonStarted(); }) }
        : success({ ...address, full_address: 'Lokasi terbaru' }));
    form.pin();
    const oldLookup = form.run();
    await started;
    form.pin(-8, 113);
    await form.run();
    finishJson({ success: true, data: { ...address, full_address: 'Lokasi lama' } });
    await oldLookup;
    assert.equal(form.model.full_address, 'Lokasi terbaru');
});

test('autofilled values can follow a new pin, but manually cleared values stay empty', async () => {
    let city = 'Kota Malang';
    const form = fixture('checkout', async () => success({ ...address, city }));
    form.pin();
    await form.run();
    form.edit('full_address', '');
    city = 'Kota Batu';
    form.pin(-8, 113);
    await form.run();
    assert.equal(form.model.city, 'Kota Batu');
    assert.equal(form.model.full_address, '');
});

test('partial data fills only available fields and preserves other inputs', async () => {
    const form = fixture('profile', async () => success({ full_address: 'Jalan Ijen', province: null, city: '', district: false }), {
        province: 'Provinsi tersimpan', city: 'Kota tersimpan', district: 'Kecamatan tersimpan',
    });
    form.pin();
    await form.run();
    assert.equal(form.model.full_address, 'Jalan Ijen');
    assert.equal(form.model.province, 'Provinsi tersimpan');
    assert.equal(form.model.city, 'Kota tersimpan');
    assert.equal(form.model.district, 'Kecamatan tersimpan');
    assert.match(form.component.autofillMessage, /belum lengkap/);
});

for (const [name, fetcher] of [
    ['API failure', async () => response({ message: 'raw provider credentials' }, 503)],
    ['rate limit', async () => response({}, 429)],
    ['expired session', async () => response({}, 401)],
    ['network failure', async () => { throw new Error('raw provider credentials'); }],
    ['invalid JSON', async () => ({ ok: true, json: async () => { throw new SyntaxError('bad JSON'); } })],
    ['invalid payload', async () => response({ success: false, data: [] })],
]) {
    test(`${name}: preserves coordinates and inputs and shows a friendly message`, async () => {
        const form = fixture('owner', fetcher, { address: 'Alamat manual', city: 'Kota manual' });
        form.pin();
        await form.run();
        assert.deepEqual(form.model, { address: 'Alamat manual', city: 'Kota manual' });
        assert.equal(form.coordinates.latitude.value, -7.9);
        assert.equal(form.coordinates.longitude.value, 112.6);
        assert.match(form.component.autofillMessage, /manual/);
        assert.doesNotMatch(form.component.autofillMessage, /raw provider/);
    });
}

test('closing and reopening cancels a pending lookup and protects corrections', async () => {
    let finish;
    const form = fixture('checkout', () => new Promise(resolve => { finish = resolve; }));
    form.pin();
    const pending = form.run();
    form.edit('city', 'Koreksi pengguna');
    form.component.syncAutofillContext(false, null);
    form.component.syncAutofillContext(true, null);
    finish(success());
    await pending;
    assert.equal(form.model.city, 'Koreksi pengguna');
    assert.equal(form.model.full_address, '');
    assert.equal(form.component.autofillMessage, '');
    assert.equal(form.calls[0].options.signal.aborted, true);
});

test('switching addresses clears edit protection and ignores the old lookup', async () => {
    const pending = [];
    const form = fixture('checkout', () => new Promise(resolve => pending.push(resolve)));
    form.component.syncAutofillContext(true, 1);
    form.edit('city', 'Kota alamat pertama');
    form.pin();
    const oldLookup = form.run();
    form.component.syncAutofillContext(true, 2);
    form.pin(-8, 113);
    const newLookup = form.run();
    pending[1](success());
    await newLookup;
    pending[0](success({ ...address, city: 'Kota lama' }));
    await oldLookup;
    assert.equal(form.model.city, 'Kota Malang');
});

for (const action of ['clear pin', 'close modal', 'submit', 'destroy']) {
    test(`${action}: cancels debounced requests`, async () => {
        const form = fixture('owner', () => assert.fail('cancelled lookup must not fetch'));
        form.pin();
        if (action === 'clear pin') form.pin(null, null);
        if (action === 'close modal') form.component.syncAutofillContext(false, null);
        if (action === 'submit') form.component.cancelAddressAutofill();
        if (action === 'destroy') form.component.destroy();
        await form.run();
        assert.equal(form.calls.length, 0);
    });
}

test('events from another picker and invalid coordinates do not trigger a request', async () => {
    const form = fixture('profile', () => assert.fail('must not fetch'));
    form.pin(-7.9, 112.6, 'other-picker');
    form.pin(91, 112.6);
    form.pin(-7.9, null);
    await form.run();
    assert.equal(form.calls.length, 0);
});

test('programmatic field changes during a request are preserved without an input event', async () => {
    let finish;
    const form = fixture('profile', () => new Promise(resolve => { finish = resolve; }));
    form.pin();
    const pending = form.run();
    form.inputs.get('city').value = 'Data baru dari form';
    finish(success());
    await pending;
    assert.equal(form.inputs.get('city').value, 'Data baru dari form');
});

function checkoutAutofillContext(state) {
    const template = fs.readFileSync(new URL('../../' + templates.checkout, import.meta.url), 'utf8');
    const expression = template.match(/context-expression="([^"]+)"/)[1];
    return state[expression];
}

test('a fresh checkout draft does not inherit manual protection from a previous new address', async () => {
    const form = fixture('checkout', async () => success(), { city: 'Malang' });
    const firstDraft = { editingAddressId: null, newAddressForm: { city: 'Malang' } };
    form.component.syncAutofillContext(true, checkoutAutofillContext(firstDraft));
    form.edit('city', 'Malang');
    form.component.syncAutofillContext(false, checkoutAutofillContext(firstDraft));

    const freshDraft = { editingAddressId: null, newAddressForm: { city: 'Malang' } };
    form.component.syncAutofillContext(true, checkoutAutofillContext(freshDraft));
    assert.equal(form.calls.length, 0);
    form.pin();
    await form.run();
    assert.equal(form.model.city, 'Kota Malang');
});

test('replacing a checkout draft ignores its pending response even if its address id stays the same', async () => {
    let finish;
    const form = fixture('checkout', () => new Promise(resolve => { finish = resolve; }));
    const firstDraft = { editingAddressId: 7, newAddressForm: {} };
    form.component.syncAutofillContext(true, checkoutAutofillContext(firstDraft));
    form.pin();
    const pending = form.run();

    const replacement = { editingAddressId: 7, newAddressForm: {} };
    form.component.syncAutofillContext(true, checkoutAutofillContext(replacement));
    finish(success());
    await pending;
    assert.equal(form.calls[0].options.signal.aborted, true);
    assert.equal(form.model.full_address, '');
});

test('reopening the same checkout draft keeps its manual corrections protected', async () => {
    const form = fixture('checkout');
    const draft = { editingAddressId: null, newAddressForm: {} };
    form.component.syncAutofillContext(true, checkoutAutofillContext(draft));
    form.edit('city', 'Koreksi pada draft');
    form.component.syncAutofillContext(false, checkoutAutofillContext(draft));
    form.component.syncAutofillContext(true, checkoutAutofillContext(draft));
    assert.equal(form.calls.length, 0);
    form.pin();
    await form.run();
    assert.equal(form.model.city, 'Koreksi pada draft');
});

for (const kind of ['profile', 'checkout']) {
    test(`${kind}: an automatic district follows a move to a different district`, async () => {
        let district = 'Klojen';
        const form = fixture(kind, async () => success({ ...address, district }));
        form.pin();
        await form.run();
        assert.equal(form.model.district, 'Klojen');

        district = 'Blimbing';
        form.pin(-7.94, 112.65);
        assert.equal(form.model.district, '');
        await form.run();
        assert.equal(form.model.district, 'Blimbing');
    });

    test(`${kind}: unknown district clears only the stale automatic value and explains why`, async () => {
        let district = 'Klojen';
        const form = fixture(kind, async () => success({ ...address, district }));
        form.pin();
        await form.run();

        district = null;
        form.pin(-8, 113);
        await form.run();
        assert.equal(form.model.district, '');
        assert.match(form.component.autofillMessage, /Kecamatan.*belum tersedia/);
        assert.match(form.component.autofillMessage, /dikosongkan/);
        assert.equal(form.coordinates.latitude.value, -8);
        assert.equal(form.coordinates.longitude.value, 113);
    });

    test(`${kind}: a manually confirmed district survives both a new district and a missing one`, async () => {
        let district = 'Klojen';
        const form = fixture(kind, async () => success({ ...address, district }));
        form.pin();
        await form.run();
        form.edit('district', 'Klojen');

        district = 'Blimbing';
        form.pin(-7.94, 112.65);
        await form.run();
        assert.equal(form.model.district, 'Klojen');

        district = null;
        form.pin(-8, 113);
        await form.run();
        assert.equal(form.model.district, 'Klojen');
    });

    test(`${kind}: district correction during a lookup is not classified as automatic`, async () => {
        let finish;
        const form = fixture(kind, () => new Promise(resolve => { finish = resolve; }));
        form.pin();
        const first = form.run();
        finish(success());
        await first;

        form.pin(-7.94, 112.65);
        const pending = form.run();
        form.edit('district', 'Kecamatan dikoreksi manual');
        finish(success({ ...address, district: 'Blimbing' }));
        await pending;
        form.pin(-8, 113);
        const next = form.run();
        finish(success({ ...address, district: null }));
        await next;
        assert.equal(form.model.district, 'Kecamatan dikoreksi manual');
    });
}

test('a late response cannot restore an automatic district after the latest pin has no district', async () => {
    const pending = [];
    const form = fixture('checkout', () => new Promise(resolve => pending.push(resolve)));
    form.pin();
    const first = form.run();
    pending[0](success());
    await first;

    form.pin(-7.94, 112.65);
    const oldLookup = form.run();
    form.pin(-8, 113);
    const latestLookup = form.run();
    pending[2](success({ ...address, district: null }));
    await latestLookup;
    pending[1](success({ ...address, district: 'Blimbing' }));
    await oldLookup;
    assert.equal(form.model.district, '');
    assert.equal(form.calls[1].options.signal.aborted, true);
    assert.match(form.component.autofillMessage, /Kecamatan.*belum tersedia/);
});

for (const status of [429, 503]) {
    test(`HTTP ${status}: a failed lookup cannot leave a stale automatic district`, async () => {
        let fail = false;
        const form = fixture('profile', async () => fail ? response({}, status) : success());
        form.pin();
        await form.run();
        fail = true;
        form.pin(-8, 113);
        await form.run();
        assert.equal(form.model.district, '');
        assert.match(form.component.autofillMessage, /Kecamatan.*dikosongkan/);
    });
}

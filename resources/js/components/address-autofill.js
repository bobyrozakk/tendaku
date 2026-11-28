import { isValidCoordinatePair, parseCoordinateValue } from "./location-picker.js";

export function createAddressAutofillState(config = {}) {
    let root;
    let active = false;
    let context;
    let lastCoordinates = null;
    let sequence = 0;
    let timer = null;
    let controller = null;
    let applying = false;
    let automaticDistrict = null;
    let districtCleared = false;
    const manualValues = new Map();
    const fields = () => Array.from(root.querySelectorAll("[data-address-field]"));
    const coordinateKey = (latitude, longitude) => isValidCoordinatePair(latitude, longitude)
        ? `${latitude},${longitude}` : null;
    const districtNotice = () => districtCleared ? "Kecamatan otomatis sebelumnya dikosongkan karena penanda lokasi berubah. " : "";

    return {
        autofillMessage: "",

        init() {
            root = this.$el;
        },

        syncAutofillContext(isActive, nextContext = null) {
            const changedContext = context !== nextContext;
            if (!changedContext && active === Boolean(isActive)) {
                return;
            }

            this.cancelAddressAutofill();
            if (changedContext) {
                manualValues.clear();
                automaticDistrict = null;
            }
            context = nextContext;
            active = Boolean(isActive);
            lastCoordinates = coordinateKey(
                parseCoordinateValue(root.querySelector('[data-location-picker-input="latitude"]')?.value),
                parseCoordinateValue(root.querySelector('[data-location-picker-input="longitude"]')?.value),
            );

            for (const input of fields()) {
                const field = input.dataset.addressField;
                if (manualValues.has(field) && manualValues.get(field) !== input.value) {
                    manualValues.delete(field);
                }
                if (field === "district" && input.value !== automaticDistrict) {
                    automaticDistrict = null;
                }
            }
        },

        trackAddressEdit(event) {
            const input = event.target;
            if (!applying && input?.dataset?.addressField) {
                manualValues.set(input.dataset.addressField, input.value);
                if (input.dataset.addressField === "district") {
                    automaticDistrict = null;
                    districtCleared = false;
                }
            }
        },

        scheduleAddressAutofill(detail) {
            if (!active || detail?.pickerId !== config.pickerId) {
                return;
            }

            const latitude = parseCoordinateValue(detail.latitude);
            const longitude = parseCoordinateValue(detail.longitude);
            const coordinates = coordinateKey(latitude, longitude);
            if (coordinates === lastCoordinates) {
                return;
            }

            this.cancelAddressAutofill();
            lastCoordinates = coordinates;
            const districtInput = fields().find(input => input.dataset.addressField === "district");
            if (automaticDistrict !== null && districtInput?.value === automaticDistrict && !manualValues.has("district")) {
                this.writeAddressValue(districtInput, "");
                districtCleared = true;
            }
            automaticDistrict = null;
            if (coordinates === null) {
                this.autofillMessage = districtNotice();
                return;
            }

            const snapshot = Object.fromEntries(fields().map(input => [input.dataset.addressField, input.value]));
            const requestSequence = sequence;
            this.autofillMessage = districtNotice() + "Mencari alamat dari titik lokasi...";
            timer = setTimeout(() => this.fetchAddress(latitude, longitude, requestSequence, snapshot), 400);
        },

        async fetchAddress(latitude, longitude, requestSequence, snapshot) {
            if (requestSequence !== sequence || !active) {
                return;
            }

            timer = null;
            controller = new AbortController();
            const isCurrent = () => requestSequence === sequence && active && root.isConnected;

            try {
                const url = new URL(config.url, window.location.origin);
                url.searchParams.set("latitude", latitude);
                url.searchParams.set("longitude", longitude);
                const response = await fetch(url.toString(), {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                    signal: controller.signal,
                });

                if (!isCurrent()) {
                    return;
                }
                if (response.status === 429) {
                    this.autofillMessage = districtNotice() + "Terlalu banyak permintaan alamat. Tunggu sebentar atau isi alamat secara manual.";
                    return;
                }
                if (!response.ok) {
                    throw new Error("Address lookup failed");
                }

                const payload = await response.json();
                if (!isCurrent()) {
                    return;
                }
                if (payload?.success !== true || !payload.data || typeof payload.data !== "object" || Array.isArray(payload.data)) {
                    throw new Error("Invalid address response");
                }

                let incomplete = false;
                let districtUnknown = false;
                for (const input of fields()) {
                    const field = input.dataset.addressField;
                    const value = payload.data[field];
                    if (typeof value !== "string" || value.trim() === "") {
                        incomplete = true;
                        districtUnknown ||= field === "district";
                        continue;
                    }
                    if (manualValues.has(field) || input.value !== snapshot[field]) {
                        continue;
                    }
                    this.writeAddressValue(input, value.trim());
                    if (field === "district") {
                        automaticDistrict = input.value;
                        districtCleared = false;
                    }
                }

                if (districtUnknown) {
                    this.autofillMessage = districtNotice() + "Data alamat belum lengkap. Kecamatan untuk titik ini belum tersedia. Silakan periksa atau isi kecamatan secara manual; koreksi manual tetap dipertahankan.";
                    return;
                }

                this.autofillMessage = incomplete
                    ? "Data alamat belum lengkap. Periksa dan lengkapi kolom yang diperlukan; perubahan manual tetap dipertahankan."
                    : "Alamat telah diperiksa. Silakan sesuaikan bila perlu; perubahan manual tetap dipertahankan.";
            } catch (error) {
                if (isCurrent() && error?.name !== "AbortError") {
                    this.autofillMessage = districtNotice() + "Alamat otomatis belum tersedia. Titik lokasi dan isian Anda tetap tersimpan di form; silakan isi alamat secara manual.";
                }
            } finally {
                if (requestSequence === sequence) {
                    controller = null;
                }
            }
        },

        writeAddressValue(input, value) {
            applying = true;
            try {
                input.value = value;
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
            } finally {
                applying = false;
            }
        },

        cancelAddressAutofill() {
            sequence++;
            clearTimeout(timer);
            timer = null;
            controller?.abort();
            controller = null;
            districtCleared = false;
            this.autofillMessage = "";
        },

        destroy() {
            this.cancelAddressAutofill();
        },
    };
}

function registerAddressAutofill() {
    if (typeof window !== "undefined" && window.Alpine) {
        window.Alpine.data("addressAutofill", createAddressAutofillState);
    }
}

document.addEventListener("alpine:init", registerAddressAutofill);

if (typeof window !== "undefined" && window.Alpine) {
    registerAddressAutofill();
}

import { isValidCoordinatePair, parseCoordinateValue } from "./location-picker.js";

// Nomor ponsel ke bentuk lokal 08xxxxxxxxxx (hanya angka, maks. 13 digit).
const normalizePhone = (value) => {
    let digits = String(value ?? "").replace(/\D/g, "");
    if (digits.startsWith("62")) digits = "0" + digits.slice(2);
    return digits.slice(0, 13);
};
const onlyDigits = (value, max) => String(value ?? "").replace(/\D/g, "").slice(0, max);

export function checkoutAddressState(cfg, defaults = {}) {
    const emptyForm = () => ({
        label: "Rumah",
        recipient_name: cfg.name || "",
        phone_number: normalizePhone(cfg.phone),
        full_address: "",
        province: "Jawa Timur",
        city: "Malang",
        district: "",
        village: "",
        postal_code: "",
        notes: "",
        latitude: null,
        longitude: null,
        is_default: false,
        ...defaults,
    });

    return {
        addresses: cfg.addresses || [],
        selectedAddressId: cfg.selectedAddressId || null,
        addressPickerId: "checkout-add-address-location-picker",
        showAddAddressModal: false,
        editingAddressId: null,
        newAddressSubmitting: false,
        newAddressErrors: {},
        newAddressForm: emptyForm(),

        getSelectedAddress() {
            return this.addresses.find(address => Number(address.id) === Number(this.selectedAddressId)) || null;
        },

        addressHasCoordinates(address) {
            return isValidCoordinatePair(
                parseCoordinateValue(address?.latitude),
                parseCoordinateValue(address?.longitude),
            );
        },

        needsAddressLocation() {
            return Boolean(this.selectedAddressId) && !this.addressHasCoordinates(this.getSelectedAddress());
        },

        syncNewAddressCoordinates(detail) {
            if (!detail || (detail.pickerId && detail.pickerId !== this.addressPickerId)) {
                return;
            }
            this.newAddressForm.latitude = detail.latitude ?? null;
            this.newAddressForm.longitude = detail.longitude ?? null;
        },

        openAddAddressModal() {
            if (this.newAddressSubmitting) return;
            if (this.editingAddressId !== null) {
                this.newAddressForm = emptyForm();
            }
            this.editingAddressId = null;
            this.newAddressForm.recipient_name ||= this.name || "";
            this.newAddressForm.phone_number ||= normalizePhone(this.phone);
            this.openAddressModal();
        },

        openEditAddressModal() {
            const address = this.getSelectedAddress();
            if (!address || this.newAddressSubmitting) return;
            this.editingAddressId = address.id;
            this.newAddressForm = emptyForm();
            for (const field of Object.keys(this.newAddressForm)) {
                this.newAddressForm[field] = address[field] ?? this.newAddressForm[field];
            }
            this.openAddressModal();
        },

        openAddressModal() {
            this.newAddressErrors = {};
            this.showAddAddressModal = true;
            this.$nextTick(() => {
                window.refreshLocationPicker?.(this.addressPickerId, {
                    latitude: this.newAddressForm.latitude,
                    longitude: this.newAddressForm.longitude,
                });
                window.renderLucideIcons?.();
            });
        },

        closeAddAddressModal() {
            if (this.newAddressSubmitting) return;
            this.showAddAddressModal = false;
            this.newAddressErrors = {};
        },

        async saveAddress() {
            if (this.newAddressSubmitting) return;
            this.newAddressErrors = {};
            if ((this.pickupMethod ?? this.method) === "delivery" && !this.addressHasCoordinates(this.newAddressForm)) {
                this.newAddressErrors = { latitude: ["Pilih titik lokasi yang valid pada peta sebelum menyimpan alamat pengantaran."] };
                return;
            }
            this.newAddressForm.phone_number = normalizePhone(this.newAddressForm.phone_number);
            this.newAddressForm.postal_code = onlyDigits(this.newAddressForm.postal_code, 5);
            const formErrors = {};
            if (!/^08[0-9]{8,11}$/.test(this.newAddressForm.phone_number)) {
                formErrors.phone_number = ["Nomor telepon harus diawali 08 dan terdiri dari 10 hingga 13 angka."];
            }
            if (this.newAddressForm.postal_code && this.newAddressForm.postal_code.length !== 5) {
                formErrors.postal_code = ["Kode pos harus 5 angka."];
            }
            if (Object.keys(formErrors).length) {
                this.newAddressErrors = formErrors;
                return;
            }
            this.newAddressSubmitting = true;
            const editingId = this.editingAddressId;
            try {
                const url = editingId === null
                    ? cfg.storeAddressUrl
                    : cfg.updateAddressUrl.replace("__ADDRESS__", encodeURIComponent(editingId));
                const response = await fetch(url, {
                    method: editingId === null ? "POST" : "PUT",
                    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": this.csrf },
                    body: JSON.stringify(this.newAddressForm),
                });
                const data = await response.json();
                if (!response.ok) {
                    this.newAddressErrors = data.errors || { general: data.message || "Gagal menyimpan alamat." };
                    return;
                }
                const saved = data.data;
                if (saved.is_default) {
                    this.addresses.forEach(address => address.is_default = false);
                }
                const index = this.addresses.findIndex(address => Number(address.id) === Number(saved.id));
                if (index === -1) {
                    this.addresses.unshift(saved);
                } else {
                    this.addresses.splice(index, 1, saved);
                }
                this.selectedAddressId = saved.id;
                if (typeof this.selectAddress === "function") {
                    this.selectAddress(saved.id);
                }
                this.scheduleShipping();
                this.showAddAddressModal = false;
                this.editingAddressId = null;
                this.newAddressForm = { ...emptyForm(), is_default: false };
                window.resetLocationPicker?.(this.addressPickerId);
                this.$nextTick(() => window.renderLucideIcons?.());
            } catch (error) {
                this.newAddressErrors = { general: "Terjadi gangguan jaringan saat menyimpan alamat." };
            } finally {
                this.newAddressSubmitting = false;
            }
        },
    };
}

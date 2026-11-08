document.addEventListener("alpine:init", () => {
    Alpine.data("packageCheckout", (cfg) => ({
        ...cfg,
        errorMessage: "", isValid: true,
        addressPickerId: "checkout-add-address-location-picker",
        showAddAddressModal: false, newAddressSubmitting: false, newAddressErrors: {},
        newAddressForm: { label: "Rumah", recipient_name: cfg.name || "", phone_number: cfg.phone || "", full_address: "", province: "Jawa Timur", city: "Malang", district: "", village: "", postal_code: "", notes: "", latitude: null, longitude: null, is_default: false },
        shippingState: "incomplete", shipping: null, shippingMessage: "",
        shippingSequence: 0, shippingTimer: null, shippingController: null,
        shippingResultKey: "", shippingNeedsApproval: cfg.requiresApproval || false,
        isSubmitting: false, formError: "",
        init() {
            this.recalculate();
            if (this.selectedAddressId) {
                const exists = this.addresses.some(a => parseInt(a.id) === parseInt(this.selectedAddressId));
                if (!exists && this.addresses.length > 0) {
                    this.selectedAddressId = this.addresses[0].id;
                }
            } else if (this.addresses.length > 0) {
                this.selectedAddressId = this.addresses[0].id;
            }
            this.$watch("shippingKey", () => this.scheduleShipping());
            this.scheduleShipping();
        },

        syncNewAddressCoordinates(detail) {
            if (!detail || (detail.pickerId && detail.pickerId !== this.addressPickerId)) {
                return;
            }
            this.newAddressForm.latitude = detail.latitude ?? null;
            this.newAddressForm.longitude = detail.longitude ?? null;
        },

        openAddAddressModal() {
            this.newAddressErrors = {};
            if (
                this.newAddressForm.latitude === null &&
                this.newAddressForm.longitude === null &&
                typeof window.resetLocationPicker === 'function'
            ) {
                window.resetLocationPicker(this.addressPickerId);
            }
            this.showAddAddressModal = true;
            this.$nextTick(() => {
                if (typeof window.refreshLocationPicker === 'function') {
                    window.refreshLocationPicker(this.addressPickerId);
                }
                if (typeof window.renderLucideIcons === 'function') {
                    window.renderLucideIcons();
                }
            });
        },

        closeAddAddressModal() {
            this.showAddAddressModal = false;
            this.newAddressErrors = {};
        },

        async saveNewAddress() {
            if (this.newAddressSubmitting) return;
            this.newAddressSubmitting = true;
            this.newAddressErrors = {};
            try {
                const resp = await fetch(this.storeAddressUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf
                    },
                    body: JSON.stringify(this.newAddressForm)
                });

                const data = await resp.json();

                if (!resp.ok) {
                    this.newAddressErrors = data.errors || { general: data.message || 'Gagal menyimpan alamat.' };
                    this.newAddressSubmitting = false;
                    return;
                }

                const created = data.data;
                if (created.is_default) {
                    this.addresses.forEach(a => a.is_default = false);
                }
                this.addresses.unshift(created);
                this.selectedAddressId = created.id;
                this.closeAddAddressModal();

                this.newAddressForm = {
                    label: 'Rumah',
                    recipient_name: cfg.name || "",
                    phone_number: cfg.phone || "",
                    full_address: '',
                    province: 'Jawa Timur',
                    city: 'Malang',
                    district: '',
                    village: '',
                    postal_code: '',
                    notes: '',
                    latitude: null,
                    longitude: null,
                    is_default: false,
                };

                if (typeof window.resetLocationPicker === 'function') {
                    window.resetLocationPicker(this.addressPickerId);
                }

                this.$nextTick(() => {
                    if (typeof window.renderLucideIcons === 'function') {
                        window.renderLucideIcons();
                    }
                });
            } catch (err) {
                this.newAddressErrors = { general: 'Terjadi gangguan jaringan saat menyimpan alamat.' };
            } finally {
                this.newAddressSubmitting = false;
            }
        },

        setDuration(days) {
            this.durationDays = Math.max(1, parseInt(days) || 1);
            if (!this.pickupDate) return;
            const [y, m, d] = this.pickupDate.split('-').map(Number);
            const [h, min] = (this.pickupTime || '09:00').split(':').map(Number);
            const pickup = new Date(y, m - 1, d, h, min, 0);

            if (isNaN(pickup.getTime())) return;

            const ret = new Date(pickup.getTime() + this.durationDays * 24 * 60 * 60 * 1000);
            const rY = ret.getFullYear();
            const rM = String(ret.getMonth() + 1).padStart(2, '0');
            const rD = String(ret.getDate()).padStart(2, '0');
            const rH = String(ret.getHours()).padStart(2, '0');
            const rMin = String(ret.getMinutes()).padStart(2, '0');

            this.returnDate = `${rY}-${rM}-${rD}`;
            this.returnTime = `${rH}:${rMin}`;
            this.recalculate();
        },

        onPickupChange() {
            this.setDuration(this.durationDays);
        },

        onReturnChange() {
            this.recalculate();
        },

        recalculate() {
            this.errorMessage = '';
            if (!this.pickupDate || !this.pickupTime || !this.returnDate || !this.returnTime) {
                this.isValid = false;
                return;
            }

            const [pY, pM, pD] = this.pickupDate.split('-').map(Number);
            const [pH, pMin] = this.pickupTime.split(':').map(Number);
            const pickup = new Date(pY, pM - 1, pD, pH, pMin, 0);

            const [rY, rM, rD] = this.returnDate.split('-').map(Number);
            const [rH, rMin] = this.returnTime.split(':').map(Number);
            const ret = new Date(rY, rM - 1, rD, rH, rMin, 0);

            if (isNaN(pickup.getTime()) || isNaN(ret.getTime())) {
                this.isValid = false;
                return;
            }

            const now = new Date();
            if (pickup.getTime() < now.getTime() - 60000) {
                this.errorMessage = 'Waktu pengambilan tidak boleh di masa lalu.';
                this.isValid = false;
                return;
            }

            const diffMs = ret.getTime() - pickup.getTime();
            const diffHours = diffMs / (1000 * 60 * 60);

            if (diffMs <= 0) {
                this.errorMessage = 'Waktu pengembalian wajib setelah waktu pengambilan (tidak boleh sama atau lebih awal).';
                this.isValid = false;
                this.durationDays = 0;
                this.totalHours = 0;
                this.subtotal = 0;
                return;
            }

            this.totalHours = Math.round(diffHours * 10) / 10;
            this.durationDays = Math.max(1, Math.ceil(diffHours / 24));
            this.subtotal = this.durationDays * this.packagePrice;
            this.isValid = true;
        },

        formatRupiah(val) {
            if (val === null || val === undefined) return "\u2014";
            return 'Rp ' + (Math.round(val) || 0).toLocaleString('id-ID');
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },
        get shippingKey() {
            return JSON.stringify([this.packageId, this.method, this.selectedAddressId, this.pickupDate, this.pickupTime, this.returnDate, this.returnTime]);
        },
        get shippingReady() {
            return this.shippingState === "ready" && this.shippingResultKey === this.shippingKey;
        },
        get deliveryFee() {
            return this.method === "self_pickup" ? 0 : (this.shippingReady ? this.shipping.delivery_fee : null);
        },
        get grandTotal() {
            return this.shippingReady ? this.shipping.grand_total : null;
        },
        get canSubmit() {
            return this.isValid && this.shippingReady && !this.isSubmitting && !this.shippingNeedsApproval;
        },
        measurement(value) {
            return Number(value).toLocaleString("id-ID", { maximumFractionDigits: 2 });
        },
        scheduleShipping() {
            clearTimeout(this.shippingTimer);
            this.shippingController?.abort();
            const sequence = ++this.shippingSequence;
            this.shipping = null;
            this.shippingResultKey = "";
            this.shippingState = "incomplete";
            this.recalculate();
            this.shippingMessage = this.isValid ? "Pilih alamat pengantaran terlebih dahulu." : (this.errorMessage || "Lengkapi jadwal sewa terlebih dahulu.");
            if (!this.isValid || (this.method === "delivery" && !this.selectedAddressId)) return;
            this.shippingState = "loading";
            this.shippingMessage = "Menghitung rincian pengiriman...";
            const key = this.shippingKey;
            this.shippingTimer = setTimeout(() => this.loadShipping(sequence, key), 450);
        },
        async loadShipping(sequence, key) {
            const controller = new AbortController();
            this.shippingController = controller;
            try {
                const response = await fetch(this.shippingPreviewUrl, {
                    method: "POST", signal: controller.signal,
                    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": this.csrf },
                    body: JSON.stringify({ checkout_type: "package", package_id: this.packageId, pickup_method: this.method,
                        customer_address_id: this.method === "delivery" ? this.selectedAddressId : null,
                        pickup_date: this.pickupDate, pickup_time: this.pickupTime, return_date: this.returnDate, return_time: this.returnTime }),
                });
                const data = await response.json();
                if (sequence !== this.shippingSequence || key !== this.shippingKey) return;
                if (!response.ok || !data.success) throw new Error(data.message || "Ongkir belum dapat dihitung.");
                this.applyShipping(data, key);
            } catch (error) {
                if (error.name === "AbortError" || sequence !== this.shippingSequence || key !== this.shippingKey) return;
                this.shippingState = "error";
                this.shippingMessage = error.message || "Koneksi bermasalah. Silakan coba lagi.";
            }
        },
        applyShipping(data, key) {
            if (Number(data.package_id) !== Number(this.packageId)) {
                this.shippingState = "error";
                this.shippingMessage = "Identitas paket berubah. Muat ulang halaman.";
                return;
            }
            this.shipping = data;
            this.shippingResultKey = key;
            this.shippingState = "ready";
            this.shippingMessage = "";
            this.packagePrice = data.package_price;
            this.packageName = data.package_name;
            this.packageItems = data.items;
            this.subtotal = data.subtotal;
            this.durationDays = data.rental_days;
            this.totalHours = data.total_hours;
        },
        approveShippingChange() {
            this.shippingNeedsApproval = false;
            this.formError = "";
        },
        async submitBooking(form) {
            if (!this.canSubmit || !form.reportValidity()) return;
            const body = new FormData(form);
            body.set("package_id", this.packageId);
            body.set("expected_delivery_fee", this.shipping.delivery_fee);
            body.set("expected_shipping_context", this.shipping.shipping_context);
            const key = this.shippingKey;
            this.isSubmitting = true;
            this.formError = "";
            try {
                const response = await fetch(form.action, { method: "POST", headers: { Accept: "application/json", "X-CSRF-TOKEN": this.csrf }, body });
                const data = await response.json();
                if (response.status === 409 && data.shipping) {
                    this.applyShipping(data.shipping, key);
                    this.shippingNeedsApproval = true;
                    this.formError = data.message;
                    this.isSubmitting = false;
                    return;
                }
                if (!response.ok) {
                    this.formError = Object.values(data.errors || {}).flat().join(" ") || data.message || "Pesanan belum dapat diproses.";
                    this.isSubmitting = false;
                    return;
                }
                if (!data.redirect_url) throw new Error("Halaman status booking tidak tersedia.");
                window.location.assign(data.redirect_url);
            } catch (error) {
                this.formError = "Koneksi bermasalah. Silakan coba lagi; permintaan yang sama akan memakai booking yang sudah dibuat.";
                this.isSubmitting = false;
            }
        },
        destroy() {
            clearTimeout(this.shippingTimer);
            this.shippingController?.abort();
        },
    }));
});

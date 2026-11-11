const DEFAULT_MALANG_CENTER = Object.freeze({
    latitude: -7.9797,
    longitude: 112.6304,
});

const COORDINATE_DECIMALS = 7;

const LEAFLET_CSS_URL = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
const LEAFLET_JS_URL = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
const OSM_TILE_URL = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
const OSM_ATTRIBUTION =
    '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors';

let leafletLoadPromise = null;

/**
 * Mengonversi input menjadi angka koordinat jika valid, atau null jika kosong.
 * Nilai 0 dan "0" tetap diperlakukan sebagai angka 0 yang valid.
 */
export function parseCoordinateValue(rawValue) {
    if (rawValue === null || rawValue === undefined || typeof rawValue === "boolean") {
        return null;
    }

    if (typeof rawValue === "number") {
        return Number.isFinite(rawValue) ? rawValue : null;
    }

    if (typeof rawValue === "string") {
        const trimmed = rawValue.trim();
        if (trimmed === "") {
            return null;
        }

        const parsed = Number(trimmed);
        return Number.isFinite(parsed) ? parsed : null;
    }

    return null;
}

export function isValidLatitude(latitude) {
    return (
        typeof latitude === "number" &&
        Number.isFinite(latitude) &&
        latitude >= -90 &&
        latitude <= 90
    );
}

export function isValidLongitude(longitude) {
    return (
        typeof longitude === "number" &&
        Number.isFinite(longitude) &&
        longitude >= -180 &&
        longitude <= 180
    );
}

export function isValidCoordinatePair(latitude, longitude) {
    return isValidLatitude(latitude) && isValidLongitude(longitude);
}

export function normalizeCoordinate(value) {
    return Number(value.toFixed(COORDINATE_DECIMALS));
}

export function formatCoordinateDisplay(value) {
    const parsed = parseCoordinateValue(value);
    if (parsed === null) {
        return "";
    }

    return parsed.toFixed(COORDINATE_DECIMALS);
}

function ensureLeafletStylesheet() {
    if (document.querySelector('link[data-tendaku-leaflet-css="true"]')) {
        return;
    }

    const existingCss = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).some(
        (link) => link.href && link.href.includes("leaflet"),
    );

    if (existingCss) {
        return;
    }

    const linkElement = document.createElement("link");
    linkElement.rel = "stylesheet";
    linkElement.href = LEAFLET_CSS_URL;
    linkElement.dataset.tendakuLeafletCss = "true";
    document.head.appendChild(linkElement);
}

function ensureLeafletLoaded() {
    if (typeof window !== "undefined" && window.L && typeof window.L.map === "function") {
        ensureLeafletStylesheet();
        return Promise.resolve(window.L);
    }

    if (leafletLoadPromise) {
        return leafletLoadPromise;
    }

    leafletLoadPromise = new Promise((resolve, reject) => {
        ensureLeafletStylesheet();

        const existingScript = document.querySelector('script[data-tendaku-leaflet-js="true"]');
        if (existingScript) {
            existingScript.addEventListener("load", () => resolve(window.L), { once: true });
            existingScript.addEventListener(
                "error",
                () => reject(new Error("Gagal memuat pustaka peta Leaflet.")),
                { once: true },
            );
            return;
        }

        const scriptElement = document.createElement("script");
        scriptElement.src = LEAFLET_JS_URL;
        scriptElement.async = true;
        scriptElement.dataset.tendakuLeafletJs = "true";

        scriptElement.addEventListener(
            "load",
            () => {
                if (window.L && typeof window.L.map === "function") {
                    resolve(window.L);
                    return;
                }
                leafletLoadPromise = null;
                reject(new Error("Pustaka Leaflet tidak tersedia setelah dimuat."));
            },
            { once: true },
        );

        scriptElement.addEventListener(
            "error",
            () => {
                leafletLoadPromise = null;
                reject(new Error("Gagal memuat pustaka peta Leaflet."));
            },
            { once: true },
        );

        document.head.appendChild(scriptElement);
    });

    return leafletLoadPromise;
}

function createPinMarkerIcon(leaflet) {
    const svgMarkup = `
        <div class="tendaku-location-picker__pin" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C7.58172 2 4 5.58172 4 10C4 15.25 11.04 21.38 11.49 21.76C11.78 22.01 12.22 22.01 12.51 21.76C12.96 21.38 20 15.25 20 10C20 5.58172 16.4183 2 12 2Z" fill="#6C8B08" stroke="#FFFFFF" stroke-width="1.75"/>
                <circle cx="12" cy="10" r="3.25" fill="#FFFFFF"/>
            </svg>
        </div>
    `;

    return leaflet.divIcon({
        className: "tendaku-location-picker__marker-icon",
        html: svgMarkup,
        iconSize: [32, 32],
        iconAnchor: [16, 30],
    });
}

function mapGeolocationError(error) {
    if (!error) {
        return "Gagal mendapatkan lokasi perangkat Anda.";
    }

    switch (error.code) {
        case 1:
            return "Izin akses lokasi ditolak. Silakan aktifkan izin lokasi pada browser atau pilih titik secara manual di peta.";
        case 2:
            return "Informasi lokasi perangkat tidak tersedia saat ini. Silakan pilih titik langsung pada peta.";
        case 3:
            return "Waktu permintaan lokasi habis. Coba lagi atau klik langsung pada peta.";
        default:
            return "Gagal mendeteksi lokasi Anda. Silakan pilih titik langsung pada peta.";
    }
}

export function createLocationPickerState(config = {}) {
    const initialLat = parseCoordinateValue(config.initialLatitude);
    const initialLng = parseCoordinateValue(config.initialLongitude);
    const hasInitialSelection = isValidCoordinatePair(initialLat, initialLng);

    const fallbackCenterLat = parseCoordinateValue(config.defaultLatitude);
    const fallbackCenterLng = parseCoordinateValue(config.defaultLongitude);
    const hasValidFallbackCenter = isValidCoordinatePair(fallbackCenterLat, fallbackCenterLng);

    return {
        pickerId: config.pickerId || "location-picker",
        disabled: Boolean(config.disabled),
        defaultZoom: Number.isFinite(Number(config.defaultZoom)) ? Number(config.defaultZoom) : 13,
        selectedZoom: Number.isFinite(Number(config.selectedZoom))
            ? Number(config.selectedZoom)
            : 16,
        wireLatitudeModel: config.wireLatitudeModel || null,
        wireLongitudeModel: config.wireLongitudeModel || null,

        viewCenterLatitude: hasValidFallbackCenter
            ? normalizeCoordinate(fallbackCenterLat)
            : DEFAULT_MALANG_CENTER.latitude,
        viewCenterLongitude: hasValidFallbackCenter
            ? normalizeCoordinate(fallbackCenterLng)
            : DEFAULT_MALANG_CENTER.longitude,

        selectedLatitude: hasInitialSelection ? normalizeCoordinate(initialLat) : null,
        selectedLongitude: hasInitialSelection ? normalizeCoordinate(initialLng) : null,

        isMapReady: false,
        isMapError: false,
        isLocating: false,
        feedbackMessage: "",
        feedbackType: "",

        mapInstance: null,
        markerInstance: null,
        resizeObserver: null,
        intersectionObserver: null,
        invalidateTimer: null,
        invalidateTimers: [],
        hasRenderedVisibleCanvas: false,
        boundRefreshHandler: null,
        boundResetHandler: null,

        get hasSelectedCoordinates() {
            return isValidCoordinatePair(this.selectedLatitude, this.selectedLongitude);
        },

        get formattedLatitude() {
            return this.hasSelectedCoordinates
                ? formatCoordinateDisplay(this.selectedLatitude)
                : "";
        },

        get formattedLongitude() {
            return this.hasSelectedCoordinates
                ? formatCoordinateDisplay(this.selectedLongitude)
                : "";
        },

        init() {
            this.boundRefreshHandler = (event) => {
                if (event?.detail?.pickerId && event.detail.pickerId !== this.pickerId) {
                    return;
                }
                if (event?.detail && Object.hasOwn(event.detail, "latitude") && Object.hasOwn(event.detail, "longitude")) {
                    const latitude = parseCoordinateValue(event.detail.latitude);
                    const longitude = parseCoordinateValue(event.detail.longitude);
                    if (isValidCoordinatePair(latitude, longitude)) {
                        this.selectCoordinates(latitude, longitude, { emitEvents: false, zoom: this.selectedZoom });
                    } else {
                        this.resetLocation({ emitEvents: false });
                    }
                }
                if (!this.mapInstance) {
                    this.initializeMap();
                }
                this.scheduleInvalidateSize();
            };

            this.boundResetHandler = (event) => {
                if (event?.detail?.pickerId && event.detail.pickerId !== this.pickerId) {
                    return;
                }
                this.resetLocation();
            };

            if (this.$el && typeof this.$el.addEventListener === "function") {
                this.$el.addEventListener("location-picker:refresh", this.boundRefreshHandler);
                this.$el.addEventListener("location-picker:reset", this.boundResetHandler);
            }

            window.addEventListener("location-picker:refresh", this.boundRefreshHandler);
            window.addEventListener("location-picker:reset", this.boundResetHandler);

            this.initializeMap();
            this.refreshIcons();
        },

        refreshIcons() {
            this.$nextTick(() => {
                if (typeof window.renderLucideIcons === "function") {
                    window.renderLucideIcons();
                }
            });
        },

        async initializeMap() {
            const mapElement = this.$refs.mapCanvas;
            if (!mapElement || this.mapInstance || mapElement._leaflet_id) {
                return;
            }

            try {
                const leaflet = await ensureLeafletLoaded();

                if (!mapElement.isConnected || this.mapInstance || mapElement._leaflet_id) {
                    return;
                }

                const initialCenter = this.hasSelectedCoordinates
                    ? [this.selectedLatitude, this.selectedLongitude]
                    : [this.viewCenterLatitude, this.viewCenterLongitude];

                const initialZoom = this.hasSelectedCoordinates
                    ? this.selectedZoom
                    : this.defaultZoom;

                // Leaflet selalu menggunakan urutan [latitude, longitude]
                const map = leaflet.map(mapElement, {
                    center: initialCenter,
                    zoom: initialZoom,
                    zoomControl: true,
                    attributionControl: true,
                });

                leaflet
                    .tileLayer(OSM_TILE_URL, {
                        maxZoom: 19,
                        attribution: OSM_ATTRIBUTION,
                    })
                    .addTo(map);

                this.mapInstance = map;
                this.isMapReady = true;
                this.isMapError = false;

                if (this.hasSelectedCoordinates) {
                    this.renderOrMoveMarker(this.selectedLatitude, this.selectedLongitude);
                }

                map.on("click", (event) => {
                    if (this.disabled || !event?.latlng) {
                        return;
                    }
                    this.selectCoordinates(event.latlng.lat, event.latlng.lng, {
                        panTo: true,
                        zoom: null,
                    });
                });

                this.setupVisibilityAndResizeObservers(mapElement);
                this.scheduleInvalidateSize();
            } catch (error) {
                this.isMapError = true;
                this.setFeedback(
                    "Peta tidak dapat dimuat saat ini. Periksa koneksi internet Anda lalu muat ulang halaman.",
                    "error",
                );
            }
        },

        renderOrMoveMarker(latitude, longitude) {
            if (!this.mapInstance || !window.L) {
                return;
            }

            const latLngTuple = [latitude, longitude];

            if (this.markerInstance) {
                this.markerInstance.setLatLng(latLngTuple);
                return;
            }

            const marker = window.L.marker(latLngTuple, {
                draggable: !this.disabled,
                icon: createPinMarkerIcon(window.L),
                autoPan: true,
            }).addTo(this.mapInstance);

            marker.on("dragend", () => {
                if (this.disabled) {
                    return;
                }
                const position = marker.getLatLng();
                this.selectCoordinates(position.lat, position.lng, {
                    panTo: false,
                    zoom: null,
                });
            });

            this.markerInstance = marker;
        },

        removeMarker() {
            if (this.markerInstance && this.mapInstance) {
                this.markerInstance.off();
                this.mapInstance.removeLayer(this.markerInstance);
            }
            this.markerInstance = null;
        },

        selectCoordinates(rawLatitude, rawLongitude, options = {}) {
            const parsedLat = parseCoordinateValue(rawLatitude);
            const parsedLng = parseCoordinateValue(rawLongitude);

            if (!isValidCoordinatePair(parsedLat, parsedLng)) {
                this.setFeedback(
                    "Koordinat tidak valid. Latitude harus di antara -90 s/d 90 dan Longitude di antara -180 s/d 180.",
                    "error",
                );
                return false;
            }

            const normalizedLat = normalizeCoordinate(parsedLat);
            const normalizedLng = normalizeCoordinate(parsedLng);

            this.selectedLatitude = normalizedLat;
            this.selectedLongitude = normalizedLng;
            this.clearFeedback();

            if (this.mapInstance) {
                this.renderOrMoveMarker(normalizedLat, normalizedLng);

                const shouldPan = options.panTo !== false;
                const targetZoom = options.zoom ?? this.mapInstance.getZoom();

                if (shouldPan) {
                    this.mapInstance.setView([normalizedLat, normalizedLng], targetZoom, {
                        animate: true,
                    });
                }
            }

            if (options.emitEvents !== false) {
                this.syncExternalState();
            }

            return true;
        },

        clearLocation() {
            if (this.disabled) {
                return;
            }

            this.selectedLatitude = null;
            this.selectedLongitude = null;
            this.removeMarker();
            this.clearFeedback();
            this.syncExternalState();
        },

        resetLocation(options = {}) {
            this.selectedLatitude = null;
            this.selectedLongitude = null;
            this.removeMarker();
            this.clearFeedback();

            if (this.mapInstance) {
                this.mapInstance.setView(
                    [this.viewCenterLatitude, this.viewCenterLongitude],
                    this.defaultZoom,
                    { animate: false },
                );
                this.scheduleInvalidateSize();
            }

            if (options.emitEvents !== false) {
                this.syncExternalState();
            }
        },

        useCurrentLocation() {
            if (this.disabled || this.isLocating) {
                return;
            }

            if (typeof navigator === "undefined" || !("geolocation" in navigator)) {
                this.setFeedback(
                    "Browser Anda tidak mendukung fitur deteksi lokasi otomatis. Silakan klik titik lokasi langsung pada peta.",
                    "error",
                );
                return;
            }

            this.isLocating = true;
            this.clearFeedback();

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.isLocating = false;
                    const { latitude, longitude } = position.coords;

                    const updated = this.selectCoordinates(latitude, longitude, {
                        panTo: true,
                        zoom: this.selectedZoom,
                    });

                    if (updated) {
                        this.setFeedback(
                            "Lokasi perangkat ditemukan. Anda dapat menggeser pin agar lebih presisi.",
                            "info",
                        );
                    }
                },
                (error) => {
                    this.isLocating = false;
                    this.setFeedback(mapGeolocationError(error), "error");
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                },
            );
        },

        syncExternalState() {
            const payload = {
                pickerId: this.pickerId,
                latitude: this.selectedLatitude,
                longitude: this.selectedLongitude,
                hasCoordinates: this.hasSelectedCoordinates,
            };

            if (this.$refs.latitudeInput) {
                this.$refs.latitudeInput.value = this.formattedLatitude;
                this.$refs.latitudeInput.dispatchEvent(new Event("input", { bubbles: true }));
                this.$refs.latitudeInput.dispatchEvent(new Event("change", { bubbles: true }));
            }

            if (this.$refs.longitudeInput) {
                this.$refs.longitudeInput.value = this.formattedLongitude;
                this.$refs.longitudeInput.dispatchEvent(new Event("input", { bubbles: true }));
                this.$refs.longitudeInput.dispatchEvent(new Event("change", { bubbles: true }));
            }

            if (this.$wire && typeof this.$wire.set === "function") {
                if (this.wireLatitudeModel) {
                    this.$wire.set(this.wireLatitudeModel, this.selectedLatitude, false);
                }
                if (this.wireLongitudeModel) {
                    this.$wire.set(this.wireLongitudeModel, this.selectedLongitude, false);
                }
            }

            this.$dispatch("location-picker:change", payload);
        },

        setFeedback(message, type = "info") {
            this.feedbackMessage = message;
            this.feedbackType = type;
        },

        clearFeedback() {
            this.feedbackMessage = "";
            this.feedbackType = "";
        },

        setupVisibilityAndResizeObservers(mapElement) {
            if (typeof ResizeObserver !== "undefined") {
                this.resizeObserver = new ResizeObserver(() => {
                    this.scheduleInvalidateSize();
                });
                this.resizeObserver.observe(mapElement);
            }

            if (typeof IntersectionObserver !== "undefined") {
                this.intersectionObserver = new IntersectionObserver((entries) => {
                    const isVisible = entries.some((entry) => entry.isIntersecting);
                    if (isVisible) {
                        this.scheduleInvalidateSize();
                    }
                });
                this.intersectionObserver.observe(mapElement);
            }
        },

        clearInvalidateTimers() {
            if (this.invalidateTimer) {
                clearTimeout(this.invalidateTimer);
                this.invalidateTimer = null;
            }

            if (Array.isArray(this.invalidateTimers) && this.invalidateTimers.length > 0) {
                this.invalidateTimers.forEach((timerId) => clearTimeout(timerId));
                this.invalidateTimers = [];
            }
        },

        performInvalidateSize() {
            if (!this.mapInstance) {
                return;
            }

            const mapElement = this.$refs?.mapCanvas;
            const isVisible =
                mapElement && mapElement.clientWidth > 0 && mapElement.clientHeight > 0;

            if (!this.hasRenderedVisibleCanvas && isVisible) {
                this.hasRenderedVisibleCanvas = true;
                this.mapInstance.invalidateSize({ animate: false });

                const targetCenter = this.hasSelectedCoordinates
                    ? [this.selectedLatitude, this.selectedLongitude]
                    : [this.viewCenterLatitude, this.viewCenterLongitude];
                const targetZoom = this.hasSelectedCoordinates
                    ? this.selectedZoom
                    : this.defaultZoom;

                this.mapInstance.setView(targetCenter, targetZoom, { animate: false });
                return;
            }

            this.mapInstance.invalidateSize({ pan: false });
        },

        scheduleInvalidateSize() {
            this.clearInvalidateTimers();

            const delays = [60, 180, 340];
            this.invalidateTimers = delays.map((delay) =>
                setTimeout(() => {
                    this.performInvalidateSize();
                }, delay),
            );
            this.invalidateTimer = this.invalidateTimers[0] || null;
        },

        destroy() {
            if (this.boundRefreshHandler) {
                if (this.$el && typeof this.$el.removeEventListener === "function") {
                    this.$el.removeEventListener(
                        "location-picker:refresh",
                        this.boundRefreshHandler,
                    );
                }
                window.removeEventListener("location-picker:refresh", this.boundRefreshHandler);
                this.boundRefreshHandler = null;
            }

            if (this.boundResetHandler) {
                if (this.$el && typeof this.$el.removeEventListener === "function") {
                    this.$el.removeEventListener("location-picker:reset", this.boundResetHandler);
                }
                window.removeEventListener("location-picker:reset", this.boundResetHandler);
                this.boundResetHandler = null;
            }

            this.clearInvalidateTimers();

            if (this.resizeObserver) {
                this.resizeObserver.disconnect();
                this.resizeObserver = null;
            }

            if (this.intersectionObserver) {
                this.intersectionObserver.disconnect();
                this.intersectionObserver = null;
            }

            this.removeMarker();

            if (this.mapInstance) {
                this.mapInstance.off();
                this.mapInstance.remove();
                this.mapInstance = null;
            }
        },
    };
}

export function refreshLocationPicker(pickerId, coordinates = {}) {
    if (!pickerId || typeof document === "undefined") {
        return;
    }

    const pickerElement = document.getElementById(pickerId);
    if (pickerElement) {
        pickerElement.dispatchEvent(
            new CustomEvent("location-picker:refresh", {
                detail: { ...coordinates, pickerId },
                bubbles: false,
            }),
        );
    }
}

export function resetLocationPicker(pickerId) {
    if (!pickerId || typeof document === "undefined") {
        return;
    }

    const pickerElement = document.getElementById(pickerId);
    if (pickerElement) {
        pickerElement.dispatchEvent(
            new CustomEvent("location-picker:reset", {
                detail: { pickerId },
                bubbles: false,
            }),
        );
    }
}

if (typeof window !== "undefined") {
    window.refreshLocationPicker = refreshLocationPicker;
    window.resetLocationPicker = resetLocationPicker;
}

function registerLocationPickerAlpineComponent() {
    if (typeof window === "undefined" || !window.Alpine) {
        return;
    }

    window.Alpine.data("locationPicker", createLocationPickerState);
}

document.addEventListener("alpine:init", registerLocationPickerAlpineComponent);

if (typeof window !== "undefined" && window.Alpine) {
    registerLocationPickerAlpineComponent();
}

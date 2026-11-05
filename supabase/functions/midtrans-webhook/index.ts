import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "jsr:@supabase/supabase-js@2";

/**
 * Midtrans Webhook Handler - Supabase Edge Function
 *
 * Menerima webhook langsung dari Midtrans Sandbox/Production,
 * memvalidasi SHA-512 signature, lalu mengupdate database Supabase
 * (tabel payments) secara realtime tanpa perlu tunnel ngrok.
 *
 * Escrow dan komisi TIDAK dibuat di sini, tapi oleh Laravel lewat
 * command payments:reconcile.
 */

const JSON_HEADERS = { "Content-Type": "application/json" };

function json(body: unknown, status = 200): Response {
    return new Response(JSON.stringify(body), {
        status,
        headers: JSON_HEADERS,
    });
}

// Helper: Ubah ArrayBuffer menjadi hex string
function buf2hex(buffer: ArrayBuffer): string {
    return [...new Uint8Array(buffer)]
        .map((x) => x.toString(16).padStart(2, "0"))
        .join("");
}

// Helper: Constant-time comparison untuk mencegah timing attack
function constantTimeEquals(a: string, b: string): boolean {
    if (a.length !== b.length) return false;
    let result = 0;
    for (let i = 0; i < a.length; i++) {
        result |= a.charCodeAt(i) ^ b.charCodeAt(i);
    }
    return result === 0;
}

// Helper: Pemetaan status Midtrans ke status internal Tendaku.
// Harus sama dengan MidtransService::mapMidtransStatus() di Laravel.
function mapMidtransStatus(
    transactionStatus: string,
    fraudStatus?: string | null,
): string | null {
    switch (transactionStatus) {
        case "settlement":
            return "paid";
        case "capture":
            if (fraudStatus === "accept") return "paid";
            if (fraudStatus === "deny") return "failed";
            return null;
        case "expire":
            return "expired";
        case "cancel":
            return "cancelled";
        case "deny":
        case "failure":
            return "failed";
        case "refund":
        case "partial_refund":
            return "refunded";
        default:
            return null;
    }
}

// Transisi status 1-arah. Sama dengan PaymentStatus::canTransitionTo() di Laravel.
function canTransition(current: string, next: string): boolean {
    if (current === next) return false;
    if (current === "pending") return true;
    if (current === "paid") return next === "refunded";
    return false;
}

function isMidtransDashboardPing(orderId: unknown): boolean {
    return (
        typeof orderId === "string" && orderId.startsWith("payment_notif_test_")
    );
}

Deno.serve(async (req: Request) => {
    if (req.method === "OPTIONS") {
        return new Response("ok", {
            status: 200,
            headers: {
                "Access-Control-Allow-Origin": "*",
                "Access-Control-Allow-Methods": "POST, OPTIONS",
                "Access-Control-Allow-Headers": "Content-Type, Authorization",
            },
        });
    }

    if (req.method !== "POST") {
        return json({ message: "Method not allowed" }, 405);
    }

    try {
        // Body kosong / bukan JSON -> 400, bukan 500
        const payload = await req.json().catch(() => null);
        if (!payload || typeof payload !== "object") {
            return json({ message: "Bad request" }, 400);
        }

        const {
            order_id,
            status_code,
            gross_amount,
            signature_key,
            transaction_status,
            transaction_id,
            fraud_status,
            payment_type,
        } = payload;

        if (
            !order_id ||
            !status_code ||
            !gross_amount ||
            !signature_key ||
            !transaction_status
        ) {
            return json({ message: "Payload webhook tidak lengkap" }, 422);
        }

        if (isMidtransDashboardPing(order_id)) {
            console.log("Midtrans test ping received:", order_id);
            return json({
                status: "success",
                message: "Test notification received successfully",
            });
        }

        const serverKey = Deno.env.get("MIDTRANS_SERVER_KEY");
        if (!serverKey) {
            console.error(
                "MIDTRANS_SERVER_KEY secret belum diset di Supabase!",
            );
            return json({ message: "Konfigurasi server belum lengkap" }, 500);
        }

        const rawString = `${order_id}${status_code}${gross_amount}${serverKey}`;
        const hashBuffer = await crypto.subtle.digest(
            "SHA-512",
            new TextEncoder().encode(rawString),
        );
        const expectedSignature = buf2hex(hashBuffer);

        if (!constantTimeEquals(expectedSignature, String(signature_key))) {
            console.warn(
                "Signature Midtrans tidak valid untuk order:",
                order_id,
            );
            return json({ message: "Invalid signature" }, 403);
        }

        const supabaseUrl = Deno.env.get("SUPABASE_URL");
        const supabaseServiceKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY");

        if (!supabaseUrl || !supabaseServiceKey) {
            console.error(
                "SUPABASE_URL atau SUPABASE_SERVICE_ROLE_KEY tidak ditemukan!",
            );
            return json({ message: "Database credential missing" }, 500);
        }

        const supabase = createClient(supabaseUrl, supabaseServiceKey);

        const { data: payment, error: pError } = await supabase
            .from("payments")
            .select(
                "id, rental_id, amount, status, method, paid_at, raw_notification",
            )
            .eq("order_id", order_id)
            .single();

        if (pError || !payment) {
            console.warn(
                "Payment tidak ditemukan di database untuk order_id:",
                order_id,
            );
            return json({ status: "ignored", message: "Payment not found" });
        }

        const notifGross = parseFloat(gross_amount);
        const payAmount = parseFloat(payment.amount);
        if (Math.abs(notifGross - payAmount) > 0.01) {
            console.warn("Nominal gross_amount tidak cocok:", {
                notifGross,
                payAmount,
            });
            return json({ message: "Nominal tidak cocok" }, 422);
        }

        const internalStatus = mapMidtransStatus(
            transaction_status,
            fraud_status,
        );

        const updateFields: Record<string, unknown> = {
            midtrans_status: transaction_status,
            midtrans_transaction_id: transaction_id || null,
            raw_notification: {
                ...(payment.raw_notification ?? {}),
                ...payload,
            },
            updated_at: new Date().toISOString(),
        };

        if (payment_type && !payment.method) {
            updateFields.method = payment_type;
        }

        // Aturan status 1-arah (lihat canTransition)
        if (internalStatus && canTransition(payment.status, internalStatus)) {
            updateFields.status = internalStatus;
            if (internalStatus === "paid" && !payment.paid_at) {
                updateFields.paid_at = new Date().toISOString();
            }
        }

        const { error: updateError } = await supabase
            .from("payments")
            .update(updateFields)
            .eq("id", payment.id);

        if (updateError) {
            console.error("Gagal mengupdate payment:", updateError);
            return json({ message: "Gagal mengupdate database" }, 500);
        }

        // Catatan: status rental TIDAK diubah di sini. Rental tetap pending sampai
        // kasir memvalidasi barang di POS. Komisi dan escrow dibuat oleh Laravel
        // lewat command payments:reconcile.

        return json({
            status: "success",
            order_id,
            payment_status: updateFields.status ?? payment.status,
        });
    } catch (err) {
        console.error("Unhandled error di Edge Function:", err);
        return json({ message: "Internal server error" }, 500);
    }
});

import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "jsr:@supabase/supabase-js@2";

/**
 * Midtrans Webhook Handler - Supabase Edge Function
 *
 * Menerima webhook langsung dari Midtrans Sandbox/Production,
 * memvalidasi SHA-512 signature, lalu mengupdate database Supabase
 * (tabel payments & rentals) secara realtime tanpa perlu tunnel ngrok.
 */

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

Deno.serve(async (req: Request) => {
    // 1. Handle CORS preflight jika ada
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
        return new Response(JSON.stringify({ message: "Method not allowed" }), {
            status: 405,
            headers: { "Content-Type": "application/json" },
        });
    }

    try {
        const payload = await req.json();

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
            return new Response(
                JSON.stringify({ message: "Payload webhook tidak lengkap" }),
                {
                    status: 422,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        // 2. Verifikasi SHA-512 Signature Midtrans
        const serverKey = Deno.env.get("MIDTRANS_SERVER_KEY");
        if (!serverKey) {
            console.error(
                "MIDTRANS_SERVER_KEY secret belum diset di Supabase!",
            );
            return new Response(
                JSON.stringify({ message: "Konfigurasi server belum lengkap" }),
                {
                    status: 500,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        const rawString = `${order_id}${status_code}${gross_amount}${serverKey}`;
        const hashBuffer = await crypto.subtle.digest(
            "SHA-512",
            new TextEncoder().encode(rawString),
        );
        const expectedSignature = buf2hex(hashBuffer);

        if (!constantTimeEquals(expectedSignature, signature_key)) {
            console.warn(
                "Signature Midtrans tidak valid untuk order:",
                order_id,
            );
            return new Response(
                JSON.stringify({ message: "Invalid signature" }),
                {
                    status: 403,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        // 3. Inisialisasi Supabase Client dengan Service Role Key
        const supabaseUrl = Deno.env.get("SUPABASE_URL");
        const supabaseServiceKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY");

        if (!supabaseUrl || !supabaseServiceKey) {
            console.error(
                "SUPABASE_URL atau SUPABASE_SERVICE_ROLE_KEY tidak ditemukan!",
            );
            return new Response(
                JSON.stringify({ message: "Database credential missing" }),
                {
                    status: 500,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        const supabase = createClient(supabaseUrl, supabaseServiceKey);

        // Handle Midtrans Dashboard "Test notification URL" ping
        if (
            typeof order_id === "string" &&
            (order_id.toLowerCase().includes("test") ||
                order_id.toLowerCase().includes("sample") ||
                order_id.toLowerCase().includes("dummy"))
        ) {
            console.log("Midtrans test ping received successfully:", order_id);
            return new Response(
                JSON.stringify({
                    status: "success",
                    message: "Test notification received successfully",
                }),
                {
                    status: 200,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        // 4. Cari data payment berdasarkan order_id
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
            // Return 200 agar Midtrans test / unknown order tidak dianggap error 5xx/4xx
            return new Response(
                JSON.stringify({
                    status: "ignored",
                    message: "Payment not found",
                }),
                {
                    status: 200,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        // 5. Validasi kesesuaian nominal
        const notifGross = parseFloat(gross_amount);
        const payAmount = parseFloat(payment.amount);
        if (Math.abs(notifGross - payAmount) > 0.01) {
            console.warn("Nominal gross_amount tidak cocok:", {
                notifGross,
                payAmount,
            });
            return new Response(
                JSON.stringify({ message: "Nominal tidak cocok" }),
                {
                    status: 422,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        // 6. Petakan status
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

        // 7. Simpan pembaruan payment
        const { error: updateError } = await supabase
            .from("payments")
            .update(updateFields)
            .eq("id", payment.id);

        if (updateError) {
            console.error("Gagal mengupdate payment:", updateError);
            return new Response(
                JSON.stringify({ message: "Gagal mengupdate database" }),
                {
                    status: 500,
                    headers: { "Content-Type": "application/json" },
                },
            );
        }

        // Catatan: status rental TIDAK diubah di sini. Rental tetap pending sampai
        // kasir memvalidasi barang di POS. Komisi dan escrow dibuat oleh Laravel
        // lewat command payments:reconcile.

        return new Response(
            JSON.stringify({
                status: "success",
                order_id,
                payment_status: updateFields.status ?? payment.status,
            }),
            { status: 200, headers: { "Content-Type": "application/json" } },
        );
    } catch (err) {
        console.error("Unhandled error di Edge Function:", err);
        return new Response(
            JSON.stringify({ message: "Internal server error" }),
            { status: 500, headers: { "Content-Type": "application/json" } },
        );
    }
});

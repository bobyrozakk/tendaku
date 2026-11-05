<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Edge Function Supabase yang menandai payment lunas menulis paid_at/updated_at
 * dalam UTC. Trigger ini menormalkannya ke waktu dinding Asia/Jakarta (24 jam),
 * sama seperti created_at. Berlaku untuk penulis mana pun (Laravel/Edge Function).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
-- 1) Perbaiki data lama: baris Midtrans lunas yang paid_at-nya tertulis UTC
--    (selisih ~ -7 jam dari settlement_time Midtrans yang berzona Jakarta).
UPDATE payments
SET updated_at = updated_at + interval '7 hours',
    paid_at    = (raw_notification::jsonb ->> 'settlement_time')::timestamp
WHERE channel = 'midtrans'
  AND status = 'paid'
  AND raw_notification::jsonb ->> 'settlement_time' ~ '^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}'
  AND paid_at - (raw_notification::jsonb ->> 'settlement_time')::timestamp
      BETWEEN interval '-7 hours 5 minutes' AND interval '-6 hours 55 minutes';

-- 2) Trigger agar data baru selalu waktu Jakarta.
CREATE OR REPLACE FUNCTION payments_jakarta_time() RETURNS trigger AS $$
DECLARE
    st text;
BEGIN
    NEW.updated_at := timezone('Asia/Jakarta', now());

    IF NEW.status = 'paid' AND NEW.channel = 'midtrans'
       AND (TG_OP = 'INSERT'
            OR OLD.status IS DISTINCT FROM 'paid'
            OR NEW.paid_at IS DISTINCT FROM OLD.paid_at) THEN
        st := NEW.raw_notification::jsonb ->> 'settlement_time';
        IF st ~ '^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}' THEN
            NEW.paid_at := st::timestamp;               -- sudah waktu Jakarta
        ELSE
            NEW.paid_at := timezone('Asia/Jakarta', now());
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_payments_jakarta_time ON payments;
CREATE TRIGGER trg_payments_jakarta_time
    BEFORE INSERT OR UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION payments_jakarta_time();
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_payments_jakarta_time ON payments; DROP FUNCTION IF EXISTS payments_jakarta_time();');
    }
};

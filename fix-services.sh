#!/usr/bin/env bash
# Jalankan dari root proyek Laravel: bash fix-services.sh
# Merapikan namespace Services setelah file dipindah ke Booking/, Payment/, Finance/.
set -e
[ -d app/Services/Booking ] || { echo "Jalankan dari root proyek (folder app/Services/Booking tidak ditemukan)"; exit 1; }

echo "[1/4] Samakan namespace dengan nama folder"
for f in Booking Payment Finance; do
  for p in app/Services/$f/*.php; do
    if grep -q '^namespace ' "$p"; then
      sed -i -E "s/^namespace App\\\\Services(\\\\[A-Za-z]+)?;/namespace App\\\\Services\\\\$f;/" "$p"
    else
      echo "  PERHATIAN: $p tidak punya baris namespace (kosong atau belum selesai?)"
    fi
  done
done

echo "[2/4] Ganti referensi lama (use dan nama lengkap) di app, resources, routes"
fix() { folder=$1; shift; for c in "$@"; do
  grep -rlE "App\\\\Services\\\\$c\b" app resources routes 2>/dev/null \
    | xargs -r sed -i -E "s/App\\\\Services\\\\$c\b/App\\\\Services\\\\$folder\\\\$c/g"
done; }
fix Booking AvailabilityService BookingReceiptService CartService CheckoutService CheckoutSummaryBuilder RentalAccessGuard RentalService
fix Payment MidtransService PaymentService
fix Finance CommissionCalculator CommissionService EscrowService SettlementService

echo "[3/4] Tambah 'use' untuk service yang saling memanggil lintas folder"
python3 - <<'PY'
import re, glob, os
classes = {}
for ns in ["Booking", "Payment", "Finance"]:
    for p in glob.glob(f"app/Services/{ns}/*.php"):
        classes[os.path.basename(p)[:-4]] = ns
for p in glob.glob("app/Services/**/*.php", recursive=True):
    s = open(p).read()
    m = re.search(r"^namespace ([^;]+);", s, re.M)
    if not m:
        continue
    myns = m.group(1)
    body = re.sub(r"^(namespace|use) .*$", "", s, flags=re.M)
    need = []
    for c, ns in classes.items():
        target = f"App\\Services\\{ns}"
        if target == myns or c == os.path.basename(p)[:-4]:
            continue
        pattern = r"(?<![\\\w])" + c + r"\b"
        has_use = re.search(r"^use " + re.escape(target + "\\" + c) + ";", s, re.M)
        if re.search(pattern, body) and not has_use:
            need.append(f"use {target}\\{c};")
    if need:
        s = s.replace(m.group(0), m.group(0) + "\n\n" + "\n".join(sorted(need)), 1)
        open(p, "w").write(s)
        print(f"  {p}: +{len(need)} use")
PY

echo "[4/4] Pengecekan"
bad=0
for f in Booking Payment Finance; do
  out=$(grep -L "^namespace App\\\\Services\\\\$f;" app/Services/$f/*.php || true)
  [ -n "$out" ] && { echo "  namespace belum benar:"; echo "$out"; bad=1; }
done
old=$(grep -rnE "App\\\\Services\\\\(AvailabilityService|BookingReceiptService|CartService|CheckoutService|CheckoutSummaryBuilder|RentalAccessGuard|RentalService|MidtransService|PaymentService|CommissionCalculator|CommissionService|EscrowService|SettlementService)\b" app resources routes || true)
[ -n "$old" ] && { echo "  referensi lama tersisa:"; echo "$old"; bad=1; }
[ $bad -eq 0 ] && echo "  OK: semua namespace dan referensi sudah sesuai." || echo "  Masih ada yang perlu dicek (lihat di atas)."
echo "Lanjut: composer dump-autoload && php artisan optimize:clear"

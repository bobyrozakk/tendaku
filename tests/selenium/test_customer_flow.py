"""E2E alur customer: login, tambah ke keranjang, checkout, lihat status booking."""

from __future__ import annotations

from datetime import date, timedelta

from pages.cart_page import CartPage
from pages.catalog_page import CatalogPage
from pages.checkout_page import CheckoutPage
from pages.login_page import LoginPage


def test_customer_can_create_booking_from_catalog(driver, base_url, rental_cleanup):
    LoginPage(driver, base_url).open().login("test@example.com", "password")
    LoginPage(driver, base_url).wait_until_login_finished()

    cart = CartPage(driver, base_url).open()
    cart.clear_if_needed()

    catalog = CatalogPage(driver, base_url).open()
    item_name = catalog.add_first_available_item()

    cart.open()
    assert cart.has_item(item_name), f"Produk '{item_name}' tidak muncul di keranjang."

    pickup_date = date.today() + timedelta(days=30)
    return_date = pickup_date + timedelta(days=2)
    cart.set_dates(pickup_date.isoformat(), return_date.isoformat())
    cart.proceed_to_checkout()

    checkout = CheckoutPage(driver, base_url).wait_until_ready()
    checkout.fill_missing_identity()
    checkout.submit_booking()
    checkout.wait_for_status_page()

    booking_code = checkout.booking_code()
    rental_cleanup(booking_code)

    body = checkout.body_text()
    assert booking_code in body, "Kode booking tidak tampil di halaman status."
    assert "Menunggu Pembayaran" in body
    assert "Durasi Sewa" in body
    assert "2 hari" in body

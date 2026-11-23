"""E2E panel Super Admin dan pembatasan aksesnya."""

from __future__ import annotations

import pytest

from pages.admin_page import AdminPage
from pages.login_page import LoginPage


@pytest.fixture()
def admin_page(driver, base_url) -> AdminPage:
    LoginPage(driver, base_url).open_superadmin().login("admin@tendaku.com", "password")
    page = AdminPage(driver, base_url)
    page.wait_for_path("/dashboard")
    return page


def test_super_admin_dashboard_shows_vendor_management(admin_page: AdminPage):
    admin_page.open_dashboard()

    body = admin_page.body_text()
    assert "Kelola & Approval Vendor" in body
    assert "Tendaku Store Central" in body


def test_super_admin_can_open_revenue_report(admin_page: AdminPage):
    admin_page.open_revenue_report()

    body = admin_page.body_text()
    assert "Laba Bersih Platform" in body
    assert "Tren Komisi Platform" in body
    assert "Ringkasan per Vendor" in body
    assert "Server Error" not in body


def test_super_admin_can_open_settlement_page(admin_page: AdminPage):
    admin_page.open_settlements()

    body = admin_page.body_text()
    assert "Daftar Batch Settlement" in body
    assert "Server Error" not in body


def test_customer_cannot_access_admin_area(driver, base_url):
    LoginPage(driver, base_url).open().login("test@example.com", "password")
    LoginPage(driver, base_url).wait_until_login_finished()

    page = AdminPage(driver, base_url)
    page.visit("/admin/pendapatan")

    body = page.body_text()
    assert "403" in body or "Akses ditolak" in body

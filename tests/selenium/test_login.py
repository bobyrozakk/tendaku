"""E2E login: customer, vendor owner, kasir, dan super admin."""

from __future__ import annotations

import pytest

from pages.login_page import LoginPage

PASSWORD = "password"

ROLE_LOGINS = [
    pytest.param("test@example.com", "/", id="customer"),
    pytest.param("owner@tendaku.com", "/vendor/owner/dashboard", id="vendor-owner"),
    pytest.param("kasir@tendaku.com", "/kasir/dashboard", id="kasir"),
]


@pytest.mark.parametrize(("email", "expected_path"), ROLE_LOGINS)
def test_login_redirects_each_role_to_its_home(driver, base_url, email, expected_path):
    page = LoginPage(driver, base_url).open()
    page.login(email, PASSWORD)

    page.wait_until_login_finished()
    page.wait_for_path(expected_path)

    assert page.current_path() == expected_path


def test_super_admin_logs_in_through_admin_gateway(driver, base_url):
    page = LoginPage(driver, base_url).open_superadmin()
    page.login("admin@tendaku.com", PASSWORD)

    page.wait_for_path("/dashboard")

    assert page.current_path() == "/dashboard"
    assert "Dashboard Operasional" in page.body_text()


def test_invalid_password_shows_validation_error(driver, base_url):
    page = LoginPage(driver, base_url).open()
    page.login("test@example.com", "password-salah")

    page.wait_for_text("These credentials do not match our records.")

    assert "These credentials do not match our records." in page.body_text()
    assert page.current_path() == "/login"

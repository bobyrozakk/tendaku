"""Halaman login customer/vendor dan super admin."""

from __future__ import annotations

from pages.base_page import BasePage


class LoginPage(BasePage):
    EMAIL_INPUT = 'input[name="form.email"]'
    PASSWORD_INPUT = 'input[name="form.password"]'
    SUBMIT_BUTTON = 'form button[type="submit"]'

    def open(self) -> "LoginPage":
        self.visit("/login")
        return self

    def open_superadmin(self) -> "LoginPage":
        self.visit("/login/supadmin")
        return self

    def login(self, email: str, password: str) -> "LoginPage":
        self.type(self.EMAIL_INPUT, email)
        self.type(self.PASSWORD_INPUT, password)
        self.click(self.SUBMIT_BUTTON)
        return self

    def error_message(self) -> str:
        return self.find_visible("#form\\.email-error").text

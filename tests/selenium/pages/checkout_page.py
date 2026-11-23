"""Halaman checkout booking dan halaman status booking."""

from __future__ import annotations

import re

from selenium.webdriver.common.by import By

from pages.base_page import BasePage


class CheckoutPage(BasePage):
    IDENTITY_SECTION = "#section-identity"
    NIK_INPUT = "#f_nik"
    NAME_INPUT = "#f_name"
    PHONE_INPUT = "#f_phone"
    SUBMIT_BUTTON_XPATH = "//button[contains(normalize-space(.), 'Buat booking')]"
    STATUS_PATH = "/katalog/booking/status/"

    def wait_until_ready(self) -> "CheckoutPage":
        self.wait.until(
            lambda current: current.find_elements(By.CSS_SELECTOR, self.IDENTITY_SECTION)
        )
        return self

    def identity_field_is_visible(self, css_selector: str) -> bool:
        elements = self.find_all(css_selector)
        return bool(elements) and elements[0].is_displayed()

    def fill_missing_identity(self, nik: str = "3573012345670001") -> "CheckoutPage":
        if self.identity_field_is_visible(self.NIK_INPUT):
            self.type(self.NIK_INPUT, nik)

        if self.identity_field_is_visible(self.NAME_INPUT):
            self.type(self.NAME_INPUT, "Test Customer")

        if self.identity_field_is_visible(self.PHONE_INPUT):
            self.type(self.PHONE_INPUT, "081234567800")

        return self

    def submit_booking(self) -> "CheckoutPage":
        self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")

        button = self.wait.until(
            lambda current: current.find_element(By.XPATH, self.SUBMIT_BUTTON_XPATH)
        )

        self.wait.until(lambda current: button.is_enabled())
        self.driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", button)
        button.click()
        return self

    def wait_for_status_page(self, timeout: float = 45) -> "CheckoutPage":
        self.wait_for_path_containing(self.STATUS_PATH, timeout=timeout)
        return self

    def booking_code(self) -> str:
        match = re.search(r"/katalog/booking/status/([A-Za-z0-9]+)", self.current_url())

        if not match:
            raise AssertionError(f"Booking code tidak ditemukan pada URL {self.current_url()}")

        return match.group(1)

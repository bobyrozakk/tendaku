"""Halaman keranjang sewa."""

from __future__ import annotations

from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC

from pages.base_page import BasePage


class CartPage(BasePage):
    PICKUP_DATE = 'input[name="pickup_date"]'
    RETURN_DATE = 'input[name="return_date"]'
    EMPTY_STATE = "Keranjang Sewamu Masih Kosong"

    def open(self) -> "CartPage":
        self.visit("/keranjang")
        self.wait.until(
            lambda current: current.find_elements(By.CSS_SELECTOR, self.PICKUP_DATE)
            or self.EMPTY_STATE in current.find_element(By.TAG_NAME, "body").text
        )
        return self

    def clear_if_needed(self) -> "CartPage":
        buttons = [
            button
            for button in self.find_all("button")
            if "Kosongkan Keranjang" in button.text
        ]

        if not buttons:
            return self

        buttons[0].click()

        try:
            self.wait.until(EC.alert_is_present()).accept()
        except TimeoutException:
            pass

        self.wait.until(
            lambda current: self.EMPTY_STATE in current.find_element(By.TAG_NAME, "body").text
        )
        return self

    def has_item(self, item_name: str) -> bool:
        return item_name in self.body_text()

    def set_dates(self, pickup_date: str, return_date: str) -> "CartPage":
        for selector, value in (
            (self.PICKUP_DATE, pickup_date),
            (self.RETURN_DATE, return_date),
        ):
            element = self.find(selector)
            self.driver.execute_script(
                """
                arguments[0].value = arguments[1];
                arguments[0].dispatchEvent(new Event('input', { bubbles: true }));
                arguments[0].dispatchEvent(new Event('change', { bubbles: true }));
                """,
                element,
                value,
            )
        return self

    def proceed_to_checkout(self) -> "CartPage":
        self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        button = self.wait.until(
            lambda current: current.find_element(
                By.XPATH, "//button[contains(normalize-space(.), 'Lanjut ke Checkout')]"
            )
        )
        self.driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", button)
        button.click()
        self.wait_for_path_containing("/katalog/booking")
        return self

"""Halaman katalog produk customer."""

from __future__ import annotations

import time

from selenium.webdriver.common.by import By

from pages.base_page import BasePage


class CatalogPage(BasePage):
    ADD_BUTTON = 'button[aria-label^="Tambah "][aria-label$=" ke keranjang"]'
    CART_LINK = 'a[title="Keranjang Sewa"]'
    CART_BADGE = f'{CART_LINK} span'

    def open(self) -> "CatalogPage":
        self.visit("/katalog")
        self.wait.until(lambda current: len(current.find_elements(By.CSS_SELECTOR, self.ADD_BUTTON)) > 0)
        return self

    def add_first_available_item(self) -> str:
        """Klik tombol tambah pada kartu pertama yang stoknya tersedia."""
        candidates = self.find_all(self.ADD_BUTTON)

        for button in candidates:
            if not (button.is_displayed() and button.is_enabled()):
                continue

            label = button.get_attribute("aria-label")
            item_name = label.removeprefix("Tambah ").removesuffix(" ke keranjang")

            button.click()
            self._wait_until_cart_has_items()
            time.sleep(0.3)
            return item_name

        raise AssertionError("Tidak ada produk dengan stok tersedia di katalog.")

    def _wait_until_cart_has_items(self) -> None:
        self.wait.until(
            lambda current: current.find_element(By.CSS_SELECTOR, self.CART_BADGE).text.strip().isdigit()
            and int(current.find_element(By.CSS_SELECTOR, self.CART_BADGE).text.strip()) > 0
        )

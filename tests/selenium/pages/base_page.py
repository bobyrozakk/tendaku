"""Page object dasar untuk semua halaman Tendaku."""

from __future__ import annotations

from urllib.parse import urlparse

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


class BasePage:
    def __init__(self, driver, base_url: str, timeout: float = 20) -> None:
        self.driver = driver
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self.wait = WebDriverWait(driver, timeout)

    def visit(self, path: str = "/"):
        self.driver.get(f"{self.base_url}{path}")
        return self

    def current_path(self) -> str:
        return urlparse(self.driver.current_url).path

    def current_url(self) -> str:
        return self.driver.current_url

    def body_text(self) -> str:
        return self.driver.find_element(By.TAG_NAME, "body").text

    def find(self, css_selector: str):
        return self.wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, css_selector))
        )

    def find_visible(self, css_selector: str):
        return self.wait.until(
            EC.visibility_of_element_located((By.CSS_SELECTOR, css_selector))
        )

    def find_all(self, css_selector: str) -> list:
        return self.driver.find_elements(By.CSS_SELECTOR, css_selector)

    def click(self, css_selector: str):
        element = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, css_selector))
        )
        element.click()
        return element

    def type(self, css_selector: str, value: str):
        element = self.find_visible(css_selector)
        element.clear()
        element.send_keys(value)
        return element

    def wait_for_path(self, path: str, timeout: float | None = None) -> None:
        wait = WebDriverWait(self.driver, timeout or self.timeout)
        wait.until(lambda current: urlparse(current.current_url).path == path)

    def wait_for_path_containing(self, fragment: str, timeout: float | None = None) -> None:
        wait = WebDriverWait(self.driver, timeout or self.timeout)
        wait.until(lambda current: fragment in urlparse(current.current_url).path)

    def wait_for_text(self, text: str, timeout: float | None = None) -> None:
        wait = WebDriverWait(self.driver, timeout or self.timeout)
        wait.until(lambda current: text in current.find_element(By.TAG_NAME, "body").text)

    def wait_until_login_finished(self, timeout: float | None = None) -> None:
        wait = WebDriverWait(self.driver, timeout or self.timeout)
        wait.until(lambda current: "/login" not in urlparse(current.current_url).path)

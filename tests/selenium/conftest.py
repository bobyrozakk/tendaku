"""Konfigurasi pytest + Selenium WebDriver untuk E2E test Tendaku."""

from __future__ import annotations

import os
import re
import subprocess
from pathlib import Path

import pytest
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait

HERE = Path(__file__).resolve().parent
PROJECT_ROOT = HERE.parents[1]
ARTIFACTS_DIR = HERE / "artifacts"

DEFAULT_BASE_URL = os.environ.get("TENDAKU_BASE_URL", "http://localhost:8000")
DEFAULT_TIMEOUT = float(os.environ.get("SELENIUM_TIMEOUT", "20"))
HEADED_BY_DEFAULT = os.environ.get("SELENIUM_HEADED", "0") == "1"


def pytest_addoption(parser: pytest.Parser) -> None:
    parser.addoption(
        "--base-url",
        action="store",
        default=DEFAULT_BASE_URL,
        help="Base URL aplikasi Tendaku yang sedang berjalan.",
    )
    parser.addoption(
        "--headed",
        action="store_true",
        default=HEADED_BY_DEFAULT,
        help="Tampilkan jendela Chrome (default: headless).",
    )
    parser.addoption(
        "--selenium-timeout",
        action="store",
        type=float,
        default=DEFAULT_TIMEOUT,
        help="Batas waktu tunggu elemen (detik).",
    )


@pytest.fixture(scope="session")
def base_url(request: pytest.FixtureRequest) -> str:
    return str(request.config.getoption("--base-url")).rstrip("/")


@pytest.fixture(scope="session")
def timeout(request: pytest.FixtureRequest) -> float:
    return float(request.config.getoption("--selenium-timeout"))


@pytest.fixture()
def driver(request: pytest.FixtureRequest):
    options = Options()
    options.add_argument("--window-size=1440,1000")
    options.add_argument("--disable-gpu")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--lang=id-ID")
    options.set_capability("goog:loggingPrefs", {"browser": "ALL"})

    if not request.config.getoption("--headed"):
        options.add_argument("--headless=new")

    browser = webdriver.Chrome(options=options)
    browser.set_window_size(1440, 1000)
    browser.set_page_load_timeout(60)

    yield browser

    browser.quit()


@pytest.fixture()
def wait(driver, timeout: float) -> WebDriverWait:
    return WebDriverWait(driver, timeout)


@pytest.fixture()
def rental_cleanup():
    """Kumpulkan booking code yang dibuat test lalu hapus setelah test selesai."""
    codes: list[str] = []
    yield codes.append

    for code in codes:
        delete_rental(code)


def delete_rental(booking_code: str) -> None:
    """Hapus booking buatan test agar database tetap bersih dan test bisa diulang."""
    safe_code = re.sub(r"[^A-Za-z0-9]", "", booking_code)

    if not safe_code:
        return

    php = (
        f'$rental = App\\Models\\Rental::where("booking_code", "{safe_code}")->first(); '
        'if ($rental) { '
        'App\\Models\\Payment::where("rental_id", $rental->id)->delete(); '
        'App\\Models\\RentalDetail::where("rental_id", $rental->id)->delete(); '
        '$rental->delete(); '
        '}'
    )

    subprocess.run(
        ["php", "artisan", "tinker", "--execute", php],
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        timeout=60,
        check=False,
    )


@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item: pytest.Item, call: pytest.CallInfo):
    outcome = yield
    report = outcome.get_result()

    if report.when != "call" or not report.failed:
        return

    browser = item.funcargs.get("driver")

    if browser is None:
        return

    try:
        ARTIFACTS_DIR.mkdir(parents=True, exist_ok=True)
        safe_name = re.sub(r"[^A-Za-z0-9_.-]+", "_", item.nodeid)
        screenshot = ARTIFACTS_DIR / f"{safe_name}.png"
        screenshot.write_bytes(browser.get_screenshot_as_png())
        report.sections.append(("screenshot", str(screenshot)))
    except Exception as error:  # pragma: no cover - hanya untuk diagnosa
        report.sections.append(("screenshot-error", str(error)))

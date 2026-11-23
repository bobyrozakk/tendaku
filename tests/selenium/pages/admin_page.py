"""Halaman panel Super Admin."""

from __future__ import annotations

from pages.base_page import BasePage


class AdminPage(BasePage):
    def open_dashboard(self) -> "AdminPage":
        self.visit("/dashboard")
        self.wait_for_text("Dashboard Operasional")
        return self

    def open_revenue_report(self) -> "AdminPage":
        self.visit("/admin/pendapatan")
        self.wait_for_text("Laporan Pendapatan Platform")
        return self

    def open_settlements(self) -> "AdminPage":
        self.visit("/admin/settlements")
        self.wait_for_text("Settlement Vendor")
        return self

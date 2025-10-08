import os
from playwright.sync_api import sync_playwright, expect

# Base URL for the WordPress site
base_url = os.environ.get("SANDBOX_WORDPRESS_URL", "http://localhost:8888")
admin_user = "admin"
admin_pass = "password"

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto(f"{base_url}/wp-login.php")
    page.fill('input[name="log"]', admin_user)
    page.fill('input[name="pwd"]', admin_pass)
    page.click('input[name="wp-submit"]')

    # Wait for the dashboard to load after login
    expect(page).to_have_url(f"{base_url}/wp-admin/")

    # Navigate to the settings page
    settings_url = f"{base_url}/wp-admin/admin.php?page=custom-lottery-settings"
    page.goto(settings_url)

    # Verify that the new settings fields are visible
    expect(page.get_by_role("heading", name="Lottery Settings")).to_be_visible()
    expect(page.get_by_role("checkbox", name="Enable Auto-Blocking")).to_be_visible()
    expect(page.get_by_role("checkbox", name="Enable Commission Agent System")).to_be_visible()
    expect(page.get_by_role("checkbox", name="Enable Cover Agent System")).to_be_visible()

    # Take a screenshot
    screenshot_path = "jules-scratch/verification/verification.png"
    page.screenshot(path=screenshot_path, full_page=True)
    print(f"Screenshot saved to {screenshot_path}")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
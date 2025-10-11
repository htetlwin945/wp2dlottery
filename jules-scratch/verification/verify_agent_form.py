
import re
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Go directly to the "Add New Agent" page.
    page.goto("http://localhost/wp-admin/admin.php?page=custom-lottery-agents&action=add")

    # Handle potential redirection to the login page.
    if "wp-login.php" in page.url:
        print("Redirected to login page. Attempting to log in with default credentials.")
        page.fill('input[name="log"]', 'admin')
        page.fill('input[name="pwd"]', 'password')
        page.click('input[name="wp-submit"]')
        page.wait_for_load_state("networkidle")
        # After logging in, navigate back to the "Add New Agent" page.
        page.goto("http://localhost/wp-admin/admin.php?page=custom-lottery-agents&action=add")

    # Verify that we are on the correct page by checking the heading.
    expect(page.get_by_role("heading", name="Add New Agent")).to_be_visible()

    # The new time input fields are only visible for "Commission Agent" type, which is the default.
    # We will verify that the new fields are visible.
    expect(page.get_by_label("Morning Open Time")).to_be_visible()
    expect(page.get_by_label("Morning Close Time")).to_be_visible()
    expect(page.get_by_label("Evening Open Time")).to_be_visible()
    expect(page.get_by_label("Evening Close Time")).to_be_visible()

    # Take a screenshot of the form.
    page.screenshot(path="jules-scratch/verification/verification.png")
    print("Screenshot saved to jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)

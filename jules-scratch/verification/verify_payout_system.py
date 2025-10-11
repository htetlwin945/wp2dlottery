from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Login
    page.goto("http://localhost:8080/wp-login.php")
    page.fill('input[name="log"]', 'admin')
    page.fill('input[name="pwd"]', 'password')
    page.click('input[name="wp-submit"]')
    page.wait_for_load_state('networkidle')

    # Go to the Agent Payouts page
    page.goto("http://localhost:8080/wp-admin/admin.php?page=custom-lottery-agent-payouts")
    page.wait_for_load_state('networkidle')

    # Take screenshot
    page.screenshot(path="jules-scratch/verification/agent-payouts-page.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)

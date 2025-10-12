
import os
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Get credentials from environment variables
        username = os.environ.get('WORDPRESS_USER')
        password = os.environ.get('WORDPRESS_PASSWORD')

        # Log in
        page.goto('http://localhost:8888/wp-login.php')
        page.fill('input[name="log"]', username)
        page.fill('input[name="pwd"]', password)
        page.click('input[name="wp-submit"]')
        page.wait_for_load_state('networkidle')

        # Navigate to the "My Wallet" page
        page.goto('http://localhost:8888/wp-admin/admin.php?page=custom-lottery-agent-wallet')
        page.wait_for_load_state('networkidle')

        # Click the "Request Payout" button to open the modal
        page.click('#request-payout-button')

        # Wait for the modal to be visible
        page.wait_for_selector('.wp-dialog', state='visible')

        # Take a screenshot of the modal
        page.screenshot(path='jules-scratch/verification/verification.png')

        browser.close()

if __name__ == "__main__":
    run()

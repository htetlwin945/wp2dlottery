import os
from playwright.sync_api import sync_playwright, expect

def run_verification(playwright):
    # Setup: Get credentials from environment variables
    admin_user = os.environ.get("WORDPRESS_USER", "admin")
    admin_password = os.environ.get("WORDPRESS_PASSWORD", "password")
    agent_user = "agent"
    agent_password = "password" # Assuming a simple password for the test user

    # Define URLs
    base_url = "http://localhost:8888"
    login_url = f"{base_url}/wp-login.php"
    agent_entry_url = f"{base_url}/wp-admin/admin.php?page=custom-lottery-entry"
    agent_my_entries_url = f"{base_url}/wp-admin/admin.php?page=custom-lottery-agent-entries"

    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Step 1: Login as Commission Agent
        print("Logging in as Commission Agent...")
        page.goto(login_url)
        page.fill('input[name="log"]', agent_user)
        page.fill('input[name="pwd"]', agent_password)
        page.click('input[name="wp-submit"]')
        expect(page.locator("#wpadminbar")).to_be_visible()
        print("Login successful.")

        # Step 2: Navigate to Lottery Entry page and verify "Add More" button
        print("Navigating to Lottery Entry page...")
        page.goto(agent_entry_url)
        expect(page.locator("h1")).to_have_text("Lottery Entry")

        # Click "Add More" and verify a new row appears
        page.click("button#add-entry-row")
        entry_rows = page.locator(".entry-row")
        expect(entry_rows).to_have_count(2)
        print("'Add More' button is working.")

        # Step 3: Fill out and submit the form
        print("Submitting a new lottery entry...")
        page.fill('input[name="customer_name"]', "Test Customer Agent")
        page.fill('input[name="phone"]', "0987654321")
        page.locator(".entry-row").first.locator('input[name="lottery_number[]"]').fill("42")
        page.locator(".entry-row").first.locator('input[name="amount[]"]').fill("1000")
        page.click('button[type="submit"]')

        # Verify success message
        response_div = page.locator("#form-response")
        expect(response_div).to_contain_text("Transaction complete. 1 entries added successfully.")
        print("Entry submitted successfully.")

        # Step 4: Navigate to "My Entries" and verify the new entry
        print("Navigating to 'My Entries' page...")
        page.goto(agent_my_entries_url)
        expect(page.locator("h1")).to_have_text("All Lottery Entries")

        # Verify the table contains the customer and the entry details
        expect(page.locator("table.wp-list-table")).to_contain_text("Test Customer Agent")

        # Click to view details
        page.click('a.view-entries-details')
        expect(page.locator("ul.entries-details-list")).to_contain_text("42 - 1,000.00")
        print("New entry is visible on 'My Entries' page.")

        # Step 5: Take a screenshot
        screenshot_path = "jules-scratch/verification/agent_portal_verification.png"
        page.screenshot(path=screenshot_path)
        print(f"Screenshot saved to {screenshot_path}")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")
    finally:
        browser.close()

with sync_playwright() as p:
    run_verification(p)
from playwright.sync_api import sync_playwright, expect
import time

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page()

    # Create a unique username and email for registration
    unique_id = str(int(time.time()))
    username = f"testuser_{unique_id}"
    email = f"testuser_{unique_id}@example.com"
    password = "password"

    # Register a new user
    page.goto("http://localhost:8000/register.php")
    page.fill('input[name="username"]', username)
    page.fill('input[name="email"]', email)
    page.fill('input[name="password"]', password)
    page.click('button[type="submit"]')

    # Wait for the login page to load after registration
    expect(page).to_have_url("http://localhost:8000/login.php?success=Registration%20successful.%20Please%20log%20in.")

    # Log in with the new user's credentials
    page.fill('input[name="username"]', username)
    page.fill('input[name="password"]', password)

    # Click login and wait for navigation to dashboard
    page.click('button[type="submit"]')
    expect(page).to_have_url("http://localhost:8000/dashboard.php")

    # Now that we are on the dashboard, take a screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()
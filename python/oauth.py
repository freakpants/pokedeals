import time
import re
import requests
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager

# Step 1: Set up Selenium
chrome_options = Options()
chrome_options.add_argument("--headless")  # Run in headless mode (no GUI)
chrome_options.add_argument("--disable-gpu")
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--disable-dev-shm-usage")

# Launch browser
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service, options=chrome_options)

# Step 2: Load the product page
product_url = "https://www.migros.ch/de/product/746658000000"
driver.get(product_url)

# Give the page time to load
time.sleep(5)  # Adjust this delay if needed

# Step 3: Extract authentication redirect URL
page_source = driver.page_source
redirect_pattern = re.search(r'https:\/\/www\.migros\.ch\/authentication\/public\/v1\/api\/oauth\/login-success\?code=[^"]+', page_source)

if not redirect_pattern:
    print("Failed to find authentication redirect URL.")
    driver.quit()
    exit()

auth_url = redirect_pattern.group(0)
print(f"Extracted Auth URL: {auth_url}")

# Close the browser
driver.quit()

# Step 4: Request the authentication URL to get the Bearer Token
headers = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36"
}

session = requests.Session()
auth_response = session.get(auth_url, headers=headers)

if auth_response.status_code == 200:
    token_data = auth_response.json()
    bearer_token = token_data.get("access_token")

    if bearer_token:
        print(f"Bearer Token: {bearer_token}")
    else:
        print("Token not found in response.")
else:
    print("Failed to retrieve token:", auth_response.status_code, auth_response.text)

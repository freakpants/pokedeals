import requests

# API URL
product_id = "100187827"  # Replace with your product ID
cost_center_ids = "0034803,0033670,0024400,0034813,0033290,0033833,0034373,0034260,0034390,0034730"
url = f"https://www.migros.ch/store-availability/public/v2/availabilities/products/{product_id}"

# Headers
headers = {
    "accept": "application/json, text/plain, */*",
    "migros-language": "de",
}

# Parameters
params = {"costCenterIds": cost_center_ids}

# Make the GET request
response = requests.get(url, headers=headers, params=params)

# Check for successful response
if response.status_code == 200:
    print("Store availability data:")
    print(response.json())
else:
    print(f"Error fetching store availability: {response.status_code} - {response.text}")

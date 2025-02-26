import json
from datetime import datetime

# Load availability data
with open("availability.json", "r", encoding="utf-8") as f:
    availability_data = json.load(f)

# Load found store data
with open("found_stores.json", "r", encoding="utf-8") as f:
    found_stores_data = json.load(f)

# Load additional store data
with open("131.json", "r", encoding="utf-8") as f:
    additional_stores_data = json.load(f)

# Convert additional stores into a dictionary for easier lookup
additional_stores = {store["name"]: store for store in additional_stores_data.get("results", []) if "name" in store}

# Function to format the date into a human-readable format
def format_datetime(timestamp):
    try:
        dt = datetime.strptime(timestamp, "%Y-%m-%dT%H:%M:%S.%f")
        return dt.strftime("%d %b %Y, %I:%M %p")
    except ValueError:
        return "Invalid Date"

# Merge availability with store details
store_list = []
for shop in availability_data["shops"]:
    pos_id = shop["posId"]
    store_info = found_stores_data.get(pos_id, additional_stores.get(pos_id, {}))

    store_list.append({
        "Store ID": pos_id,
        "Store Name": store_info.get("displayName", f"Store {pos_id}"),
        "Address": store_info.get("address", {}).get("formattedAddress", "Unknown"),
        "Phone": store_info.get("address", {}).get("phone", "N/A"),
        "Available Stock": shop["available"],
        "Last Updated": format_datetime(shop["lastModifiedDateTime"])
    })

# Display the merged data in a table format
print(f"{'Store ID':<10} {'Store Name':<30} {'Address':<50} {'Phone':<20} {'Stock':<10} {'Last Updated'}")
print("=" * 130)
for store in store_list:
    print(f"{store['Store ID']:<10} {store['Store Name']:<30} {store['Address']:<50} {store['Phone']:<20} {store['Available Stock']:<10} {store['Last Updated']}")

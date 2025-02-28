import json
from datetime import datetime
import pandas as pd

# Load the JSON files
with open("availability.json", "r") as file:
    availability_data = json.load(file)

with open("131.json", "r") as file:
    store_data = json.load(file)

# Convert store data into a dictionary for quick lookup by store ID (posId)
store_details = {store["name"]: store for store in store_data["results"]}

# Prepare a list to store formatted results
availability_list = []

# Process each shop in availability data
for shop in availability_data["shops"]:
    store_id = shop["posId"]
    if store_id in store_details:
        store = store_details[store_id]
        city = store["address"]["town"]
        address = store["address"]["formattedAddress"]
        phone = store["address"].get("phone", "N/A")
        available = shop["available"]
        last_modified = datetime.strptime(shop["lastModifiedDateTime"], "%Y-%m-%dT%H:%M:%S.%f")
        last_modified_human = last_modified.strftime("%Y-%m-%d %H:%M:%S")

        availability_list.append({
            "City": city,
            "Store ID": store_id,
            "Available": available,
            "Last Modified": last_modified_human,
            "Address": address,
            "Phone": phone
        })

# Convert to DataFrame and sort by Last Modified in descending order
df = pd.DataFrame(availability_list).sort_values(by="Last Modified", ascending=False)

# Print the results
print(df.to_string(index=False))

# Save the results to a CSV file
df.to_csv("availability_summary.csv", index=False, encoding="utf-8")

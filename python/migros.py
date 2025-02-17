import requests
import json
import time
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()
BEARER_TOKEN = os.getenv("MIGROS_BEARER_TOKEN")

if not BEARER_TOKEN:
    raise ValueError("❌ MIGROS_BEARER_TOKEN is missing! Set it in the .env file.")

# API URLs
migros_api = "https://www.migros.ch/store/public/v1/stores/search"

# Headers
headers = {
    "accept": "application/json, text/plain, */*",
    "authorization": f"Bearer {BEARER_TOKEN}",
    "migros-language": "de"
}

# File paths
store_data_file = "migros_stores_with_queries.json"
failed_postal_codes_file = "failed_postal_codes.json"
postal_code_file = "swiss_postal_codes.json"


### Load Swiss postal codes ###
def load_swiss_postal_codes():
    if os.path.exists(postal_code_file):
        with open(postal_code_file, "r", encoding="utf-8") as f:
            postal_codes = json.load(f)
        print(f"✅ Loaded {len(postal_codes)} Swiss postal codes.")
        return postal_codes
    raise FileNotFoundError(f"❌ Postal code file '{postal_code_file}' not found!")


### Load existing store data & failed postal codes ###
def load_existing_data():
    store_lookup = {}
    failed_postal_codes = set()

    if os.path.exists(store_data_file):
        with open(store_data_file, "r", encoding="utf-8") as f:
            store_lookup = json.load(f)

    if os.path.exists(failed_postal_codes_file):
        with open(failed_postal_codes_file, "r", encoding="utf-8") as f:
            failed_postal_codes = set(json.load(f))

    return store_lookup, failed_postal_codes


### Fetch stores from Migros API ###
def fetch_migros_stores(postal_codes, store_lookup, failed_postal_codes):
    queried_postal_codes = {pc for store in store_lookup.values() for pc in store["triggered_by"]}
    remaining_postal_codes = [
        pc for pc in postal_codes if pc not in queried_postal_codes and pc not in failed_postal_codes
    ][:5000]

    if not remaining_postal_codes:
        print("✅ All postal codes have been queried!")
        return store_lookup, failed_postal_codes

    print(f"🚀 Querying {len(remaining_postal_codes)} new postal codes...")

    for postal_code in remaining_postal_codes:
        params = {"query": postal_code}
        try:
            response = requests.get(migros_api, headers=headers, params=params)
            if response.status_code == 200:
                stores = response.json()
                if not stores:  # No stores found
                    print(f"⚠️ No stores found for {postal_code}, adding to failed list.")
                    failed_postal_codes.add(postal_code)
                else:
                    for store in stores:
                        store_id = store["storeId"]
                        store_info = {
                            "name": store["storeName"],
                            "address": store["location"]["address"],
                            "city": store["location"]["city"],
                            "zip": store["location"]["zip"],
                            "latitude": store["location"]["latitude"],
                            "longitude": store["location"]["longitude"],
                        }
                        if store_id in store_lookup:
                            store_lookup[store_id]["triggered_by"].append(postal_code)
                        else:
                            store_lookup[store_id] = {"info": store_info, "triggered_by": [postal_code]}
                    print(f"✅ {len(stores)} stores found for postal code {postal_code}")
            else:
                print(f"⚠️ Failed to fetch data for {postal_code}: {response.status_code}")
        except Exception as e:
            print(f"❌ Error fetching stores for {postal_code}: {e}")
        time.sleep(0.5)

    return store_lookup, failed_postal_codes


### Run the script ###
postal_codes = load_swiss_postal_codes()
store_lookup, failed_postal_codes = load_existing_data()
updated_store_lookup, updated_failed_postal_codes = fetch_migros_stores(postal_codes, store_lookup, failed_postal_codes)

# Save store data
with open(store_data_file, "w", encoding="utf-8") as f:
    json.dump(updated_store_lookup, f, indent=4, ensure_ascii=False)

# Save failed postal codes
with open(failed_postal_codes_file, "w", encoding="utf-8") as f:
    json.dump(list(updated_failed_postal_codes), f, indent=4, ensure_ascii=False)

print(f"✅ Saved {len(updated_store_lookup)} unique Migros stores.")
print(f"✅ Stored {len(updated_failed_postal_codes)} failed postal codes.")

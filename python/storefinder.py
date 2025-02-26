import requests
import json

CACHE_FILE = "found_stores.json"
MISSING_STORES = {"2922", "2964", "2980", "3050", "3073", "3101", "3387", "3579", "4122", "6167"}
GEO_API_URL = "https://www.interdiscount.ch/services/google-maps/api/geocoding/{}?language=de"
STORE_LOOKUP_API_URL = "https://www.interdiscount.ch/idocc/occ/id/stores?latitude={}&longitude={}&lang=de"

def load_cached_stores():
    try:
        with open(CACHE_FILE, "r") as f:
            return json.load(f)
    except (FileNotFoundError, json.JSONDecodeError):
        return {}

def save_cached_stores(found_stores):
    with open(CACHE_FILE, "w") as f:
        json.dump(found_stores, f, indent=4)

def fetch_store_coordinates(store_id):
    url = GEO_API_URL.format(store_id)
    try:
        response = requests.get(url, timeout=5)
        response.raise_for_status()
        data = response.json()
        
        if "latitude" in data and "longitude" in data:
            return float(data["latitude"]), float(data["longitude"])
        else:
            print(f"No coordinates found for store {store_id}")
    except requests.exceptions.RequestException as e:
        print(f"Error fetching store {store_id}: {e}")
    return None, None

def fetch_store_details(lat, lon):
    url = STORE_LOOKUP_API_URL.format(lat, lon)
    try:
        response = requests.get(url, timeout=5)
        response.raise_for_status()
        data = response.json()
        return data
    except requests.exceptions.RequestException as e:
        print(f"Error fetching store details for {lat}, {lon}: {e}")
    return None

def update_missing_stores():
    found_stores = load_cached_stores()
    
    for store_id in MISSING_STORES:
        if store_id not in found_stores:
            lat, lon = fetch_store_coordinates(store_id)
            if lat and lon:
                store_info = fetch_store_details(lat, lon)
                found_stores[store_id] = {
                    "name": store_id,
                    "geoPoint": {"latitude": lat, "longitude": lon},
                    "details": store_info
                }
                print(f"Updated store {store_id}: {lat}, {lon}, Details: {store_info}")
    
    save_cached_stores(found_stores)
    print("Finished updating missing stores.")

if __name__ == "__main__":
    update_missing_stores()

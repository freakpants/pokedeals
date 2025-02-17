import requests
import time
import json
import math

TARGET_STORES = {"2974", "3058", "3019", "2978", "4274", "3828", "3040", "3068", "3030"}
CACHE_FILE = "found_stores.json"
CHECKED_LOCATIONS_FILE = "checked_locations.json"
STEP_INCREMENT = 0.15
MAX_STEP = 1.0

def load_cached_stores():
    try:
        with open(CACHE_FILE, "r") as f:
            return json.load(f)
    except (FileNotFoundError, json.JSONDecodeError):
        return {}

def save_cached_stores(found_stores):
    with open(CACHE_FILE, "w") as f:
        json.dump(found_stores, f, indent=4)

def load_checked_locations():
    try:
        with open(CHECKED_LOCATIONS_FILE, "r") as f:
            return set(tuple(loc) for loc in json.load(f))
    except (FileNotFoundError, json.JSONDecodeError):
        return set()

def save_checked_locations(checked_locations):
    with open(CHECKED_LOCATIONS_FILE, "w") as f:
        json.dump(list(checked_locations), f, indent=4)

def fetch_stores(lat, lon, checked_locations):
    if (lat, lon) in checked_locations:
        return None  # Skip duplicate requests
    
    url = f"https://www.interdiscount.ch/idocc/occ/id/stores?latitude={lat}&longitude={lon}&lang=de"
    try:
        response = requests.get(url, timeout=5)
        response.raise_for_status()
        checked_locations.add((lat, lon))
        save_checked_locations(checked_locations)
        return response.json()
    except requests.exceptions.RequestException as e:
        with open("debug_log.txt", "a") as log_file:
            log_file.write(f"Fehler bei der Anfrage: {e}\n")
        return None

def find_new_search_coordinates(found_stores, checked_locations, step):
    known_coords = [(s["geoPoint"]["latitude"], s["geoPoint"]["longitude"]) 
                    for s in found_stores.values() if "geoPoint" in s]
    
    if not known_coords:
        return None, None
    
    # Find the largest gaps between known stores to prioritize missing areas
    min_lat, max_lat = min(c[0] for c in known_coords), max(c[0] for c in known_coords)
    min_lon, max_lon = min(c[1] for c in known_coords), max(c[1] for c in known_coords)
    
    for lat in [min_lat - step, max_lat + step]:
        for lon in [min_lon - step, max_lon + step]:
            if (lat, lon) not in checked_locations:
                return lat, lon
    
    return None, None

def search_for_store():
    lat, lon = 46.8, 8.3  # Startpunkt
    step = 0.1
    found_stores = load_cached_stores()
    checked_locations = load_checked_locations()
    unique_stores = set(found_stores.keys())
    total_checked_stores = len(unique_stores)
    failed_attempts = 0
    
    with open("stores_list.txt", "w") as store_list_file:
        while True:
            data = fetch_stores(lat, lon, checked_locations)
            if not data or "results" not in data or not data["results"]:
                failed_attempts += 1
                step = min(step * 1.5, MAX_STEP)  # Expand search radius when failing
                new_lat, new_lon = find_new_search_coordinates(found_stores, checked_locations, step)
                if new_lat and new_lon:
                    lat, lon = new_lat, new_lon
                time.sleep(0.3)
                continue
            
            failed_attempts = 0
            store_count = len(data["results"])
            print(f"Gefundene Stores bei ({lat}, {lon}): {store_count}, Einzigartige Stores: {len(unique_stores)}")
            
            for store in data["results"]:
                store_id = str(store.get("name"))
                if store_id not in found_stores:
                    found_stores[store_id] = store
                    unique_stores.add(store_id)
                    total_checked_stores += 1
                    store_list_file.write(store_id + "\n")
                    print(f"Gefunden: Store {store_id} - {store.get('displayName', 'Keine Beschreibung verfügbar')}")
                    save_cached_stores(found_stores)
            
            new_lat, new_lon = find_new_search_coordinates(found_stores, checked_locations, step)
            if new_lat and new_lon:
                lat, lon = new_lat, new_lon
                step = max(step * 0.5, 0.05)  # Reduce step size to fine-tune search
            else:
                step = min(step * 1.2, MAX_STEP)
                lat += step
                lon += step
            
            time.sleep(0.2)

if __name__ == "__main__":
    search_for_store()

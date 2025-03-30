<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migros Mini Tin Stocks</title>
    <style>
        body { font-family: Arial, sans-serif; }
        #map { height: 600px; width: 100%; margin-top: 20px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
        .neutral { background-color: #f9f9f9; }
        .increase { background-color: #ccffcc; }
        .contradiction { background-color: #ffff99; }
        .clickable { cursor: pointer; color: #007BFF; text-decoration: underline; }
        .hidden { display: none; }
        .controls { margin: 15px 0; }
        .controls button {
            margin-right: 10px;
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}"></script>
</head>
<body>

<h2>Migros Mini Tin Stocks</h2>

@php
    $storeHistories = [];

    foreach ($stockChanges as $change) {
        $sid = $change->store_id;
        if (!$sid) continue;

        $storeHistories[$sid][] = [
            'stock' => $change->stock,
            'change' => $change->change,
            'timestamp' => $change->timestamp,
        ];
    }
@endphp

@if(isset($stockChanges) && $stockChanges->isNotEmpty())
    @php
        $latestChanges = [];
        $highlightClasses = [];
        $storesWithRestock = [];
        $processedStockChanges = $stockChanges->reverse()->values();
        $changeCounts = [];
        foreach ($processedStockChanges as $change) {
            if (!empty($change->store_id)) {
                $changeCounts[$change->store_id] = ($changeCounts[$change->store_id] ?? 0) + 1;
            }
        }
        $mapStores = [];

        foreach ($processedStockChanges as $change) {
            $storeId = $change->store_id ?? null;
            $changeValue = $change->change ?? 0;
            $highlightClass = 'neutral';

            if ($storeId !== null && isset($latestChanges[$storeId])) {
                $previousChange = $latestChanges[$storeId]->change ?? 0;
                if ($previousChange === -$changeValue) {
                    $highlightClass = 'contradiction';
                }
            }

            if ($changeValue > 0 && $highlightClass !== 'contradiction') {
                $highlightClass = 'increase';
            }
            if ($highlightClass === 'increase' && $storeId !== null) {
                $storesWithRestock[$storeId] = true;
            }

            // 🟥 Mark as "never stocked" if ONLY one entry AND it's stock 0, change 0
            if (
                $storeId !== null &&
                ($changeCounts[$storeId] ?? 0) === 1 &&
                $change->stock == 0 &&
                $change->change == 0
            ) {
                $highlightClass = 'no_stock';
            }


            if ($storeId !== null) {
                $latestChanges[$storeId] = $change;

                $finalHighlight = $highlightClass;
                if (isset($storesWithRestock[$storeId])) {
                    $finalHighlight = 'increase';
                }

                // For map
                $mapStores[$storeId] = [
    'store_id' => $storeId, // ← ADD THIS
    'name' => $change->store_name,
    'address' => $change->address,
    'city' => $change->city,
    'zip' => $change->zip,
    'stock' => $change->stock,
    'change' => $change->change,
    'timestamp' => $change->timestamp,
    'highlight' => $finalHighlight,
    'lat' => (float) $change->latitude,
    'lng' => (float) $change->longitude,
];

            }

            $highlightClasses[$change->timestamp] = $highlightClass;
        }



        $processedStockChanges = $processedStockChanges->reverse()->values();
    @endphp


    <p>This project is using the <a href="https://github.com/aliyss/migros-api-wrapper">Migros API Wrapper</a> by aliyss to get the stock data.</p>
    <p><strong>Click a store name to filter entries for that store.</strong> Click again to reset.</p>
    <p>Yellow changes within 20 minutes of a previous change are usually a glitch by the migros system and the stock hasn’t actually changed.</p>
    <p>It is completely random which mini tins a store gets. It can be anything from Shrouded Fable / Vibrant Paldea / 151 / Paldean Fates / Prismatic Evolutions.</p>
    <p>Stock data can be out of date because of theft. Some stores never fix that.</p>
    <p>It can take a while from a restock to being on the actual shelf. When in doubt, ask customer service.</p>
    <p><b>Please be patient and friendly with employees, they often don't understand how it works.</b></p>

    <div id="map"></div>

    <div class="controls">
        <button id="filter-increase">Show Only Increases</button>
        <button id="reset-filter">Reset Filter</button>
    </div>

    <table id="stock-table">
        <thead>
        <tr>
            <th>Store ID</th>
            <th>Store Name</th>
            <th>Address</th>
            <th>City</th>
            <th>ZIP</th>
            <th>Stock</th>
            <th>Change</th>
            <th>Timestamp</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($processedStockChanges as $change)
            @php
                $highlightClass = $highlightClasses[$change->timestamp] ?? 'neutral';
            @endphp
            <tr class="{{ $highlightClass }}" data-store-id="{{ $change->store_id }}">
                <td>{{ $change->store_id }}</td>
                <td><span class="clickable" data-store-id="{{ $change->store_id }}">{{ $change->store_name }}</span></td>
                <td>{{ $change->address }}</td>
                <td>{{ $change->city }}</td>
                <td>{{ $change->zip }}</td>
                <td>{{ $change->stock }}</td>
                <td>{{ $change->change }}</td>
                <td>{{ $change->timestamp }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <script>
        const storeHistories = @json($storeHistories);

        const stockRows = document.querySelectorAll('#stock-table tbody tr');
        const clickableNames = document.querySelectorAll('.clickable');
        const filterIncreaseBtn = document.getElementById('filter-increase');
        const resetFilterBtn = document.getElementById('reset-filter');

        let activeStoreId = null;

        const resetAllFilters = () => {
            stockRows.forEach(row => row.classList.remove('hidden'));
            activeStoreId = null;
        };

        clickableNames.forEach(name => {
            name.addEventListener('click', () => {
                const storeId = name.getAttribute('data-store-id');
                if (activeStoreId === storeId) {
                    resetAllFilters();
                } else {
                    stockRows.forEach(row => {
                        if (row.getAttribute('data-store-id') !== storeId) {
                            row.classList.add('hidden');
                        } else {
                            row.classList.remove('hidden');
                        }
                    });
                    activeStoreId = storeId;
                }
            });
        });

        filterIncreaseBtn.addEventListener('click', () => {
            stockRows.forEach(row => {
                if (!row.classList.contains('increase')) {
                    row.classList.add('hidden');
                } else {
                    row.classList.remove('hidden');
                }
            });
            activeStoreId = null;
        });

        resetFilterBtn.addEventListener('click', resetAllFilters);
        

        const stores = @json(array_values($mapStores));

        function initMap() {
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 7,
        center: { lat: 46.8, lng: 8.2 }
    });

    const bounds = new google.maps.LatLngBounds();



    stores.forEach(store => {

        if (!store.lat || !store.lng) {
        console.warn("Skipped (no coords):", store.name);
        return;
    }



        const position = { lat: store.lat, lng: store.lng };
        bounds.extend(position);

        const marker = new google.maps.Marker({
            position,
            map,
            title: store.name,
            icon: getMarkerColor(store.highlight, store.stock)
        });

const history = storeHistories[store.store_id] || [];

let content = `
    <strong>${store.name}</strong><br>
    ${store.address}<br>
    ${store.zip} ${store.city}<br><br>
    <strong>Latest Stock:</strong> ${store.stock}<br><br>
    <strong>History:</strong><br>
`;

history.slice().reverse().forEach(entry => {
    const change = entry.change > 0 ? `+${entry.change}` : entry.change;
    content += `${entry.timestamp} → Stock: ${entry.stock}, Change: ${change}<br>`;
});

const infoWindow = new google.maps.InfoWindow({ content });


        marker.addListener("click", () => {
            infoWindow.open(map, marker);
        });
    });

    map.fitBounds(bounds); // ← auto zoom & center
}


function getMarkerColor(highlight, stock) {
    switch (highlight) {
        case 'increase':
            return createLabeledMarker(stock, "#28a745", "#1c7e36");
        case 'contradiction':
            return createLabeledMarker(stock, "#ffeb3b", "#c1b500");
        case 'no_stock':
            return {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 7,
                fillColor: "#ff4444",
                fillOpacity: 1,
                strokeColor: "#aa0000",
                strokeWeight: 1
            };
        default:
            return createLabeledMarker(stock, "#bbbbbb", "#888888");
    }
}

function createLabeledMarker(stock, fillColor, strokeColor) {
    const radius = 12;
    const canvas = document.createElement("canvas");
    canvas.width = canvas.height = radius * 2;

    const ctx = canvas.getContext("2d");

    // Draw circle
    ctx.beginPath();
    ctx.arc(radius, radius, radius - 1, 0, 2 * Math.PI);
    ctx.fillStyle = fillColor;
    ctx.fill();
    ctx.strokeStyle = strokeColor;
    ctx.lineWidth = 2;
    ctx.stroke();

    // Determine text color based on fill brightness
    const useDarkText = isLightColor(fillColor);
    ctx.fillStyle = useDarkText ? "#000000" : "#ffffff";

    // Draw label
    ctx.font = "bold 12px sans-serif";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(stock, radius, radius);

    return {
        url: canvas.toDataURL(),
        scaledSize: new google.maps.Size(radius * 2, radius * 2)
    };
}

function isLightColor(hex) {
    const rgb = hex.replace("#", "")
        .match(/.{1,2}/g)
        .map(c => parseInt(c, 16));

    const brightness = (rgb[0] * 299 + rgb[1] * 587 + rgb[2] * 114) / 1000;
    return brightness > 140;
}



        document.addEventListener('DOMContentLoaded', initMap);
    </script>
@else
    <p>No stock data available.</p>
@endif

</body>
</html>

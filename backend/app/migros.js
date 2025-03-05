import fs from 'fs';
import axios from 'axios';
import { MigrosAPI } from 'migros-api-wrapper';

// Load the cost centers from the local JSON file
const costCentersFile = './migros_stores_with_queries.json';
const outputFile = './stock_data.json';

const products = [
    { id: '100119063', type: 'Mini Tins' },
    { id: '100007250', type: 'Sleeved Boosters' },
    { id: '100007280', type: 'Three Pack Blisters' }
];

const BATCH_SIZE = 10; // Maximum number of cost centers per request

// Function to read cost centers from the JSON file
async function getCostCenters() {
    const data = fs.readFileSync(costCentersFile, 'utf-8');
    const jsonData = JSON.parse(data);

    if (typeof jsonData !== 'object' || Array.isArray(jsonData)) {
        throw new Error('The provided JSON file does not contain an object of cost centers.');
    }

    return jsonData;
}

// Function to fetch stock data for a batch of cost centers
async function fetchStockDataForBatch(productId, costCenterBatch, leshopchToken) {
    const costCenterQuery = costCenterBatch.join(',');
    const url = `https://www.migros.ch/store-availability/public/v2/availabilities/products/${productId}?costCenterIds=${costCenterQuery}`;

    const headers = {
        'accept': 'application/json, text/plain, */*',
        'leshopch': leshopchToken,
        'migros-language': 'de',
        'peer-id': 'website-js-800.0.0',
        'Referer': `https://www.migros.ch/de/product/${productId}`,
    };

    try {
        const response = await axios.get(url, { headers });
        return response.data;
    } catch (error) {
        console.error(`❌ Failed to fetch data for batch: ${costCenterQuery}`, error.response?.data || error.message);
        return null;
    }
}

// Function to save stock data to a JSON file
function saveStockDataToFile(stockData) {
    fs.writeFileSync(outputFile, JSON.stringify(stockData, null, 2), 'utf-8');
}

// Main function to orchestrate the fetching and saving of stock data
async function fetchAllStockData() {
    try {
        const { token: leshopchToken } = await MigrosAPI.account.oauth2.getGuestToken();

        if (!leshopchToken) {
            throw new Error('❌ Failed to retrieve `leshopch` token.');
        }

        const costCenterInfo = await getCostCenters();
        const costCenters = Object.keys(costCenterInfo);

        // Load existing stock data
        let existingData = {};
        if (fs.existsSync(outputFile)) {
            try {
                existingData = JSON.parse(fs.readFileSync(outputFile, 'utf-8'));
            } catch (error) {
                console.error('⚠️ Error reading existing stock data file:', error.message);
            }
        }

        const stockData = {};
        const previousStock = existingData;

        for (const product of products) {
            const { id: productId, type: productType } = product;

            stockData[productId] = { availabilities: [] };

            for (let i = 0; i < costCenters.length; i += BATCH_SIZE) {
                const batch = costCenters.slice(i, i + BATCH_SIZE);
                const batchData = await fetchStockDataForBatch(productId, batch, leshopchToken);

                if (batchData && batchData.availabilities) {
                    batchData.availabilities.forEach(({ id, stock }) => {
                        if (previousStock[productId]?.availabilities?.some(item => item.id === id)) {
                            const oldStock = previousStock[productId].availabilities.find(item => item.id === id)?.stock;
                            if (stock !== oldStock) {
                                const storeInfo = costCenterInfo[id]?.info;
                                if (storeInfo) {
                                    const { city, address } = storeInfo;
                                    const changeType = stock > oldStock ? 'increase' : 'decrease';
                                    console.log(`${productType} at ${city}, ${address}: Stock ${changeType} from ${oldStock} to ${stock}`);
                                }
                            }
                        }
                        stockData[productId].availabilities.push({ id, stock });
                    });
                }
            }
        }

        saveStockDataToFile(stockData);
    } catch (error) {
        console.error('❌ Error during fetching stock data:', error.message);
    }
}

// Run the main function
fetchAllStockData();

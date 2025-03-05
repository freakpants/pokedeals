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
const TEN_MINUTES = 10 * 60 * 1000; // 10 minutes in milliseconds

// Function to read cost centers from the JSON file
async function getCostCenters() {
    const data = fs.readFileSync(costCentersFile, 'utf-8');
    return JSON.parse(data);
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

// Function to create a delay
function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

// Function to show progress status
function showProgress(current, total, message = '') {
    const percentage = ((current / total) * 100).toFixed(2);
    process.stdout.write(`\r${message} ${current}/${total} (${percentage}%)`);
}

// Main function to orchestrate the fetching and saving of stock data
async function fetchAllStockData() {
    try {
        const { token: leshopchToken } = await MigrosAPI.account.oauth2.getGuestToken();

        const costCenterInfo = await getCostCenters();
        const costCenters = Object.keys(costCenterInfo);
        const totalRequests = products.length * Math.ceil(costCenters.length / BATCH_SIZE);

        let existingData = fs.existsSync(outputFile)
            ? JSON.parse(fs.readFileSync(outputFile, 'utf-8'))
            : {};

        const now = Date.now();
        let requestCount = 0;

        for (const product of products) {
            const { id: productId, type: productType } = product;

            for (let i = 0; i < costCenters.length; i += BATCH_SIZE) {
                const batch = costCenters.slice(i, i + BATCH_SIZE);
                const batchData = await fetchStockDataForBatch(productId, batch, leshopchToken);

                if (batchData && batchData.availabilities) {
                    batch.forEach((costCenterId) => {
                        const stockInfo = batchData.availabilities.find(({ id }) => id === costCenterId);
                        const stock = stockInfo ? stockInfo.stock : 0;
                        const existingStock = existingData[productId]?.availabilities?.find(item => item.id === costCenterId);

                        let outputChange = true;

                        if (existingStock) {
                            const oldStock = existingStock.stock;
                            if (stock !== oldStock) {
                                const changeType = stock > oldStock ? 'increase' : 'decrease';
                                const storeInfo = costCenterInfo[costCenterId]?.info;
                                if (storeInfo) {
                                    const { zip, city, address } = storeInfo;

                                    if (changeType === 'increase' && existingStock.lastDecreaseAmount) {
                                        const { lastDecreaseAmount, timestamp } = existingStock;
                                        if ((oldStock + lastDecreaseAmount === stock) && (now - new Date(timestamp).getTime()) < TEN_MINUTES) {
                                            console.log(`\n⏳ Ignoring temporary stock increase for ${productType} at ${city}, ${address}`);
                                            outputChange = false;
                                        } 
                                    }

                                    if(outputChange) {
                                        console.log(`\n${productType} at ${zip} ${city}, ${address}: Stock ${changeType} from ${oldStock} to ${stock}`);
                                    }

                                    if (changeType === 'decrease') {
                                        existingStock.lastDecreaseAmount = oldStock - stock;
                                        existingStock.timestamp = new Date().toISOString();
                                    }
                                }
                                if(outputChange){
                                    existingStock.stock = stock;
                                }
                            }
                        } else {
                            existingData[productId] = existingData[productId] || { availabilities: [] };
                            existingData[productId].availabilities.push({ id: costCenterId, stock });
                        }
                    });
                }

                requestCount++;
                showProgress(requestCount, totalRequests, `Fetching stock data for ${productType}`);
                await delay(100);
            }
        }

        saveStockDataToFile(existingData);
        console.log('\n✅ Stock data fetching completed!');
    } catch (error) {
        console.error('❌ Error during fetching stock data:', error.message);
    }
}

// Run the main function
fetchAllStockData();
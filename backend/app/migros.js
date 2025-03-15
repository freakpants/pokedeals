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
const TEN_MINUTES = 15 * 60 * 1000; // 10 minutes in milliseconds

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

            let totalProducts = 0;

            for (let i = 0; i < costCenters.length; i += BATCH_SIZE) {
                const batch = costCenters.slice(i, i + BATCH_SIZE);
                const batchData = await fetchStockDataForBatch(productId, batch, leshopchToken);

                if (batchData && batchData.availabilities) {
                    batch.forEach((costCenterId) => {
                        const stockInfo = batchData.availabilities.find(({ id }) => id === costCenterId);
                        const stock = stockInfo ? stockInfo.stock : 0;
                        const existingStock = existingData[productId]?.availabilities?.find(item => item.id === costCenterId);

                        totalProducts += stock;

                        let outputChange = true;

                        if (existingStock) {
                            const oldStock = existingStock.stock;
                            if (stock !== oldStock) {
                                const changeType = stock > oldStock ? 'increase' : 'decrease';
                                const storeInfo = costCenterInfo[costCenterId]?.info;
                                if (storeInfo) {
                                    const { zip, name, address } = storeInfo;

                                
                                    let changeTypeFormatted;
                                    if (changeType === 'increase') {
                                        // If lastChange exists and is the exact opposite of current change
                                        if (existingStock.lastChange && existingStock.lastChange === (oldStock - stock)) {
                                            changeTypeFormatted = '\x1b[1m\x1b[33mincrease\x1b[0m'; // yellow
                                        } else {
                                            changeTypeFormatted = '\x1b[1m\x1b[32mincrease\x1b[0m'; // green
                                        }
                                    } else {
                                        changeTypeFormatted = 'decrease';
                                    }
                                    console.log(`\n${productType} at ${zip} ${name}, ${address}: Stock ${changeTypeFormatted} from ${oldStock} to ${stock}`);
                                    // output details of previous change
                                    if (existingStock.lastChange) {
                                        // calculate when the stock last changed
                                        const lastChangeAgo = now - new Date(existingStock.timestamp).getTime();
                                        const lastChangeMinutes = Math.floor(lastChangeAgo / 60000);
                                        const lastChangeSeconds = Math.floor((lastChangeAgo % 60000) / 1000);
                                        const lastChangeType = existingStock.lastChange > 0 ? 'increase' : 'decrease';
                                        const lastChangeAmount = Math.abs(existingStock.lastChange);
                                        console.log(`\tPrevious change: ${lastChangeType} of ${lastChangeAmount} units ${lastChangeMinutes} minutes and ${lastChangeSeconds} seconds ago`);
                                    }


                                    
                                    existingStock.lastChange = stock - oldStock; 
                                    existingStock.timestamp = new Date().toISOString();
                                    
                                }
                                
                                existingStock.stock = stock;
                            }
                        } else {
                            existingData[productId] = existingData[productId] || { availabilities: [] };
                            existingData[productId].availabilities.push({ id: costCenterId, stock });
                        }
                    });
                }

                requestCount++;
                showProgress(requestCount, totalRequests, `Fetching stock data for ${productType}`);
                const delayPerRequest = (55000 / totalRequests).toFixed(0);
                await delay(delayPerRequest);
            }
            console.log(`\nTotal stock for ${productType}: ${totalProducts}`);
        }

        saveStockDataToFile(existingData);
        console.log('\n✅ Stock data fetching completed!');
    } catch (error) {
        console.error('❌ Error during fetching stock data:', error.message);
    }
}

// Run the main function
fetchAllStockData();
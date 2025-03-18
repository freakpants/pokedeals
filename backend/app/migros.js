import fs from 'fs';
import axios from 'axios';
import { MigrosAPI } from 'migros-api-wrapper';
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

const costCentersFile = './migros_stores_with_queries.json';
const outputFile = './stock_data.json';

const products = [
    { id: '100119063', type: 'Mini Tins' },
    { id: '100007250', type: 'Sleeved Boosters' },
    { id: '100007280', type: 'Three Pack Blisters' }
];

const BATCH_SIZE = 10;
const SCRAPER_API_KEY = '36ab00a69c3659025119051957dac92a'; // Replace with your actual API key

async function getCostCenters() {
    const data = fs.readFileSync(costCentersFile, 'utf-8');
    return JSON.parse(data);
}

async function fetchStockDataForBatch(productId, costCenterBatch) {
    const costCenterQuery = costCenterBatch.join(',');
    const url = `https://www.migros.ch/de/product/${productId}`;

    const browser = await puppeteer.launch({
        executablePath: '/usr/bin/google-chrome', // Force Puppeteer to use system Chrome
        headless: false,  // Change to true if running on a server
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const page = await browser.newPage();

    // Set a realistic User-Agent
    await page.setUserAgent(
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36"
    );

    await page.setViewport({ width: 1280, height: 800 });

    console.log(`Navigating to ${url}...`);
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });

    // Wait for the API request and capture response
    const [response] = await Promise.all([
        page.waitForResponse(
            (res) => res.url().includes('store-availability/public/v2/availabilities/products') && res.status() === 200,
            { timeout: 15000 }
        ),
    ]);

    const jsonData = await response.json();
    
    await browser.close();
    
    return jsonData;
}

function saveStockDataToFile(stockData) {
    fs.writeFileSync(outputFile, JSON.stringify(stockData, null, 2), 'utf-8');
}

function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function fetchAllStockData() {
    console.log("Fetching all stock data...");

    try {
        const leshopchToken = 'eyJsdmwiOiJVIiwiZW5jIjoiQTI1NkdDTSIsImFsZyI6ImRpciIsImtpZCI6IjY5MGJhOTlhLTkyZTktNGNlMS05M2ZjLTQ3MDY4MmM1NmRhYSJ9..hJhs5j7VSQNpMjlY.3lMrHRwNbCan7Qyk0YzjruhmnyIJAOM1Ad1NbGavRzMQlv4OvmA7_y8wQt8HiZK73neSWsWyOS1NkuOrKKZwLGOqPKlrEutbECkONoowe7tdmrhG6pZqQmqZNfY0TZLoagdvhW8Isk6JYaav8hk3S-hABk1DufeH_PxwWHcthJ6TjYcRqO6TO_Xy0mvE2O1CwqTe9nOJYAg5TrUdOGA3d2pp3LdJ8p2XB2IdMxGWtjdzyz_kOOupGbT2Ud6ppZTWQduPVBo_v3uqGS7ibVor5nxnaD0N.ASC-q5Fc5ElCCms4EKqNvw'; // Replace with a valid token

        const costCenterInfo = await getCostCenters();
        const costCenters = Object.keys(costCenterInfo);
        const totalRequests = products.length * Math.ceil(costCenters.length / BATCH_SIZE);

        let existingData = fs.existsSync(outputFile)
            ? JSON.parse(fs.readFileSync(outputFile, 'utf-8'))
            : {};

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
                        totalProducts += stock;

                        if (!existingData[productId]) {
                            existingData[productId] = { availabilities: [] };
                        }

                        existingData[productId].availabilities.push({ id: costCenterId, stock });
                    });
                }

                requestCount++;
                console.log(`Fetching stock data for ${productType} - Progress: ${requestCount}/${totalRequests}`);
                await delay(2000); // Delay to prevent hitting API rate limits
            }

            console.log(`\nTotal stock for ${productType}: ${totalProducts}`);
        }

        saveStockDataToFile(existingData);
        console.log('\n✅ Stock data fetching completed!');
    } catch (error) {
        console.error('❌ Error during fetching stock data:', error.message);
    }
}

fetchAllStockData();
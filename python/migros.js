import axios from 'axios';
import * as fs from 'fs';

async function getBearerTokenV2() {
    try {
        const response = await axios.get('https://www.migros.ch/de', {
            headers: {
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            }
        });
        // Look for the token in the HTML response using an updated regex pattern
        // Save response to file for analysis
        fs.writeFileSync('response.html', response.data);
        fs.writeFileSync('response.html', response.data);
        console.log('Response saved to response.html');
        
        const match = response.data.match(/apiToken:\s*['"]([^'"]+)['"]/);
        if (match && match[1]) {
            return match[1];
        }
        // Try alternative pattern if first one fails
        const altMatch = response.data.match(/token:\s*['"]([^'"]+)['"]/);
        if (altMatch && altMatch[1]) {
            return altMatch[1];
        }
        throw new Error('Token not found in response');
    } catch (error) {
        console.error('Error getting bearer token:', error);
        throw error;
    }
}

async function fetchStoreAvailability(productId) {
    try {
        const bearerToken = await getBearerTokenV2();
        console.log("✅ Retrieved API v2 Bearer Token:", bearerToken);

        const storeAvailabilityUrl = `https://www.migros.ch/store-availability/public/v2/availabilities/products/${productId}`;
        
        const headers = {
            "accept": "application/json, text/plain, */*",
            "authorization": `Bearer ${bearerToken}`,
            "migros-language": "de",
            "peer-id": "website-js-800.0.0",
            "Referer": "https://www.migros.ch/de/product/746658000000"
        };

        const response = await axios.get(storeAvailabilityUrl, { headers });
        console.log("✅ Store Availability Data:", response.data);
        return response.data;
    } catch (error) {
        console.error("❌ Error:", error.response?.data || error.message);
        throw error;
    }
}

// Example usage
fetchStoreAvailability("100186845").catch(console.error);

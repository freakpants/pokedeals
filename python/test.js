import fetch from 'node-fetch';

async function getLeshopchToken() {
    console.log('Requesting leshopch token...');
    try {
        const response = await fetch('https://www.migros.ch/authentication/public/v1/api/oauth/authorize?redirectUri=https://www.migros.ch/m-login-silent-login-redirect.html&withLoginPrompt=false&claimType=LOGIN&authorizationNotRequired=true', {
            method: 'GET',
            headers: {
                "accept": "application/json, text/plain, */*",
                "accept-language": "de",
                "migros-language": "de",
                "peer-id": "website-js-809.0.0",
                "priority": "u=1, i",
                "sec-ch-ua": "\"Not(A:Brand\";v=\"99\", \"Google Chrome\";v=\"133\", \"Chromium\";v=\"133\"",
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": "\"Windows\"",
                "sec-fetch-dest": "empty",
                "sec-fetch-mode": "cors",
                "sec-fetch-site": "same-origin",
                "Referer": "https://www.migros.ch/de/product/749706600000",
                "Referrer-Policy": "strict-origin-when-cross-origin"
            }
        });

        const data = await response.json();
        if (data.url) {
            console.log('Authorization URL obtained:', data.url);

            // Simulate a fetch to the authorization URL to get the token
            const tokenResponse = await fetch(data.url, {
                method: 'GET',
                headers: {
                    "accept": "application/json, text/plain, */*",
                    "accept-language": "de",
                    "migros-language": "de",
                }
            });

            const tokenData = await tokenResponse.json();
            if (tokenData.leshopch) {
                console.log('Found leshopch token:', tokenData.leshopch);
                await fetchStoreAvailability(tokenData.leshopch);
            } else {
                console.error('No leshopch token found in response.');
            }
        } else {
            console.error('Failed to obtain authorization URL.');
        }

    } catch (error) {
        console.error('Error fetching leshopch token:', error);
    }
}

// Function to fetch store availability with the leshopch token
async function fetchStoreAvailability(token) {
    const url = 'https://www.migros.ch/store-availability/public/v2/availabilities/products/100119063?costCenterIds=0150054,0150148,0150082,0150253,0150169,0150163,0150153,0150014,0150319';

    const headers = {
        "accept": "application/json, text/plain, */*",
        "leshopch": token,
        "accept-language": "de",
        "migros-language": "de",
        "peer-id": "website-js-809.0.0",
        "priority": "u=1, i",
        "sec-ch-ua": "\"Not(A:Brand\";v=\"99\", \"Google Chrome\";v=\"133\", \"Chromium\";v=\"133\"",
        "sec-ch-ua-mobile": "?0",
        "sec-ch-ua-platform": "\"Windows\"",
        "sec-fetch-dest": "empty",
        "sec-fetch-mode": "cors",
        "sec-fetch-site": "same-origin",
        "x-correlation-id": "random-generated-id",
        "referer": "https://www.migros.ch/de/product/749706600000",
        "referrerPolicy": "strict-origin-when-cross-origin"
    };

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: headers,
            credentials: 'include',
            mode: 'cors'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Store Availability Data:', data);
    } catch (error) {
        console.error('Error fetching store availability:', error);
    }
}

// Start the process to fetch a fresh leshopch token and use it dynamically
getLeshopchToken();

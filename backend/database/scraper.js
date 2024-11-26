(async function resumeExtractor() {
    const startIndex = 0; // Resume from modal #86
    const maxProducts = 100; // Maximum products to extract
    const products = []; // Array to store new extracted product data
    let currentPage = 1;
    let stopFlag = false; // Flag to stop the process when needed

    // Add a Stop Button to the Page
    function addStopButton() {
        const stopButton = document.createElement('button');
        stopButton.textContent = 'Stop Extraction';
        stopButton.style.position = 'fixed';
        stopButton.style.top = '10px';
        stopButton.style.right = '10px';
        stopButton.style.zIndex = '9999';
        stopButton.style.padding = '10px 20px';
        stopButton.style.backgroundColor = '#ff0000';
        stopButton.style.color = '#fff';
        stopButton.style.border = 'none';
        stopButton.style.borderRadius = '5px';
        stopButton.style.cursor = 'pointer';
        document.body.appendChild(stopButton);

        stopButton.addEventListener('click', () => {
            stopFlag = true;
            console.log('Extraction stopped by user.');
        });
    }

    addStopButton();

    function randomDelay() {
        return new Promise(resolve => setTimeout(resolve, Math.random() * 2000 + 1000)); // Random delay (1–3 seconds)
    }

    function logProgress() {
        console.log(`Progress saved. Current JSON:`);
        console.log(JSON.stringify(products, null, 2));
    }

    function saveToFile(data) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'extracted-products.json';
        a.click();
        URL.revokeObjectURL(url);
    }

    async function extractDataFromModal() {
        const modal = document.querySelector('#quick-view-modal');

        if (modal) {
            // Extract product details
            const title = modal.querySelector('.quickview-modal-product-title--nDyQ5')?.textContent.trim() || "N/A";
            const price = modal.querySelector('.quickview-modal-price--UMJbK')?.textContent.trim() || "N/A";
            const productUrl = modal.querySelector('.quickview-modal-product-url--_1FaRq')?.href || "N/A";

            // Extract SKU from the URL
            const skuMatch = productUrl.match(/\/product\/(\d+-\d+)\//);
            const sku = skuMatch ? skuMatch[1] : "N/A";

            // Extract images
            const imageElements = modal.querySelectorAll('.image-gallery-thumbnail img');
            const images = Array.from(imageElements).map(img => img.src);

            // Add product to the array
            products.push({ title, sku, price, productUrl, images });

            console.log(`Extracted product: ${title} (${sku})`);
            logProgress(); // Save progress after each product
        } else {
            console.log("Modal not found or failed to load.");
        }
    }

    async function extractDataFromModalWithRetry(retries = 3) {
        for (let attempt = 0; attempt < retries; attempt++) {
            try {
                await extractDataFromModal();
                return; // Exit loop if successful
            } catch (error) {
                console.log(`Attempt ${attempt + 1} failed: ${error.message}`);
                await randomDelay();
            }
        }
        console.log("Failed to extract data after maximum retries.");
    }

    async function processModals() {
        const quickViewButtons = Array.from(document.querySelectorAll('.quickview-button--vPjyS')); // Locate Quick View buttons
        console.log(`Found ${quickViewButtons.length} Quick View buttons on page ${currentPage}.`);

        let currentIndex = startIndex; // Start from the correct modal index

        async function clickNextButton() {
            if (stopFlag) {
                console.log("Extraction stopped.");
                saveToFile(products);
                return;
            }

            if (products.length >= maxProducts) {
                console.log("Reached product limit. Stopping extraction.");
                saveToFile(products);
                return;
            }

            if (currentIndex < quickViewButtons.length) {
                console.log(`Clicking button ${currentIndex + 1} of ${quickViewButtons.length} on page ${currentPage}`);
                quickViewButtons[currentIndex].click(); // Click the Quick View button
                currentIndex++;

                // Wait for modal to load
                await randomDelay();

                // Extract data from the modal
                await extractDataFromModalWithRetry();

                // Close the modal
                const closeButton = document.querySelector('[data-testid="modalCloseButton"]');
                if (closeButton) {
                    closeButton.click();
                    console.log("Modal closed.");
                } else {
                    console.log("Close button not found. Trying alternative methods.");
                    document.body.click(); // Click outside modal as fallback
                }

                // Wait for modal to close
                await randomDelay();

                // Move to the next button
                await clickNextButton();
            } else {
                console.log(`Finished processing Quick View buttons on page ${currentPage}.`);
            }
        }

        await clickNextButton(); // Start extracting data
    }

    async function goToNextPage() {
        const nextPageButton = document.querySelector('.pager-arrow--Whe0d:not(.disabled--vkECP)'); // Select "Next" button
        if (nextPageButton) {
            nextPageButton.click();
            console.log(`Navigating to page ${currentPage + 1}...`);
            currentPage++;

            // Wait for the next page to load
            await randomDelay();

            // Process the next page
            await processModals();

            // Continue to the next page
            await goToNextPage();
        } else {
            console.log("No more pages available.");
            saveToFile(products); // Save collected data when done
        }
    }

    // Start extracting data from the first page
    await processModals();

    // Navigate to the next page if available
    await goToNextPage();

    // Final log of collected data
    console.log("Final data collection complete. Full JSON:");
    console.log(JSON.stringify(products, null, 2));
})();

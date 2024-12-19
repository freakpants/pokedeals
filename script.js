// Fetch products and shops from the Laravel API
const PRODUCTS_API_URL = 'https://pokeapi.freakpants.ch/api/products';
const SHOPS_API_URL = 'https://pokeapi.freakpants.ch/api/shops';
const SETS_API_URL = 'https://pokeapi.freakpants.ch/api/sets';

let shops = {}; // To store shop data for quick lookup
let allProducts = []; // To store all fetched products

async function fetchShops() {
    try {
        const response = await fetch(SHOPS_API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error fetching shops! Status: ${response.status}`);
        }
        const shopData = await response.json();
        shops = shopData.reduce((map, shop) => {
            map[shop.id] = shop;
            return map;
        }, {});
    } catch (error) {
        console.error('Error fetching shops:', error);
    }
}

async function fetchProducts() {
    try {
        const response = await fetch(PRODUCTS_API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error fetching products! Status: ${response.status}`);
        }
        const products = await response.json();
        allProducts = products; // Store all products for filtering
        await initializeFilters(products);
        renderProducts(products);
    } catch (error) {
        console.error('Error fetching products:', error);
        const productList = document.getElementById('product-list');
        productList.textContent = 'Failed to load products.';
    }
}

async function fetchSets() {
    try {
        const response = await fetch(SETS_API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error fetching sets! Status: ${response.status}`);
        }
        const sets = await response.json();
        return sets;
    }
    catch (error) {
        console.error('Error fetching sets:', error);
        return [];
    }
}

async function initializeFilters(products) {
    const url = new URL(window.location.href);
    const language = url.searchParams.get('language');
    const set = url.searchParams.get('set');

    const languageFilter = document.getElementById('language-filter');
    const setFilter = document.getElementById('set-filter');

    const languages = new Set();

    // Collect all languages from product matches
    products.forEach(product => {
        product.matches.forEach(match => {
            if (match.language) languages.add(match.language);
        });
    });

    // Populate the language filter
    languageFilter.innerHTML = `
        <option value="">All Languages</option>
        ${[...languages].map(lang => `<option value="${lang}">${lang}</option>`).join('')}
    `;

    // Fetch sets and populate the set filter with English names in reverse order
    const setData = await fetchSets();
    const setOptions = setData
        .reverse() // Reverse the sets array
        .map(set => ({
            value: set.set_identifier,
            label: set.title_en || set.set_identifier // Use English name or fall back to identifier
        }));

    setFilter.innerHTML = `
        <option value="">All Sets</option>
        ${setOptions.map(set => `<option value="${set.value}">${set.label}</option>`).join('')}
    `;

    // Set the filter values from the URL parameters after the options are populated
    if (language) {
        languageFilter.value = language;
    }
    if (set) {
        setFilter.value = set;
    }

    setFilter.addEventListener('change', applyFilters);
    languageFilter.addEventListener('change', applyFilters);
}

async function applyFilters() {
    const language = document.getElementById('language-filter').value;
    const setIdentifier = document.getElementById('set-filter').value;

    const filteredProducts = allProducts.filter(product => {
        const languageMatch = !language || product.matches.some(match => match.language === language);
        const setMatch = !setIdentifier || product.set_identifier === setIdentifier;
        return languageMatch && setMatch;
    });

    // also amend the url
    const url = new URL(window.location.href);
    url.searchParams.set('language', language);
    url.searchParams.set('set', setIdentifier);

    window.history.pushState({}, '', url);

    renderProducts(filteredProducts, language, setIdentifier);
}

function renderProducts(products, filterLanguage = '', filterSetIdentifier = '') {
    const productList = document.getElementById('product-list');
    productList.innerHTML = ''; // Clear previous content

    if (products.length === 0) {
        productList.textContent = 'No products found.';
        return;
    }

    products.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';

        const imageContainer = document.createElement('div');
        imageContainer.className = 'image-container';

        const mainImage = document.createElement('img');
        mainImage.src = product.images[0] || '';
        mainImage.alt = product.title;
        mainImage.className = 'main-image';
        imageContainer.appendChild(mainImage);

        productCard.innerHTML += `
            <h2>${product.title}</h2>
            <p>Pokemon Center Price: ${product.price || 'Price not available'}</p>
            <p>Pack Count: ${product.pack_count}</p>
            <a href="${product.product_url}" target="_blank">View Product</a>
        `;

        // Filter matches strictly by the selected language
        const filteredMatches = product.matches.filter(match => {
            return !filterLanguage || match.language === filterLanguage;
        });

        // Only include shops with matches in the correct language
        const shopGroups = filteredMatches.reduce((groups, match) => {
            const shop = shops[match.shop_id] || {};
            if (!groups[shop.id]) groups[shop.id] = [];
        
            // Only add matches that pass the language filter
            const languageMatch = !filterLanguage || match.language === filterLanguage;
            if (languageMatch) {
                groups[shop.id].push(match);
            }
        
            return groups;
        }, {});
        

        // Remove empty shops (i.e., those with no matches after filtering)
        Object.keys(shopGroups).forEach(shopId => {
            if (shopGroups[shopId].length === 0) {
                delete shopGroups[shopId];
            }
        });

        // determine cheapest offer in each shop, then sort the shopgroups by their cheapest offer
        Object.keys(shopGroups).forEach(shopId => {
            // determine which of the offers of the shop is the cheapest
            const offers = shopGroups[shopId];
            const cheapestOffer = offers.reduce((cheapest, offer) => {
                return offer.price < cheapest.price ? offer : cheapest;
            }, { price: Infinity });

            shopGroups[shopId] = offers;
            shopGroups[shopId].cheapestOffer = cheapestOffer;
        });

        // sort the shopgroups by their cheapest offer
        const sortedShopGroups = Object.entries(shopGroups).sort((a, b) => {
            return a[1].cheapestOffer.price - b[1].cheapestOffer.price;
        });


        const matchesContainer = document.createElement('div');
        matchesContainer.className = 'matches';
        matchesContainer.innerHTML = '<h3>Offers:</h3>';

        // Render only shops with filtered offers
        let first = true;
        sortedShopGroups.forEach(([shopId, currentShopGroup]) => {
            const shop = shops[shopId] || {};
            const offers = currentShopGroup.filter(match => {
                // Filter matches by the selected language during rendering
                return !filterLanguage || match.language === filterLanguage;
                
            });
        
            if (offers.length === 0) return; // Skip shops with no valid offers

            // sort the offers inside the shop group too
            offers.sort((a, b) => a.price - b.price);
        
            const shopGroup = document.createElement('div');
            shopGroup.className = 'shop-group';
        
            const shopLogo = `<img src="assets/images/shop-logos/${shop.image || ''}" 
                                        alt="${shop.name || 'Shop'} Logo" 
                                        class="shop-logo">`;
        
            
                                        
                     
            shopGroup.innerHTML = `
                <div class="shop-header">
                    ${shopLogo}
                    <strong>${shop.name}</strong>
                </div>
                <ul>
                    ${offers
                        .map(offer => `
                            <li>
                                <a href="${offer.external_product.url}" target="_blank" class="match-link">
                                    ${offer.title}
                                </a>
                                <span class="product-price">CHF ${offer.price.toFixed(2) || 'Price not available'}</span>              
                                <span class="price-per-pack">(~${(offer.price / product.pack_count).toFixed(2) || 'Price per pack not available'} per pack)</span>
                            </li>`).join('')}
                </ul>
            `;

            // only show the shop group initially if it is the first one
            if (!first) {
                shopGroup.style.display = 'none';
            }
            first = false;


            matchesContainer.appendChild(shopGroup);
        });

        // Add a button to show/hide the other shop groups
        if (sortedShopGroups.length > 1) {
            const toggleButton = document.createElement('button');
            toggleButton.textContent = 'Show/Hide Other Offers';
            toggleButton.className = 'toggle-button';
            // remember the product id to toggle the shop groups
            toggleButton.dataset.productId = product.id;
            // toggle all shop groups inside this product
            toggleButton.addEventListener('click', event => {
                const productId = event.target.dataset.productId;
                const productCard = event.target.closest('.product-card');
                const shopGroups = productCard.querySelectorAll('.shop-group');
                let first = true;
                shopGroups.forEach(shopGroup => {
                    // only toggle the shop group if it is not the first one
                    if (!first) {
                        shopGroup.style.display = shopGroup.style.display === 'none' ? 'block' : 'none';
                    }
                    first = false;
                });
            });

            matchesContainer.appendChild(toggleButton);
        }

        // Add the product id to the product card
        productCard.dataset.productId = product.id;

        if (Object.keys(shopGroups).length > 0) {
            productCard.appendChild(imageContainer);
            productCard.appendChild(matchesContainer);
            productList.appendChild(productCard);
        }
    });
}

// Initialize the app
(async function initialize() {
    await fetchShops(); // Fetch shops first to have the data ready
    await fetchProducts().then(() => {
        const url = new URL(window.location.href);
        const language = url.searchParams.get('language');
        const set = url.searchParams.get('set');
        if (language) {
            document.getElementById('language-filter').value = language;
        }
        if (set) {
            document.getElementById('set-filter').value = set;
        }
        applyFilters(); // Apply filters after setting the filter values
    });
})();

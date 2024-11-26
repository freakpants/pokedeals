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
        initializeFilters(products);
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

function initializeFilters(products) {
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
    fetchSets().then(setData => {
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

        setFilter.addEventListener('change', () => {
            const setIdentifier = setFilter.value;
            applyFilters('', setIdentifier); // Explicitly pass setIdentifier to applyFilters
        });


    });

    languageFilter.addEventListener('change', applyFilters);
}

function applyFilters() {
    const language = document.getElementById('language-filter').value;
    const setIdentifier = document.getElementById('set-filter').value;

    const filteredProducts = allProducts.filter(product => {
        const languageMatch = !language || product.matches.some(match => match.language === language);
        const setMatch = !setIdentifier || product.set_identifier === setIdentifier;
        return languageMatch && setMatch;
    });


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
            <p>Pokemon Center UK Price: ${product.price || 'Price not available'}</p>
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



        const matchesContainer = document.createElement('div');
        matchesContainer.className = 'matches';
        matchesContainer.innerHTML = '<h3>Offers:</h3>';

        // Render only shops with filtered offers
        Object.keys(shopGroups).forEach(shopId => {
            const shop = shops[shopId] || {};
            const offers = shopGroups[shopId].filter(match => {
                // Filter matches by the selected language during rendering
                console.log(match.language);
                console.log(filterLanguage);
                return !filterLanguage || match.language === filterLanguage;
                
            });
        
            if (offers.length === 0) return; // Skip shops with no valid offers
        
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
                                    ${offer.title}: ${offer.price || 'Price not available'}
                                </a>
                            </li>`).join('')}
                </ul>
            `;
            matchesContainer.appendChild(shopGroup);
        });
        
        

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
    await fetchProducts();
})();

// Fetch products and shops from the Laravel API
const PRODUCTS_API_URL = 'https://pokeapi.freakpants.ch/api/products';
const SHOPS_API_URL = 'https://pokeapi.freakpants.ch/api/shops';
const SETS_API_URL = 'https://pokeapi.freakpants.ch/api/sets';
const SERIES_API_URL = 'https://pokeapi.freakpants.ch/api/series';
const PRODUCT_TYPES_API_URL = 'https://pokeapi.freakpants.ch/api/product_types';

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
        const productsData = products.data;
        allProducts = productsData ; // Store all products for filtering
        await initializeFilters(productsData);
        renderProducts(productsData);
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

async function fetchSeries() {
    try {
        const response = await fetch(SERIES_API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error fetching sets! Status: ${response.status}`);
        }
        const series = await response.json();
        // save to window
        window.series = series;
    }
    catch (error) {
        console.error('Error fetching sets:', error);
        return [];
    }
}

async function fetchProductTypes() {
    try {
        const response = await fetch(PRODUCT_TYPES_API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error fetching sets! Status: ${response.status}`);
        }
        const productTypes = await response.json();
        return productTypes;
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
    const productTypeFilter = document.getElementById('product-type-filter');

    const languages = new Set();

    // Collect all languages from product matches
    products.forEach(product => {
        product.matches.forEach(match => {
            if (match.language) languages.add(match.language);
        });
    });

    // add japanese manually
    languages.add('ja');


    // Populate the language filter
    languageFilter.innerHTML = `
        <option value=""><span class="flag-icon flag-icon-gb" product-language-flag"></span>English, German and French</option>
        ${[...languages].map(lang => `<option value="${lang}">${lang}</option>`).join('')}
    `;

    // Fetch sets and populate the set filter with English names in reverse order
    const setData = await fetchSets();

    // save the unfiltered sets in a global variable
    window.allSets = setData;

    // filter out japanese sets unless the language is set to japanese
    createSetFilter(language === 'ja');

    // Set the filter values from the URL parameters after the options are populated
    if (language) {
        languageFilter.value = language;
    }
    if (set) {
        setFilter.value = set;
    }

    // fetch product types and populate the filter
    const productTypes = await fetchProductTypes();
    productTypeFilter.innerHTML = `
        <option value="">All Product Types</option>
        ${productTypes.map(type => `<option value="${type.product_type}">${type.en_name}</option>`).join('')}
    `;

    setFilter.addEventListener('change', applyFilters);
    languageFilter.addEventListener('change', applyFilters);
    productTypeFilter.addEventListener('change', applyFilters);
}

async function applyFilters() {
    const language = document.getElementById('language-filter').value;
    const setIdentifier = document.getElementById('set-filter').value;
    const productType = document.getElementById('product-type-filter').value;

    const filteredProducts = allProducts.filter(product => {
        const languageMatch = !language || product.matches.some(match => match.language === language);
        const setMatch = !setIdentifier || product.set_identifier === setIdentifier;
        const typeMatch = !productType || product.product_type === productType;
        return languageMatch && setMatch && typeMatch;
    });

    const setData = window.allSets;
    const setFilter = document.getElementById('set-filter');
    const firstSet = setFilter.options[1].value;
    // select that set from the setData
    const set = setData.find(set => set.set_identifier === firstSet);
    // check if that set has title_ja set to null
    const currentlyJapanese = set.title_ja !== null;

    // if the language is ja, show only ja sets
    if(language === 'ja') {
        console.log('we are switching to japanese');
        if(!currentlyJapanese) {
            createSetFilter(true);
        }

    } else {
        console.log('we are switching to western');
        if(currentlyJapanese) {
            createSetFilter(false);
        }
    }

    // also amend the url
    const url = new URL(window.location.href);
    url.searchParams.set('language', language);
    url.searchParams.set('set', setIdentifier);

    window.history.pushState({}, '', url);

    renderProducts(filteredProducts, language, setIdentifier);
}

function createSetFilter(japanese = false) {
    const setData = window.allSets;
    const setFilter = document.getElementById('set-filter');
    let setDataFiltered;

    if (japanese) {
        setDataFiltered = setData.filter(set => set.title_ja !== null);
    } else {
        setDataFiltered = setData.filter(set => set.title_ja === null);
    }

    // Sort sets by release_date in descending order
    setDataFiltered.sort((a, b) => new Date(b.release_date) - new Date(a.release_date));

    // Group sets by series
    const setsBySeries = setDataFiltered.reduce((groups, set) => {
        const series = set.series_id || 'Other';
        if (!groups[series]) {
            groups[series] = [];
        }
        groups[series].push(set);
        return groups;
    }, {});

    // Populate the set filter with optgroups for each series
    setFilter.innerHTML = '<option value="">All Products</option>';
    Object.keys(setsBySeries).forEach(series => {
        const optgroup = document.createElement('optgroup');
        // lookup the english title in the series object
        const seriesObject = window.series.find(s => s.id === series);
        console.log(seriesObject);
        optgroup.label = seriesObject ? seriesObject.name_en : series;
        setsBySeries[series].forEach(set => {
            const option = document.createElement('option');
            option.value = set.set_identifier;
            option.textContent = set.title_en || set.set_identifier;
            optgroup.appendChild(option);
        });
        setFilter.appendChild(optgroup);
    });

    // Set the filter value from the URL parameter if available
    const url = new URL(window.location.href);
    const set = url.searchParams.get('set');
    if (set) {
        setFilter.value = set;
    }
}

// Mapping of languages to country codes
const languageToCountryCode = {
    en: 'gb', // UK
    de: 'de', // Germany
    fr: 'fr', // France
    ja: 'jp', // Japan
    // Add more languages and their respective country codes here
};

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

        // Full-width title
        const title = document.createElement('h2');
        title.textContent = product.title.replace('Pokémon TCG: ', '');
        productCard.appendChild(title);

        // Row for image and details
        const contentRow = document.createElement('div');
        contentRow.className = 'content-row';

        // Image container
        const imageContainer = document.createElement('div');
        imageContainer.className = 'image-container';

        const mainImage = document.createElement('img');
        mainImage.src = product.images[0] || '';
        mainImage.alt = product.title;
        mainImage.className = 'main-image';
        imageContainer.appendChild(mainImage);

        // Product details container
        const productDetails = document.createElement('div');
        productDetails.className = 'product-details';

        const price = document.createElement('span');
        price.textContent = `Pokemon Center Price: ${product.price || 'Price not available'}`;

        const packs = document.createElement('span');
        packs.textContent = `Packs in product: ${product.pack_count}`;

        const link = document.createElement('a');
        link.href = product.product_url;
        link.target = '_blank';
        link.textContent = 'View on Pokemon Center';

        productDetails.appendChild(price);
        productDetails.appendChild(packs);
        productDetails.appendChild(link);

        contentRow.appendChild(imageContainer);
        contentRow.appendChild(productDetails);
        productCard.appendChild(contentRow);

        // Offers section
        const matchesContainer = document.createElement('div');
        matchesContainer.className = 'matches';
        matchesContainer.innerHTML = '<h3>Offers:</h3>';

        const filteredMatches = product.matches.filter(match => {
            return !filterLanguage || match.language === filterLanguage;
        });

        filteredMatches.sort((a, b) => a.price - b.price);

        // Display the cheapest offer
        if (filteredMatches.length > 0) {
            const cheapestOfferElement = renderOffer(product, filteredMatches[0]);
            cheapestOfferElement.classList.add('cheapest-offer');
            matchesContainer.appendChild(cheapestOfferElement);
        }

        // Expandable offers section
        if (filteredMatches.length > 1) {
            const toggleContainer = document.createElement('div');
            toggleContainer.className = 'toggle-container';

            const toggleButton = document.createElement('button');
            toggleButton.className = 'toggle-button';
            toggleButton.textContent = 'Show More Offers';

            const otherOffersContainer = document.createElement('div');
            otherOffersContainer.className = 'other-offers';
            otherOffersContainer.style.display = 'none'; // Hidden by default

            filteredMatches.slice(1).forEach(match => {
                const offerElement = renderOffer(product, match);
                otherOffersContainer.appendChild(offerElement);
            });

            toggleButton.addEventListener('click', () => {
                const isHidden = otherOffersContainer.style.display === 'none';
                otherOffersContainer.style.display = isHidden ? 'flex' : 'none';
                toggleButton.textContent = isHidden ? 'Show Fewer Offers' : 'Show More Offers';
            });

            toggleContainer.appendChild(toggleButton);
            toggleContainer.appendChild(otherOffersContainer);
            matchesContainer.appendChild(toggleContainer);
        }

        productCard.appendChild(matchesContainer);
        productList.appendChild(productCard);
    });
}

// Reusable function to render a single offer
function renderOffer(product, match) {
    const shop = shops[match.shop_id] || {};
    const shopLogo = `<img src="assets/images/shop-logos/${shop.image || ''}" 
                      alt="${shop.name || 'Shop'} Logo" 
                      class="shop-logo">`;

    // Ensure pack_count is handled correctly
    const packCount = product.pack_count || 1; // Default to 1 if pack_count is undefined or 0
    const pricePerPack = (match.price / packCount).toFixed(2); // Calculate price per pack

    const offerElement = document.createElement('div');
    offerElement.className = 'offer';

    offerElement.innerHTML = `
        <div class="shop-info">
            ${shopLogo}
            <strong>${shop.name || 'Unknown Shop'}</strong>
            <div class="product-price">
                CHF ${match.price.toFixed(2)}
                <span class="price-per-pack">(~${pricePerPack} per pack)</span>
            </div>
        </div>
        <div class="language-and-title">
            <span class="flag-icon flag-icon-${languageToCountryCode[match.language] || 'unknown'} product-language-flag"></span>
            <a href="${match.external_product.url}" target="_blank" class="match-link">
                ${match.title}
            </a>
        </div>
    `;

    return offerElement;
}




// Initialize the app
(async function initialize() {
    await fetchShops(); // Fetch shops first to have the data ready
    await fetchSeries();
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

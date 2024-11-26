// Fetch products and shops from the Laravel API
const PRODUCTS_API_URL = 'https://pokeapi.freakpants.ch/api/products';
const SHOPS_API_URL = 'https://pokeapi.freakpants.ch/api/shops';

let shops = {}; // To store shop data for quick lookup

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
        renderProducts(products);
    } catch (error) {
        console.error('Error fetching products:', error);
        const productList = document.getElementById('product-list');
        productList.textContent = 'Failed to load products.';
    }
}

function renderProducts(products) {
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

        const shopGroups = product.matches.reduce((groups, match) => {
            const shop = shops[match.shop_id] || {};
            if (!groups[shop.id]) groups[shop.id] = [];
            groups[shop.id].push(match);
            return groups;
        }, {});

        const matchesContainer = document.createElement('div');
        matchesContainer.className = 'matches';
        matchesContainer.innerHTML = '<h3>Offers:</h3>';

        Object.keys(shopGroups).forEach(shopId => {
            const shop = shops[shopId] || {};
            const offers = shopGroups[shopId];

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
                            </li>`)
                        .join('')}
                </ul>
            `;
            matchesContainer.appendChild(shopGroup);
        });

        productCard.appendChild(imageContainer);
        productCard.appendChild(matchesContainer);
        productList.appendChild(productCard);
    });
}




// Initialize the app
(async function initialize() {
    await fetchShops(); // Fetch shops first to have the data ready
    await fetchProducts();
})();

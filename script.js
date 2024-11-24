// Fetch products from your Laravel API
const API_URL = 'https://pokeapi.freakpants.ch/api/products'; // Replace with your actual API URL

async function fetchProducts() {
    try {
        const response = await fetch(API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const products = await response.json();
        renderProducts(products);
    } catch (error) {
        console.error('Error fetching products:', error);
        document.getElementById('product-list').textContent = 'Failed to load products.';
    }
}

function renderProducts(products) {
    const productList = document.getElementById('product-list');
    productList.innerHTML = ''; // Clear the loading text

    products.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';

        productCard.innerHTML = `
            <img src="${product.images[0] || ''}" alt="${product.title}">
            <h2>${product.title}</h2>
            <p>${product.price || 'Price not available'}</p>
            <a href="${product.product_url}" target="_blank">View Product</a>
        `;

        productList.appendChild(productCard);
    });
}

// Initialize the app
fetchProducts();

// Fetch products from your Laravel API
const API_URL = 'https://pokeapi.freakpants.ch/api/products';

async function fetchProducts() {
    try {
        const response = await fetch(API_URL);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
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
    productList.innerHTML = ''; // Clear the loading message or previous content

    if (products.length === 0) {
        productList.textContent = 'No products found.';
        return;
    }

    products.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';

        // Create the product HTML
        productCard.innerHTML = `
            <div class="product-images">
                ${product.images
                    .map(imageUrl => `<img src="${imageUrl}" alt="${product.title}" class="product-image">`)
                    .join('')}
            </div>
            <h2>${product.title}</h2>
            <p>Local Price: ${product.price || 'Price not available'}</p>
            <a href="${product.product_url}" target="_blank">View Product</a>
            <div class="matches">
                <h3>Matches:</h3>
                <ul>
                    ${
                        product.matches && product.matches.length > 0
                            ? product.matches
                                  .map(
                                      match =>
                                          `<li>
                                              <span>${match.title}:</span>
                                              <span>${match.price || 'Price not available'}</span>
                                          </li>`
                                  )
                                  .join('')
                            : '<li>No matches found</li>'
                    }
                </ul>
            </div>
        `;

        productList.appendChild(productCard);
    });
}

// Initialize the app
fetchProducts();

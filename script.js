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

        // Create a container for the main image
        const imageContainer = document.createElement('div');
        imageContainer.className = 'image-container';

        const mainImage = document.createElement('img');
        mainImage.src = product.images[0] || '';
        mainImage.alt = product.title;
        mainImage.className = 'main-image';

        // Handle hover to cycle through images
        let currentImageIndex = 0;
        let hoverInterval;

        imageContainer.addEventListener('mouseenter', () => {
            if (product.images.length > 1) {
                hoverInterval = setInterval(() => {
                    currentImageIndex = (currentImageIndex + 1) % product.images.length;
                    mainImage.src = product.images[currentImageIndex];
                }, 1000); // Change image every 1 second
            }
        });

        imageContainer.addEventListener('mouseleave', () => {
            clearInterval(hoverInterval);
            currentImageIndex = 0;
            mainImage.src = product.images[0]; // Reset to the first image
        });

        imageContainer.appendChild(mainImage);

        // Add the rest of the product details
        productCard.innerHTML += `
            <h2>${product.title}</h2>
            <p>Pokemon Center UK Price: ${product.price || 'Price not available'}</p>
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

        productCard.prepend(imageContainer); // Add the image container at the top
        productList.appendChild(productCard);
    });
}


// Initialize the app
fetchProducts();

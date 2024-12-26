const { useState, useEffect } = React;
const { Select, MenuItem, InputLabel, FormControl, Checkbox, ListItemText, ListSubheader } = MaterialUI;

// API Endpoints
const PRODUCTS_API_URL = 'https://pokeapi.freakpants.ch/api/products';
const SHOPS_API_URL = 'https://pokeapi.freakpants.ch/api/shops';
const SETS_API_URL = 'https://pokeapi.freakpants.ch/api/sets';
const SERIES_API_URL = 'https://pokeapi.freakpants.ch/api/series';
const PRODUCT_TYPES_API_URL = 'https://pokeapi.freakpants.ch/api/product_types';

// Utility for language-country mapping
const languageToCountryCode = {
  en: 'gb',
  de: 'de',
  fr: 'fr',
  ja: 'jp',
};

const languageToDisplayName = {
  en: 'English',
  de: 'German',
  fr: 'French',
  ja: 'Japanese',
};

const parseQueryParams = () => {
  const params = new URLSearchParams(window.location.search);
  return {
    language: params.get('language') ? params.get('language').split(',') : [],
    set: params.get('set') ? params.get('set').split(',') : [],
    productType: params.get('productType') || '',
  };
};

const updateUrlParams = (filters) => {
  const params = new URLSearchParams();

  if (filters.language.length > 0) params.set('language', filters.language.join(','));
  if (filters.set.length > 0) params.set('set', filters.set.join(','));
  if (filters.productType) params.set('productType', filters.productType);

  const newUrl = `${window.location.pathname}?${params.toString()}`;
  window.history.replaceState(null, '', newUrl); // Update the URL
};

const App = () => {
  const [shops, setShops] = useState({});
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [sets, setSets] = useState([]);
  const [series, setSeries] = useState([]);
  const [productTypes, setProductTypes] = useState([]);
  const [filters, setFilters] = useState({
    language: ['de', 'en', 'fr'], // Default selected languages
    set: [],
    productType: '',
  });

  const [sortConfig, setSortConfig] = useState({
    key: 'release-date',
    order: 'desc',
  });

  const [productCount, setProductCount] = useState(0);
  const [offerCount, setOfferCount] = useState(0);

  useEffect(() => {
    fetchInitialData(); // Fetch all data
    const queryParams = parseQueryParams(); // Extract filters from URL
    // if the query params have no language, default to en, de, fr
    if (queryParams.language.length === 0) {
      queryParams.language = ['en', 'de', 'fr'];
    }
    setFilters(queryParams); // Update state with filters
  }, []); // Only on mount

  useEffect(() => {
    if (products.length > 0) {
      applyFilters(); // Apply filters after products are loaded
      updateUrlParams(filters); // Sync the updated filters with the URL
    }
  }, [filters, sortConfig, products]); // Run when filters, sortConfig, or products change

  const fetchInitialData = async () => {
    await Promise.all([fetchShops(), fetchSets(), fetchSeries(), fetchProductTypes(), fetchProducts()]);
  };

  const fetchShops = async () => {
    try {
      const response = await fetch(SHOPS_API_URL);
      const data = await response.json();
      const shopsMap = data.reduce((acc, shop) => {
        acc[shop.id] = shop;
        return acc;
      }, {});
      setShops(shopsMap);
    } catch (error) {
      console.error('Error fetching shops:', error);
    }
  };

  const fetchProducts = async () => {
    try {
      const response = await fetch(PRODUCTS_API_URL);
      const data = await response.json();
      setProducts(data.data);
      setFilteredProducts(data.data);
    } catch (error) {
      console.error('Error fetching products:', error);
    }
  };

  const fetchSets = async () => {
    try {
      const response = await fetch(SETS_API_URL);
      const data = await response.json();
      setSets(data);
    } catch (error) {
      console.error('Error fetching sets:', error);
    }
  };

  const fetchSeries = async () => {
    try {
      const response = await fetch(SERIES_API_URL);
      const data = await response.json();
      setSeries(data);
    } catch (error) {
      console.error('Error fetching series:', error);
    }
  };

  const fetchProductTypes = async () => {
    try {
      const response = await fetch(PRODUCT_TYPES_API_URL);
      const data = await response.json();
      setProductTypes(data);
    } catch (error) {
      console.error('Error fetching product types:', error);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters((prevFilters) => ({
      ...prevFilters,
      [key]: key === 'set' ? value : value, // Ensure 'set' filter is always an array
    }));
  };

  // Updated filtering logic in applyFilters
  const applyFilters = () => {
    let result = products;
    let totalOffers = 0;

    // Filter products based on language and update their matches
    if (filters.language.length > 0) {
      result = result
        .map((product) => {
          const filteredMatches = product.matches.filter((match) =>
            filters.language.includes(match.language)
          );
          return filteredMatches.length > 0 ? { ...product, matches: filteredMatches } : null;
        })
        .filter((product) => product !== null); // Remove products with no valid matches
    }

    // Filter products based on set
    if (filters.set.length > 0) {
      result = result.filter((product) =>
        filters.set.includes(product.set_identifier)
      );
    }

    // Filter products based on product type
    if (filters.productType) {
      result = result.filter((product) => product.product_type === filters.productType);
    }

    // Count total offers after all filters are applied
    totalOffers = result.reduce((count, product) => count + product.matches.length, 0);

    // Sort the filtered products
    result = sortProducts(result, sortConfig.key, sortConfig.order);

    setFilteredProducts(result);
    setProductCount(result.length);
    setOfferCount(totalOffers);
  };

  const sortProducts = (products, key, order) => {
    if (order === 'none') return products;
    return [...products].sort((a, b) => {
      let valueA, valueB;
      if (key === 'release-date') {
        valueA = new Date(a.release_date);
        valueB = new Date(b.release_date);
      } else if (key === 'price-per-pack') {
        const cheapestA = a.matches.reduce((min, match) => match.price < min ? match.price : min, Infinity);
        const cheapestB = b.matches.reduce((min, match) => match.price < min ? match.price : min, Infinity);
        valueA = cheapestA / (a.pack_count || 1);
        valueB = cheapestB / (b.pack_count || 1);
      }
      return order === 'asc' ? valueA - valueB : valueB - valueA;
    });
  };

  const toggleSort = (key) => {
    setSortConfig((prevConfig) => ({
      key,
      order: prevConfig.key === key
        ? prevConfig.order === 'asc'
          ? 'desc'
          : 'asc'
        : 'asc',
    }));
  };

  const renderSetFilterOptions = () => {
    const languageIsJapanese = filters.language.includes('ja');
    const filteredSets = sets.filter((set) =>
      languageIsJapanese ? set.title_ja : !set.title_ja
    );

    // Sort sets by release_date descending
    const sortedSets = [...filteredSets].sort((a, b) => {
      const dateA = new Date(a.release_date);
      const dateB = new Date(b.release_date);
      return dateB - dateA; // Descending order
    });

    const groupedSets = sortedSets.reduce((acc, set) => {
      const seriesTitle = series.find((s) => s.id === set.series_id)?.name_en || 'Other';
      if (!acc[seriesTitle]) acc[seriesTitle] = [];
      acc[seriesTitle].push(set);
      return acc;
    }, {});

    return Object.entries(groupedSets).flatMap(([seriesTitle, sets]) => [
      React.createElement(
        ListSubheader,
        { key: `header-${seriesTitle}` },
        seriesTitle
      ),
      ...sets.map((set) =>
        React.createElement(
          MenuItem,
          { value: set.set_identifier, key: set.set_identifier },
          set.title_en || set.title_ja
        )
      ),
    ]);
  };

  const renderOffer = (product, match, isCheapest) => {
    const shop = shops[match.shop_id] || {};
    const packCount = product.pack_count || 1;
    const pricePerPack = (match.price / packCount).toFixed(2);
    const countryCode = languageToCountryCode[match.language] || 'unknown'; // Default if no match

    return React.createElement(
      'div',
      { className: `offer ${isCheapest ? 'cheapest-offer' : ''}`, key: match.id },
      React.createElement(
        'div',
        { className: 'shop-info' },
        React.createElement('img', {
          src: `assets/images/shop-logos/${shop.image || ''}`,
          alt: `${shop.name || 'Shop'} Logo`,
          className: 'shop-logo',
        }),
        React.createElement('strong', null, shop.name || 'Unknown Shop'),
        React.createElement(
          'div',
          { className: 'product-price' },
          `CHF ${match.price.toFixed(2)}`,
          React.createElement('span', { className: 'price-per-pack' }, `(~${pricePerPack} per pack)`)
        )
      ),
      React.createElement(
        'div',
        { className: 'language-and-title' },
        React.createElement('span', {
          className: `flag-icon flag-icon-${countryCode}`,
          title: match.language, // Tooltip for accessibility
        }),
        React.createElement(
          'a',
          { href: match.external_product.url, target: '_blank', className: 'match-link' },
          match.title
        )
      )
    );
  };

  const renderProductCard = (product) => {
    const cheapestMatch = product.matches.slice().sort((a, b) => a.price - b.price)[0];
    const otherMatches = product.matches
      .slice()
      .sort((a, b) => a.price - b.price)
      .slice(1);

    const matchesContainer = React.createElement('div', { className: 'matches' }, [
      renderOffer(product, cheapestMatch, true),
      otherMatches.length > 0 &&
        React.createElement(
          'div',
          { className: 'toggle-container' },
          React.createElement(
            'button',
            {
              className: 'toggle-button',
              onClick: (e) => {
                const sibling = e.target.nextSibling;
                sibling.style.display = sibling.style.display === 'none' ? 'block' : 'none';
                e.target.textContent =
                  sibling.style.display === 'none' ? 'Show More Offers' : 'Show Fewer Offers';
              },
            },
            'Show More Offers'
          ),
          React.createElement(
            'div',
            { className: 'other-offers', style: { display: 'none' } },
            otherMatches.map((match) => renderOffer(product, match, false))
          )
        ),
    ]);

    const productDetails = React.createElement(
      'div',
      { className: 'product-details' },
      React.createElement('span', null, `Pokemon Center Price: ${product.price || 'Price not available'}`),
      React.createElement('span', null, `Packs in product: ${product.pack_count}`),
      React.createElement(
        'a',
        { href: product.product_url, target: '_blank' },
        'View on Pokemon Center'
      )
    );

    return React.createElement(
      'div',
      { className: 'product-card', key: product.id },
      React.createElement('h2', null, product.title.replace('Pokémon TCG: ', '')),
      React.createElement(
        'div',
        { className: 'content-row' },
        React.createElement(
          'div',
          { className: 'image-container' },
          React.createElement('img', {
            src: product.images[0] || '',
            alt: product.title,
            className: 'main-image',
          })
        ),
        productDetails
      ),
      matchesContainer
    );
  };

  const filtersComponent = React.createElement(
    'div',
    { id: 'filters' },
    React.createElement(
      FormControl,
      null,
      React.createElement(InputLabel, { id: 'language-filter-label' }, 'Language'),
      React.createElement(
        Select,
        {
          labelId: 'language-filter-label',
          multiple: true,
          value: filters.language,
          onChange: (e) => handleFilterChange('language', e.target.value),
          renderValue: (selected) => selected.map(lang => languageToDisplayName[lang]).join(', '),
        },
        React.createElement(MenuItem, { value: 'en' }, 'English'),
        React.createElement(MenuItem, { value: 'de' }, 'German'),
        React.createElement(MenuItem, { value: 'fr' }, 'French'),
        React.createElement(MenuItem, { value: 'ja' }, 'Japanese')
      )
    ),
    React.createElement(
      FormControl,
      null,
      React.createElement(InputLabel, { id: 'set-filter-label' }, 'Set'),
      React.createElement(
        Select,
        {
          id: 'set-filter',
          labelId: 'set-filter-label',
          multiple: true, // Enable multi-select
          value: filters.set, // Array of selected values
          onChange: (e) => handleFilterChange('set', e.target.value),
          renderValue: (selected) =>
            selected
              .map((setId) => {
                const set = sets.find((s) => s.set_identifier === setId);
                return set ? set.title_en || set.title_ja : '';
              })
              .join(', '),
        },
        renderSetFilterOptions()
      )
    ),
    React.createElement(
      FormControl,
      null,
      React.createElement(InputLabel, { id: 'product-type-filter-label' }, 'Product Type'),
      React.createElement(
        Select,
        {
          labelId: 'product-type-filter-label',
          value: filters.productType,
          onChange: (e) => handleFilterChange('productType', e.target.value),
        },
        React.createElement(MenuItem, { value: '' }, 'All Product Types'),
        productTypes.map((type) =>
          React.createElement(MenuItem, { key: type.product_type, value: type.product_type }, type.en_name)
        )
      )
    )
  );

  const sortingComponent = React.createElement(
    'div',
    { id: 'sorting' },
    React.createElement(
      'button',
      { onClick: () => toggleSort('release-date') },
      'Sort by Release Date'
    ),
    React.createElement(
      'button',
      { onClick: () => toggleSort('price-per-pack') },
      'Sort by Price per Pack'
    )
  );

  return React.createElement(
    'div',
    null,
    filtersComponent,
    sortingComponent,
    React.createElement('div', { id: 'result-count' }, `Matched Products: ${productCount}`),
    React.createElement('div', { id: 'offer-count' }, `Offers found for these products: ${offerCount}`),
    React.createElement('div', { id: 'product-list' }, filteredProducts.map(renderProductCard))
  );
};

ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(App));
const { useState, useEffect } = React;
const { Select, MenuItem, InputLabel, FormControl, Checkbox, ListItemText, ListSubheader, Chip, SvgIcon } = MaterialUI;

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
  cn: 'cn',
  kr: 'kr',
};

const languageToDisplayName = {
  en: 'English',
  de: 'German',
  fr: 'French',
  ja: 'Japanese',
  cn: '(Simplified/Traditional) Chinese',
  kr: 'Korean',  

};

const parseQueryParams = () => {
  const params = new URLSearchParams(window.location.search);
  return {
    language: params.get('language') ? params.get('language').split(',') : [],
    set: params.get('set') ? params.get('set').split(',') : [],
    productType: params.get('productType') ? params.get('productType').split(',') : [],
    shop: params.get('shop') ? params.get('shop').split(',') : [],
    sortKey: params.get('sortKey') || 'cheapest',
    sortOrder: params.get('sortOrder') || 'asc',
  };
};

const updateUrlParams = (filters, sortConfig) => {
  const params = new URLSearchParams();

  if (filters.language.length > 0) params.set('language', filters.language.join(','));
  if (filters.set.length > 0) params.set('set', filters.set.join(','));
  if (filters.productType.length > 0) params.set('productType', filters.productType.join(','));
  if (filters.shop.length > 0) params.set('shop', filters.shop.join(','));
  params.set('sortKey', sortConfig.key);
  params.set('sortOrder', sortConfig.order);

  const newUrl = `${window.location.pathname}?${params.toString()}`;
  window.history.replaceState(null, '', newUrl); // Update the URL
};

const darkThemeStyles = `
  body {
    background-color: #121212;
    color: #e0e0e0;
  }
  .product-card {
    background-color: #1e1e1e;
    border: 1px solid #333;
  }
  .product-card h2 {
    color: #ffffff;
  }
  .product-details span {
    color: #b0b0b0;
  }
  .offer {
    background-color: #2c2c2c;
    border: 1px solid #444;
  }
  .offer .shop-info strong {
    color: #ffffff;
  }
  .offer .product-price {
    color: #ffffff;
  }
  .offer .price-per-pack {
    color: #b0b0b0;
  }
  .offer .language-and-title a {
    color: #90caf9;
    text-decoration: none;
  }
  .offer .language-and-title a:hover {
    color: #90caf9;
    text-decoration: underline;
  }
  .product-details a {
    color: #90caf9;
    text-decoration: none;
    border: 1px solid #90caf9;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
    margin-top: 10px;
  }
  .product-details a:hover {
    color: #ffffff;
    border-color: #ffffff;
  }
  .flag-icon {
    filter: brightness(0.8);
  }
  .selected-set {
    background-color: #333;
    color: #ffffff;
  }
  .toggle-button {
    background-color: #333;
    color: #ffffff;
  }
  .toggle-button:hover {
    background-color: #444;
  }
  .matches {
    border-top: 1px solid #444;
  }
  .main-image {
    border: 1px solid #444;
    background-color: #ffffff; /* Add white background to images */
    padding: 5px; /* Add padding to separate image from border */
    filter: brightness(0.9); /* Reduce contrast for better integration */
    border-radius: 10px; /* Add rounded corners */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); /* Add shadow for a cool effect */
  }
  #filters {
    background-color: #1e1e1e;
    border: 1px solid #333;
  }
  #filters .MuiFormControl-root {
    background-color: #2c2c2c;
  }
  #filters .MuiInputLabel-root {
    color: #ffffff; /* Make labels more readable */
  }
  #filters .MuiSelect-root {
    color: #ffffff;
  }
  #filters .MuiSelect-icon {
    color: #ffffff;
  }
  #filters .MuiMenuItem-root {
    background-color: #2c2c2c;
    color: #ffffff;
  }
  #filters .MuiMenuItem-root:hover {
    background-color: #333;
  }
  #filters .MuiChip-root {
    background-color: #333;
    color: #ffffff;
  }
  #filters .MuiChip-deleteIcon {
    color: #ffffff;
  }
  #last-updated {
    color: #b0b0b0;
  }
  #result-count, #offer-count {
    color: #b0b0b0;
  }
`;

const App = () => {
  const [shops, setShops] = useState({});
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [sets, setSets] = useState([]);
  const [series, setSeries] = useState([]);
  const [productTypes, setProductTypes] = useState([]);
  const [filters, setFilters] = useState({
    language: ['en'], // Default selected languages
    set: [],
    productType: [],
    shop: [],
  });

  const [sortConfig, setSortConfig] = useState({
    key: 'cheapest',
    order: 'asc',
  });

  const [productCount, setProductCount] = useState(0);
  const [offerCount, setOfferCount] = useState(0);
  const [lastUpdated, setLastUpdated] = useState(null);

  useEffect(() => {
    fetchInitialData(); // Fetch all data
    const queryParams = parseQueryParams(); // Extract filters from URL
    // if the query params have no language, default to en, de, fr
    if (queryParams.language.length === 0) {
      queryParams.language = ['en'];
    }
    setFilters(queryParams); // Update state with filters
    setSortConfig({ key: queryParams.sortKey, order: queryParams.sortOrder }); // Update state with sort config
  }, []); // Only on mount

  useEffect(() => {
    if (products.length > 0) {
      applyFilters(); // Apply filters after products are loaded
      updateUrlParams(filters, sortConfig); // Sync the updated filters and sort config with the URL
    }
  }, [filters, sortConfig, products]); // Run when filters, sortConfig, or products change

  useEffect(() => {
    if (Object.keys(shops).length > 0) {
      const dates = Object.values(shops)
        .map(shop => shop.last_scraped_at)
        .filter(date => date !== null)
        .sort();
      if (dates.length > 0) {
        setLastUpdated(dates[0]);
      }
    }
  }, [shops]);

  useEffect(() => {
    const styleElement = document.createElement('style');
    styleElement.textContent = darkThemeStyles;
    document.head.appendChild(styleElement);
  }, []);

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
      [key]: key === 'set' || key === 'productType' || key === 'shop' ? value : value, // Ensure 'set', 'productType', and 'shop' filters are always arrays
    }));
  };

  // Updated filtering logic in applyFilters
  // Apply filters and sort
const applyFilters = () => {
  let result = products;
  let totalOffers = 0;

  if (filters.language.length > 0) {
    result = result
      .map((product) => {
        const filteredMatches = filterUniqueOffers(
          product.matches.filter((match) => filters.language.includes(match.language))
        );
        return filteredMatches.length > 0 ? { ...product, matches: filteredMatches } : null;
      })
      .filter((product) => product !== null);
  }

  if (filters.set.length > 0) {
    result = result.filter((product) => filters.set.includes(product.set_identifier));
  }

  if (filters.productType.length > 0) {
    result = result.filter((product) => filters.productType.includes(product.product_type));
  }

  if (filters.shop.length > 0) {
    result = result
      .map((product) => {
        const filteredMatches = product.matches.filter((match) => filters.shop.includes(match.shop_id));
        return filteredMatches.length > 0 ? { ...product, matches: filteredMatches } : null;
      })
      .filter((product) => product !== null);
  }

  totalOffers = result.reduce((count, product) => count + product.matches.length, 0);

  result = sortProducts(result, sortConfig.key, sortConfig.order);

  setFilteredProducts(result);
  setProductCount(result.length);
  setOfferCount(totalOffers);
};

  const sortProducts = (products, key, order) => {
    if (order === 'none') return products;
    return [...products].sort((a, b) => {
      let valueA, valueB;
      if (key === 'newest' || key === 'oldest') {
        valueA = new Date(a.release_date);
        valueB = new Date(b.release_date);
        return key === 'newest' ? valueB - valueA : valueA - valueB;
      } else if (key === 'cheapest' || key === 'most-expensive') {
        const cheapestA = a.matches.reduce((min, match) => match.price < min ? match.price : min, Infinity);
        const cheapestB = b.matches.reduce((min, match) => match.price < min ? match.price : min, Infinity);
        valueA = cheapestA / (a.pack_count || 1);
        valueB = cheapestB / (b.pack_count || 1);
        return key === 'cheapest' ? valueA - valueB : valueB - valueA;
      }
    });
  };

  const handleSortChange = (key) => {
    setSortConfig((prevConfig) => ({
      key,
      order: prevConfig.key === key
        ? prevConfig.order === 'asc'
          ? 'desc'
          : 'asc'
        : 'asc',
    }));
  };


  const renderProductTypeFilterOptions = () => {
    let filteredProductTypes = productTypes.filter(type => {
      const hasOffers = products.some(product => product.product_type === type.product_type);
      return hasOffers;
    });
  
    if (filters.set.length > 0) {
      filteredProductTypes = filteredProductTypes.filter(type => {
        const hasOffers = products.some(product =>
          product.product_type === type.product_type && filters.set.includes(product.set_identifier)
        );
        return hasOffers;
      });
    }
  
    if (filters.shop.length > 0) {
      filteredProductTypes = filteredProductTypes.filter(type => {
        const hasOffers = products.some(product =>
          product.product_type === type.product_type && product.matches.some(match => filters.shop.includes(match.shop_id))
        );
        return hasOffers;
      });
    }
  
    return filteredProductTypes.map(type => {
      const offerCount = products
        .filter(product =>
          product.product_type === type.product_type &&
          (filters.set.length === 0 || filters.set.includes(product.set_identifier)) &&
          (filters.shop.length === 0 || product.matches.some(match => filters.shop.includes(match.shop_id)))
        )
        .reduce((sum, product) =>
          sum + (product.matches || []).filter(match =>
            filters.language.length === 0 || filters.language.includes(match.language) &&
            (filters.shop.length === 0 || filters.shop.includes(match.shop_id))
          ).length,
          0
        );
  
      return offerCount > 0 ? React.createElement(
        MenuItem,
        { key: type.product_type, value: type.product_type },
        `${type.en_name} (${offerCount})`
      ) : null;
    });
  };

  const renderSetFilterOptions = () => {
    const languageIsJapanese = filters.language.includes('ja');
    var filteredSets = sets.filter((set) =>
      languageIsJapanese ? set.title_ja : !set.title_ja
    ).filter(set => !set.title_en.includes('Black Star Promos')); // Ignore sets with 'Black Star Promos' in their name
  
    // for every set, check if there are offers for it
    // if there are no offers, remove it from the list
    filteredSets = filteredSets.filter(set => {
      const hasOffers = products.some(product => product.set_identifier === set.set_identifier);
      return hasOffers;
    });
  
    // if we are filtering by product types, also check if the set has offers for that product type
    if (filters.productType.length > 0) {
      filteredSets = filteredSets.filter(set => {
        const hasOffers = products.some(product => product.set_identifier === set.set_identifier && filters.productType.includes(product.product_type));
        return hasOffers;
      });
    }
  
    // if we are filtering by shops, also check if the set has offers for those shops
    if (filters.shop.length > 0) {
      filteredSets = filteredSets.filter(set => {
        const hasOffers = products.some(product => product.set_identifier === set.set_identifier && product.matches.some(match => filters.shop.includes(match.shop_id)));
        return hasOffers;
      });
    }
  
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
      ...sets.map((set) => {
        const releaseDate = set.release_date && set.set_identifier !== 'none' && set.set_identifier !== 'other' 
          ? new Date(set.release_date).toLocaleDateString('de-DE') 
          : '';
        const isSelected = filters.set.includes(set.set_identifier);
  
        // Calculate the total number of matches across all products for this set
        const offerCount = products
        .filter(product => 
          product.set_identifier === set.set_identifier && 
          (filters.productType.length === 0 || filters.productType.includes(product.product_type)) &&
          (filters.shop.length === 0 || product.matches.some(match => filters.shop.includes(match.shop_id)))
        )
        .reduce((sum, product) => 
          sum + (product.matches || []).filter(match => 
            filters.language.length === 0 || filters.language.includes(match.language)
          ).length, 
          0
        );

        // if the count is 0, don't show the set
        if (offerCount === 0) return null;

  
        return React.createElement(
          MenuItem,
          { 
            value: set.set_identifier, 
            key: set.set_identifier,
            className: isSelected ? 'selected-set' : '' // Apply the selected-set class
          },
          React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', width: '100%' } },
            // Add offer count after the set name
            React.createElement('span', null, `${set.title_en || set.title_ja} (${offerCount})`),
            releaseDate && React.createElement('span', null, releaseDate)
          )
        );
      }),
    ]);
  };

  const renderShopFilterOptions = () => {
    // Filter shops to include only those with offers
    const filteredShops = Object.values(shops).filter(shop => {
      const hasOffers = products.some(product => product.matches.some(match => match.shop_id === shop.id));
      return hasOffers;
    });

    // Calculate offer counts for each shop
    return filteredShops.map(shop => {
      const offerCount = products
        .reduce((sum, product) => 
          sum + (product.matches || []).filter(match => 
            match.shop_id === shop.id &&
            (filters.language.length === 0 || filters.language.includes(match.language)) &&
            (filters.set.length === 0 || filters.set.includes(product.set_identifier)) &&
            (filters.productType.length === 0 || filters.productType.includes(product.product_type))
          ).length, 
          0
        );

      return offerCount > 0 ? React.createElement(
        MenuItem,
        { key: shop.id, value: shop.id },
        `${shop.name} (${offerCount})`
      ) : null;
    });
  };

  const renderOffer = (product, match, isCheapest) => {
    const shop = shops[match.shop_id] || {};
    const packCount = product.pack_count || 1;
    const pricePerPack = (match.price / packCount).toFixed(2);
    const countryCode = languageToCountryCode[match.language] || 'unknown';
  
    // Ensure valid URL
    const productUrl = match.external_product.url.startsWith("http")
      ? match.external_product.url
      : `https://fallbackurl.com${match.external_product.url}`;
  
    return React.createElement(
      'div',
      { className: `offer ${isCheapest ? 'cheapest-offer' : ''}`, key: match.id },
      React.createElement(
        'div',
        { className: 'shop-info' },
        React.createElement('img', {
          src: `assets/images/shop-logos/${shop.image || 'default-logo.png'}`,
          alt: `${shop.name || 'Shop'} Logo`,
          className: 'shop-logo',
        }),
        React.createElement('strong', null, shop.name || 'Unknown Shop'),
        React.createElement(
          'div',
          { className: 'product-price' },
          `CHF ${match.price.toFixed(2)}`,
          React.createElement('span', { className: 'price-per-pack' }, ` (~${pricePerPack} per pack)`)
        )
      ),
      React.createElement(
        'div',
        { className: 'language-and-title' },
        React.createElement('span', {
          className: `flag-icon flag-icon-${countryCode}`,
          title: match.language, // Tooltip
        }),
        React.createElement(
          'a',
          { href: productUrl, target: '_blank', className: 'match-link' },
          match.title
        )
      )
    );
  };

  // Ensure unique matches
const filterUniqueOffers = (matches) => {
  const seen = new Set();
  return matches.filter((match) => {
    const identifier = `${match.shop_id}-${match.external_id}`;
    if (seen.has(identifier)) return false;
    seen.add(identifier);
    return true;
  });
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
                  sibling.style.display === 'none'
                    ? `Show More Offers (${otherMatches.length})`
                    : 'Show Fewer Offers';
              },
            },
            `Show More Offers (${otherMatches.length})`
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
      // React.createElement('span', null, `Pokemon Center Price: ${product.price || 'Price not available'}`),
      React.createElement('span', null, `Packs in product: ${product.pack_count}`),
      /* React.createElement(
        'a',
        { href: product.product_url, target: '_blank' },
        'View on Pokemon Center'
      ) */
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

// Define the SvgIcon for the delete icon using React.createElement
const DeleteIcon = (props) =>
  React.createElement(SvgIcon, {
    ...props,
    style: { fontSize: '18px', color: '#ccc', ...props.style }
  },
    React.createElement('circle', { cx: '12', cy: '12', r: '10', stroke: '#ccc', strokeWidth: '2', fill: 'none' }),
    React.createElement('line', { x1: '8', y1: '8', x2: '16', y2: '16', stroke: '#ccc', strokeWidth: '2' }),
    React.createElement('line', { x1: '8', y1: '16', x2: '16', y2: '8', stroke: '#ccc', strokeWidth: '2' })
  );

  const filtersComponent = React.createElement(
    'div',
    { id: 'filters' },
    React.createElement(
      FormControl,
      { sx: { minWidth: 300, marginBottom: 2 } }, // Ensure a minimum width and margin bottom
      React.createElement(InputLabel, { id: 'language-filter-label' }, 'Languages'),
      React.createElement(
        Select,
        {
          labelId: 'language-filter-label',
          multiple: true,
          value: filters.language,
          label: 'Languages',
          onChange: (e) => handleFilterChange('language', e.target.value),
          renderValue: (selected) =>
            React.createElement('div', { style: { display: 'flex', flexWrap: 'wrap', gap: '5px' } },
              selected.map(lang => (
                React.createElement(Chip, {
                  key: lang,
                  label: (
                    React.createElement('span', { style: { display: 'flex', alignItems: 'center', gap: '5px' } },
                      React.createElement('span', { className: `flag-icon flag-icon-${languageToCountryCode[lang]}` }),
                      languageToDisplayName[lang]
                    )
                  ),
                  onDelete: (e) => {
                    e.stopPropagation();
                    handleFilterChange('language', selected.filter(item => item !== lang));
                  },
                  deleteIcon: React.createElement(DeleteIcon, {
                    onMouseDown: (event) => event.stopPropagation(),
                    style: { cursor: 'pointer', marginLeft: '5px' }
                  }),
                  style: { margin: '2px' },
                  clickable: true,
                  onClick: (e) => e.stopPropagation()
                })
              ))
            ),
        },
        Object.keys(languageToDisplayName).map(lang => (
          React.createElement(MenuItem, { key: lang, value: lang, className: filters.language.includes(lang) ? 'selected-set' : '' },
            React.createElement('span', { style: { display: 'flex', alignItems: 'center', gap: '5px' } },
              React.createElement('span', { className: `flag-icon flag-icon-${languageToCountryCode[lang]}` }),
              languageToDisplayName[lang]
            )
          )
        ))
      )
    ),
    React.createElement(
      FormControl,
      { sx: { minWidth: 300, marginBottom: 2 } }, // Ensure a minimum width and margin bottom
      React.createElement(InputLabel, { id: 'set-filter-label' }, 'Sets'),
      React.createElement(
        Select,
        {
          id: 'set-filter',
          labelId: 'set-filter-label',
          multiple: true, // Enable multi-select
          value: filters.set, // Array of selected values
          onChange: (e) => handleFilterChange('set', e.target.value),
          renderValue: (selected) =>
            React.createElement('div', { style: { display: 'flex', flexWrap: 'wrap', gap: '5px' } },
              selected.map(setId => {
                const set = sets.find(s => s.set_identifier === setId);
                return set ? (
                  React.createElement(Chip, {
                    key: setId,
                    label: set.title_en || set.title_ja,
                    onDelete: (e) => {
                      e.stopPropagation();
                      handleFilterChange('set', selected.filter(item => item !== setId));
                    },
                    deleteIcon: React.createElement(DeleteIcon, {
                      onMouseDown: (event) => event.stopPropagation(),
                      style: { cursor: 'pointer', marginLeft: '5px' }
                    }),
                    style: { margin: '2px' },
                    clickable: true,
                    onClick: (e) => e.stopPropagation()
                  })
                ) : null;
              })
            ),
        },
        renderSetFilterOptions()
      )
    ),
    React.createElement(
      FormControl,
      { sx: { minWidth: 300, marginBottom: 2 } },
      React.createElement(InputLabel, { id: 'product-type-filter-label' }, 'Product Types'),
      React.createElement(
        Select,
        {
          labelId: 'product-type-filter-label',
          multiple: true,
          value: filters.productType,
          onChange: (e) => handleFilterChange('productType', e.target.value),
          renderValue: (selected) =>
            React.createElement('div', { style: { display: 'flex', flexWrap: 'wrap', gap: '5px' } },
              selected.map(type => {
                const productType = productTypes.find(pt => pt.product_type === type);
                return productType ? (
                  React.createElement(Chip, {
                    key: type,
                    label: productType.en_name,
                    onDelete: (e) => {
                      e.stopPropagation();
                      handleFilterChange('productType', selected.filter(item => item !== type));
                    },
                    deleteIcon: React.createElement(DeleteIcon, {
                      onMouseDown: (event) => event.stopPropagation(),
                      style: { cursor: 'pointer', marginLeft: '5px' }
                    }),
                    style: { margin: '2px' },
                    clickable: true,
                    onClick: (e) => e.stopPropagation()
                  })
                ) : null;
              })
            ),
        },
        renderProductTypeFilterOptions()
      )
    ),
    React.createElement(
      FormControl,
      { sx: { minWidth: 300, marginBottom: 2 } },
      React.createElement(InputLabel, { id: 'shop-filter-label' }, 'Shops'),
      React.createElement(
        Select,
        {
          labelId: 'shop-filter-label',
          multiple: true,
          value: filters.shop,
          onChange: (e) => handleFilterChange('shop', e.target.value),
          renderValue: (selected) =>
            React.createElement('div', { style: { display: 'flex', flexWrap: 'wrap', gap: '5px' } },
              selected.map(shopId => {
                const shop = shops[shopId];
                return shop ? (
                  React.createElement(Chip, {
                    key: shopId,
                    label: shop.name,
                    onDelete: (e) => {
                      e.stopPropagation();
                      handleFilterChange('shop', selected.filter(item => item !== shopId));
                    },
                    deleteIcon: React.createElement(DeleteIcon, {
                      onMouseDown: (event) => event.stopPropagation(),
                      style: { cursor: 'pointer', marginLeft: '5px' }
                    }),
                    style: { margin: '2px' },
                    clickable: true,
                    onClick: (e) => e.stopPropagation()
                  })
                ) : null;
              })
            ),
        },
        renderShopFilterOptions()
      )
    ),
    React.createElement(
      FormControl,
      { sx: { minWidth: 300, marginBottom: 2 } }, // Ensure a minimum width and margin bottom
      React.createElement(InputLabel, { id: 'sort-filter-label' }, 'Sort By'),
      React.createElement(
        Select,
        {
          labelId: 'sort-filter-label',
          value: sortConfig.key,
          onChange: (e) => handleSortChange(e.target.value),
        },
        React.createElement(MenuItem, { value: 'cheapest' }, 'Cheapest (per Pack)'),
        React.createElement(MenuItem, { value: 'newest' }, 'Newest'),
        React.createElement(MenuItem, { value: 'oldest' }, 'Oldest'),
        React.createElement(MenuItem, { value: 'most-expensive' }, 'Most expensive (per Pack)')
      )
    )
  );

  return React.createElement(
    'div',
    null,
    lastUpdated && React.createElement('div', { id: 'last-updated' }, `Last updated at: ${new Date(lastUpdated).toLocaleString('de-DE')}`),
    filtersComponent,
    React.createElement('div', { id: 'result-count' }, `Matched Products: ${productCount}`),
    React.createElement('div', { id: 'offer-count' }, `Offers found for these products: ${offerCount}`),
    React.createElement('div', { id: 'product-list' }, filteredProducts.map(renderProductCard))
  );
};

ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(App));
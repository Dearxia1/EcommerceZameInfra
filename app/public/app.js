// Global Client State Management
const state = {
  token: localStorage.getItem('zame_token') || null,
  username: localStorage.getItem('zame_username') || null,
  cart: [],
  products: [],
  cartLoaded: false
};

// REST API Endpoints
const API = {
  login: '/api/auth/login',
  register: '/api/auth/register',
  products: '/api/products',
  cart: '/api/cart',
  checkout: '/api/checkout'
};

// Get request headers
function getHeaders() {
  const headers = { 'Content-Type': 'application/json' };
  if (state.token) {
    headers['Authorization'] = `Bearer ${state.token}`;
  }
  return headers;
}

// Check authentications
function updateAuthUI() {
  const userIndicator = document.getElementById('user-indicator');
  const loginBtn = document.getElementById('login-nav-btn');
  const logoutBtn = document.getElementById('logout-nav-btn');
  const usernameDisplay = document.getElementById('username-display');

  if (state.token && state.username) {
    if (userIndicator) userIndicator.classList.remove('hidden');
    if (usernameDisplay) usernameDisplay.textContent = state.username;
    if (loginBtn) loginBtn.classList.add('hidden');
    if (logoutBtn) logoutBtn.classList.remove('hidden');
  } else {
    if (userIndicator) userIndicator.classList.add('hidden');
    if (loginBtn) loginBtn.classList.remove('hidden');
    if (logoutBtn) logoutBtn.classList.add('hidden');
  }
}

// Log out user
function logout() {
  localStorage.removeItem('zame_token');
  localStorage.removeItem('zame_username');
  state.token = null;
  state.username = null;
  state.cart = [];
  updateAuthUI();
  updateCartBadge();
  if (window.location.pathname.includes('cart.html') || window.location.pathname.includes('checkout.html')) {
    window.location.href = 'index.html';
  }
}

// Fetch Catalog Products
async function fetchProducts() {
  try {
    const res = await fetch(API.products);
    if (!res.ok) throw new Error('Error al cargar catálogo');
    state.products = await res.json();
    renderProducts(state.products);
  } catch (err) {
    console.error(err);
  }
}

// Render Products Grid
function renderProducts(products) {
  const container = document.getElementById('products-grid');
  if (!container) return;
  
  if (products.length === 0) {
    container.innerHTML = `<div class="col-span-full text-center text-zinc-500 py-12">No hay fragancias disponibles en esta familia olfativa.</div>`;
    return;
  }

  container.innerHTML = products.map((prod, idx) => {
    const delayClass = `delay-${(idx % 4) * 100}`;
    return `
      <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-lg overflow-hidden transition-all duration-500 group hover-luxury scroll-reveal animate-revealUp ${delayClass}">
        <div class="relative aspect-[1/1.1] overflow-hidden bg-zinc-50 dark:bg-zinc-950 p-6 flex items-center justify-center">
          <span class="absolute top-4 left-4 z-20 bg-amber-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest shadow-md">Colección</span>
          <img src="${prod.image || '/assets/images/hero-bg.png'}" alt="${prod.name}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&q=80&w=600'"/>
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10 flex items-center justify-center">
            <button onclick="addToCart(${prod.id})" class="px-6 py-3 bg-white text-black text-xs font-bold uppercase tracking-widest rounded-full shadow-2xl hover:bg-amber-600 hover:text-white transition-all transform translate-y-4 group-hover:translate-y-0 duration-500">
              Agregar al Carrito
            </button>
          </div>
        </div>
        <div class="p-6">
          <span class="text-[10px] uppercase tracking-widest text-amber-600 dark:text-amber-500 font-bold block mb-2">${prod.category}</span>
          <h3 class="font-display text-lg font-bold text-zinc-900 dark:text-white mb-2 line-clamp-1 group-hover:text-amber-600 transition-colors">${prod.name}</h3>
          <p class="text-zinc-500 dark:text-zinc-400 text-xs line-clamp-2 mb-4 leading-relaxed">${prod.description}</p>
          <div class="flex items-center justify-between">
            <span class="text-zinc-900 dark:text-white font-semibold text-base font-mono">$${parseFloat(prod.price).toLocaleString('es-CO')} COP</span>
            <button onclick="addToCart(${prod.id})" class="md:hidden p-2 bg-amber-600 text-white rounded-full"><span class="material-icons-outlined text-sm">shopping_bag</span></button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Fetch user's cart from REST API
async function fetchCart() {
  if (!state.token) return;
  try {
    const res = await fetch(API.cart, { headers: getHeaders() });
    if (res.status === 401 || res.status === 403) {
      logout();
      return;
    }
    if (!res.ok) throw new Error('Error al obtener carrito');
    state.cart = await res.json();
    state.cartLoaded = true;
    updateCartBadge();
    renderMiniCart();
    if (window.location.pathname.includes('cart.html')) {
      renderCartPage();
    }
    if (window.location.pathname.includes('checkout.html')) {
      renderCheckoutSummary();
    }
  } catch (err) {
    console.error(err);
  }
}

// Add Item to Cart
async function addToCart(productId, quantity = 1) {
  if (!state.token) {
    alert('Por favor inicia sesión para agregar fragancias a tu carrito.');
    window.location.href = 'login.html';
    return;
  }
  try {
    const res = await fetch(API.cart, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({ productId, quantity })
    });
    if (!res.ok) throw new Error('Error al agregar producto');
    
    await fetchCart();
    openCartDrawer();
  } catch (err) {
    alert(err.message);
  }
}

// Update Cart Item quantity
async function updateCartItem(id, quantity) {
  try {
    const res = await fetch(`/api/cart/${id}`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify({ quantity })
    });
    if (!res.ok) throw new Error('Error al actualizar item');
    await fetchCart();
    if (window.location.pathname.includes('cart.html')) {
      renderCartPage();
    }
  } catch (err) {
    alert(err.message);
  }
}

// Remove Cart Item
async function removeCartItem(id) {
  try {
    const res = await fetch(`/api/cart/${id}`, {
      method: 'DELETE',
      headers: getHeaders()
    });
    if (!res.ok) throw new Error('Error al eliminar item');
    await fetchCart();
    if (window.location.pathname.includes('cart.html')) {
      renderCartPage();
    }
  } catch (err) {
    alert(err.message);
  }
}

// Update Header Cart Badge
function updateCartBadge() {
  const countBadges = document.querySelectorAll('.cart-count');
  const count = state.cart.reduce((sum, item) => sum + item.quantity, 0);
  
  countBadges.forEach(badge => {
    badge.textContent = count;
    if (count > 0) {
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  });
}

// Render Side Drawer Mini-Cart
function renderMiniCart() {
  const container = document.getElementById('mini-cart-items');
  const totalDisplay = document.getElementById('mini-cart-total');
  const footerActions = document.getElementById('mini-cart-actions');
  if (!container) return;

  if (state.cart.length === 0) {
    container.innerHTML = `
      <div class="flex flex-col items-center justify-center h-64 text-center">
        <span class="material-icons-outlined text-4xl text-zinc-500 mb-4">shopping_bag</span>
        <p class="text-zinc-500 text-sm uppercase tracking-widest">Tu carrito está vacío</p>
        <button onclick="closeCartDrawer()" class="mt-6 text-xs font-bold uppercase tracking-widest text-amber-600 hover:text-amber-500 border-b border-amber-600 pb-1">Seguir Comprando</button>
      </div>
    `;
    if (totalDisplay) totalDisplay.textContent = '$0 COP';
    if (footerActions) footerActions.classList.add('hidden');
    return;
  }

  let total = 0;
  container.innerHTML = state.cart.map(item => {
    const subtotal = item.Product.price * item.quantity;
    total += subtotal;
    return `
      <div class="flex gap-4 py-4 border-b border-zinc-100 dark:border-zinc-800">
        <div class="w-16 h-20 bg-zinc-50 dark:bg-zinc-950 rounded overflow-hidden flex-shrink-0 flex items-center justify-center p-2">
          <img src="${item.Product.image || '/assets/images/hero-bg.png'}" alt="${item.Product.name}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&q=80&w=100'"/>
        </div>
        <div class="flex-1 flex flex-col justify-between">
          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider text-zinc-900 dark:text-white line-clamp-1">${item.Product.name}</h4>
            <span class="text-[10px] text-zinc-400 uppercase tracking-widest block mt-0.5">${item.Product.category}</span>
          </div>
          <div class="flex items-center justify-between mt-2">
            <div class="flex items-center border border-zinc-200 dark:border-zinc-800 rounded">
              <button onclick="updateCartItem(${item.id}, ${item.quantity - 1})" class="px-2 py-0.5 text-zinc-500 hover:text-black dark:hover:text-white">-</button>
              <span class="px-2 py-0.5 text-xs font-mono font-bold">${item.quantity}</span>
              <button onclick="updateCartItem(${item.id}, ${item.quantity + 1})" class="px-2 py-0.5 text-zinc-500 hover:text-black dark:hover:text-white">+</button>
            </div>
            <button onclick="removeCartItem(${item.id})" class="text-[10px] uppercase tracking-widest text-zinc-400 hover:text-red-500 font-bold">Eliminar</button>
          </div>
        </div>
        <div class="text-right flex flex-col justify-between">
          <span class="text-xs font-bold text-zinc-900 dark:text-white font-mono">$${parseFloat(subtotal).toLocaleString('es-CO')}</span>
        </div>
      </div>
    `;
  }).join('');

  if (totalDisplay) totalDisplay.textContent = `$${total.toLocaleString('es-CO')} COP`;
  if (footerActions) footerActions.classList.remove('hidden');
}

// Side Cart Drawer Display controls
function openCartDrawer() {
  const drawer = document.getElementById('side-cart-drawer');
  const panel = document.getElementById('cart-panel');
  const overlay = document.getElementById('cart-overlay');
  if (!drawer) return;

  drawer.style.display = 'block';
  setTimeout(() => {
    overlay.classList.remove('opacity-0');
    overlay.classList.add('opacity-100');
    panel.classList.remove('translate-x-full');
  }, 10);
  document.body.style.overflow = 'hidden';
}

function closeCartDrawer() {
  const drawer = document.getElementById('side-cart-drawer');
  const panel = document.getElementById('cart-panel');
  const overlay = document.getElementById('cart-overlay');
  if (!drawer) return;

  panel.classList.add('translate-x-full');
  overlay.classList.remove('opacity-100');
  overlay.classList.add('opacity-0');
  
  setTimeout(() => {
    drawer.style.display = 'none';
  }, 300);
  document.body.style.overflow = '';
}

// Render full Cart Page (`cart.html`)
function renderCartPage() {
  const itemsContainer = document.getElementById('cart-page-items');
  const totalItemsDisplay = document.getElementById('cart-page-count');
  const subtotalDisplay = document.getElementById('cart-page-subtotal');
  const shippingDisplay = document.getElementById('cart-page-shipping');
  const totalDisplay = document.getElementById('cart-page-total');
  const summaryContainer = document.getElementById('cart-page-summary');
  
  if (!itemsContainer) return;

  if (!state.cartLoaded) {
    itemsContainer.innerHTML = `
      <div class="text-center py-24">
        <p class="text-zinc-500 text-xs uppercase tracking-widest animate-pulse">Cargando carrito...</p>
      </div>
    `;
    if (summaryContainer) summaryContainer.classList.add('hidden');
    return;
  }

  if (state.cart.length === 0) {
    itemsContainer.innerHTML = `
      <div class="text-center py-24 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
        <span class="material-icons-outlined text-5xl text-zinc-400 mb-4">shopping_bag</span>
        <h3 class="font-display text-xl font-bold mb-2">Tu carrito está vacío</h3>
        <p class="text-zinc-500 text-xs uppercase tracking-widest mb-6">Explora nuestra colección de alta perfumería</p>
        <a href="index.html" class="inline-block px-8 py-4 bg-black text-white dark:bg-white dark:text-black text-xs font-bold uppercase tracking-widest rounded-full hover:bg-amber-600 hover:text-white dark:hover:bg-amber-600 dark:hover:text-white transition-all">Ver Fragancias</a>
      </div>
    `;
    if (summaryContainer) summaryContainer.classList.add('hidden');
    return;
  }

  if (summaryContainer) summaryContainer.classList.remove('hidden');

  let subtotal = 0;
  let itemsCount = 0;

  itemsContainer.innerHTML = state.cart.map(item => {
    const itemSubtotal = item.Product.price * item.quantity;
    subtotal += itemSubtotal;
    itemsCount += item.quantity;

    return `
      <div class="flex flex-col sm:flex-row items-center gap-6 py-6 border-b border-zinc-100 dark:border-zinc-800">
        <div class="w-20 h-24 bg-zinc-50 dark:bg-zinc-950 rounded flex-shrink-0 flex items-center justify-center p-2 border border-zinc-100 dark:border-zinc-900">
          <img src="${item.Product.image || '/assets/images/hero-bg.png'}" alt="${item.Product.name}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&q=80&w=100'"/>
        </div>
        <div class="flex-1 text-center sm:text-left">
          <h4 class="font-display font-bold text-base text-zinc-900 dark:text-white">${item.Product.name}</h4>
          <span class="text-[10px] text-amber-600 dark:text-amber-500 uppercase tracking-widest font-bold mt-0.5 block">${item.Product.category}</span>
          <p class="text-zinc-500 text-xs mt-1 line-clamp-1 max-w-sm">${item.Product.description}</p>
        </div>
        <div class="flex items-center justify-center border border-zinc-200 dark:border-zinc-800 rounded">
          <button onclick="updateCartItem(${item.id}, ${item.quantity - 1})" class="px-3 py-1 text-zinc-500 hover:text-black dark:hover:text-white">-</button>
          <span class="px-3 py-1 font-mono font-bold text-sm">${item.quantity}</span>
          <button onclick="updateCartItem(${item.id}, ${item.quantity + 1})" class="px-3 py-1 text-zinc-500 hover:text-black dark:hover:text-white">+</button>
        </div>
        <div class="text-center sm:text-right min-w-[120px]">
          <span class="text-sm font-bold text-zinc-900 dark:text-white font-mono block">$${parseFloat(itemSubtotal).toLocaleString('es-CO')}</span>
          <button onclick="removeCartItem(${item.id})" class="text-[10px] uppercase tracking-widest text-zinc-400 hover:text-red-500 font-bold mt-2">Eliminar</button>
        </div>
      </div>
    `;
  }).join('');

  if (totalItemsDisplay) totalItemsDisplay.textContent = `${itemsCount} item(s)`;
  if (subtotalDisplay) subtotalDisplay.textContent = `$${subtotal.toLocaleString('es-CO')} COP`;
  
  // Free shipping above $200,000 COP, else $15,000
  const shipping = subtotal >= 200000 ? 0 : 15000;
  if (shippingDisplay) shippingDisplay.textContent = shipping === 0 ? 'Gratis' : `$${shipping.toLocaleString('es-CO')} COP`;
  
  const total = subtotal + shipping;
  if (totalDisplay) totalDisplay.textContent = `$${total.toLocaleString('es-CO')} COP`;
}

// Render Checkout Page Summary
function renderCheckoutSummary() {
  const container = document.getElementById('checkout-items-list');
  const subtotalDisplay = document.getElementById('checkout-subtotal');
  const shippingDisplay = document.getElementById('checkout-shipping');
  const totalDisplay = document.getElementById('checkout-total');

  if (!container) return;

  if (!state.cartLoaded) {
    container.innerHTML = `<div class="text-zinc-500 text-xs py-3 animate-pulse">Cargando resumen de pedido...</div>`;
    return;
  }

  if (state.cart.length === 0) {
    window.location.href = 'cart.html';
    return;
  }

  let subtotal = 0;
  container.innerHTML = state.cart.map(item => {
    const itemSubtotal = item.Product.price * item.quantity;
    subtotal += itemSubtotal;
    return `
      <div class="flex items-center justify-between text-xs py-3 border-b border-zinc-100 dark:border-zinc-800">
        <div>
          <span class="font-bold text-zinc-900 dark:text-white uppercase tracking-wider">${item.Product.name}</span>
          <span class="text-zinc-500 font-mono"> x${item.quantity}</span>
        </div>
        <span class="font-mono font-bold text-zinc-900 dark:text-white">$${itemSubtotal.toLocaleString('es-CO')}</span>
      </div>
    `;
  }).join('');

  if (subtotalDisplay) subtotalDisplay.textContent = `$${subtotal.toLocaleString('es-CO')} COP`;
  const shipping = subtotal >= 200000 ? 0 : 15000;
  if (shippingDisplay) shippingDisplay.textContent = shipping === 0 ? 'Gratis' : `$${shipping.toLocaleString('es-CO')} COP`;
  
  const total = subtotal + shipping;
  if (totalDisplay) totalDisplay.textContent = `$${total.toLocaleString('es-CO')} COP`;
}

// Handle Simulated Checkout Order Submit
async function handleCheckout(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('checkout-submit-btn');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'PROCESANDO PAGO...';
  }

  const billingName = document.getElementById('billing-name').value;
  const billingAddress = document.getElementById('billing-address').value;
  const billingCity = document.getElementById('billing-city').value;
  const cardNumber = document.getElementById('card-number').value;

  try {
    const res = await fetch(API.checkout, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({ billingName, billingAddress, billingCity, cardNumber })
    });

    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Error al procesar el pedido');

    // Show modal success
    document.getElementById('checkout-success-modal').classList.remove('hidden');
    document.getElementById('success-order-id').textContent = data.orderId;
    document.getElementById('success-order-total').textContent = `$${parseFloat(data.total).toLocaleString('es-CO')} COP`;
    
    // Reset Cart state
    state.cart = [];
    updateCartBadge();
  } catch (err) {
    alert(err.message);
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'PROCESAR PEDIDO SEGURO';
    }
  }
}

// Category Filter Controller
function filterByCategory(category) {
  const buttons = document.querySelectorAll('.category-filter-btn');
  buttons.forEach(btn => {
    if (btn.getAttribute('data-category') === category) {
      btn.classList.add('bg-black', 'text-white', 'dark:bg-white', 'dark:text-black');
      btn.classList.remove('bg-zinc-100', 'text-zinc-800', 'dark:bg-zinc-800', 'dark:text-zinc-200');
    } else {
      btn.classList.remove('bg-black', 'text-white', 'dark:bg-white', 'dark:text-black');
      btn.classList.add('bg-zinc-100', 'text-zinc-800', 'dark:bg-zinc-800', 'dark:text-zinc-200');
    }
  });

  if (category === 'Todos') {
    renderProducts(state.products);
  } else {
    const filtered = state.products.filter(p => p.category.toLowerCase().includes(category.toLowerCase()));
    renderProducts(filtered);
  }
}

// Initialise core event bindings
document.addEventListener('DOMContentLoaded', () => {
  updateAuthUI();
  
  if (state.token) {
    fetchCart();
  } else {
    if (window.location.pathname.includes('checkout.html') || window.location.pathname.includes('cart.html')) {
      window.location.href = 'login.html';
      return;
    }
  }

  // Load context based pages
  if (document.getElementById('products-grid')) {
    fetchProducts();
  }

  if (window.location.pathname.includes('cart.html')) {
    renderCartPage();
  }

  if (window.location.pathname.includes('checkout.html')) {
    renderCheckoutSummary();
    const form = document.getElementById('checkout-billing-form');
    if (form) form.addEventListener('submit', handleCheckout);
  }

  // Mobile Menu Logic
  const mobileTrigger = document.getElementById('mobile-menu-trigger');
  const mobileClose = document.getElementById('mobile-menu-close');
  const mobileDrawer = document.getElementById('mobile-menu-drawer');
  const mobilePanel = document.getElementById('mobile-menu-panel');
  const mobileOverlay = document.getElementById('mobile-menu-overlay');

  if (mobileTrigger) {
    mobileTrigger.addEventListener('click', () => {
      mobileDrawer.style.display = 'block';
      setTimeout(() => {
        mobileOverlay.classList.remove('opacity-0');
        mobileOverlay.classList.add('opacity-100');
        mobilePanel.classList.remove('translate-x-full');
      }, 10);
    });
  }

  const closeMenu = () => {
    if (!mobileDrawer) return;
    mobilePanel.classList.add('translate-x-full');
    mobileOverlay.classList.remove('opacity-100');
    mobileOverlay.classList.add('opacity-0');
    setTimeout(() => {
      mobileDrawer.style.display = 'none';
    }, 300);
  };

  if (mobileClose) mobileClose.addEventListener('click', closeMenu);
  if (mobileOverlay) mobileOverlay.addEventListener('click', closeMenu);
});

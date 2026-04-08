// Configuração da API
const API_BASE = '/api';

// Gerenciar usuário logado
class User {
    constructor() {
        this.data = this.loadFromStorage();
    }

    loadFromStorage() {
        const stored = localStorage.getItem('user');
        return stored ? JSON.parse(stored) : null;
    }

    saveToStorage(userData) {
        this.data = userData;
        localStorage.setItem('user', JSON.stringify(userData));
    }

    logout() {
        this.data = null;
        localStorage.removeItem('user');
    }

    isLoggedIn() {
        return this.data !== null;
    }

    getName() {
        return this.data ? this.data.name : 'Visitante';
    }
}

const user = new User();

// Funções auxiliares
function formatPrice(cents) {
    return `R$ ${(cents / 100).toFixed(2).replace('.', ',')}`;
}

function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'error';
    alertDiv.textContent = message;
    document.body.insertBefore(alertDiv, document.body.firstChild);
    setTimeout(() => alertDiv.remove(), 5000);
}

function showSuccess(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'success';
    alertDiv.textContent = message;
    document.body.insertBefore(alertDiv, document.body.firstChild);
    setTimeout(() => alertDiv.remove(), 5000);
}

// Carrinho (usando localStorage)
class Cart {
    constructor() {
        this.items = this.loadFromStorage();
    }

    loadFromStorage() {
        const stored = localStorage.getItem('cart');
        return stored ? JSON.parse(stored) : [];
    }

    saveToStorage() {
        localStorage.setItem('cart', JSON.stringify(this.items));
    }

    addItem(product, quantity = 1) {
        const existingItem = this.items.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity: quantity
            });
        }
        
        this.saveToStorage();
        this.updateCartCount();
        showSuccess('Produto adicionado ao carrinho!');
    }

    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.saveToStorage();
        this.updateCartCount();
    }

    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else {
                item.quantity = quantity;
                this.saveToStorage();
                this.updateCartCount();
            }
        }
    }

    getTotal() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }

    clear() {
        this.items = [];
        this.saveToStorage();
        this.updateCartCount();
    }

    updateCartCount() {
        const count = this.items.reduce((sum, item) => sum + item.quantity, 0);
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.style.display = count > 0 ? 'flex' : 'none';
        }
    }
}

const cart = new Cart();

// Atualizar header com informações do usuário
function updateUserHeader() {
    const headerActions = document.querySelector('.header-actions');
    if (!headerActions) return;

    if (user.isLoggedIn()) {
        // Usuário logado
        const userMenu = document.createElement('div');
        userMenu.style.cssText = 'display: flex; align-items: center; gap: 1rem;';
        userMenu.innerHTML = `
            <span style="color: var(--primary-color); font-weight: 500;">👤 ${user.getName()}</span>
            <a href="/public/pedidos.html" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">Pedidos</a>
            <button onclick="logoutUser()" style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-weight: 500;">Sair</button>
        `;
        headerActions.innerHTML = '';
        headerActions.appendChild(userMenu);
    } else {
        // Usuário não logado
        const loginLink = document.createElement('a');
        loginLink.href = '/public/login.html';
        loginLink.style.cssText = 'color: var(--primary-color); text-decoration: none; font-weight: 500;';
        loginLink.textContent = 'Login';
        headerActions.innerHTML = '';
        headerActions.appendChild(loginLink);
    }

    // Adicionar carrinho
    const cartIcon = document.createElement('a');
    cartIcon.href = '/public/cart.html';
    cartIcon.className = 'cart-icon';
    cartIcon.innerHTML = `
        🛒
        <span class="cart-count" style="display: none;">0</span>
    `;
    headerActions.appendChild(cartIcon);
}

function logoutUser() {
    user.logout();
    localStorage.removeItem('cart');
    showSuccess('Logout realizado com sucesso!');
    setTimeout(() => {
        window.location.href = '/public/index.html';
    }, 1500);
}

// Inicializar carrinho ao carregar página
document.addEventListener('DOMContentLoaded', () => {
    cart.updateCartCount();
    updateUserHeader();
});

// Funções de API
async function fetchAPI(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        let data;
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            console.error('Não é JSON:', text);
            throw new Error('Resposta inválida do servidor');
        }

        if (!response.ok) {
            throw new Error(data.error || 'Erro na requisição');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        showError(error.message || 'Erro ao conectar com o servidor');
        throw error;
    }
}

// Produtos
async function loadProducts(filters = {}) {
    try {
        const params = new URLSearchParams();
        params.append('action', 'list');
        
        if (filters.category) {
            params.append('action', 'by_category');
            params.append('category_id', filters.category);
        }

        const data = await fetchAPI(`/products.php?${params}`);
        return data.data || [];
    } catch (error) {
        return [];
    }
}

async function loadFeaturedProducts() {
    try {
        const data = await fetchAPI(`/products.php?action=featured`);
        return data.data || [];
    } catch (error) {
        return [];
    }
}

async function loadProductDetail(slug) {
    try {
        const data = await fetchAPI(`/products.php?action=detail&slug=${slug}`);
        return data.data;
    } catch (error) {
        return null;
    }
}

// Categorias
async function loadCategories() {
    try {
        const data = await fetchAPI(`/categories.php?action=list`);
        return data.data || [];
    } catch (error) {
        return [];
    }
}

// Renderizar produtos
function renderProducts(products, container) {
    if (!container) return;

    container.innerHTML = '';

    if (products.length === 0) {
        container.innerHTML = '<p style="text-align: center; padding: 2rem;">Nenhum produto encontrado</p>';
        return;
    }

    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <img src="${product.image}" alt="${product.name}" class="product-image">
            <div class="product-info">
                <div class="product-name">${product.name}</div>
                <div class="product-description">${product.description}</div>
                <div class="product-footer">
                    <div class="product-price">${formatPrice(product.price)}</div>
                    <div class="product-stock ${product.stock === 0 ? 'out' : ''}">
                        ${product.stock > 0 ? `${product.stock} em estoque` : 'Fora de estoque'}
                    </div>
                </div>
            </div>
        `;
        
        card.addEventListener('click', () => {
            window.location.href = `/public/product.html?slug=${product.slug}`;
        });

        container.appendChild(card);
    });
}

// Renderizar carrinho
function renderCart(container) {
    if (!container) return;

    if (cart.items.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <p>Seu carrinho está vazio</p>
                <a href="/public/products.html" class="btn btn-primary">Explorar Produtos</a>
            </div>
        `;
        return;
    }

    const itemsHtml = cart.items.map(item => `
        <div class="cart-item">
            <img src="${item.image}" alt="${item.name}" class="cart-item-image">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${formatPrice(item.price)}</div>
            </div>
            <div class="cart-item-actions">
                <button onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">−</button>
                <input type="number" value="${item.quantity}" readonly style="width: 50px; text-align: center;">
                <button onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">+</button>
            </div>
            <div class="cart-item-total">${formatPrice(item.price * item.quantity)}</div>
            <button onclick="removeFromCart(${item.id})" style="background: none; border: none; color: #F44336; cursor: pointer; font-size: 1.2rem;">✕</button>
        </div>
    `).join('');

    container.innerHTML = `<div class="cart-items">${itemsHtml}</div>`;
}

function updateCartQuantity(productId, quantity) {
    cart.updateQuantity(productId, quantity);
    const container = document.querySelector('.cart-items-container');
    if (container) {
        renderCart(container);
        updateCartSummary();
    }
}

function removeFromCart(productId) {
    cart.removeItem(productId);
    const container = document.querySelector('.cart-items-container');
    if (container) {
        renderCart(container);
        updateCartSummary();
    }
}

// Atualizar resumo do carrinho
function updateCartSummary() {
    const subtotal = cart.getTotal();
    const shipping = subtotal > 0 ? 1500 : 0;
    const total = subtotal + shipping;

    const summaryContainer = document.querySelector('.cart-summary');
    if (summaryContainer) {
        summaryContainer.innerHTML = `
            <h2>Resumo do Pedido</h2>
            <div class="summary-row">
                <span>Subtotal</span>
                <span>${formatPrice(subtotal)}</span>
            </div>
            <div class="summary-row">
                <span>Frete</span>
                <span>${formatPrice(shipping)}</span>
            </div>
            <div class="summary-row">
                <span>Total</span>
                <span>${formatPrice(total)}</span>
            </div>
            <a href="/public/checkout.html" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 1rem;">
                Ir para Checkout
            </a>
        `;
    }
}

// Checkout
async function submitCheckout(event) {
    event.preventDefault();

    // Verificar se usuário está logado
    if (!user.isLoggedIn()) {
        showError('Você precisa estar logado para fazer um pedido');
        setTimeout(() => {
            window.location.href = '/public/login.html';
        }, 1500);
        return;
    }

    const shippingAddress = document.querySelector('textarea[name="shipping_address"]').value;
    const billingAddress = document.querySelector('textarea[name="billing_address"]').value;

    if (!shippingAddress || !billingAddress) {
        showError('Por favor, preencha todos os campos');
        return;
    }

    const totalPrice = cart.getTotal() + (cart.getTotal() > 0 ? 1500 : 0);

    // Validar se há itens no carrinho
    if (cart.items.length === 0) {
        showError('Seu carrinho está vazio');
        return;
    }

    // Validar se o preço total é válido
    if (totalPrice <= 0) {
        showError('Preço total inválido');
        return;
    }

    try {
        const data = await fetchAPI(`/orders.php?action=create`, {
            method: 'POST',
            body: JSON.stringify({
                user_id: user.data.id,
                total_price: totalPrice,
                shipping_address: shippingAddress,
                billing_address: billingAddress,
                items: cart.items
            })
        });

        if (data.success) {
            cart.clear();
            showSuccess('Pedido criado com sucesso!');
            setTimeout(() => {
                window.location.href = `/public/pedidos.html`;
            }, 1500);
        }
    } catch (error) {
        showError('Erro ao criar pedido');
    }
}

// Inicializar página
async function initPage() {
    const page = document.body.getAttribute('data-page');

    if (page === 'home') {
        const container = document.querySelector('.products-grid');
        if (container) {
            container.innerHTML = '<div class="loading"><div class="spinner"></div>Carregando...</div>';
            const products = await loadFeaturedProducts();
            renderProducts(products, container);
        }
    }
    else if (page === 'products') {
        const container = document.querySelector('.products-grid');
        const filterButtons = document.querySelectorAll('.filter-group button');

        async function loadAndRender(filters = {}) {
            if (container) {
                container.innerHTML = '<div class="loading"><div class="spinner"></div>Carregando...</div>';
                const products = await loadProducts(filters);
                renderProducts(products, container);
            }
        }

        // Carregar categorias
        const categories = await loadCategories();
        const filterGroup = document.querySelector('.filter-group');
        if (filterGroup && categories.length > 0) {
            filterGroup.innerHTML = '<h3>Categorias</h3>' + 
                '<button class="filter-btn active" data-category="">Todas as Categorias</button>' +
                categories.map(cat => `<button class="filter-btn" data-category="${cat.id}">${cat.name}</button>`).join('');

            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    const category = btn.getAttribute('data-category');
                    loadAndRender(category ? { category: parseInt(category) } : {});
                });
            });
        }

        loadAndRender();
    }
    else if (page === 'product') {
        const params = new URLSearchParams(window.location.search);
        const slug = params.get('slug');

        if (slug) {
            const product = await loadProductDetail(slug);
            if (product) {
                document.querySelector('.product-detail-image').src = product.image;
                document.querySelector('.product-detail-image').alt = product.name;
                document.querySelector('.product-detail-info h1').textContent = product.name;
                document.querySelector('.product-detail-price').textContent = formatPrice(product.price);
                document.querySelector('.product-detail-info p').textContent = product.description;

                const addBtn = document.querySelector('.btn-add-to-cart');
                if (addBtn) {
                    addBtn.addEventListener('click', () => {
                        const quantity = parseInt(document.querySelector('.quantity-input').value);
                        cart.addItem(product, quantity);
                    });
                }
            }
        }
    }
    else if (page === 'cart') {
        const container = document.querySelector('.cart-items-container');
        renderCart(container);
        updateCartSummary();
    }
    else if (page === 'checkout') {
        updateCartSummary();
        const form = document.querySelector('.checkout-form');
        if (form) {
            form.addEventListener('submit', submitCheckout);
        }
    }
}

// Iniciar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', initPage);

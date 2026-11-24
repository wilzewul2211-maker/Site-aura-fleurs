/* ==========================================================================
   Aura Fleurs — клиентская логика.
   Полностью повторяет поведение исходной React-версии, но без сборки.
   Корзина, избранное, заказы и пользователь хранятся в localStorage.
   ========================================================================== */
(function () {
  'use strict';

  var CONFIG = window.AURA_CONFIG || { promo: {}, freeDeliveryFrom: 5000, deliveryCost: 490, defaultBonuses: 500 };

  var LS = {
    cart: 'aura_cart',
    favorites: 'aura_favorites',
    user: 'aura_user',
    orders: 'aura_orders'
  };

  // Варианты размера букета (как в исходной версии)
  var SIZES = {
    standard: { extraPrice: 0, sizeName: 'Стандарт (35-40 см)' },
    deluxe: { extraPrice: 1500, sizeName: 'Делюкс (+30% цветов)' },
    royal: { extraPrice: 3500, sizeName: 'Royal Премиум (пышный)' }
  };

  // ---------- Состояние ----------
  var cart = readLS(LS.cart, []);
  var favorites = readLS(LS.favorites, []);
  var orders = readLS(LS.orders, []);
  var currentUser = readLS(LS.user, null);
  var discount = 0;
  var pendingOrder = null;
  var quickProduct = null;
  var selectedCategory = 'Все';
  var searchQuery = '';

  // ---------- Утилиты ----------
  function readLS(key, fallback) {
    try {
      var raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function writeLS(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) {}
  }

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function money(value) {
    return Math.round(Number(value) || 0).toLocaleString('ru-RU') + ' ₽';
  }

  function escapeHtml(text) {
    return String(text == null ? '' : text)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  // ---------- Модальные окна ----------
  function openModal(id) {
    var el = document.getElementById(id);
    if (el) { el.hidden = false; document.body.classList.add('no-scroll'); }
  }
  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) { el.hidden = true; }
    var anyOpen = $$('.overlay, .drawer-overlay').some(function (m) { return !m.hidden; });
    if (!anyOpen) { document.body.classList.remove('no-scroll'); }
  }
  function closeAllModals() {
    $$('.overlay, .drawer-overlay').forEach(function (m) { m.hidden = true; });
    document.body.classList.remove('no-scroll');
  }

  // ---------- Навигация по вкладкам ----------
  function showTab(name) {
    $$('[data-view]').forEach(function (view) {
      view.hidden = view.getAttribute('data-view') !== name;
    });
    $$('.nav-link').forEach(function (link) {
      link.classList.toggle('active', link.getAttribute('data-tab') === name);
    });
    $('#userBtnLogged').classList.toggle('active', name === 'cabinet');
    if (name === 'cabinet') { renderCabinet(); }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // ---------- Каталог: поиск и категории ----------
  function applyFilters() {
    var query = searchQuery.trim().toLowerCase();
    var visibleCount = 0;
    $$('#productGrid .card').forEach(function (card) {
      var catOk = selectedCategory === 'Все' || card.getAttribute('data-category') === selectedCategory;
      var searchOk = query === '' || card.getAttribute('data-search').toLowerCase().indexOf(query) !== -1;
      var show = catOk && searchOk;
      card.hidden = !show;
      if (show) { visibleCount++; }
    });
    $('#noResults').hidden = visibleCount !== 0;
  }

  // ---------- Избранное ----------
  function renderFavorites() {
    $$('[data-fav]').forEach(function (btn) {
      var id = btn.getAttribute('data-fav');
      var active = favorites.indexOf(id) !== -1;
      btn.classList.toggle('active', active);
      var svg = btn.querySelector('svg');
      if (svg) { svg.setAttribute('fill', active ? 'currentColor' : 'none'); }
    });
  }

  function toggleFavorite(id) {
    var idx = favorites.indexOf(id);
    if (idx === -1) { favorites.push(id); } else { favorites.splice(idx, 1); }
    writeLS(LS.favorites, favorites);
    renderFavorites();
  }

  // ---------- Корзина ----------
  function subtotal() {
    return cart.reduce(function (sum, item) {
      return sum + (item.product.price + item.extraPrice) * item.quantity;
    }, 0);
  }

  function deliveryCost() {
    var sum = subtotal();
    return (sum > CONFIG.freeDeliveryFrom || sum === 0) ? 0 : CONFIG.deliveryCost;
  }

  function finalTotal() {
    return Math.max(0, subtotal() + deliveryCost() - discount);
  }

  function addToCart(product, size) {
    size = size || 'standard';
    var meta = SIZES[size] || SIZES.standard;

    var existingIndex = -1;
    for (var i = 0; i < cart.length; i++) {
      if (cart[i].product.id === product.id && cart[i].size === size) { existingIndex = i; break; }
    }

    if (existingIndex > -1) {
      cart[existingIndex].quantity += 1;
    } else {
      cart.push({
        product: product,
        quantity: 1,
        size: size,
        sizeName: meta.sizeName,
        extraPrice: meta.extraPrice
      });
    }

    writeLS(LS.cart, cart);
    renderCart();
    openModal('cartDrawer');
  }

  function updateQuantity(index, delta) {
    if (!cart[index]) { return; }
    cart[index].quantity += delta;
    if (cart[index].quantity <= 0) { cart.splice(index, 1); }
    writeLS(LS.cart, cart);
    renderCart();
  }

  function removeItem(index) {
    if (!cart[index]) { return; }
    cart.splice(index, 1);
    writeLS(LS.cart, cart);
    renderCart();
  }

  function updateCartBadge() {
    var count = cart.reduce(function (sum, item) { return sum + item.quantity; }, 0);
    var badge = $('#cartBadge');
    badge.textContent = count;
    badge.hidden = count === 0;
  }

  function renderCart() {
    var box = $('#cartItems');
    var foot = $('#cartFoot');

    if (cart.length === 0) {
      box.innerHTML =
        '<div class="drawer-empty">' +
        '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>' +
        '<p>Корзина пуста</p>' +
        '</div>';
      foot.hidden = true;
      updateCartBadge();
      return;
    }

    box.innerHTML = cart.map(function (item, idx) {
      var lineTotal = (item.product.price + item.extraPrice) * item.quantity;
      return '' +
        '<div class="cart-item">' +
          '<img class="cart-item-img" src="' + escapeHtml(item.product.image) + '" alt="' + escapeHtml(item.product.name) + '">' +
          '<div class="cart-item-main">' +
            '<div class="cart-item-top">' +
              '<div>' +
                '<div class="cart-item-name">' + escapeHtml(item.product.name) + '</div>' +
                '<div class="cart-item-size">' + escapeHtml(item.sizeName) + '</div>' +
              '</div>' +
              '<button class="cart-remove" data-remove="' + idx + '" aria-label="Удалить">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' +
              '</button>' +
            '</div>' +
            '<div class="cart-item-bottom">' +
              '<div class="cart-item-price">' + money(lineTotal) + '</div>' +
              '<div class="qty">' +
                '<button data-dec="' + idx + '" aria-label="Меньше"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg></button>' +
                '<span>' + item.quantity + '</span>' +
                '<button data-inc="' + idx + '" aria-label="Больше"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg></button>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>';
    }).join('');

    foot.hidden = false;
    $('#cartTotal').textContent = money(finalTotal());
    $('#checkoutAmount').textContent = money(finalTotal());
    updateCartBadge();
  }

  // ---------- Промокод ----------
  function applyPromo() {
    var input = $('#promoInput');
    var msg = $('#promoMsg');
    var code = (input.value || '').trim().toUpperCase();
    var promo = CONFIG.promo[code];

    msg.hidden = false;
    msg.className = 'promo-msg';

    if (!promo) {
      discount = 0;
      msg.classList.add('err');
      msg.textContent = 'Неверный промокод (пробуйте AURA500)';
    } else if (promo.type === 'fixed') {
      discount = Number(promo.value) || 0;
      msg.classList.add('ok');
      msg.textContent = 'Промокод применен: скидка ' + discount + ' ₽';
    } else {
      discount = Math.round(subtotal() * (Number(promo.value) || 0) / 100);
      msg.classList.add('ok');
      msg.textContent = 'Промокод применен: скидка ' + promo.value + '%';
    }

    renderCart();
  }

  // ---------- Быстрый просмотр ----------
  function openQuickView(product) {
    quickProduct = product;
    $('#quickImg').src = product.image;
    $('#quickImg').alt = product.name;
    $('#quickCat').textContent = product.category;
    $('#quickName').textContent = product.name;
    $('#quickDesc').textContent = product.description;
    $('#quickPrice').textContent = money(product.price);
    openModal('quickModal');
  }

  // ---------- Оформление заказа и оплата ----------
  function buildOrder() {
    var now = new Date();
    return {
      id: 'AURA-' + Math.floor(1000 + Math.random() * 9000),
      date: now.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }),
      items: JSON.parse(JSON.stringify(cart)),
      total: finalTotal(),
      status: 'Оплачен',
      address: $('#deliveryAddress').value,
      recipient: $('#recipientName').value,
      phone: $('#recipientPhone').value
    };
  }

  function saveOrder(order) {
    orders.unshift(order);
    writeLS(LS.orders, orders);
  }

  function finishOrderSuccess(order) {
    saveOrder(order);
    cart = [];
    writeLS(LS.cart, cart);
    discount = 0;
    pendingOrder = null;
    $('#promoInput').value = '';
    $('#promoMsg').hidden = true;
    closeAllModals();
    renderCart();
    showTab('cabinet');
  }

  function handleCheckout(e) {
    e.preventDefault();
    if (cart.length === 0) { return; }

    var order = buildOrder();
    var payBtn = $('#payBtn');
    payBtn.disabled = true;
    payBtn.textContent = 'Соединение с ЮKassa...';

    fetch('api/create-payment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        amount: order.total,
        description: 'Заказ цветов Aura Fleurs #' + order.id,
        orderId: order.id,
        customerPhone: order.phone
      })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.confirmationUrl) {
          saveOrder(order);
          window.location.href = data.confirmationUrl;
          return;
        }
        if (data && data.simulated) {
          pendingOrder = order;
          closeModal('checkoutModal');
          openModal('sandboxModal');
          return;
        }
        finishOrderSuccess(order);
      })
      .catch(function () {
        finishOrderSuccess(order);
      })
      .then(function () {
        payBtn.disabled = false;
        payBtn.textContent = 'Оплатить';
      });
  }

  // ---------- Авторизация ----------
  function saveUserSession(user) {
    currentUser = user;
    writeLS(LS.user, user);
    renderUser();
    renderCabinet();
  }

  function logout() {
    currentUser = null;
    try { localStorage.removeItem(LS.user); } catch (e) {}
    renderUser();
    renderCabinet();
  }

  function renderUser() {
    var logged = $('#userBtnLogged');
    var guest = $('#userBtnGuest');
    if (currentUser) {
      logged.hidden = false;
      guest.hidden = true;
      $('#userBtnName').textContent = currentUser.name || currentUser.phone;
      $('#userBtnBonus').textContent = currentUser.bonuses;
    } else {
      logged.hidden = true;
      guest.hidden = false;
    }
  }

  function renderCabinet() {
    var hasUser = !!currentUser;
    $('#cabinetName').textContent = hasUser ? (currentUser.name || 'Покупатель') : 'Личный кабинет';
    $('#cabinetPhone').textContent = hasUser ? currentUser.phone : '+7 (999) 000-00-00';
    $('#cabinetBonus').textContent = hasUser ? currentUser.bonuses : CONFIG.defaultBonuses;
    $('#logoutBtn').hidden = !hasUser;
    $('#cabinetGuestNote').hidden = hasUser;
    $('#cabinetOrdersWrap').hidden = !hasUser;

    if (!hasUser) { return; }

    var list = $('#ordersList');
    var empty = $('#ordersEmpty');
    if (orders.length === 0) {
      list.innerHTML = '';
      empty.hidden = false;
      return;
    }
    empty.hidden = true;
    list.innerHTML = orders.map(function (order) {
      var items = order.items.map(function (item) {
        return '<li>' + escapeHtml(item.product.name) + ' — ' + item.quantity + ' × ' + money(item.product.price + item.extraPrice) + '</li>';
      }).join('');
      return '' +
        '<div class="order">' +
          '<div class="order-top">' +
            '<div>' +
              '<div class="order-id">#' + escapeHtml(order.id) + '</div>' +
              '<div class="order-date">' + escapeHtml(order.date) + '</div>' +
            '</div>' +
            '<span class="order-status">' + escapeHtml(order.status) + '</span>' +
          '</div>' +
          '<div class="order-items"><ul>' + items + '</ul>' +
            '<div>Получатель: ' + escapeHtml(order.recipient) + ' • ' + escapeHtml(order.phone) + '</div>' +
            '<div>Адрес: ' + escapeHtml(order.address) + '</div>' +
          '</div>' +
          '<div class="order-total"><span>Итого:</span><span class="val">' + money(order.total) + '</span></div>' +
        '</div>';
    }).join('');
  }

  function handleSendCode(e) {
    e.preventDefault();
    var phone = $('#authPhoneInput').value;
    var btn = $('#authSendBtn');
    btn.disabled = true;
    btn.textContent = 'Отправка...';

    var proceed = function () {
      $('#authPhoneForm').hidden = true;
      $('#authCodeStep').hidden = false;
      // Фиктивное авто-подтверждение через 1.2 с, как в исходной версии
      setTimeout(function () { verifyCode(phone, '7744'); }, 1200);
    };

    fetch('api/auth-send-code.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: phone })
    })
      .then(function (res) { return res.json(); })
      .then(function () { proceed(); })
      .catch(function () { proceed(); })
      .then(function () {
        btn.disabled = false;
        btn.textContent = 'Получить код';
      });
  }

  function verifyCode(phone, code) {
    var fallbackUser = {
      id: 'usr_' + Date.now(),
      phone: phone,
      name: 'Покупатель Aura',
      bonuses: CONFIG.defaultBonuses
    };

    fetch('api/auth-verify-code.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: phone, code: code })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        saveUserSession((data && data.success && data.user) ? data.user : fallbackUser);
      })
      .catch(function () {
        saveUserSession(fallbackUser);
      })
      .then(function () {
        closeModal('authModal');
        $('#authPhoneForm').hidden = false;
        $('#authCodeStep').hidden = true;
      });
  }

  // ---------- Инициализация ----------
  function bindEvents() {
    // Вкладки
    $$('[data-tab]').forEach(function (el) {
      el.addEventListener('click', function () { showTab(el.getAttribute('data-tab')); });
    });
    $$('[data-go-tab]').forEach(function (el) {
      el.addEventListener('click', function () { showTab(el.getAttribute('data-go-tab')); });
    });

    // Кнопки открытия/закрытия модальных окон
    $$('[data-open]').forEach(function (el) {
      el.addEventListener('click', function () { openModal(el.getAttribute('data-open')); });
    });
    $$('[data-close]').forEach(function (el) {
      el.addEventListener('click', function () { closeModal(el.getAttribute('data-close')); });
    });

    // Прокрутка к каталогу
    $$('[data-scroll]').forEach(function (el) {
      el.addEventListener('click', function () {
        var target = document.getElementById(el.getAttribute('data-scroll'));
        if (target) { target.scrollIntoView({ behavior: 'smooth' }); }
      });
    });

    // Поиск
    $('#searchInput').addEventListener('input', function (e) {
      searchQuery = e.target.value;
      applyFilters();
    });

    // Категории
    $$('#categoryChips .chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        selectedCategory = chip.getAttribute('data-category');
        $$('#categoryChips .chip').forEach(function (c) { c.classList.toggle('active', c === chip); });
        applyFilters();
      });
    });

    // Карточки товаров
    $$('#productGrid .card').forEach(function (card) {
      var product = JSON.parse(card.getAttribute('data-product'));

      card.querySelector('[data-add-to-cart]').addEventListener('click', function () {
        addToCart(product, 'standard');
      });
      card.querySelector('[data-quickview]').addEventListener('click', function () {
        openQuickView(product);
      });
      card.querySelector('[data-fav]').addEventListener('click', function () {
        toggleFavorite(card.querySelector('[data-fav]').getAttribute('data-fav'));
      });
    });

    // Быстрый просмотр — в корзину
    $('#quickAdd').addEventListener('click', function () {
      if (quickProduct) {
        addToCart(quickProduct, 'standard');
        quickProduct = null;
        closeModal('quickModal');
      }
    });

    // Корзина: открытие
    $('#cartBtn').addEventListener('click', function () {
      renderCart();
      openModal('cartDrawer');
    });

    // Корзина: делегированные клики по количеству/удалению
    $('#cartItems').addEventListener('click', function (e) {
      var btn = e.target.closest('[data-inc],[data-dec],[data-remove]');
      if (!btn) { return; }
      if (btn.hasAttribute('data-inc')) { updateQuantity(parseInt(btn.getAttribute('data-inc'), 10), 1); }
      else if (btn.hasAttribute('data-dec')) { updateQuantity(parseInt(btn.getAttribute('data-dec'), 10), -1); }
      else if (btn.hasAttribute('data-remove')) { removeItem(parseInt(btn.getAttribute('data-remove'), 10)); }
    });

    // Промокод
    $('#promoApply').addEventListener('click', applyPromo);

    // Оформление заказа
    $('#checkoutBtn').addEventListener('click', function () {
      if (cart.length === 0) { return; }
      closeModal('cartDrawer');
      $('#checkoutAmount').textContent = money(finalTotal());
      openModal('checkoutModal');
    });
    $('#checkoutForm').addEventListener('submit', handleCheckout);

    // Тестовый платеж
    $('#sandboxConfirm').addEventListener('click', function () {
      if (pendingOrder) { finishOrderSuccess(pendingOrder); }
    });

    // Авторизация
    $('#authPhoneForm').addEventListener('submit', handleSendCode);
    $('#logoutBtn').addEventListener('click', logout);
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindEvents();
    renderFavorites();
    renderUser();
    renderCart();
    renderCabinet();
    applyFilters();

    // Возврат после оплаты ЮKassa
    var params = new URLSearchParams(window.location.search);
    if (params.get('payment_success') === 'true') {
      showTab('cabinet');
    }
  });
})();

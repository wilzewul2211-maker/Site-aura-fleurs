<?php
/**
 * Aura Fleurs — главная страница (весь интерфейс в одном файле).
 * Никакой сборки не требуется: обычный PHP + CSS + JS.
 */
declare(strict_types=1);

$config   = require __DIR__ . '/config.php';
$products = require __DIR__ . '/data/products.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/icons.php';

$brand      = $config['brand'];
$categories = ['Все', 'Премиум', 'Розы', 'Пионы', 'Шляпные коробки', 'Свадебные'];

// Стартовые значения формы оформления (как в исходной версии)
$checkoutDefaults = [
    'recipient' => 'Анна',
    'phone'     => '+7 (916) 123-45-67',
    'address'   => 'г. Москва, ул. Тверская, д. 12, кв. 45',
];
$authPhoneDefault = '+7 (999) 888-77-66';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura Fleurs — Премиальная доставка цветов</title>
    <meta name="description" content="Элегантные авторские букеты с доставкой за 2 часа. Единый стиль бренда, свежие цветы и удобная оплата.">
    <meta property="og:title" content="Aura Fleurs — Премиальная доставка цветов">
    <meta property="og:description" content="Элегантные авторские букеты с доставкой за 2 часа.">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Верхняя полоса -->
<div class="topbar">
    <?= icon('sparkles', 14) ?>
    <span>ЮKassa (ShopID: <?= e($config['yookassa']['shop_id']) ?>) • Бесплатная доставка при заказе от <?= money($brand['free_delivery_from']) ?> ₽ • Промокод: <strong><?= e($brand['promo_hint']) ?></strong></span>
</div>

<!-- Шапка -->
<header class="header">
    <div class="wrap header-inner">
        <div class="brand" data-go-tab="catalog">
            <div class="brand-logo">A</div>
            <div>
                <span class="brand-title"><?= e($brand['name']) ?></span>
                <span class="brand-sub"><?= e($brand['tagline']) ?></span>
            </div>
        </div>

        <nav class="nav">
            <button class="nav-link active" data-tab="catalog">Каталог букетов</button>
            <button class="nav-link" data-tab="about">О бренде и музе</button>
            <button class="nav-link" data-tab="delivery">Доставка и ЮKassa</button>
        </nav>

        <div class="header-actions">
            <a class="phone-link" href="tel:<?= e($brand['phone_href']) ?>">
                <?= icon('phone', 14) ?>
                <span><?= e($brand['phone']) ?></span>
            </a>

            <!-- Кнопка вошедшего пользователя -->
            <button class="user-btn" id="userBtnLogged" data-tab="cabinet" hidden>
                <?= icon('user', 16) ?>
                <span class="user-name" id="userBtnName">Покупатель</span>
                <span class="user-bonus"><span id="userBtnBonus">500</span> бонусов</span>
            </button>

            <!-- Кнопка входа -->
            <button class="user-btn" id="userBtnGuest" data-open="auth">
                <?= icon('user', 16) ?>
                <span>Войти / Телефон</span>
            </button>

            <button class="cart-btn" id="cartBtn" aria-label="Корзина">
                <?= icon('bag', 20) ?>
                <span class="cart-badge" id="cartBadge" hidden>0</span>
            </button>
        </div>
    </div>
</header>

<main>
    <!-- ================= КАТАЛОГ ================= -->
    <section data-view="catalog">
        <div class="hero">
            <div class="wrap hero-grid">
                <div>
                    <div class="hero-badge">
                        <?= icon('sparkles', 14) ?>
                        <span>ЮKassa ShopID: <?= e($config['yookassa']['shop_id']) ?> • Реальные онлайн-платежи</span>
                    </div>
                    <h1>Искусство дарить <br>эмоции с первой ноты</h1>
                    <p class="hero-text">Наша муза София представляет коллекцию из <?= count($products) ?> эксклюзивных букетов. Быстрая доставка по городу за 2 часа и безопасная оплата через ЮKassa.</p>
                    <div class="hero-actions">
                        <button class="btn btn-dark" data-scroll="catalog-grid">
                            <span>Выбрать букет</span>
                            <?= icon('arrow-right', 16) ?>
                        </button>
                        <button class="btn btn-outline" data-tab="delivery">Оплата ЮKassa</button>
                    </div>
                </div>
                <div class="hero-media">
                    <div class="hero-media-inner">
                        <img class="hero-img" src="assets/images/brand_model_muse_1795517668979.jpg" alt="Муза бренда Aura Fleurs">
                    </div>
                </div>
            </div>
        </div>

        <div class="wrap section" id="catalog-grid">
            <div class="catalog-head">
                <div>
                    <h2 class="catalog-title">Коллекция букетов (<?= count($products) ?> товаров)</h2>
                    <p class="catalog-sub">Реалистичные фотографии цветов и нашей музы</p>
                </div>
                <div class="search">
                    <?= icon('search', 16) ?>
                    <input type="text" id="searchInput" placeholder="Поиск по цветам..." autocomplete="off">
                </div>
            </div>

            <div class="chips" id="categoryChips">
                <?php foreach ($categories as $i => $cat): ?>
                    <button class="chip<?= $i === 0 ? ' active' : '' ?>" data-category="<?= e($cat) ?>"><?= e($cat) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="grid" id="productGrid">
                <?php foreach ($products as $p): ?>
                    <?php
                        $searchBlob = $p['name'] . ' ' . $p['description'] . ' ' . implode(' ', $p['flowers']);
                        $productJson = json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ?>
                    <article class="card"
                             data-category="<?= e($p['category']) ?>"
                             data-search="<?= e($searchBlob) ?>"
                             data-product="<?= e($productJson) ?>">
                        <div class="card-media">
                            <img class="card-img" src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                            <?php if (!empty($p['badge'])): ?>
                                <span class="badge"><?= e($p['badge']) ?></span>
                            <?php endif; ?>
                            <button class="fav-btn" data-fav="<?= e($p['id']) ?>" aria-label="В избранное">
                                <?= icon('heart', 16) ?>
                            </button>
                        </div>
                        <div class="card-body">
                            <div>
                                <div class="card-meta">
                                    <span><?= e($p['category']) ?></span>
                                    <span class="rating">★ <?= e(number_format((float) $p['rating'], 1)) ?> (<?= (int) $p['reviewsCount'] ?>)</span>
                                </div>
                                <h3 class="card-title"><?= e($p['name']) ?></h3>
                                <p class="card-desc"><?= e($p['description']) ?></p>
                            </div>
                            <div class="card-foot">
                                <span class="price"><?= money($p['price']) ?> ₽</span>
                                <div class="card-actions">
                                    <button class="icon-btn" data-quickview title="Быстрый просмотр">
                                        <?= icon('search', 16) ?>
                                    </button>
                                    <button class="btn btn-dark btn-add" data-add-to-cart>
                                        <?= icon('bag', 14) ?>
                                        <span>В корзину</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <p class="empty-note" id="noResults" hidden>Ничего не найдено. Попробуйте изменить запрос.</p>
            </div>
        </div>
    </section>

    <!-- ================= О БРЕНДЕ ================= -->
    <section data-view="about" hidden>
        <div class="wrap section">
            <div class="about-grid">
                <div>
                    <span class="eyebrow">Единый бренд</span>
                    <h1>София — муза и лицо Aura Fleurs</h1>
                    <p>Каждая фотография в каталоге создана с участием нашей музы, что подчеркивает безупречный вкус, парижский шарм и живую эмоцию бренда.</p>
                </div>
                <div>
                    <img class="about-img" src="assets/images/brand_model_bouquet_2_1795517681985.jpg" alt="О бренде Aura Fleurs">
                </div>
            </div>
        </div>
    </section>

    <!-- ================= ДОСТАВКА ================= -->
    <section data-view="delivery" hidden>
        <div class="wrap section">
            <span class="eyebrow">Оплата и доставка</span>
            <h1 class="page-title">Интеграция с ЮKassa (ShopID: <?= e($config['yookassa']['shop_id']) ?>)</h1>
            <p class="lead">Заказы оплачиваются через официальный защищенный шлюз ЮKassa. После подтверждения вы автоматически вернетесь на сайт в личный кабинет.</p>

            <div class="info-cards">
                <div class="info-card">
                    <div class="ico"><?= icon('truck', 22) ?></div>
                    <h3>Доставка за 2 часа</h3>
                    <p>Бесплатно при заказе от <?= money($brand['free_delivery_from']) ?> ₽. Иначе стоимость доставки — <?= money($brand['delivery_cost']) ?> ₽.</p>
                </div>
                <div class="info-card">
                    <div class="ico"><?= icon('card', 22) ?></div>
                    <h3>Безопасная оплата</h3>
                    <p>Приём платежей через ЮKassa: банковские карты, СБП и другие способы.</p>
                </div>
                <div class="info-card">
                    <div class="ico"><?= icon('shield', 22) ?></div>
                    <h3>Гарантия свежести</h3>
                    <p>Свежий срез ежедневно. Если букет не понравится — заменим в течение дня.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= ЛИЧНЫЙ КАБИНЕТ ================= -->
    <section data-view="cabinet" hidden>
        <div class="wrap section">
            <div class="cabinet-head">
                <div>
                    <h1 id="cabinetName">Личный кабинет</h1>
                    <p class="sub" id="cabinetPhone">+7 (999) 000-00-00</p>
                </div>
                <div class="cabinet-actions">
                    <div class="bonus-box">
                        <div class="label">Бонусы</div>
                        <div class="value"><span id="cabinetBonus"><?= (int) $config['default_bonuses'] ?></span> баллов</div>
                    </div>
                    <button class="logout-link" id="logoutBtn" hidden>Выйти</button>
                </div>
            </div>

            <div id="cabinetGuestNote" class="lead" style="margin-top:24px;">
                Войдите по телефону, чтобы видеть свои заказы и бонусы.
                <div style="margin-top:16px;">
                    <button class="btn btn-dark" data-open="auth">Войти по телефону</button>
                </div>
            </div>

            <div id="cabinetOrdersWrap" hidden>
                <h2 class="orders-title">Мои заказы</h2>
                <div id="ordersList"></div>
                <p class="lead" id="ordersEmpty" hidden>У вас пока нет заказов.</p>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="wrap">
        <p>© 2026 Aura Fleurs. ЮKassa ShopID: <?= e($config['yookassa']['shop_id']) ?>. Все права защищены.</p>
    </div>
</footer>

<!-- ================= БЫСТРЫЙ ПРОСМОТР ================= -->
<div class="overlay" id="quickModal" hidden>
    <div class="modal">
        <button class="modal-close" data-close="quickModal" aria-label="Закрыть"><?= icon('x', 20) ?></button>
        <div class="quick">
            <div class="quick-media">
                <img id="quickImg" src="" alt="">
            </div>
            <div class="quick-body">
                <div>
                    <span class="quick-cat" id="quickCat"></span>
                    <h2 id="quickName"></h2>
                    <p class="quick-desc" id="quickDesc"></p>
                </div>
                <div class="quick-foot">
                    <span class="quick-price" id="quickPrice"></span>
                    <button class="btn btn-dark" id="quickAdd">В корзину</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= КОРЗИНА ================= -->
<div class="drawer-overlay" id="cartDrawer" hidden>
    <div class="drawer-backdrop" data-close="cartDrawer"></div>
    <div class="drawer-holder">
        <div class="drawer">
            <div class="drawer-head">
                <h2><?= icon('bag', 20) ?> Ваша корзина</h2>
                <button class="drawer-close" data-close="cartDrawer" aria-label="Закрыть"><?= icon('x', 20) ?></button>
            </div>

            <div class="drawer-body" id="cartItems"></div>

            <div class="drawer-foot" id="cartFoot" hidden>
                <div class="promo-row">
                    <input type="text" id="promoInput" placeholder="Промокод (<?= e($brand['promo_hint']) ?>)">
                    <button class="btn btn-dark btn-sm" id="promoApply">Применить</button>
                </div>
                <p class="promo-msg" id="promoMsg" hidden></p>

                <div class="total-row">
                    <span>Итого:</span>
                    <span class="total-val" id="cartTotal">0 ₽</span>
                </div>

                <button class="btn btn-dark btn-block" id="checkoutBtn">
                    <span>Оформить заказ</span>
                    <?= icon('arrow-right', 16) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= ОФОРМЛЕНИЕ ЗАКАЗА ================= -->
<div class="overlay scrollable" id="checkoutModal" hidden>
    <div class="modal">
        <button class="modal-close on-cream" data-close="checkoutModal" aria-label="Закрыть"><?= icon('x', 20) ?></button>
        <form class="form-space" id="checkoutForm">
            <h2>Оплата заказа через ЮKassa</h2>

            <p class="form-group-title">Данные получателя</p>
            <div class="field-row">
                <div class="field">
                    <label for="recipientName">Имя</label>
                    <input type="text" id="recipientName" required value="<?= e($checkoutDefaults['recipient']) ?>">
                </div>
                <div class="field">
                    <label for="recipientPhone">Телефон</label>
                    <input type="text" id="recipientPhone" required value="<?= e($checkoutDefaults['phone']) ?>">
                </div>
            </div>
            <div class="field">
                <label for="deliveryAddress">Адрес</label>
                <input type="text" id="deliveryAddress" required value="<?= e($checkoutDefaults['address']) ?>">
            </div>

            <div class="checkout-total">
                <div>
                    <div class="label">К оплате через ЮKassa:</div>
                    <div class="amount" id="checkoutAmount">0 ₽</div>
                </div>
                <button type="submit" class="btn btn-dark" id="payBtn">Оплатить</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= ТЕСТОВЫЙ ПЛАТЕЖ ЮKASSA ================= -->
<div class="overlay" id="sandboxModal" hidden>
    <div class="modal modal-md">
        <div class="sandbox">
            <div class="sandbox-ico">Ю</div>
            <div>
                <h3>ЮKassa Тест (ShopID: <?= e($config['yookassa']['shop_id']) ?>)</h3>
                <p>Тестовая среда платежного шлюза</p>
            </div>
            <button class="btn btn-dark btn-block" id="sandboxConfirm">Подтвердить тестовый платеж в ЮKassa</button>
        </div>
    </div>
</div>

<!-- ================= АВТОРИЗАЦИЯ ================= -->
<div class="overlay" id="authModal" hidden>
    <div class="modal modal-md">
        <div class="auth">
            <button class="modal-close on-cream" data-close="authModal" aria-label="Закрыть"><?= icon('x', 20) ?></button>

            <div class="auth-head">
                <h3>Вход по телефону</h3>
                <p>Автоматическое подтверждение</p>
            </div>

            <form id="authPhoneForm">
                <div class="field">
                    <input type="text" id="authPhoneInput" required value="<?= e($authPhoneDefault) ?>">
                </div>
                <button type="submit" class="btn btn-dark btn-block" id="authSendBtn">Получить код</button>
            </form>

            <div id="authCodeStep" hidden>
                <p class="auth-note">Код подтвержден автоматически: <strong><?= e($config['auth_dev_code']) ?></strong></p>
            </div>
        </div>
    </div>
</div>

<script>
    window.AURA_CONFIG = <?= json_encode([
        'promo' => $config['promo_codes'],
        'freeDeliveryFrom' => $brand['free_delivery_from'],
        'deliveryCost' => $brand['delivery_cost'],
        'defaultBonuses' => $config['default_bonuses'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>

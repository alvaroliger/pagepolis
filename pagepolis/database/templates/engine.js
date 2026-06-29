/* ───────────────────────────────────────────────────────────────────────────
   Motor compartido de las plantillas de Pagepolis.
   Vanilla JS, sin librerías. Funciona por CONVENCIÓN de ids/clases, así que el
   mismo archivo sirve para todas las plantillas y la IA puede amoldarlas:
   - Menú móvil: #menu-toggle alterna .open en #main-nav
   - Header: .scrolled al hacer scroll
   - Animación de aparición: .reveal -> .visible (IntersectionObserver)
   - FAQ: .faq-item con .faq-q (botón) y .faq-a; alterna .open
   - Galería: .gallery-img abre lightbox
   - Formulario: #contact-form valida y muestra #form-success
   - Volver arriba: #to-top
   - TIENDA: .product[data-id|name|price|img], #cart-toggle/#cart-count,
     #cart-drawer/#cart-items/#cart-total/#cart-close/#cart-overlay,
     #checkout-whatsapp / #checkout-email
   Edita estas dos líneas para recibir pedidos:                                 */
const WHATSAPP_NUMERO = '34600000000'; // tu WhatsApp: dígitos con prefijo país, sin '+'
const EMAIL_PEDIDOS  = 'pedidos@tunegocio.com';

(function () {
  document.body.classList.add('js-ready');

  /* Menú móvil */
  var menuToggle = document.getElementById('menu-toggle');
  var nav = document.getElementById('main-nav');
  if (menuToggle && nav) {
    menuToggle.addEventListener('click', function () {
      nav.classList.toggle('open');
      menuToggle.classList.toggle('open');
    });
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { nav.classList.remove('open'); menuToggle.classList.remove('open'); });
    });
  }

  /* Scroll suave en anclas */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (id.length > 1) {
        var el = document.querySelector(id);
        if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth' }); }
      }
    });
  });

  /* Sombra del header */
  var header = document.getElementById('site-header');
  if (header) {
    var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 10); };
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* Animaciones de aparición */
  var reveals = document.querySelectorAll('.reveal');
  if (reveals.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('visible'); io.unobserve(en.target); } });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('visible'); });
  }

  /* FAQ acordeón */
  document.querySelectorAll('.faq-item .faq-q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.closest('.faq-item');
      var open = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function (i) { i.classList.remove('open'); });
      if (!open) item.classList.add('open');
    });
  });

  /* Carrusel de testimonios */
  var slider = document.querySelector('.testi-slider');
  if (slider) {
    var track = slider.querySelector('.testi-track');
    var slides = slider.querySelectorAll('.testi-slide');
    var idx = 0;
    var go = function (n) { idx = (n + slides.length) % slides.length; if (track) track.style.transform = 'translateX(-' + (idx * 100) + '%)'; };
    var prev = slider.querySelector('.testi-prev'); var next = slider.querySelector('.testi-next');
    if (prev) prev.addEventListener('click', function () { go(idx - 1); });
    if (next) next.addEventListener('click', function () { go(idx + 1); });
    if (slides.length > 1) setInterval(function () { go(idx + 1); }, 6000);
  }

  /* Lightbox de galería */
  var galleryImgs = document.querySelectorAll('.gallery-img');
  if (galleryImgs.length) {
    var box = document.createElement('div');
    box.id = 'pp-lightbox';
    box.innerHTML = '<img alt="">';
    box.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.9);display:none;align-items:center;justify-content:center;z-index:3000;padding:24px;cursor:zoom-out';
    box.querySelector('img').style.cssText = 'max-width:92%;max-height:92%;border-radius:8px';
    document.body.appendChild(box);
    var close = function () { box.style.display = 'none'; };
    box.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    galleryImgs.forEach(function (img) {
      img.style.cursor = 'zoom-in';
      img.addEventListener('click', function () { box.querySelector('img').src = img.currentSrc || img.src; box.style.display = 'flex'; });
    });
  }

  /* Formulario de contacto */
  var form = document.getElementById('contact-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var ok = true;
      form.querySelectorAll('[required]').forEach(function (f) {
        var bad = !f.value.trim() || (f.type === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.value));
        f.style.borderColor = bad ? '#ef4444' : '';
        if (bad) ok = false;
      });
      if (!ok) return;
      var success = document.getElementById('form-success');
      if (success) success.hidden = false;
      form.reset();
    });
  }

  /* Volver arriba */
  var toTop = document.getElementById('to-top');
  if (toTop) {
    window.addEventListener('scroll', function () { toTop.classList.toggle('show', window.scrollY > 500); }, { passive: true });
    toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  /* ─────────────── TIENDA / CARRITO ─────────────── */
  var products = document.querySelectorAll('.product');
  var cartToggle = document.getElementById('cart-toggle');
  if (products.length && cartToggle) {
    var KEY = 'pp_cart_' + location.pathname;
    var cart = {};
    try { cart = JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { cart = {}; }

    var drawer = document.getElementById('cart-drawer');
    var overlay = document.getElementById('cart-overlay');
    var itemsEl = document.getElementById('cart-items');
    var totalEl = document.getElementById('cart-total');
    var countEl = document.getElementById('cart-count');
    var euros = function (n) { return n.toFixed(2).replace('.', ',') + ' €'; };
    var save = function () { try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (e) {} };

    var render = function () {
      var count = 0, total = 0, html = '';
      Object.keys(cart).forEach(function (id) {
        var it = cart[id]; count += it.qty; total += it.qty * it.price;
        html += '<div class="cart-row" data-id="' + id + '">' +
          (it.img ? '<img src="' + it.img + '" alt="">' : '') +
          '<div class="cart-row-info"><strong>' + it.name + '</strong><span>' + euros(it.price) + '</span></div>' +
          '<div class="cart-qty"><button class="qminus">−</button><span>' + it.qty + '</span><button class="qplus">+</button></div>' +
          '<button class="cart-del" aria-label="Quitar">×</button></div>';
      });
      if (itemsEl) itemsEl.innerHTML = html || '<p class="cart-empty">Tu cesta está vacía.</p>';
      if (totalEl) totalEl.textContent = euros(total);
      if (countEl) { countEl.textContent = count; countEl.style.display = count ? '' : 'none'; }
      save();
    };

    var add = function (p) {
      if (!cart[p.id]) cart[p.id] = { name: p.name, price: p.price, img: p.img, qty: 0 };
      cart[p.id].qty++; render();
      if (drawer) { drawer.classList.add('open'); if (overlay) overlay.classList.add('open'); }
    };

    products.forEach(function (card) {
      var btn = card.querySelector('.add-to-cart');
      if (!btn) return;
      btn.addEventListener('click', function () {
        add({ id: card.dataset.id, name: card.dataset.name, price: parseFloat(card.dataset.price) || 0, img: card.dataset.img || '' });
      });
    });

    if (itemsEl) itemsEl.addEventListener('click', function (e) {
      var row = e.target.closest('.cart-row'); if (!row) return;
      var id = row.dataset.id; if (!cart[id]) return;
      if (e.target.classList.contains('qplus')) cart[id].qty++;
      else if (e.target.classList.contains('qminus')) { cart[id].qty--; if (cart[id].qty <= 0) delete cart[id]; }
      else if (e.target.classList.contains('cart-del')) delete cart[id];
      render();
    });

    var openCart = function () { if (drawer) drawer.classList.add('open'); if (overlay) overlay.classList.add('open'); };
    var closeCart = function () { if (drawer) drawer.classList.remove('open'); if (overlay) overlay.classList.remove('open'); };
    cartToggle.addEventListener('click', openCart);
    var cartClose = document.getElementById('cart-close');
    if (cartClose) cartClose.addEventListener('click', closeCart);
    if (overlay) overlay.addEventListener('click', closeCart);

    var orderLines = function () {
      var lines = [], total = 0;
      Object.keys(cart).forEach(function (id) { var it = cart[id]; total += it.qty * it.price; lines.push('• ' + it.name + ' x' + it.qty + ' = ' + euros(it.qty * it.price)); });
      return { text: lines.join('\n'), total: total, empty: lines.length === 0 };
    };

    var waBtn = document.getElementById('checkout-whatsapp');
    if (waBtn) waBtn.addEventListener('click', function () {
      var o = orderLines(); if (o.empty) { alert('Tu cesta está vacía.'); return; }
      var msg = 'Hola, quiero hacer un pedido:\n\n' + o.text + '\n\nTOTAL: ' + euros(o.total);
      window.open('https://wa.me/' + WHATSAPP_NUMERO + '?text=' + encodeURIComponent(msg), '_blank');
    });

    var emailBtn = document.getElementById('checkout-email');
    if (emailBtn) emailBtn.addEventListener('click', function () {
      var o = orderLines(); if (o.empty) { alert('Tu cesta está vacía.'); return; }
      var body = 'Quiero hacer un pedido:\n\n' + o.text + '\n\nTOTAL: ' + euros(o.total) + '\n\nNombre:\nDirección:\nTeléfono:';
      window.location.href = 'mailto:' + EMAIL_PEDIDOS + '?subject=' + encodeURIComponent('Nuevo pedido') + '&body=' + encodeURIComponent(body);
    });

    render();
  }
})();

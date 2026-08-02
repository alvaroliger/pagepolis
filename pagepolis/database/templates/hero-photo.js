/* PAGEPOLIS-HERO-PHOTO-ENGINE — no borrar este marcador (evita duplicados si la
   web se regenera o edita). ─────────────────────────────────────────────────
   Hero de foto cinemática: da vida y profundidad a una fotografía real sin
   WebGL. Combina dos movimientos:
   1) "Ken Burns" en reposo — zoom y desplazamiento lentos y continuos, para que
      la foto nunca se sienta estática (igual en la miniatura de la galería).
   2) "Vuelo hacia dentro" ligado al scroll — al bajar la página, la foto se
      acerca y las capas de fondo se desplazan más despacio que las de primer
      plano (paralaje real), dando la sensación de moverse a través de la
      escena — sirve igual para una montaña, una ciudad, un plato o un producto.
   Uso: <div class="hero-photo" data-hero-photo style="--photo:url('...')">
          <div class="hero-photo-img"></div>
          <div class="hero-photo-veil"></div>
          <div class="hero-photo-content">...</div>
        </div>
   Respeta prefers-reduced-motion (queda estática) y no usa WebGL, así que
   funciona en cualquier dispositivo sin coste de batería relevante. */
(function () {
  'use strict';

  function initPhotoHero(el) {
    var img = el.querySelector('.hero-photo-img');
    if (!img) return;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) { el.classList.add('hp-static'); return; }

    el.classList.add('hp-live');
    var ticking = false;

    function update() {
      ticking = false;
      var rect = el.getBoundingClientRect();
      var vh = window.innerHeight || document.documentElement.clientHeight;
      // progreso 0→1 según cuánto se ha desplazado el hero por debajo de la
      // ventana (0 = hero recién visible arriba, 1 = ha salido por completo).
      var progress = 1 - Math.max(0, Math.min(1, (rect.bottom) / (rect.height + vh)));
      var scale = 1.08 + progress * 0.32;      // "vuelo hacia dentro": zoom progresivo
      var shiftY = progress * -34;             // paralaje: el fondo sube más despacio que el scroll
      img.style.transform = 'scale(' + scale.toFixed(3) + ') translateY(' + shiftY.toFixed(1) + 'px)';
    }

    function onScroll() {
      if (!ticking) { ticking = true; requestAnimationFrame(update); }
    }

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) {
            window.addEventListener('scroll', onScroll, { passive: true });
            update();
          } else {
            window.removeEventListener('scroll', onScroll);
          }
        });
      }, { threshold: 0, rootMargin: '40% 0px 40% 0px' });
      io.observe(el);
    } else {
      window.addEventListener('scroll', onScroll, { passive: true });
    }
    update();
    window.addEventListener('resize', onScroll, { passive: true });
  }

  function init() {
    document.querySelectorAll('.hero-photo, [data-hero-photo]').forEach(function (el) {
      if (el.dataset.heroPhotoInit) return;
      el.dataset.heroPhotoInit = '1';
      try { initPhotoHero(el); } catch (e) {}
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

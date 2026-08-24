try{
/* ═══════════════════════════════════════════════════════════════════════════
   KINETIC — motor de movimiento de las plantillas premium de Pagepolis.
   Vanilla JS, sin librerías, un solo requestAnimationFrame para todo.

   No es un "fade-in al hacer scroll": la gracia está en que el scroll ES la
   línea de tiempo. Cada elemento con [data-scroll] recibe su progreso 0→1 al
   atravesar la pantalla y con él se escala, gira, recorta o se desplaza, que es
   lo que hace que estas páginas se sientan cinematográficas y no una web con
   apariciones.

   API por atributos (la IA puede usarlos al amoldar la plantilla):
     [data-reveal="up|left|right|scale|blur|clip"]   entrada al entrar en pantalla
     [data-reveal-delay="0.2"]                        retardo en segundos
     [data-split="words|chars"]                       texto que sube por trozos
     [data-scroll="zoom|rise|rotate|clip|scale-out|
                   drift|pin|hscroll|counter-bar"]    animación ligada al scroll
     [data-scroll-range="0.2 0.9"]                    tramo del viewport (opcional)
     [data-scroll-amount="1.8"]                       intensidad (opcional)
     [data-parallax="0.25"]                           parallax vertical
     [data-tilt]                                      inclinación 3D con el ratón
     [data-magnetic]                                  el botón "persigue" al cursor
     [data-count="1284"] [data-count-suffix="+"]      contador
     [data-eyes]                                      pupilas que siguen al ratón
     [data-fx="iris|mesh|constellation|flow|rain|
               grid3d|blob"]                          canvas de partículas
     .kx-marquee                                      se duplica sola para el bucle
   ═══════════════════════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  var doc = document, win = window;
  var reduced = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = win.matchMedia && win.matchMedia('(hover: hover) and (pointer: fine)').matches;

  doc.body.classList.add('kx-js');

  var clamp = function (v, a, b) { return v < a ? a : v > b ? b : v; };
  var lerp = function (a, b, t) { return a + (b - a) * t; };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || doc).querySelectorAll(sel)); };

  /* ═══ 0. SCROLL CON INERCIA ═══════════════════════════════════════════════
     Es lo que más separa una web cara de una normal, y no se ve en una
     captura: se siente. El scroll nativo para en seco; aquí la página
     persigue a la rueda con amortiguación, así que todo lo que va atado al
     scroll (cámara, textos, escenas) se mueve con peso en vez de a tirones.

     Implementación: el documento se deja con su altura real (para que la barra
     de scroll y el teclado funcionen igual que siempre) y solo se desplaza un
     contenedor con transform. Nada de secuestrar la rueda.                   */
  var smoother = null;
  var scrollTicks = [];   // repintados que dependen del scroll (cámara, escenas)
  function onScrollTick(fn) { scrollTicks.push(fn); }

  function initSmoothScroll() {
    if (reduced || !fine) return;            // en móvil el scroll nativo ya es bueno
    if (win.innerWidth < 900) return;

    // Un ancestro con transform crea un bloque contenedor: todo lo que sea
    // position:fixed dentro dejaría de ser fijo y se iría con el scroll. Por
    // eso el nav, el grano, la barra de progreso y el cajón del carrito se
    // quedan FUERA del contenedor que se desplaza.
    var FIXED = '.kx-nav, .kx-grain, .kx-progress, .kx-spotlight, .kx-cursor, #cart-drawer';
    var stay = $$(FIXED);

    var content = doc.createElement('div');
    content.className = 'kx-smooth';
    while (doc.body.firstChild) content.appendChild(doc.body.firstChild);
    doc.body.appendChild(content);
    stay.forEach(function (el) { doc.body.appendChild(el); });
    doc.documentElement.classList.add('kx-has-smooth');

    var current = 0, target = 0, raf = null;

    function setHeight() {
      // Un espaciador invisible mantiene la altura real del documento.
      doc.body.style.height = content.getBoundingClientRect().height + 'px';
    }

    function render() {
      target = win.scrollY;
      current = lerp(current, target, 0.085);
      if (Math.abs(current - target) < 0.08) current = target;
      content.style.transform = 'translate3d(0,' + (-current).toFixed(2) + 'px,0)';
      smoother.value = current;
      // Todo lo atado al scroll se repinta AQUÍ, no en el evento 'scroll'. Si
      // no, la cámara seguiría la posición nativa (que ya llegó) mientras la
      // página aún se está deslizando, y se vería desincronizado.
      // Cada repintado devuelve si aún le queda amortiguación pendiente: el
      // bucle no puede pararse solo porque el scroll haya llegado, o la cámara
      // se quedaría congelada a mitad de su recorrido.
      var busy = false;
      for (var i = 0; i < scrollTicks.length; i++) {
        if (scrollTicks[i]() === true) busy = true;
      }
      raf = (current !== target || busy) ? requestAnimationFrame(render) : null;
    }

    smoother = {
      value: 0,
      kick: function () { if (!raf) raf = requestAnimationFrame(render); }
    };

    setHeight();

    // Red de seguridad: si el contenedor no midió bien (fuentes que no cargan,
    // un layout inesperado de la plantilla), se deshace el montaje y la página
    // vuelve al scroll nativo. Vale más perder la inercia que dejar una web
    // publicada con la altura rota y sin poder bajar.
    if (content.getBoundingClientRect().height < win.innerHeight * 0.9) {
      doc.body.style.height = '';
      doc.documentElement.classList.remove('kx-has-smooth');
      while (content.firstChild) doc.body.insertBefore(content.firstChild, content);
      content.remove();
      smoother = null;
      return;
    }

    win.addEventListener('scroll', smoother.kick, { passive: true });
    win.addEventListener('resize', function () { setHeight(); smoother.kick(); }, { passive: true });
    if ('ResizeObserver' in win) new ResizeObserver(setHeight).observe(content);
    if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(setHeight);
    win.addEventListener('load', setHeight);
    render();
  }

  // Posición de scroll que debe usar todo el motor: la suavizada si está
  // activa, la nativa si no.
  function scrollPos() { return smoother ? smoother.value : win.scrollY; }

  /* ═══ 0b. CORTINILLA DE ENTRADA ═══════════════════════════════════════════
     Todas las webs de referencia abren con una revelación en vez de aparecer
     de golpe. Cuesta 200 bytes y cambia por completo la primera impresión:
     el hero entra ya en movimiento, no "estaba ahí". */
  if (!reduced) {
    var curtain = doc.createElement('div');
    curtain.className = 'kx-curtain';
    curtain.innerHTML = '<span class="kx-curtain-mark"></span>';
    doc.body.appendChild(curtain);
    doc.documentElement.classList.add('kx-loading');
    var lift = function () {
      doc.documentElement.classList.remove('kx-loading');
      doc.documentElement.classList.add('kx-lifted');
      setTimeout(function () { curtain.remove(); }, 1400);
    };
    // No esperamos a que cargue todo: si la foto del hero tarda, la cortinilla
    // se levanta igual a los 1,2 s para no castigar al visitante.
    if (doc.readyState === 'complete') setTimeout(lift, 260);
    else win.addEventListener('load', function () { setTimeout(lift, 260); });
    setTimeout(lift, 1200);
  }

  /* ═══ 1. TEXTO PARTIDO ════════════════════════════════════════════════════
     Envuelve cada palabra en .kx-word > span para que el span suba desde
     debajo del recorte. Se hace antes que nada para no provocar reflow luego. */
  $$('[data-split]').forEach(function (el) {
    var mode = el.getAttribute('data-split') || 'words';
    var walk = function (node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (n) {
        if (n.nodeType === 3) {
          var text = n.nodeValue;
          if (!text.trim()) return;
          var frag = doc.createDocumentFragment();
          var parts = mode === 'chars' ? text.split('') : text.split(/(\s+)/);
          parts.forEach(function (p) {
            if (!p) return;
            if (/^\s+$/.test(p)) { frag.appendChild(doc.createTextNode(p)); return; }
            var w = doc.createElement('span'); w.className = 'kx-word';
            var inner = doc.createElement('span'); inner.textContent = p;
            w.appendChild(inner); frag.appendChild(w);
          });
          node.replaceChild(frag, n);
        } else if (n.nodeType === 1 && !n.classList.contains('kx-word')) {
          walk(n);
        }
      });
    };
    walk(el);
    $$('.kx-word > span', el).forEach(function (s, i) { s.style.setProperty('--i', i); });
  });

  /* ═══ 2. ENTRADAS AL ENTRAR EN PANTALLA ═══════════════════════════════════ */
  var revealTargets = $$('[data-reveal], [data-split]');
  revealTargets.forEach(function (el) {
    var d = el.getAttribute('data-reveal-delay');
    if (d) el.style.setProperty('--delay', d + 's');
  });
  if ('IntersectionObserver' in win && revealTargets.length) {
    var revealIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        e.target.classList.add('kx-in');
        revealIO.unobserve(e.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
    revealTargets.forEach(function (el) { revealIO.observe(el); });
  } else {
    revealTargets.forEach(function (el) { el.classList.add('kx-in'); });
  }

  /* ═══ 3. MOTOR DE SCROLL ══════════════════════════════════════════════════
     Un único bucle rAF que recalcula el progreso de cada elemento registrado.
     Las medidas (offsetTop/alto) se cachean y solo se recalculan al redimensionar
     o cuando cambia el alto del documento, así el scroll no dispara layout.     */
  var scrollItems = [];
  var vh = win.innerHeight;

  function registerScroll(el, kind) {
    var range = (el.getAttribute('data-scroll-range') || '').split(/\s+/).filter(Boolean).map(Number);
    scrollItems.push({
      el: el,
      kind: kind,
      amount: parseFloat(el.getAttribute('data-scroll-amount')) || 1,
      from: range.length === 2 ? range[0] : null,
      to: range.length === 2 ? range[1] : null,
      top: 0, height: 0,
      cur: 0, target: 0
    });
  }

  $$('[data-scroll]').forEach(function (el) { registerScroll(el, el.getAttribute('data-scroll')); });
  $$('[data-parallax]').forEach(function (el) { registerScroll(el, 'parallax'); });

  function measure() {
    vh = win.innerHeight;
    scrollItems.forEach(function (it) {
      var r = it.el.getBoundingClientRect();
      it.top = r.top + scrollPos();
      it.height = r.height;
      // Un "pin" reserva altura extra: el contenedor mide N pantallas y el hijo
      // se queda fijo mientras se recorre. Lo declara el CSS de la plantilla.
      if (it.kind === 'hscroll') {
        var track = it.el.querySelector('.kx-hscroll-track');
        it.travel = track ? Math.max(0, track.scrollWidth - win.innerWidth + 64) : 0;
      }
    });
  }

  function applyScroll(it, y) {
    var el = it.el;
    var p;

    // Progreso objetivo 0→1. La mayoría lo mide mientras el elemento cruza la
    // ventana entera; 'zoom' y 'hscroll' se miden desde su borde superior
    // porque describen un recorrido, no una aparición.
    if (it.kind === 'zoom') {
      p = clamp((y - it.top) / Math.max(1, vh * 1.1), 0, 1);
    } else if (it.kind === 'hscroll') {
      p = clamp((y - it.top) / Math.max(1, it.height - vh), 0, 1);
    } else {
      var start = it.top - vh;
      var end = it.top + it.height;
      p = clamp((y - start) / Math.max(1, end - start), 0, 1);
    }
    if (it.from !== null) p = clamp((p - it.from) / Math.max(0.0001, it.to - it.from), 0, 1);

    // Suavizado: el valor persigue al objetivo, nunca salta. Guardamos el
    // objetivo aparte para saber si al bucle le queda trabajo pendiente.
    it.target = p;
    it.cur = reduced ? p : lerp(it.cur, p, 0.14);
    var v = it.cur;
    el.style.setProperty('--prog', v.toFixed(4));

    var a = it.amount;
    switch (it.kind) {
      /* El hero se abre y "vuelas hacia dentro": a medida que bajas se acerca y
         se oscurece bajo el contenido. Es el gesto que más cambia la percepción
         de calidad de la página. */
      case 'zoom':
        el.style.transform = 'scale(' + (1 + v * 0.55 * a) + ')';
        el.style.filter = v > 0.02 ? 'brightness(' + (1 - v * 0.55) + ')' : '';
        break;
      case 'reveal-in':
        el.style.transform = 'scale(' + lerp(0.82, 1, v) + ')';
        el.style.borderRadius = lerp(48, 0, v).toFixed(1) + 'px';
        break;
      case 'rise':      el.style.transform = 'translate3d(0,' + ((1 - v) * 90 * a) + 'px,0)'; break;
      case 'drift':     el.style.transform = 'translate3d(' + ((v - 0.5) * 180 * a) + 'px,0,0)'; break;
      case 'rotate':    el.style.transform = 'rotate(' + ((v - 0.5) * 90 * a) + 'deg)'; break;
      case 'scale-out': el.style.transform = 'scale(' + lerp(1.28, 1, v) + ')'; break;
      case 'clip':      el.style.clipPath = 'inset(' + ((1 - v) * 50) + '% 0 0 0)'; break;
      case 'counter-bar': el.style.setProperty('--fill', (v * 100).toFixed(1) + '%'); break;
      case 'parallax': {
        var sp = parseFloat(el.getAttribute('data-parallax')) || 0.2;
        el.style.setProperty('--sy', ((v - 0.5) * vh * sp * -1).toFixed(1) + 'px');
        break;
      }
      /* Galería que se recorre en horizontal mientras el bloque está pinchado */
      case 'hscroll': {
        var track = el.querySelector('.kx-hscroll-track');
        if (track) track.style.transform = 'translate3d(' + (-v * (it.travel || 0)).toFixed(1) + 'px,0,0)';
        break;
      }
    }
  }

  /* Barra de progreso de lectura */
  var progressBar = doc.querySelector('.kx-progress');
  var nav = doc.querySelector('.kx-nav');

  var ticking = false;
  function frame() {
    var y = scrollPos();
    if (nav) nav.classList.toggle('kx-stuck', y > 20);
    if (progressBar) {
      var max = doc.documentElement.scrollHeight - vh;
      progressBar.style.setProperty('--p', max > 0 ? (y / max).toFixed(4) : 0);
    }
    for (var i = 0; i < scrollItems.length; i++) applyScroll(scrollItems[i], y);
    ticking = false;
    // El suavizado necesita seguir pintando aunque el scroll se haya parado,
    // hasta que cada valor alcance su objetivo. Con scroll inercial quien
    // mantiene vivo el bucle es render(), así que se le informa devolviendo
    // true mientras quede recorrido.
    var pending = settling();
    if (pending && !smoother) { ticking = true; requestAnimationFrame(frame); }
    return pending;
  }
  function settling() {
    for (var i = 0; i < scrollItems.length; i++) {
      if (Math.abs(scrollItems[i].cur - scrollItems[i].target) > 0.0015) return true;
    }
    return false;
  }
  function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(frame); } }

  initSmoothScroll();
  measure();
  onScroll();
  onScrollTick(frame);
  win.addEventListener('scroll', onScroll, { passive: true });
  win.addEventListener('resize', function () { measure(); onScroll(); }, { passive: true });
  if ('ResizeObserver' in win) new ResizeObserver(measure).observe(doc.body);
  // Las fuentes cambian la altura del documento al cargar: hay que remedir.
  if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(measure);
  win.addEventListener('load', measure);

  /* ═══ 4. RATÓN: cursor, foco, imanes, inclinación, miradas ════════════════ */
  var mouse = { x: win.innerWidth / 2, y: win.innerHeight / 2 };
  var cursorEl = null;

  if (fine && !reduced) {
    cursorEl = doc.createElement('div');
    cursorEl.className = 'kx-cursor';
    doc.body.appendChild(cursorEl);
  }

  var magnets = $$('[data-magnetic]');
  var tilts = $$('[data-tilt]');
  var eyeGroups = $$('[data-eyes]');
  var spotlight = doc.querySelector('.kx-spotlight');
  var cursorX = mouse.x, cursorY = mouse.y;
  var mouseLoop = false;

  function pointerFrame() {
    // Cursor con retardo (se siente "pesado", como en las referencias)
    if (cursorEl) {
      cursorX = lerp(cursorX, mouse.x, 0.18);
      cursorY = lerp(cursorY, mouse.y, 0.18);
      cursorEl.style.transform = 'translate3d(' + cursorX + 'px,' + cursorY + 'px,0)';
    }
    if (spotlight) {
      spotlight.style.setProperty('--mx', mouse.x + 'px');
      spotlight.style.setProperty('--my', mouse.y + 'px');
    }

    // Imanes: el botón se desplaza hacia el cursor cuando está cerca
    magnets.forEach(function (el) {
      var r = el.getBoundingClientRect();
      var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
      var dx = mouse.x - cx, dy = mouse.y - cy;
      var dist = Math.hypot(dx, dy);
      var radius = Math.max(r.width, r.height) * 1.15;
      if (dist < radius) {
        var pull = (1 - dist / radius) * 0.42;
        el.style.transform = 'translate3d(' + dx * pull + 'px,' + dy * pull + 'px,0)';
      } else if (el.style.transform) {
        el.style.transform = '';
      }
    });

    // Inclinación 3D de tarjetas
    tilts.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.bottom < 0 || r.top > vh) return;
      var px = (mouse.x - r.left) / r.width - 0.5;
      var py = (mouse.y - r.top) / r.height - 0.5;
      var inside = Math.abs(px) < 0.6 && Math.abs(py) < 0.6;
      el.style.setProperty('--ry', (inside ? px * 13 : 0).toFixed(2) + 'deg');
      el.style.setProperty('--rx', (inside ? -py * 13 : 0).toFixed(2) + 'deg');
    });

    // Miradas: las pupilas de los muñequitos siguen al ratón
    eyeGroups.forEach(function (g) {
      var r = g.getBoundingClientRect();
      if (r.bottom < -200 || r.top > vh + 200) return;
      var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
      var ang = Math.atan2(mouse.y - cy, mouse.x - cx);
      var reach = parseFloat(g.getAttribute('data-eyes')) || 4;
      var d = Math.min(1, Math.hypot(mouse.x - cx, mouse.y - cy) / 420) * reach;
      g.style.setProperty('--eye-x', (Math.cos(ang) * d).toFixed(2) + 'px');
      g.style.setProperty('--eye-y', (Math.sin(ang) * d).toFixed(2) + 'px');
    });

    // Seguimos pintando solo mientras el cursor no haya alcanzado al ratón;
    // en reposo el bucle se apaga y no gasta batería.
    var chasing = cursorEl && (Math.abs(cursorX - mouse.x) > 0.4 || Math.abs(cursorY - mouse.y) > 0.4);
    mouseLoop = !!chasing;
    if (chasing) requestAnimationFrame(pointerFrame);
  }

  if (fine) {
    win.addEventListener('mousemove', function (e) {
      mouse.x = e.clientX; mouse.y = e.clientY;
      if (cursorEl) cursorEl.classList.add('kx-on');
      if (!mouseLoop) { mouseLoop = true; requestAnimationFrame(pointerFrame); }
    }, { passive: true });

    // El cursor crece sobre elementos interactivos
    if (cursorEl) {
      $$('a, button, [data-magnetic], .kx-card, .kx-row').forEach(function (el) {
        el.addEventListener('mouseenter', function () { cursorEl.classList.add('kx-grow'); });
        el.addEventListener('mouseleave', function () { cursorEl.classList.remove('kx-grow'); });
      });
    }

    // Halo interior de las tarjetas
    $$('.kx-card').forEach(function (card) {
      card.addEventListener('mousemove', function (e) {
        var r = card.getBoundingClientRect();
        card.style.setProperty('--cx', (e.clientX - r.left) + 'px');
        card.style.setProperty('--cy', (e.clientY - r.top) + 'px');
      }, { passive: true });
    });
  }

  /* ═══ 4b. CÁMARA CINEMATOGRÁFICA ══════════════════════════════════════════
     Pilota los bloques [data-cam]. La deriva de reposo la hace el CSS; aquí se
     añade lo que el CSS no puede saber: cuánto has bajado y dónde está el
     ratón. El resultado es un plano de dron que avanza mientras lees.
       · scroll → la cámara entra en la escena y la oscurece bajo el contenido
       · ratón  → cabeceo y guiñada muy leves (2 grados), lo justo para que la
                  imagen no se sienta pegada al cristal
     El valor de [data-cam] gradúa la intensidad del vuelo (0 = solo deriva).  */
  var cams = $$('[data-cam]').map(function (el) {
    return {
      el: el,
      img: el.querySelector('.kx-cam-img') || el,
      haze: el.querySelector('.kx-cam-haze'),
      power: parseFloat(el.getAttribute('data-cam')) || 0.45,
      fly: 0, target: 0
    };
  });

  function camFrame() {
    var y = scrollPos();
    cams.forEach(function (c) {
      var r = c.el.getBoundingClientRect();
      var top = r.top + y;
      // 0 mientras el hero está entero en pantalla → 1 cuando ya ha salido
      c.target = clamp((y - top) / Math.max(1, r.height * 0.9), 0, 1);
      c.fly = reduced ? c.target : lerp(c.fly, c.target, 0.09);
      c.el.style.setProperty('--fly', c.fly.toFixed(4));

      // La deriva en reposo la lleva la animación CSS; el vuelo se aplica
      // sobre el contenedor para no pisarla.
      var p = c.fly * c.power;
      c.el.style.transform = 'scale(' + (1 + p * 0.5) + ')';
      c.el.style.filter = p > 0.005 ? 'brightness(' + (1 - p * 0.8) + ') saturate(' + (1 - p * 0.35) + ')' : '';
      if (c.haze) c.haze.style.opacity = (0.55 + c.fly * 0.4).toFixed(2);
    });
    return cams.some(function (c) { return Math.abs(c.fly - c.target) > 0.0015; });
  }

  if (cams.length) {
    // Cabeceo con el ratón: se engancha al bucle de puntero ya existente.
    if (fine && !reduced) {
      win.addEventListener('mousemove', function (e) {
        var nx = (e.clientX / win.innerWidth - 0.5);
        var ny = (e.clientY / win.innerHeight - 0.5);
        cams.forEach(function (c) {
          c.img.style.setProperty('--tiltX', (-ny * 2).toFixed(2) + 'deg');
          c.img.style.setProperty('--tiltY', (nx * 2).toFixed(2) + 'deg');
          c.img.style.setProperty('--panX', (-nx * 1.4).toFixed(2) + '%');
        });
      }, { passive: true });
    }
    var camTicking = false;
    var camLoop = function () {
      camFrame();
      camTicking = false;
      var moving = cams.some(function (c) { return Math.abs(c.fly - c.target) > 0.0015; });
      if (moving && !smoother) { camTicking = true; requestAnimationFrame(camLoop); }
    };
    win.addEventListener('scroll', function () {
      if (!camTicking) { camTicking = true; requestAnimationFrame(camLoop); }
    }, { passive: true });
    win.addEventListener('resize', camFrame, { passive: true });
    onScrollTick(camFrame);
    camFrame();
  }

  /* ═══ 4c. CARGA DIFERIDA DEL VÍDEO ════════════════════════════════════════
     Un hero de vídeo son varios megas. Cargarlo siempre y de golpe penaliza la
     primera pintada, gasta datos del visitante y hunde la nota de rendimiento.
     Aquí se carga con criterio:
       · El <source> viaja en data-src y no se pide hasta que el hero está a la
         vista, así el navegador prioriza fuente y CSS en el primer pintado.
       · En pantallas pequeñas o con «ahorro de datos» activado no se descarga
         nunca: se queda el póster, que pesa 200 veces menos.
       · Si el usuario pide menos movimiento, tampoco se reproduce.
     El resultado se ve igual y llega mucho antes.                            */
  var vids = $$('video[data-lazy]');
  if (vids.length) {
    var saveData = (navigator.connection && navigator.connection.saveData) === true;
    var tooSmall = win.matchMedia && win.matchMedia('(max-width: 700px)').matches;
    var skip = saveData || tooSmall || reduced;

    var loadVideo = function (v) {
      if (v.dataset.loaded) return;
      v.dataset.loaded = '1';
      $$('source[data-src]', v).forEach(function (s) {
        s.src = s.getAttribute('data-src');
        s.removeAttribute('data-src');
      });
      v.load();
      var p = v.play();
      if (p && p.catch) p.catch(function () { /* sin autoplay: se queda el póster */ });
    };

    if (!skip) {
      if ('IntersectionObserver' in win) {
        var vio = new IntersectionObserver(function (entries) {
          entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            loadVideo(e.target);
            vio.unobserve(e.target);
          });
        }, { rootMargin: '200px' });
        vids.forEach(function (v) { vio.observe(v); });
      } else {
        vids.forEach(loadVideo);
      }
    }

    // Fuera de pantalla se pausa: un vídeo oculto sigue decodificando y
    // calentando el equipo si no se le dice que pare.
    if ('IntersectionObserver' in win) {
      var pio = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.target.dataset.loaded) return;
          if (e.isIntersecting) { var q = e.target.play(); if (q && q.catch) q.catch(function () {}); }
          else e.target.pause();
        });
      }, { threshold: 0 });
      vids.forEach(function (v) { pio.observe(v); });
    }
  }

  /* ═══ 5. CONTADORES ═══════════════════════════════════════════════════════ */
  $$('[data-count]').forEach(function (el) {
    var target = parseFloat(el.getAttribute('data-count')) || 0;
    var suffix = el.getAttribute('data-count-suffix') || '';
    var prefix = el.getAttribute('data-count-prefix') || '';
    var dec = (el.getAttribute('data-count') || '').split('.')[1];
    var decimals = dec ? dec.length : 0;
    var fmt = function (n) {
      return prefix + n.toLocaleString('es-ES', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
    };
    el.textContent = fmt(0);
    var run = function () {
      if (reduced) { el.textContent = fmt(target); return; }
      var t0 = performance.now(), dur = 1800;
      (function step(now) {
        var p = clamp((now - t0) / dur, 0, 1);
        var eased = 1 - Math.pow(1 - p, 4);
        el.textContent = fmt(target * eased);
        if (p < 1) requestAnimationFrame(step);
      })(t0);
    };
    if ('IntersectionObserver' in win) {
      var io = new IntersectionObserver(function (en) {
        if (en[0].isIntersecting) { run(); io.disconnect(); }
      }, { threshold: 0.4 });
      io.observe(el);
    } else run();
  });

  /* ═══ 6. MARQUESINAS ══════════════════════════════════════════════════════
     Se duplica el contenido para que el bucle no tenga costura. */
  $$('.kx-marquee').forEach(function (m) {
    var track = m.querySelector('.kx-marquee-track');
    if (!track || track.dataset.kxDup) return;
    track.dataset.kxDup = '1';
    var clone = track.innerHTML;
    track.innerHTML = clone + clone;
  });

  /* ═══ 7. NAVEGACIÓN, ACORDEÓN, FORMULARIO ═════════════════════════════════ */
  var burger = doc.querySelector('.kx-burger');
  var links = doc.querySelector('.kx-nav-links');
  if (burger && links) {
    burger.addEventListener('click', function () {
      burger.classList.toggle('kx-open');
      links.classList.toggle('kx-open');
    });
    $$('a', links).forEach(function (a) {
      a.addEventListener('click', function () {
        burger.classList.remove('kx-open'); links.classList.remove('kx-open');
      });
    });
  }

  $$('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (id.length < 2) return;
      var t = doc.querySelector(id);
      if (!t) return;
      e.preventDefault();
      t.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    });
  });

  $$('.kx-acc-item').forEach(function (item) {
    var q = item.querySelector('.kx-acc-q');
    if (!q) return;
    q.addEventListener('click', function () {
      var open = item.classList.contains('kx-open');
      var parent = item.closest('.kx-acc');
      if (parent) $$('.kx-acc-item', parent).forEach(function (o) { o.classList.remove('kx-open'); });
      item.classList.toggle('kx-open', !open);
    });
  });

  $$('.kx-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      // La captura de leads real la inyecta el servidor al publicar; aquí solo
      // damos respuesta visual si aún no hay endpoint.
      if (form.getAttribute('action')) return;
      e.preventDefault();
      var ok = form.querySelector('.kx-form-ok');
      if (ok) ok.classList.add('kx-show');
      form.reset();
    });
  });

  /* ═══ 8. CANVAS DE PARTÍCULAS ═════════════════════════════════════════════
     Varios "efectos" sobre un canvas 2D. Se pausan cuando salen de pantalla y
     se apagan si el usuario pide menos movimiento.                            */
  $$('canvas[data-fx]').forEach(function (canvas) {
    var mode = canvas.getAttribute('data-fx');
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var dpr = Math.min(win.devicePixelRatio || 1, 2);
    var W = 0, H = 0, running = true, visible = true, t = 0;
    var accent = getComputedStyle(canvas).getPropertyValue('--fx-color').trim() ||
                 getComputedStyle(doc.documentElement).getPropertyValue('--accent').trim() || '#6d6dff';

    function hexToRgb(h) {
      h = h.trim().replace('#', '');
      if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
      var n = parseInt(h, 16);
      return isNaN(n) ? [109, 109, 255] : [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }
    var rgb = hexToRgb(accent);
    var rgba = function (a) { return 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + a + ')'; };

    var parts = [];
    function sizeCanvas() {
      var r = canvas.getBoundingClientRect();
      W = Math.max(1, r.width); H = Math.max(1, r.height);
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      build();
    }

    function build() {
      parts = [];
      var n, i;
      if (mode === 'iris') {
        n = Math.round(Math.min(280, W / 4));
        for (i = 0; i < n; i++) {
          parts.push({ a: Math.random() * Math.PI * 2, r: 0.25 + Math.random() * 0.28, len: 0.05 + Math.random() * 0.32, sp: 0.0007 + Math.random() * 0.0022, w: 0.4 + Math.random() * 1.5 });
        }
      } else if (mode === 'constellation') {
        n = Math.round(Math.min(90, (W * H) / 16000));
        for (i = 0; i < n; i++) {
          parts.push({ x: Math.random() * W, y: Math.random() * H, vx: (Math.random() - 0.5) * 0.28, vy: (Math.random() - 0.5) * 0.28, r: 0.7 + Math.random() * 1.6 });
        }
      } else if (mode === 'flow') {
        n = Math.round(Math.min(420, W / 2.4));
        for (i = 0; i < n; i++) {
          parts.push({ x: Math.random() * W, y: Math.random() * H, sp: 0.4 + Math.random() * 1.5, o: 0.1 + Math.random() * 0.5, l: 8 + Math.random() * 40 });
        }
      } else if (mode === 'rain') {
        n = Math.round(Math.min(160, W / 8));
        for (i = 0; i < n; i++) {
          parts.push({ x: Math.random() * W, y: Math.random() * H, sp: 1.2 + Math.random() * 4, l: 10 + Math.random() * 26, o: 0.08 + Math.random() * 0.35 });
        }
      } else if (mode === 'mesh' || mode === 'blob' || mode === 'grid3d') {
        parts = [];
      }
    }

    /* — Iris: corona de rayos que respira. El "ojo" de las referencias. — */
    function drawIris() {
      var cx = W / 2, cy = H / 2, R = Math.min(W, H) / 2;
      var pulse = 1 + Math.sin(t * 0.0012) * 0.06;
      ctx.globalCompositeOperation = 'lighter';
      parts.forEach(function (p) {
        p.a += p.sp;
        var r0 = R * p.r * pulse;
        var r1 = r0 + R * p.len * (0.7 + Math.sin(t * 0.002 + p.a * 3) * 0.3);
        var g = ctx.createLinearGradient(cx + Math.cos(p.a) * r0, cy + Math.sin(p.a) * r0,
                                         cx + Math.cos(p.a) * r1, cy + Math.sin(p.a) * r1);
        g.addColorStop(0, rgba(0));
        g.addColorStop(0.35, rgba(0.55));
        g.addColorStop(1, rgba(0));
        ctx.strokeStyle = g; ctx.lineWidth = p.w;
        ctx.beginPath();
        ctx.moveTo(cx + Math.cos(p.a) * r0, cy + Math.sin(p.a) * r0);
        ctx.lineTo(cx + Math.cos(p.a) * r1, cy + Math.sin(p.a) * r1);
        ctx.stroke();
      });
      // Núcleo oscuro (la pupila)
      ctx.globalCompositeOperation = 'source-over';
      var core = ctx.createRadialGradient(cx, cy, 0, cx, cy, R * 0.27 * pulse);
      core.addColorStop(0, 'rgba(0,0,0,.96)');
      core.addColorStop(0.72, 'rgba(0,0,0,.85)');
      core.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = core;
      ctx.beginPath(); ctx.arc(cx, cy, R * 0.3 * pulse, 0, Math.PI * 2); ctx.fill();
    }

    /* — Malla: superficie 3D ondulante en alambre (la "cinta" de datos). — */
    function drawMesh() {
      var cols = 26, rows = 16;
      var cx = W / 2, cy = H / 2;
      ctx.lineWidth = 1;
      var pts = [];
      for (var j = 0; j <= rows; j++) {
        pts[j] = [];
        for (var i = 0; i <= cols; i++) {
          var u = i / cols - 0.5, v = j / rows - 0.5;
          var wave = Math.sin(u * 5 + t * 0.0011) * Math.cos(v * 4 - t * 0.0009) * 0.16
                   + Math.sin((u + v) * 7 + t * 0.0016) * 0.05;
          var depth = 1 / (1.5 + v * 1.1 + wave);
          pts[j][i] = {
            x: cx + u * W * 1.15 * depth,
            y: cy + (v * H * 0.85 + wave * H * 1.5) * depth,
            d: depth, w: wave
          };
        }
      }
      for (var jj = 0; jj <= rows; jj++) {
        for (var ii = 0; ii <= cols; ii++) {
          var p = pts[jj][ii];
          var alpha = clamp((p.w + 0.22) * 1.5, 0.04, 0.65) * clamp(p.d, 0.15, 1);
          if (ii < cols) {
            ctx.strokeStyle = rgba(alpha);
            ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(pts[jj][ii + 1].x, pts[jj][ii + 1].y); ctx.stroke();
          }
          if (jj < rows) {
            ctx.strokeStyle = rgba(alpha * 0.6);
            ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(pts[jj + 1][ii].x, pts[jj + 1][ii].y); ctx.stroke();
          }
        }
      }
    }

    /* — Constelación: puntos que se enlazan al acercarse. — */
    function drawConstellation() {
      parts.forEach(function (p) {
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > W) p.vx *= -1;
        if (p.y < 0 || p.y > H) p.vy *= -1;
        ctx.fillStyle = rgba(0.7);
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fill();
      });
      for (var i = 0; i < parts.length; i++) {
        for (var j = i + 1; j < parts.length; j++) {
          var dx = parts[i].x - parts[j].x, dy = parts[i].y - parts[j].y;
          var d2 = dx * dx + dy * dy;
          if (d2 < 18000) {
            ctx.strokeStyle = rgba(0.22 * (1 - d2 / 18000));
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(parts[i].x, parts[i].y); ctx.lineTo(parts[j].x, parts[j].y); ctx.stroke();
          }
        }
      }
    }

    /* — Flujo: líneas que recorren un campo de ruido (aurora de datos). — */
    function drawFlow() {
      ctx.globalCompositeOperation = 'lighter';
      parts.forEach(function (p) {
        var ang = (Math.sin(p.x * 0.004 + t * 0.0004) + Math.cos(p.y * 0.005 - t * 0.0003)) * Math.PI;
        var nx = p.x + Math.cos(ang) * p.sp * 2.2;
        var ny = p.y + Math.sin(ang) * p.sp * 2.2;
        ctx.strokeStyle = rgba(p.o * 0.5);
        ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(nx, ny); ctx.stroke();
        p.x = nx; p.y = ny;
        if (p.x < -20 || p.x > W + 20 || p.y < -20 || p.y > H + 20) {
          p.x = Math.random() * W; p.y = Math.random() * H;
        }
      });
    }

    /* — Lluvia digital: trazos verticales (ciberseguridad). — */
    function drawRain() {
      parts.forEach(function (p) {
        var g = ctx.createLinearGradient(p.x, p.y, p.x, p.y + p.l);
        g.addColorStop(0, rgba(0));
        g.addColorStop(1, rgba(p.o));
        ctx.strokeStyle = g; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(p.x, p.y + p.l); ctx.stroke();
        p.y += p.sp;
        if (p.y > H) { p.y = -p.l; p.x = Math.random() * W; }
      });
    }

    /* — Rejilla en perspectiva que avanza (retro/tech). — */
    function drawGrid3d() {
      var horizon = H * 0.42;
      ctx.lineWidth = 1;
      for (var i = 0; i < 22; i++) {
        var z = ((i + (t * 0.00022) % 1) / 22);
        var y = horizon + Math.pow(z, 2.4) * (H - horizon) * 1.6;
        if (y > H + 10) continue;
        ctx.strokeStyle = rgba(0.35 * (1 - z));
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke();
      }
      for (var k = -14; k <= 14; k++) {
        ctx.strokeStyle = rgba(0.18);
        ctx.beginPath();
        ctx.moveTo(W / 2 + k * 14, horizon);
        ctx.lineTo(W / 2 + k * (W / 9), H);
        ctx.stroke();
      }
    }

    /* — Blob: metabola que late (orgánico/beauty). — */
    function drawBlob() {
      var cx = W / 2, cy = H / 2, R = Math.min(W, H) * 0.3;
      for (var layer = 0; layer < 3; layer++) {
        ctx.beginPath();
        for (var a = 0; a <= Math.PI * 2 + 0.01; a += 0.06) {
          var wob = Math.sin(a * 3 + t * 0.0011 + layer) * 0.11
                  + Math.sin(a * 5 - t * 0.0007 + layer * 2) * 0.07
                  + Math.cos(a * 2 + t * 0.0015) * 0.05;
          var r = R * (1 + wob) * (1 - layer * 0.14);
          var x = cx + Math.cos(a) * r, y = cy + Math.sin(a) * r;
          a === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        }
        ctx.closePath();
        var g = ctx.createRadialGradient(cx, cy, R * 0.1, cx, cy, R * 1.3);
        g.addColorStop(0, rgba(0.30 - layer * 0.07));
        g.addColorStop(1, rgba(0));
        ctx.fillStyle = g; ctx.fill();
        ctx.strokeStyle = rgba(0.30 - layer * 0.08); ctx.lineWidth = 1; ctx.stroke();
      }
    }

    var painters = { iris: drawIris, mesh: drawMesh, constellation: drawConstellation,
                     flow: drawFlow, rain: drawRain, grid3d: drawGrid3d, blob: drawBlob };
    var paint = painters[mode] || drawConstellation;

    function loop() {
      if (!running) return;
      if (visible) {
        // 'flow' deja estela: se limpia con una capa translúcida en vez de borrar.
        if (mode === 'flow') {
          ctx.globalCompositeOperation = 'source-over';
          ctx.fillStyle = 'rgba(0,0,0,.055)';
          ctx.fillRect(0, 0, W, H);
        } else {
          ctx.clearRect(0, 0, W, H);
        }
        paint();
        ctx.globalCompositeOperation = 'source-over';
        t += 16;
      }
      requestAnimationFrame(loop);
    }

    sizeCanvas();
    win.addEventListener('resize', sizeCanvas, { passive: true });

    if ('IntersectionObserver' in win) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }, { threshold: 0 }).observe(canvas);
    }

    if (reduced) { paint(); }  // un fotograma fijo y nada más
    else loop();
  });

  /* ═══ 9. TIENDA (opcional) ════════════════════════════════════════════════
     Mismo contrato que el motor clásico para que la IA pueda añadir productos.  */
  var cart = [];
  var cartCount = doc.getElementById('cart-count');
  var cartItems = doc.getElementById('cart-items');
  var cartTotal = doc.getElementById('cart-total');
  var drawer = doc.getElementById('cart-drawer');
  function renderCart() {
    if (cartCount) cartCount.textContent = cart.reduce(function (s, i) { return s + i.qty; }, 0);
    if (cartItems) {
      cartItems.innerHTML = cart.length ? '' : '<p style="opacity:.5">Tu carrito está vacío.</p>';
      cart.forEach(function (it, idx) {
        var row = doc.createElement('div');
        row.className = 'kx-cart-row';
        row.innerHTML = '<span>' + it.name + ' ×' + it.qty + '</span><b>' +
          (it.price * it.qty).toFixed(2) + ' €</b> <button aria-label="Quitar">×</button>';
        row.querySelector('button').addEventListener('click', function () { cart.splice(idx, 1); renderCart(); });
        cartItems.appendChild(row);
      });
    }
    if (cartTotal) cartTotal.textContent = cart.reduce(function (s, i) { return s + i.price * i.qty; }, 0).toFixed(2) + ' €';
  }
  $$('.kx-product [data-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var p = btn.closest('.kx-product');
      var name = p.getAttribute('data-name') || 'Producto';
      var price = parseFloat(p.getAttribute('data-price')) || 0;
      var found = cart.filter(function (i) { return i.name === name; })[0];
      found ? found.qty++ : cart.push({ name: name, price: price, qty: 1 });
      renderCart();
      if (drawer) drawer.classList.add('kx-open');
    });
  });
  var cartToggle = doc.getElementById('cart-toggle');
  var cartClose = doc.getElementById('cart-close');
  if (cartToggle && drawer) cartToggle.addEventListener('click', function () { drawer.classList.toggle('kx-open'); });
  if (cartClose && drawer) cartClose.addEventListener('click', function () { drawer.classList.remove('kx-open'); });
  if (cart.length || cartItems) renderCart();

})();

/* ═══════════════════════════════════════════════════════════════════════════
   GESTOS — motores de movimiento del hero.

   Al analizar las referencias del sector se ve que cada plantilla no repite el
   mismo efecto: tiene una IDEA DE MOVIMIENTO propia. Aquí están programados
   los gestos que faltaban, cada uno independiente del material (sirve una
   foto, un vídeo o una escena 3D):

     [data-scenes]  carrusel de escenas completas que se suceden con corte
     [data-swap]    el sujeto y el color de marca rotan a la vez
     [data-dolly]   travelling lateral continuo sobre la escena
     [data-cards]   sujeto quieto y tarjetas que entran escalonadas, en bucle

   Todos respetan "reducir movimiento" y se detienen fuera de pantalla.
   ═══════════════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';
  var doc = document, win = window;
  var reduced = win.matchMedia && win.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var all = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };

  // Solo corre mientras el bloque está a la vista: un carrusel oculto seguiría
  // repintando y gastando batería sin que nadie lo esté viendo.
  function whileVisible(el, tick, ms) {
    var timer = null;
    var start = function () { if (!timer) { timer = setInterval(tick, ms); } };
    var stop = function () { if (timer) { clearInterval(timer); timer = null; } };
    if ('IntersectionObserver' in win) {
      new IntersectionObserver(function (e) {
        if (e[0].isIntersecting) { start(); } else { stop(); }
      }, { threshold: 0 }).observe(el);
    } else { start(); }
  }

  /* ── 1. Carrusel de escenas ────────────────────────────────────────────
     Cada hijo .kg-scene es una escena completa (fondo y titular propios) y se
     suceden con un corte suave. Es el hero de tipo "Yacht Club": no repite un
     bucle, cuenta varias cosas seguidas. */
  all('[data-scenes]').forEach(function (box) {
    var scenes = all('.kg-scene', box);
    if (scenes.length < 2) { return; }
    var i = 0;
    scenes[0].classList.add('kg-on');
    if (reduced) { return; }
    var every = parseInt(box.getAttribute('data-scenes'), 10) || 4200;
    whileVisible(box, function () {
      scenes[i].classList.remove('kg-on');
      i = (i + 1) % scenes.length;
      scenes[i].classList.add('kg-on');
    }, every);
  });

  /* ── 2. Sujeto y color de marca ────────────────────────────────────────
     Rota a la vez la figura y la paleta. Que el fondo cambie CON el sujeto es
     lo que hace que se lea como una marca y no como un pase de diapositivas. */
  all('[data-swap]').forEach(function (box) {
    var items = all('.kg-swap-item', box);
    if (!items.length) { return; }
    var tones = (box.getAttribute('data-swap-tones') || '').split(',');
    var i = 0;
    var apply = function () {
      items.forEach(function (el, k) { el.classList.toggle('kg-on', k === i); });
      var t = (tones[i] || '').trim();
      if (t) { box.style.setProperty('--tone', t); }
    };
    apply();
    if (reduced) { return; }
    var every = parseInt(box.getAttribute('data-swap'), 10) || 2600;
    whileVisible(box, function () { i = (i + 1) % items.length; apply(); }, every);
  });

  /* ── 3. Travelling lateral ─────────────────────────────────────────────
     La cámara recorre la escena de lado mientras el titular queda clavado. Se
     resuelve con una capa más ancha que la ventana desplazándose. */
  all('[data-dolly]').forEach(function (box) {
    var layer = box.querySelector('.kg-dolly-layer');
    if (!layer || reduced) { return; }
    var secs = parseFloat(box.getAttribute('data-dolly')) || 26;
    layer.style.animation = 'kg-dolly ' + secs + 's ease-in-out infinite alternate';
    if ('IntersectionObserver' in win) {
      new IntersectionObserver(function (e) {
        layer.style.animationPlayState = e[0].isIntersecting ? 'running' : 'paused';
      }, { threshold: 0 }).observe(box);
    }
  });

  /* ── 4. Tarjetas escalonadas en bucle ──────────────────────────────────
     El sujeto no se mueve: lo que entra por tandas es la interfaz. Se repite
     cada cierto tiempo para que quien llegue tarde también lo vea. */
  all('[data-cards]').forEach(function (box) {
    var cards = all('.kg-card', box);
    if (!cards.length) { return; }
    if (reduced) {
      cards.forEach(function (c) { c.classList.add('kg-on'); });
      return;
    }
    var every = parseInt(box.getAttribute('data-cards'), 10) || 5200;
    var run = function () {
      cards.forEach(function (c) { c.classList.remove('kg-on'); });
      cards.forEach(function (c, k) {
        setTimeout(function () { c.classList.add('kg-on'); }, 220 + k * 260);
      });
    };
    run();
    whileVisible(box, run, every);
  });
})();

}catch(e){}
try{
/* ═══════════════════════════════════════════════════════════════════════════
   KX3D — renderizador 3D por raymarching para los heroes de Pagepolis.

   POR QUÉ EXISTE. Las galerías de referencia (motionsites y similares) ponen
   detrás del titular renders 3D hechos en Blender/C4D y servidos como vídeo.
   Ese tipo de asset no se puede descargar de ningún banco libre ni alojar sin
   coste, así que aquí se FABRICA: un shader recorre la escena por píxel en la
   GPU y calcula geometría, normales, especular, fresnel e iridiscencia. El
   resultado es un objeto 3D con luz de estudio, a 60 fps, de ~7 KB y sin una
   sola petición de red.

   Ventaja sobre el vídeo: no caduca, no hay 404, pesa nada y cada plantilla
   puede tener su propia forma y paleta cambiando dos atributos.

   Uso:
     <canvas data-k3d="blob|torus|crystal|ribbon|orb"
             data-k3d-a="#ff3d9a" data-k3d-b="#3df0ff" data-k3d-bg="#0a0614"
             data-k3d-speed="1"></canvas>

   OJO: la miniatura de la galería se renderiza sin JavaScript, así que el
   canvas queda vacío ahí. Cada plantilla debe poner un degradado CSS de
   respaldo bajo el canvas (ver .k3d-fallback en kinetic.css).            */

(function () {
  'use strict';

  var VERT = [
    'attribute vec2 p;',
    'void main(){ gl_Position = vec4(p, 0.0, 1.0); }'
  ].join('\n');

  /* Cada escena aporta el cuerpo de map(): la función de distancia con signo
     que define la forma. Todo lo demás (luz, cámara, color) es común. */
  var SCENES = {
    // Metabola orgánica: tres esferas que se funden y laten.
    blob: [
      'vec3 q = rot(p, t*0.22, t*0.15);',
      'float d = sph(q, 1.0);',
      'd = smin(d, sph(q - vec3(sin(t*0.8)*0.72, cos(t*0.6)*0.6, sin(t*0.4)*0.3), 0.55), 0.5);',
      'd = smin(d, sph(q + vec3(cos(t*0.5)*0.62, sin(t*0.9)*0.5, 0.28), 0.5), 0.5);',
      'd += 0.035 * sin(6.0*q.x + t) * sin(6.0*q.y) * sin(6.0*q.z);',
      'return d;'
    ].join('\n'),
    // Toro de cristal grueso, girando sobre dos ejes.
    // El giro en Y va corto a propósito: si se pasa, el toro se pone de canto
    // y deja de leerse como un anillo.
    torus: [
      'vec3 q = rot(p, t*0.3, 0.42 + sin(t*0.35)*0.16);',
      'float d = tor(q, vec2(1.0, 0.34));',
      'd += 0.022 * sin(14.0*atan(q.z,q.x) + t*1.4);',
      'return d;'
    ].join('\n'),
    // Cáscara facetada: esfera vaciada y cortada por planos.
    crystal: [
      'vec3 q = rot(p, t*0.24, t*0.31);',
      'float d = sph(q, 1.06);',
      'd = max(d, -sph(q, 0.86));',
      'd = max(d, -box(q - vec3(0.0, 0.0, 0.9), vec3(1.4)));',
      'd = min(d, sph(q, 0.42));',
      'return d;'
    ].join('\n'),
    // Cinta retorcida: la "tela" de las referencias de datos.
    ribbon: [
      'vec3 q = rot(p, t*0.18, t*0.26);',
      'float a = atan(q.z, q.x);',
      'q.xz = vec2(length(q.xz) - 1.0, q.y);',
      'q.xy = m2(a*1.5 + t*0.4) * q.xy;',
      'float d = box(vec3(q.x, q.y, 0.0), vec3(0.42, 0.055, 0.42)) - 0.03;',
      'return d;'
    ].join('\n'),
    // Esfera lisa con relieve suave: la más sobria.
    orb: [
      'vec3 q = rot(p, t*0.16, t*0.11);',
      'float d = sph(q, 1.1);',
      'd += 0.05 * sin(4.0*q.x + t*0.7) * sin(4.0*q.y + t*0.5) * sin(4.0*q.z);',
      'return d;'
    ].join('\n')
  };

  function frag(sceneBody) {
    return [
      'precision highp float;',
      'uniform vec2 u_res; uniform float u_time; uniform vec2 u_mouse;',
      'uniform vec3 u_a, u_b, u_bg;',

      'mat2 m2(float a){ float s=sin(a), c=cos(a); return mat2(c,-s,s,c); }',
      'vec3 rot(vec3 p, float x, float y){ p.xz = m2(x)*p.xz; p.xy = m2(y)*p.xy; return p; }',
      'float sph(vec3 p, float r){ return length(p) - r; }',
      'float tor(vec3 p, vec2 t){ vec2 q = vec2(length(p.xz)-t.x, p.y); return length(q)-t.y; }',
      'float box(vec3 p, vec3 b){ vec3 d = abs(p)-b; return length(max(d,0.0)) + min(max(d.x,max(d.y,d.z)),0.0); }',
      // Unión suave: es lo que funde las formas sin costura, como el metal líquido
      'float smin(float a, float b, float k){ float h = clamp(0.5+0.5*(b-a)/k, 0.0, 1.0); return mix(b,a,h) - k*h*(1.0-h); }',

      'float map(vec3 p){ float t = u_time; ' + sceneBody + ' }',

      // Normal por gradiente: sin ella no hay iluminación creíble
      'vec3 nrm(vec3 p){ vec2 e = vec2(0.0015, 0.0);',
      '  return normalize(vec3(map(p+e.xyy)-map(p-e.xyy), map(p+e.yxy)-map(p-e.yxy), map(p+e.yyx)-map(p-e.yyx))); }',

      'float march(vec3 ro, vec3 rd){ float d = 0.0;',
      '  for(int i=0;i<90;i++){ float s = map(ro + rd*d); if(s < 0.0012 || d > 9.0) break; d += s*0.82; }',
      '  return d; }',

      // Paleta cosenoidal: da el tornasol de aceite sin texturas
      'vec3 pal(float x){ return 0.5 + 0.5*cos(6.28318*(x + vec3(0.0, 0.33, 0.67))); }',

      // Oclusión ambiental. Sin esto la forma se ve de plastilina: es la
      // suciedad en los recovecos lo que da sensación de volumen real.
      'float ao(vec3 p, vec3 n){ float o = 0.0, s = 1.0;',
      '  for(int i=0;i<5;i++){ float h = 0.02 + 0.13*float(i);',
      '    o += (h - map(p + n*h)) * s; s *= 0.72; }',
      '  return clamp(1.0 - 1.7*o, 0.0, 1.0); }',

      // Entorno de estudio procedural: un softbox arriba y relleno frío abajo.
      // Reflejarlo es lo que convierte la superficie en material y no en color.
      'vec3 env(vec3 r){ float y = r.y*0.5 + 0.5;',
      '  vec3 c = mix(u_bg*0.5, vec3(0.85), smoothstep(0.32, 1.0, y));',
      '  c += vec3(1.0) * smoothstep(0.88, 1.0, y) * 1.1;',      // franja del softbox
      '  c += u_b * smoothstep(0.34, 0.0, y) * 0.4;',            // rebote inferior
      '  c += u_a * smoothstep(0.5, 0.72, y) * 0.15;',
      '  return c; }',

      // Mapeo tonal ACES: comprime las altas luces en vez de quemarlas, que es
      // la diferencia entre "captura de pantalla" y "render".
      'vec3 aces(vec3 x){ return clamp((x*(2.51*x + 0.03)) / (x*(2.43*x + 0.59) + 0.14), 0.0, 1.0); }',

      'void main(){',
      '  vec2 uv = (gl_FragCoord.xy*2.0 - u_res) / u_res.y;',
      '  vec2 mo = (u_mouse - 0.5) * 0.35;',
      '  vec3 ro = vec3(mo.x*1.2, mo.y*0.9, 3.35);',
      '  vec3 rd = normalize(vec3(uv, -1.75));',
      '  float d = march(ro, rd);',
      '  vec3 col;',
      '  if(d < 9.0){',
      '    vec3 p = ro + rd*d;',
      '    vec3 n = nrm(p);',
      '    vec3 l1 = normalize(vec3(0.75, 0.95, 0.55));',   // luz principal
      '    vec3 l2 = normalize(vec3(-0.7, -0.25, 0.45));',  // relleno frío
      '    float dif = clamp(dot(n, l1), 0.0, 1.0);',
      '    float fil = clamp(dot(n, l2), 0.0, 1.0);',
      '    float fre = pow(1.0 - clamp(dot(n, -rd), 0.0, 1.0), 3.0);',
      '    vec3 h = normalize(l1 - rd);',
      '    float spe = pow(clamp(dot(n, h), 0.0, 1.0), 96.0);',
      '    float occ = ao(p, n);',
      '    vec3 refl = env(reflect(rd, n));',
      '    vec3 irid = pal(fre*1.1 + dot(n, rd)*0.35 + u_time*0.04);',
      // Material metálico: la difusa se queda en un tinte de base y el peso lo
      // lleva el entorno reflejado. Es lo que separa "cera" de "cromado", y el
      // registro que usan los renders de las referencias.
      '    vec3 base = mix(u_a*0.16, u_a*0.55, dif) * occ;',
      '    base += u_b * fil * 0.22 * occ;',
      '    float metal = 0.78;',
      '    col  = mix(base, refl * mix(u_a, vec3(1.0), 0.55), metal);',
      '    col *= mix(1.0, occ, 0.55);',                // el reflejo también se ocluye
      '    col  = mix(col, irid, fre*0.5);',            // tornasol en el borde
      '    col += vec3(spe) * 3.0;',                    // reflejo del softbox
      '    col = mix(col, u_bg, clamp((d-2.6)*0.34, 0.0, 0.55));',  // niebla
      '  } else {',
      '    float g = smoothstep(-1.25, 1.25, uv.y);',
      '    col = mix(u_bg*0.5, u_bg*1.45, g);',
      '    col += pow(max(0.0, 1.0 - length(uv*vec2(0.7,1.0))), 3.0) * u_a * 0.16;',
      '  }',
      '  col = aces(col * 1.05);',                      // mapeo tonal
      '  col = pow(max(col, 0.0), vec3(0.4545));',      // corrección gamma
      '  gl_FragColor = vec4(col, 1.0);',
      '}'
    ].join('\n');
  }

  function hex(h, fb) {
    h = (h || '').trim().replace('#', '');
    if (h.length === 3) h = h[0]+h[0]+h[1]+h[1]+h[2]+h[2];
    var n = parseInt(h, 16);
    if (h.length !== 6 || isNaN(n)) return fb;
    return [((n>>16)&255)/255, ((n>>8)&255)/255, (n&255)/255];
  }

  function compile(gl, type, src) {
    var s = gl.createShader(type);
    gl.shaderSource(s, src); gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
      // Se traga el error a propósito: si el shader no compila en un equipo
      // viejo, la plantilla debe seguir viéndose con su degradado de respaldo.
      return null;
    }
    return s;
  }

  function init(canvas) {
    var mode = canvas.getAttribute('data-k3d') || 'blob';
    var body = SCENES[mode] || SCENES.blob;
    var gl = canvas.getContext('webgl', { antialias: false, alpha: false, powerPreference: 'low-power' })
          || canvas.getContext('experimental-webgl');
    if (!gl) return false;

    var vs = compile(gl, gl.VERTEX_SHADER, VERT);
    var fs = compile(gl, gl.FRAGMENT_SHADER, frag(body));
    if (!vs || !fs) return false;
    var pr = gl.createProgram();
    gl.attachShader(pr, vs); gl.attachShader(pr, fs); gl.linkProgram(pr);
    if (!gl.getProgramParameter(pr, gl.LINK_STATUS)) return false;
    gl.useProgram(pr);

    var buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 3,-1, -1,3]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(pr, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

    var uRes = gl.getUniformLocation(pr, 'u_res'),
        uTime = gl.getUniformLocation(pr, 'u_time'),
        uMouse = gl.getUniformLocation(pr, 'u_mouse'),
        uA = gl.getUniformLocation(pr, 'u_a'),
        uB = gl.getUniformLocation(pr, 'u_b'),
        uBg = gl.getUniformLocation(pr, 'u_bg');

    gl.uniform3fv(uA, hex(canvas.getAttribute('data-k3d-a'), [1.0, 0.24, 0.60]));
    gl.uniform3fv(uB, hex(canvas.getAttribute('data-k3d-b'), [0.24, 0.94, 1.0]));
    gl.uniform3fv(uBg, hex(canvas.getAttribute('data-k3d-bg'), [0.04, 0.03, 0.08]));

    var speed = parseFloat(canvas.getAttribute('data-k3d-speed')) || 1;
    var mouse = [0.5, 0.5], mTarget = [0.5, 0.5];
    var dpr = Math.min(window.devicePixelRatio || 1, 1.6);
    var visible = true, t0 = performance.now();

    function size() {
      var r = canvas.getBoundingClientRect();
      var w = Math.max(1, Math.round(r.width * dpr)), h = Math.max(1, Math.round(r.height * dpr));
      if (canvas.width !== w || canvas.height !== h) {
        canvas.width = w; canvas.height = h;
        gl.viewport(0, 0, w, h);
      }
      gl.uniform2f(uRes, canvas.width, canvas.height);
    }

    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function loop(now) {
      if (visible) {
        size();
        // El ratón entra con retardo para que el giro se sienta pesado
        mouse[0] += (mTarget[0] - mouse[0]) * 0.06;
        mouse[1] += (mTarget[1] - mouse[1]) * 0.06;
        gl.uniform2f(uMouse, mouse[0], mouse[1]);
        gl.uniform1f(uTime, (now - t0) * 0.001 * speed);
        gl.drawArrays(gl.TRIANGLES, 0, 3);
      }
      if (!reduced) requestAnimationFrame(loop);
    }

    window.addEventListener('mousemove', function (e) {
      mTarget[0] = e.clientX / window.innerWidth;
      mTarget[1] = 1 - e.clientY / window.innerHeight;
    }, { passive: true });
    window.addEventListener('resize', size, { passive: true });

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; },
                               { threshold: 0 }).observe(canvas);
    }

    size();
    if (reduced) { gl.uniform1f(uTime, 2.0); gl.uniform2f(uMouse, 0.5, 0.5); gl.drawArrays(gl.TRIANGLES, 0, 3); }
    else requestAnimationFrame(loop);

    canvas.classList.add('k3d-live');   // el CSS oculta el respaldo al arrancar
    return true;
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('canvas[data-k3d]'), function (c) {
      if (c.dataset.k3dOn) return;
      c.dataset.k3dOn = '1';
      try { init(c); } catch (e) { /* respaldo CSS visible */ }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();

}catch(e){}

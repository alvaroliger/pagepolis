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

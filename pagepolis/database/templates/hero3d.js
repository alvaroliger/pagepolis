/* PAGEPOLIS-HERO3D-ENGINE — no borrar este marcador (evita que se duplique el
   motor si la web se vuelve a generar o editar). ───────────────────────────
   Hero 3D de Pagepolis — fondo WebGL con figuras low-poly flotantes, reactivo
   al ratón y al scroll. Cero dependencias (sin Three.js ni CDNs).
   Uso: añade <canvas class="hero3d-canvas" data-hero3d></canvas> dentro de un
   contenedor con position:relative (p.ej. .hero). Se colorea solo con
   --brand (o --brand-2) del sistema de diseño, así que encaja con cualquier
   plantilla. Si WebGL no está disponible, o el usuario prefiere menos
   movimiento, se degrada solo sin romper nada. */
(function () {
  'use strict';

  function colorToRgb(cssColor) {
    try {
      var el = document.createElement('div');
      el.style.color = cssColor;
      el.style.display = 'none';
      document.body.appendChild(el);
      var computed = getComputedStyle(el).color;
      document.body.removeChild(el);
      var m = computed.match(/[\d.]+/g);
      if (m && m.length >= 3) {
        return [parseFloat(m[0]) / 255, parseFloat(m[1]) / 255, parseFloat(m[2]) / 255];
      }
    } catch (e) {}
    return [0.486, 0.231, 0.929];
  }

  function brandColor() {
    var raw = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim();
    return colorToRgb(raw || '#7c3aed');
  }

  /* ── Nivel de gama del dispositivo (CPU/RAM) para no reventar FPS/batería
     en móviles modestos. hardwareConcurrency y deviceMemory son señales
     imperfectas pero suficientes: 'veryLow' desactiva el hero3D del todo,
     'low' lo mantiene pero con menos figuras, sin antialias y a 1x DPR. ── */
  function deviceTier() {
    var cores = navigator.hardwareConcurrency || 8;
    var mem = navigator.deviceMemory; // undefined en iOS/Firefox: se ignora
    if (cores <= 2 || (mem !== undefined && mem <= 2)) return 'veryLow';
    if (cores <= 4 || (mem !== undefined && mem <= 4)) return 'low';
    return 'normal';
  }

  /* ── Álgebra mínima de matrices 4x4 (column-major, Float32Array) ── */
  var Mat4 = {
    identity: function () { return new Float32Array([1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,0,1]); },
    multiply: function (a, b) {
      var o = new Float32Array(16);
      for (var i = 0; i < 4; i++) {
        for (var j = 0; j < 4; j++) {
          var s = 0;
          for (var k = 0; k < 4; k++) s += a[k * 4 + j] * b[i * 4 + k];
          o[i * 4 + j] = s;
        }
      }
      return o;
    },
    perspective: function (fovy, aspect, near, far) {
      var f = 1 / Math.tan(fovy / 2), nf = 1 / (near - far);
      var o = new Float32Array(16);
      o[0] = f / aspect; o[5] = f; o[10] = (far + near) * nf; o[11] = -1;
      o[14] = 2 * far * near * nf;
      return o;
    },
    translate: function (x, y, z) {
      var o = Mat4.identity(); o[12] = x; o[13] = y; o[14] = z; return o;
    },
    rotateX: function (r) {
      var c = Math.cos(r), s = Math.sin(r), o = Mat4.identity();
      o[5] = c; o[6] = s; o[9] = -s; o[10] = c; return o;
    },
    rotateY: function (r) {
      var c = Math.cos(r), s = Math.sin(r), o = Mat4.identity();
      o[0] = c; o[2] = -s; o[8] = s; o[10] = c; return o;
    },
    scale: function (s) {
      var o = Mat4.identity(); o[0] = s; o[5] = s; o[10] = s; return o;
    }
  };

  /* ── Icosaedro low-poly con shading plano (normales por cara) ── */
  function buildIcosahedron() {
    var t = (1 + Math.sqrt(5)) / 2;
    var raw = [
      [-1, t, 0], [1, t, 0], [-1, -t, 0], [1, -t, 0],
      [0, -1, t], [0, 1, t], [0, -1, -t], [0, 1, -t],
      [t, 0, -1], [t, 0, 1], [-t, 0, -1], [-t, 0, 1]
    ].map(function (v) {
      var len = Math.hypot(v[0], v[1], v[2]);
      return [v[0] / len, v[1] / len, v[2] / len];
    });
    var faces = [
      [0,11,5],[0,5,1],[0,1,7],[0,7,10],[0,10,11],
      [1,5,9],[5,11,4],[11,10,2],[10,7,6],[7,1,8],
      [3,9,4],[3,4,2],[3,2,6],[3,6,8],[3,8,9],
      [4,9,5],[2,4,11],[6,2,10],[8,6,7],[9,8,1]
    ];
    var positions = [], normals = [];
    faces.forEach(function (f) {
      var a = raw[f[0]], b = raw[f[1]], c = raw[f[2]];
      var ux = b[0] - a[0], uy = b[1] - a[1], uz = b[2] - a[2];
      var vx = c[0] - a[0], vy = c[1] - a[1], vz = c[2] - a[2];
      var nx = uy * vz - uz * vy, ny = uz * vx - ux * vz, nz = ux * vy - uy * vx;
      var nl = Math.hypot(nx, ny, nz) || 1;
      nx /= nl; ny /= nl; nz /= nl;
      var cx = (a[0] + b[0] + c[0]) / 3, cy = (a[1] + b[1] + c[1]) / 3, cz = (a[2] + b[2] + c[2]) / 3;
      if (nx * cx + ny * cy + nz * cz < 0) { nx = -nx; ny = -ny; nz = -nz; }
      [a, b, c].forEach(function (p) {
        positions.push(p[0], p[1], p[2]);
        normals.push(nx, ny, nz);
      });
    });
    return { positions: new Float32Array(positions), normals: new Float32Array(normals), count: positions.length / 3 };
  }

  var VERT_SRC = [
    'attribute vec3 aPosition;',
    'attribute vec3 aNormal;',
    'uniform mat4 uModel;',
    'uniform mat4 uView;',
    'uniform mat4 uProj;',
    'varying vec3 vNormal;',
    'void main(){',
    '  vNormal = mat3(uModel) * aNormal;',
    '  gl_Position = uProj * uView * uModel * vec4(aPosition, 1.0);',
    '}'
  ].join('\n');

  var FRAG_SRC = [
    'precision mediump float;',
    'varying vec3 vNormal;',
    'uniform vec3 uColor;',
    'void main(){',
    '  vec3 n = normalize(vNormal);',
    '  float diff = max(dot(n, normalize(vec3(0.4, 0.7, 0.6))), 0.0);',
    '  float rim = pow(1.0 - max(dot(n, vec3(0.0, 0.0, 1.0)), 0.0), 2.2);',
    '  vec3 col = uColor * (0.32 + 0.68 * diff) + rim * uColor * 0.55;',
    '  gl_FragColor = vec4(col, 0.85);',
    '}'
  ].join('\n');

  function compile(gl, type, src) {
    var sh = gl.createShader(type);
    gl.shaderSource(sh, src);
    gl.compileShader(sh);
    if (!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) {
      throw new Error(gl.getShaderInfoLog(sh) || 'shader compile error');
    }
    return sh;
  }

  function setupScene(canvas, tier) {
    var ctxOpts = { alpha: true, antialias: tier !== 'low' };
    var gl = canvas.getContext('webgl', ctxOpts) || canvas.getContext('experimental-webgl', ctxOpts);
    if (!gl) { canvas.style.display = 'none'; return null; }

    var program = gl.createProgram();
    gl.attachShader(program, compile(gl, gl.VERTEX_SHADER, VERT_SRC));
    gl.attachShader(program, compile(gl, gl.FRAGMENT_SHADER, FRAG_SRC));
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
      throw new Error(gl.getProgramInfoLog(program) || 'program link error');
    }
    gl.useProgram(program);

    var geo = buildIcosahedron();
    var posBuf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
    gl.bufferData(gl.ARRAY_BUFFER, geo.positions, gl.STATIC_DRAW);
    var aPosition = gl.getAttribLocation(program, 'aPosition');
    gl.enableVertexAttribArray(aPosition);
    gl.vertexAttribPointer(aPosition, 3, gl.FLOAT, false, 0, 0);

    var normBuf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, normBuf);
    gl.bufferData(gl.ARRAY_BUFFER, geo.normals, gl.STATIC_DRAW);
    var aNormal = gl.getAttribLocation(program, 'aNormal');
    gl.enableVertexAttribArray(aNormal);
    gl.vertexAttribPointer(aNormal, 3, gl.FLOAT, false, 0, 0);

    gl.enable(gl.BLEND);
    gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);
    gl.enable(gl.DEPTH_TEST);
    gl.clearColor(0, 0, 0, 0);

    var uModel = gl.getUniformLocation(program, 'uModel');
    var uView = gl.getUniformLocation(program, 'uView');
    var uProj = gl.getUniformLocation(program, 'uProj');
    var uColor = gl.getUniformLocation(program, 'uColor');

    var color = brandColor();
    var count = tier === 'low' ? (3 + Math.round(Math.random())) : (6 + Math.round(Math.random() * 2));
    var shapes = [];
    for (var i = 0; i < count; i++) {
      shapes.push({
        x: (Math.random() * 2 - 1) * 3.6,
        y: (Math.random() * 2 - 1) * 2.2,
        z: -6 - Math.random() * 9,
        scale: 0.45 + Math.random() * 0.85,
        rx: Math.random() * Math.PI,
        ry: Math.random() * Math.PI,
        speedX: (Math.random() * 0.4 + 0.08) * (Math.random() < 0.5 ? -1 : 1),
        speedY: (Math.random() * 0.4 + 0.08) * (Math.random() < 0.5 ? -1 : 1),
        bobPhase: Math.random() * Math.PI * 2
      });
    }

    return { gl: gl, program: program, uModel: uModel, uView: uView, uProj: uProj, uColor: uColor, color: color, shapes: shapes, vertexCount: geo.count };
  }

  function initCanvas(canvas) {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var tier = deviceTier();
    if (tier === 'veryLow') { canvas.style.display = 'none'; return; }
    var scene;
    try {
      scene = setupScene(canvas, tier);
    } catch (e) {
      canvas.style.display = 'none';
      return;
    }
    if (!scene) return;

    var gl = scene.gl;
    var pointer = { x: 0, y: 0 }, pointerTarget = { x: 0, y: 0 };
    var running = false, rafId = null, t0 = null;

    function resize() {
      var dpr = Math.min(window.devicePixelRatio || 1, tier === 'low' ? 1 : 1.75);
      var w = canvas.clientWidth || canvas.parentElement.clientWidth || 300;
      var h = canvas.clientHeight || canvas.parentElement.clientHeight || 300;
      var pw = Math.max(1, Math.round(w * dpr)), ph = Math.max(1, Math.round(h * dpr));
      if (canvas.width !== pw || canvas.height !== ph) {
        canvas.width = pw; canvas.height = ph;
        gl.viewport(0, 0, pw, ph);
      }
    }

    function onPointerMove(e) {
      var rect = canvas.getBoundingClientRect();
      var cx = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
      var cy = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
      pointerTarget.x = (cx / (rect.width || 1)) * 2 - 1;
      pointerTarget.y = (cy / (rect.height || 1)) * 2 - 1;
    }
    window.addEventListener('mousemove', onPointerMove, { passive: true });
    window.addEventListener('touchmove', onPointerMove, { passive: true });

    function render(time) {
      if (!running) return;
      if (t0 === null) t0 = time;
      var elapsed = (time - t0) / 1000;

      pointer.x += (pointerTarget.x - pointer.x) * 0.04;
      pointer.y += (pointerTarget.y - pointer.y) * 0.04;

      resize();
      gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);

      var aspect = canvas.width / canvas.height || 1;
      var proj = Mat4.perspective(Math.PI / 4, aspect, 0.1, 100);
      var view = Mat4.translate(0, 0, 0);
      gl.uniformMatrix4fv(scene.uProj, false, proj);
      gl.uniformMatrix4fv(scene.uView, false, view);
      gl.uniform3fv(scene.uColor, scene.color);

      scene.shapes.forEach(function (s) {
        var bob = Math.sin(elapsed * 0.6 + s.bobPhase) * 0.25;
        var model = Mat4.translate(s.x + pointer.x * 0.5, s.y + bob - pointer.y * 0.3, s.z);
        model = Mat4.multiply(model, Mat4.rotateY(s.ry + elapsed * s.speedY));
        model = Mat4.multiply(model, Mat4.rotateX(s.rx + elapsed * s.speedX));
        model = Mat4.multiply(model, Mat4.scale(s.scale));
        gl.uniformMatrix4fv(scene.uModel, false, model);
        gl.drawArrays(gl.TRIANGLES, 0, scene.vertexCount);
      });

      rafId = requestAnimationFrame(render);
    }

    function start() {
      if (running) return;
      running = true;
      t0 = null;
      rafId = requestAnimationFrame(render);
    }
    function stop() {
      running = false;
      if (rafId) cancelAnimationFrame(rafId);
    }

    resize();

    if (reduceMotion) {
      gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
      var aspect0 = canvas.width / canvas.height || 1;
      gl.uniformMatrix4fv(scene.uProj, false, Mat4.perspective(Math.PI / 4, aspect0, 0.1, 100));
      gl.uniformMatrix4fv(scene.uView, false, Mat4.translate(0, 0, 0));
      gl.uniform3fv(scene.uColor, scene.color);
      scene.shapes.forEach(function (s) {
        var model = Mat4.multiply(Mat4.translate(s.x, s.y, s.z), Mat4.multiply(Mat4.rotateY(s.ry), Mat4.multiply(Mat4.rotateX(s.rx), Mat4.scale(s.scale))));
        gl.uniformMatrix4fv(scene.uModel, false, model);
        gl.drawArrays(gl.TRIANGLES, 0, scene.vertexCount);
      });
      return;
    }

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { en.isIntersecting ? start() : stop(); });
      }, { threshold: 0.01 });
      io.observe(canvas);
    } else {
      start();
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stop(); else if (canvas.getBoundingClientRect().bottom > 0) start();
    });

    window.addEventListener('resize', resize, { passive: true });
  }

  function init() {
    var canvases = document.querySelectorAll('.hero3d-canvas, [data-hero3d]');
    canvases.forEach(function (c) {
      if (c.dataset.hero3dInit) return;
      c.dataset.hero3dInit = '1';
      try { initCanvas(c); } catch (e) { c.style.display = 'none'; }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

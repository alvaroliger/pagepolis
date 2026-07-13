import { useEffect, useRef } from 'react';

/*
 * Hero3D — fondo WebGL con icosaedros low-poly flotantes, reactivo al ratón.
 * Port a React del motor propio de las plantillas generadas
 * (database/templates/hero3d.js): cero dependencias, shaders simples y
 * matemática de matrices propia, para que la marca Pagepolis use el mismo
 * lenguaje visual 3D que las webs que genera.
 * Respeta prefers-reduced-motion (render estático) y se degrada sola si no
 * hay WebGL. Pausa el bucle cuando el canvas no es visible.
 */

type Vec3 = [number, number, number];

interface Props {
    className?: string;
    /** Color base en RGB 0..1 (por defecto, violeta de marca #8b5cf6). */
    color?: Vec3;
    /** Nº de figuras (por defecto 7). */
    count?: number;
}

const Mat4 = {
    identity(): Float32Array {
        return new Float32Array([1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1]);
    },
    multiply(a: Float32Array, b: Float32Array): Float32Array {
        const o = new Float32Array(16);
        for (let i = 0; i < 4; i++) {
            for (let j = 0; j < 4; j++) {
                let s = 0;
                for (let k = 0; k < 4; k++) s += a[k * 4 + j] * b[i * 4 + k];
                o[i * 4 + j] = s;
            }
        }
        return o;
    },
    perspective(fovy: number, aspect: number, near: number, far: number): Float32Array {
        const f = 1 / Math.tan(fovy / 2);
        const nf = 1 / (near - far);
        const o = new Float32Array(16);
        o[0] = f / aspect; o[5] = f; o[10] = (far + near) * nf; o[11] = -1;
        o[14] = 2 * far * near * nf;
        return o;
    },
    translate(x: number, y: number, z: number): Float32Array {
        const o = Mat4.identity(); o[12] = x; o[13] = y; o[14] = z; return o;
    },
    rotateX(r: number): Float32Array {
        const c = Math.cos(r), s = Math.sin(r), o = Mat4.identity();
        o[5] = c; o[6] = s; o[9] = -s; o[10] = c; return o;
    },
    rotateY(r: number): Float32Array {
        const c = Math.cos(r), s = Math.sin(r), o = Mat4.identity();
        o[0] = c; o[2] = -s; o[8] = s; o[10] = c; return o;
    },
    scale(s: number): Float32Array {
        const o = Mat4.identity(); o[0] = s; o[5] = s; o[10] = s; return o;
    },
};

/* Icosaedro low-poly con shading plano (normales por cara) */
function buildIcosahedron() {
    const t = (1 + Math.sqrt(5)) / 2;
    const raw: Vec3[] = ([
        [-1, t, 0], [1, t, 0], [-1, -t, 0], [1, -t, 0],
        [0, -1, t], [0, 1, t], [0, -1, -t], [0, 1, -t],
        [t, 0, -1], [t, 0, 1], [-t, 0, -1], [-t, 0, 1],
    ] as Vec3[]).map(v => {
        const len = Math.hypot(v[0], v[1], v[2]);
        return [v[0] / len, v[1] / len, v[2] / len] as Vec3;
    });
    const faces = [
        [0, 11, 5], [0, 5, 1], [0, 1, 7], [0, 7, 10], [0, 10, 11],
        [1, 5, 9], [5, 11, 4], [11, 10, 2], [10, 7, 6], [7, 1, 8],
        [3, 9, 4], [3, 4, 2], [3, 2, 6], [3, 6, 8], [3, 8, 9],
        [4, 9, 5], [2, 4, 11], [6, 2, 10], [8, 6, 7], [9, 8, 1],
    ];
    const positions: number[] = [], normals: number[] = [];
    faces.forEach(f => {
        const a = raw[f[0]], b = raw[f[1]], c = raw[f[2]];
        const ux = b[0] - a[0], uy = b[1] - a[1], uz = b[2] - a[2];
        const vx = c[0] - a[0], vy = c[1] - a[1], vz = c[2] - a[2];
        let nx = uy * vz - uz * vy, ny = uz * vx - ux * vz, nz = ux * vy - uy * vx;
        const nl = Math.hypot(nx, ny, nz) || 1;
        nx /= nl; ny /= nl; nz /= nl;
        const cx = (a[0] + b[0] + c[0]) / 3, cy = (a[1] + b[1] + c[1]) / 3, cz = (a[2] + b[2] + c[2]) / 3;
        if (nx * cx + ny * cy + nz * cz < 0) { nx = -nx; ny = -ny; nz = -nz; }
        [a, b, c].forEach(p => {
            positions.push(p[0], p[1], p[2]);
            normals.push(nx, ny, nz);
        });
    });
    return { positions: new Float32Array(positions), normals: new Float32Array(normals), count: positions.length / 3 };
}

const VERT_SRC = `
attribute vec3 aPosition;
attribute vec3 aNormal;
uniform mat4 uModel;
uniform mat4 uView;
uniform mat4 uProj;
varying vec3 vNormal;
void main(){
  vNormal = mat3(uModel) * aNormal;
  gl_Position = uProj * uView * uModel * vec4(aPosition, 1.0);
}`;

const FRAG_SRC = `
precision mediump float;
varying vec3 vNormal;
uniform vec3 uColor;
void main(){
  vec3 n = normalize(vNormal);
  float diff = max(dot(n, normalize(vec3(0.4, 0.7, 0.6))), 0.0);
  float rim = pow(1.0 - max(dot(n, vec3(0.0, 0.0, 1.0)), 0.0), 2.2);
  vec3 col = uColor * (0.32 + 0.68 * diff) + rim * uColor * 0.55;
  gl_FragColor = vec4(col, 0.85);
}`;

function compile(gl: WebGLRenderingContext, type: number, src: string): WebGLShader {
    const sh = gl.createShader(type)!;
    gl.shaderSource(sh, src);
    gl.compileShader(sh);
    if (!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) {
        throw new Error(gl.getShaderInfoLog(sh) || 'shader compile error');
    }
    return sh;
}

/* Gama baja: pocos núcleos o poca RAM → menos figuras y menor resolución;
   gama muy baja (<=2 núcleos) → sin WebGL (ahorra batería/CPU). */
function deviceTier(): 'off' | 'low' | 'high' {
    const cores = navigator.hardwareConcurrency;
    const mem = (navigator as Navigator & { deviceMemory?: number }).deviceMemory;
    if (typeof cores === 'number' && cores > 0 && cores <= 2) return 'off';
    if ((typeof cores === 'number' && cores > 0 && cores <= 4) ||
        (typeof mem === 'number' && mem > 0 && mem <= 4)) return 'low';
    return 'high';
}

export default function Hero3D({ className = '', color = [0.545, 0.361, 0.965], count = 7 }: Props) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const tier = deviceTier();
        if (tier === 'off') { canvas.style.display = 'none'; return; }
        const effectiveCount = tier === 'low' ? Math.min(count, 4) : count;

        let gl: WebGLRenderingContext | null = null;
        try {
            gl = (canvas.getContext('webgl', { alpha: true, antialias: true })
                || canvas.getContext('experimental-webgl', { alpha: true, antialias: true })) as WebGLRenderingContext | null;
        } catch { /* sin WebGL */ }
        if (!gl) { canvas.style.display = 'none'; return; }

        let program: WebGLProgram;
        try {
            program = gl.createProgram()!;
            gl.attachShader(program, compile(gl, gl.VERTEX_SHADER, VERT_SRC));
            gl.attachShader(program, compile(gl, gl.FRAGMENT_SHADER, FRAG_SRC));
            gl.linkProgram(program);
            if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
                throw new Error(gl.getProgramInfoLog(program) || 'program link error');
            }
        } catch {
            canvas.style.display = 'none';
            return;
        }
        gl.useProgram(program);

        const geo = buildIcosahedron();
        const posBuf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
        gl.bufferData(gl.ARRAY_BUFFER, geo.positions, gl.STATIC_DRAW);
        const aPosition = gl.getAttribLocation(program, 'aPosition');
        gl.enableVertexAttribArray(aPosition);
        gl.vertexAttribPointer(aPosition, 3, gl.FLOAT, false, 0, 0);

        const normBuf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, normBuf);
        gl.bufferData(gl.ARRAY_BUFFER, geo.normals, gl.STATIC_DRAW);
        const aNormal = gl.getAttribLocation(program, 'aNormal');
        gl.enableVertexAttribArray(aNormal);
        gl.vertexAttribPointer(aNormal, 3, gl.FLOAT, false, 0, 0);

        gl.enable(gl.BLEND);
        gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);
        gl.enable(gl.DEPTH_TEST);
        gl.clearColor(0, 0, 0, 0);

        const uModel = gl.getUniformLocation(program, 'uModel');
        const uView = gl.getUniformLocation(program, 'uView');
        const uProj = gl.getUniformLocation(program, 'uProj');
        const uColor = gl.getUniformLocation(program, 'uColor');

        const shapes = Array.from({ length: effectiveCount }, () => ({
            x: (Math.random() * 2 - 1) * 3.6,
            y: (Math.random() * 2 - 1) * 2.2,
            z: -6 - Math.random() * 9,
            scale: 0.45 + Math.random() * 0.85,
            rx: Math.random() * Math.PI,
            ry: Math.random() * Math.PI,
            speedX: (Math.random() * 0.4 + 0.08) * (Math.random() < 0.5 ? -1 : 1),
            speedY: (Math.random() * 0.4 + 0.08) * (Math.random() < 0.5 ? -1 : 1),
            bobPhase: Math.random() * Math.PI * 2,
        }));

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const pointer = { x: 0, y: 0 };
        const pointerTarget = { x: 0, y: 0 };
        let running = false;
        let rafId: number | null = null;
        let t0: number | null = null;

        function resize() {
            const g = gl!;
            const dpr = Math.min(window.devicePixelRatio || 1, tier === 'low' ? 1 : 1.75);
            const w = canvas!.clientWidth || canvas!.parentElement?.clientWidth || 300;
            const h = canvas!.clientHeight || canvas!.parentElement?.clientHeight || 300;
            const pw = Math.max(1, Math.round(w * dpr)), ph = Math.max(1, Math.round(h * dpr));
            if (canvas!.width !== pw || canvas!.height !== ph) {
                canvas!.width = pw; canvas!.height = ph;
                g.viewport(0, 0, pw, ph);
            }
        }

        function drawFrame(elapsed: number) {
            const g = gl!;
            resize();
            g.clear(g.COLOR_BUFFER_BIT | g.DEPTH_BUFFER_BIT);
            const aspect = canvas!.width / canvas!.height || 1;
            g.uniformMatrix4fv(uProj, false, Mat4.perspective(Math.PI / 4, aspect, 0.1, 100));
            g.uniformMatrix4fv(uView, false, Mat4.translate(0, 0, 0));
            g.uniform3fv(uColor, color);
            shapes.forEach(s => {
                const bob = Math.sin(elapsed * 0.6 + s.bobPhase) * 0.25;
                let model = Mat4.translate(s.x + pointer.x * 0.5, s.y + bob - pointer.y * 0.3, s.z);
                model = Mat4.multiply(model, Mat4.rotateY(s.ry + elapsed * s.speedY));
                model = Mat4.multiply(model, Mat4.rotateX(s.rx + elapsed * s.speedX));
                model = Mat4.multiply(model, Mat4.scale(s.scale));
                g.uniformMatrix4fv(uModel, false, model);
                g.drawArrays(g.TRIANGLES, 0, geo.count);
            });
        }

        function render(time: number) {
            if (!running) return;
            if (t0 === null) t0 = time;
            pointer.x += (pointerTarget.x - pointer.x) * 0.04;
            pointer.y += (pointerTarget.y - pointer.y) * 0.04;
            drawFrame((time - t0) / 1000);
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
            if (rafId !== null) cancelAnimationFrame(rafId);
            rafId = null;
        }

        function onPointerMove(e: MouseEvent | TouchEvent) {
            const rect = canvas!.getBoundingClientRect();
            const p = 'touches' in e ? e.touches[0] : e;
            if (!p) return;
            pointerTarget.x = ((p.clientX - rect.left) / (rect.width || 1)) * 2 - 1;
            pointerTarget.y = ((p.clientY - rect.top) / (rect.height || 1)) * 2 - 1;
        }
        function onVisibility() {
            if (document.hidden) stop();
            else if (canvas!.getBoundingClientRect().bottom > 0) start();
        }

        resize();

        if (reduceMotion) {
            // Un solo frame estático: composición con profundidad, sin animación.
            drawFrame(0);
            return () => { gl?.getExtension('WEBGL_lose_context')?.loseContext(); };
        }

        window.addEventListener('mousemove', onPointerMove, { passive: true });
        window.addEventListener('touchmove', onPointerMove, { passive: true });
        window.addEventListener('resize', resize, { passive: true });
        document.addEventListener('visibilitychange', onVisibility);

        let io: IntersectionObserver | null = null;
        if ('IntersectionObserver' in window) {
            io = new IntersectionObserver(entries => {
                entries.forEach(en => (en.isIntersecting ? start() : stop()));
            }, { threshold: 0.01 });
            io.observe(canvas);
        } else {
            start();
        }

        return () => {
            stop();
            io?.disconnect();
            window.removeEventListener('mousemove', onPointerMove);
            window.removeEventListener('touchmove', onPointerMove);
            window.removeEventListener('resize', resize);
            document.removeEventListener('visibilitychange', onVisibility);
            gl?.getExtension('WEBGL_lose_context')?.loseContext();
        };
        // El motor se monta una vez; color/count solo se leen en el mount.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return <canvas ref={canvasRef} className={className} aria-hidden="true" />;
}

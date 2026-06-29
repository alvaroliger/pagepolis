# 📊 Estudio: qué llevan las apps líderes y plan para que Pagepolis triunfe

Comparativa con Wix ADI, Squarespace, Hostinger AI, Durable, Framer, GoDaddy Airo y
Carrd, y plan de ejecución para los 3 objetivos del negocio:
**(A)** que cualquiera —hasta un abuelo— cree y se suscriba, **(B)** salir primero en
Google, **(C)** ganar dinero.

Leyenda: ✅ ya lo tienes · 🟡 parcial · 🔴 falta

---

## A. FACILIDAD DE USO ("que lo use un abuelo")

| Lo que llevan las líderes | Pagepolis | Acción |
|---|---|---|
| Asistente guiado por preguntas simples (no escribir "prompts") | 🔴 hoy entras a un editor de código | **Wizard "Crea tu web con IA"**: 4 preguntas → genera sola |
| Generación con IA de un sitio completo | ✅ (recién mejorado: multi-sección + tienda) | hecho |
| Edición en lenguaje natural ("pon el horario a 9-20h") | ✅ chat de actualización | hecho |
| Vista previa en vivo + responsive | ✅ editor con preview desktop/tablet/móvil | hecho |
| Progreso visible mientras genera | ✅ (recién añadido: barra de progreso + sondeo) | hecho |
| Plantillas por sector con preview | ✅ 12 plantillas | ampliar a 20+ |
| Autoguardado | 🟡 guardado manual + aviso al salir | autosave con debounce |
| Onboarding/checklist ("publica tu web", "conecta dominio") | 🔴 | checklist en el dashboard |
| Cero jerga técnica en la UI | 🟡 el editor muestra HTML/CSS/JS | esconder código tras "modo avanzado" |

**Lo más importante (A):** el **wizard de creación**. Un abuelo no sabe qué escribir en
un editor; sí sabe responder "¿cómo se llama tu negocio?", "¿qué vendes?". El wizard
construye el prompt por él y dispara la generación en segundo plano → aterriza en el
editor viendo cómo se construye su web. Esto convierte muchísimo más.

---

## B. SEO ("salir primero en Google")

### B.1 La propia pagepolis.com (para captar clientes)
| Estándar del sector | Pagepolis | Acción |
|---|---|---|
| `<title>`/meta description por página | 🟡 solo la landing | meta dinámico por página |
| Open Graph / Twitter Card | 🟡 en sitios publicados, no en la landing | añadir a la landing |
| sitemap.xml + robots.txt | ✅ | hecho |
| Datos estructurados (Organization, SoftwareApplication, FAQ) | 🔴 | JSON-LD en la landing |
| Contenido/keywords ("crear página web con IA", "hacer web gratis") | 🟡 | reforzar copy + FAQ |
| Blog / guías (motor de ranking real) | 🔴 | blog con artículos SEO |
| Rendimiento (Core Web Vitals) | 🟡 bundle del editor 526 kB | code-splitting |

### B.2 Las webs que genera (para que tus usuarios —y tú— posicionen)
| Estándar | Pagepolis | Acción |
|---|---|---|
| Meta + OG + canonical por sitio | ✅ SiteController + ai/seo | hecho |
| JSON-LD LocalBusiness | ✅ lo genera la IA de SEO | hecho |
| HTML semántico, móvil, rápido (estático) | ✅ | hecho |
| sitemap por sitio publicado | 🟡 global | sitemap por dominio de usuario |
| Velocidad (estático + cache) | ✅ | hecho |

**Lo más importante (B):** la landing de pagepolis.com necesita **JSON-LD + OG + más
contenido orientado a keywords + un blog**. El blog es lo que de verdad rankea a largo
plazo ("cómo hacer una web para tu restaurante", etc.) y cada artículo es un embudo.

---

## C. MONETIZACIÓN ("ganar dinero")

| Palanca de las líderes | Pagepolis | Acción |
|---|---|---|
| Free con marca → pago la quita (gancho viral) | ✅ sello "Hecho con Pagepolis" | hecho |
| Página de precios clara con comparativa | 🟡 revisar | precios con tabla + anual -20% |
| Paywall en el momento clave (dominio propio / quitar marca) | ✅ | reforzar el mensaje de valor |
| Prueba sin fricción + checkout (Stripe) | ✅ Cashier | hecho |
| Prueba social (logos, testimonios, nº de webs creadas) | 🔴 | añadir a landing/precios |
| Urgencia/anclaje de precio (tachado, "más popular") | 🟡 | destacar plan recomendado |
| Recuperación: emails de carrito/expiración | 🟡 aviso de expiración | secuencia de bienvenida + nudge a publicar |
| Límites del free que empujan al pago (sin frustrar) | ✅ límites IA + proyectos | afinar mensajes de upgrade |

**Lo más importante (C):** **prueba social** + **página de precios persuasiva** + nudges
en los límites ("Has creado una web genial; publícala con tu dominio"). El producto de
pago ya existe; falta el *empujón de conversión*.

---

## 🎯 PLAN DE EJECUCIÓN (orden por impacto/esfuerzo)

1. **[A] Wizard "Crea tu web con IA"** — 4 preguntas → genera sola → editor con progreso. (El de mayor impacto: activación + conversión). ← *empiezo por aquí*
2. **[B] SEO de la landing** — JSON-LD (Organization + SoftwareApplication + FAQ), OG/Twitter, copy con keywords. (Rápido, alto retorno).
3. **[C] Prueba social + precios** — testimonios, "nº de webs creadas", tabla de precios con plan recomendado y anual.
4. **[A] Checklist de onboarding** en el dashboard (publica, conecta dominio, comparte).
5. **[B] Blog SEO** — estructura + primeros artículos generados (motor de tráfico).
6. **[A] Autosave** y esconder el código tras "modo avanzado".
7. **[B] Rendimiento** — code-splitting del editor (bundle de 526 kB).

> Nota: el motor de generación ya está a nivel pro (sitios complejos, multi-sección,
> tienda con carrito y checkout por WhatsApp/email, generación en segundo plano con
> progreso). El siguiente salto de negocio es **activación + tráfico + conversión**.

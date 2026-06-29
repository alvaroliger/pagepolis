# 🎨 Logo y firma de Pagepolis

Kit de marca coherente con la identidad de la app (degradado violeta → fucsia → cian,
icono de "página" + chispa de IA, wordmark `pagepolis` en minúsculas).

## Archivos
| Archivo | Uso |
|---|---|
| `logo.svg` | Logo completo (icono + wordmark con degradado). Para cabeceras, web, documentos, fondos claros. |
| `logo-blanco.svg` | Igual pero con el texto en blanco. Para **fondos oscuros**. |
| `logo-icono.svg` | Solo el icono (cuadrado). Para favicon, avatar de redes, foto de perfil, firma. |
| `og-image.svg` | Tarjeta 1200×630 para compartir el enlace en redes/WhatsApp. |
| `firma-email.html` | Firma de correo lista para pegar en Gmail (instrucciones dentro del archivo). |

## Ya añadido al proyecto
- `pagepolis/public/logo.svg` y `pagepolis/public/logo-icono.svg` → se sirven en
  `https://pagepolis.com/logo.svg` y `/logo-icono.svg` (los usa la firma de email).
- `pagepolis/resources/views/app.blade.php` → favicon SVG (`/logo-icono.svg`).

## Pendiente (necesita exportar a PNG, no se puede desde código)
- **og-image:** súbela como `pagepolis/public/og-image.png` (1200×630). Exporta
  `og-image.svg` a PNG con cualquier editor (o https://cloudconvert.com/svg-to-png).
  El SEO ya referencia `/og-image.png`.
- (Opcional) `logo-icono.png` por si algún cliente de correo no muestra SVG en la firma.

Colores de marca: `#8b5cf6` (violeta) · `#d946ef` (fucsia) · `#06b6d4` (cian) · `#7c3aed` (violeta sólido para texto/acentos).

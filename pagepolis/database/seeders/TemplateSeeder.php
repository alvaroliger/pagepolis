<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Plantillas de inicio: webs COMPLETAS y vendibles (multi-sección, varias con
 * tienda), montadas desde archivos en database/templates/. Comparten un motor
 * (engine.js + hero3d.js) y un CSS base (base.css) que las hace interactivas,
 * con motion design 3D, y amoldables por la IA (misma convención de ids/clases
 * que el generador).
 *
 * Idempotente (updateOrCreate por nombre): se puede ejecutar en cada deploy.
 */
class TemplateSeeder extends Seeder
{
    /**
     * @return array<int,array{key:string,name:string,category:string,tags:array<int,string>,premium?:bool}>
     */
    private function manifest(): array
    {
        return [
            // ══ Colección KINETIC (2026-08-03) ══════════════════════════════
            // Plantillas de gama alta al nivel de las galerías del sector
            // (motionsites y similares): scroll que hace de línea de tiempo,
            // personajes SVG que parpadean y siguen al ratón, canvas de
            // partículas, cursor magnético y tipografía de cartel.
            // Motor propio: kinetic.css + kinetic.js ('kinetic' => true).
            // Toda la animación de reposo es CSS, así que la miniatura de la
            // galería —que se renderiza sin JS— también se ve en movimiento.
            // ══ Colección GESTOS (kg-*) ══════════════════════════════════════
            // Una plantilla por cada IDEA DE MOVIMIENTO detectada al analizar
            // las referencias del sector. No comparten gesto ni composición:
            // el objetivo es que no haya dos parecidas.
            ['key' => 'kg-01', 'name' => 'Sotavento — Carrusel de escenas', 'category' => 'Turismo', 'tags' => ['gesto', 'carrusel', 'vídeo'], 'premium' => true, 'kinetic' => true],

            // ══ Colección KX — heroes cinematográficos ═══════════════════════
            // Generadas por scratchpad/gen.py desde su manifiesto. El hero es
            // un vídeo en bucle o una escena 3D fabricada con kx3d.js, con el
            // mismo lenguaje de movimiento que las referencias del sector.
            ['key' => 'kx-001', 'name' => 'Marea Alta — Escuela de surf', 'category' => 'Turismo', 'tags' => ['Surf', 'Cantábrico', 'Grupos de 6'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-002', 'name' => 'Fondeo — Alquiler de embarcaciones', 'category' => 'Turismo', 'tags' => ['Baleares', 'Con patrón', 'Día completo'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-003', 'name' => 'Salina — Arrocería junto al mar', 'category' => 'Restaurante', 'tags' => ['Arroces', 'Leña', 'Frente al mar'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-004', 'name' => 'Fermento — Panadería de masa madre', 'category' => 'Restaurante', 'tags' => ['Masa madre', 'Molido a piedra', 'Dos hornadas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-005', 'name' => 'Vértice — Centro de escalada', 'category' => 'Fitness', 'tags' => ['Bloque', 'Cuerda', 'Vías nuevas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-006', 'name' => 'Cobalto — Plataforma de datos', 'category' => 'SaaS', 'tags' => ['Sin código', '14 días', 'RGPD'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-007', 'name' => 'Fósforo — Estudio de marca', 'category' => 'Agencia', 'tags' => ['Identidad', 'Motion', '8 semanas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-008', 'name' => 'Cauce — Consultoría de sostenibilidad', 'category' => 'Servicios', 'tags' => ['CSRD', 'Alcance 1-2-3', 'Auditable'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-009', 'name' => 'Trama — Productora audiovisual', 'category' => 'Agencia', 'tags' => ['Spots', 'Branded', 'Redes'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-010', 'name' => 'Vitral — Vidrio soplado artesanal', 'category' => 'E-commerce', 'tags' => ['Soplado a mano', 'Pieza única', 'Toledo'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-011', 'name' => 'Nudo — Gestoría para autónomos', 'category' => 'Servicios', 'tags' => ['Cuota fija', 'Persona asignada', 'Sin permanencia'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-012', 'name' => 'Grada — Club de atletismo', 'category' => 'Fitness', 'tags' => ['Grupos por ritmo', 'Plan escrito', 'Pista'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-013', 'name' => 'Almadraba — Pescado de lonja', 'category' => 'Restaurante', 'tags' => ['Lonja diaria', 'Sin congelar', 'Cádiz'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-014', 'name' => 'Pórtico — Arquitectura y obra', 'category' => 'Inmobiliaria', 'tags' => ['Vivienda', 'Rehabilitación', 'Pasivo'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-015', 'name' => 'Índigo — Moda de autor', 'category' => 'E-commerce', 'tags' => ['Cápsula', 'Patronaje propio', 'Producción corta'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-016', 'name' => 'Ámbar — Bodega y viñedo', 'category' => 'E-commerce', 'tags' => ['Rioja Alta', 'Vendimia a mano', 'Viña vieja'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-017', 'name' => 'Retina — Óptica y optometría', 'category' => 'Salud', 'tags' => ['40 min de examen', 'Sin comisión', 'Terapia visual'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-018', 'name' => 'Raíz — Vivero y paisajismo', 'category' => 'Servicios', 'tags' => ['Bajo consumo', 'Planta autóctona', 'Mantenimiento'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-019', 'name' => 'Pulso — Software para clínicas', 'category' => 'SaaS', 'tags' => ['RGPD', 'Historia clínica', 'Receta electrónica'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-020', 'name' => 'Bruma — Hotel rural', 'category' => 'Turismo', 'tags' => ['8 habitaciones', 'Sin TV', 'Huerta propia'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-021', 'name' => 'Filo — Cuchillería artesanal', 'category' => 'E-commerce', 'tags' => ['Forjado a mano', 'Acero al carbono', 'Reafilado gratis'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-022', 'name' => 'Cifra — Auditoría y cuentas', 'category' => 'Servicios', 'tags' => ['ROAC', 'Due diligence', 'Valoración'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-023', 'name' => 'Trazo — Estudio de tatuaje', 'category' => 'Agencia', 'tags' => ['Diseño original', 'Consulta previa', 'Cita cerrada'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-024', 'name' => 'Sextante — Software de logística', 'category' => 'SaaS', 'tags' => ['Rutas', 'Flota', 'Albarán digital'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-025', 'name' => 'Coral — Clínica de fisioterapia', 'category' => 'Salud', 'tags' => ['1 hora', 'Uno a uno', 'Ejercicio pautado'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-026', 'name' => 'Papel — Imprenta y encuadernación', 'category' => 'Agencia', 'tags' => ['Offset', 'Cosido', 'Papel FSC'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-027', 'name' => 'Norte — Academia de idiomas', 'category' => 'Servicios', 'tags' => ['Grupos de 6', 'Nativos', 'Cambridge'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-028', 'name' => 'Ágora — Librería y café', 'category' => 'Cultura', 'tags' => ['Fondo escogido', 'Café', 'Club de lectura'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-029', 'name' => 'Voltio — Instalación solar', 'category' => 'Servicios', 'tags' => ['Autoconsumo', 'Subvención', 'Garantía 25 años'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-030', 'name' => 'Lirio — Floristería y eventos', 'category' => 'Servicios', 'tags' => ['Temporada', 'Local', 'Bodas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-031', 'name' => 'Esmalte — Clínica dental', 'category' => 'Salud', 'tags' => ['Conservador', 'Presupuesto cerrado', 'Sin financiación agresiva'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-032', 'name' => 'Manada — Hospital veterinario', 'category' => 'Salud', 'tags' => ['24 horas', 'Quirófano', 'Laboratorio propio'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-033', 'name' => 'Sereno — Psicología clínica', 'category' => 'Salud', 'tags' => ['Colegiados', '50 min', 'Online'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-034', 'name' => 'Compás — Coworking y oficinas', 'category' => 'Servicios', 'tags' => ['Fibra 1 Gb', 'Cabinas', '24/7'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-035', 'name' => 'Quilate — Joyería contemporánea', 'category' => 'E-commerce', 'tags' => ['Oro reciclado', 'Trazable', 'Taller propio'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-036', 'name' => 'Bastión — Seguridad privada', 'category' => 'Servicios', 'tags' => ['Personal propio', 'CRA propia', '24/7'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-037', 'name' => 'Semilla — Escuela infantil', 'category' => 'Servicios', 'tags' => ['Grupos pequeños', 'Patio propio', 'Cocina propia'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-038', 'name' => 'Cadencia — Estudio de grabación', 'category' => 'Música', 'tags' => ['Sala tratada', 'Válvulas', 'Mezcla y máster'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-039', 'name' => 'Faro — Seguros para autónomos', 'category' => 'SaaS', 'tags' => ['20 aseguradoras', 'Correduría', 'Sin coste'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-040', 'name' => 'Óxido — Diseño industrial', 'category' => 'Agencia', 'tags' => ['Prototipo', 'Inyección', 'Fabricación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-041', 'name' => 'Vela — Casa de campo', 'category' => 'Turismo', 'tags' => ['6 dormitorios', 'Piscina', '30 ha'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-042', 'name' => 'Pauta — Herramienta para creadores', 'category' => 'SaaS', 'tags' => ['3 redes', 'Programación', 'Analítica'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-043', 'name' => 'Trébol — Cocina vegetariana', 'category' => 'Restaurante', 'tags' => ['Temporada', 'Sin imitaciones', 'Menú mensual'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-044', 'name' => 'Ancla — Asesoría náutica', 'category' => 'Servicios', 'tags' => ['Matriculación', 'Transferencias', 'Titulaciones'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-045', 'name' => 'Bruño — Zapatería artesanal', 'category' => 'E-commerce', 'tags' => ['Cosido Goodyear', 'Curtido vegetal', 'Resuelable'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-046', 'name' => 'Cima — Estudio de yoga', 'category' => 'Fitness', 'tags' => ['12 por clase', '3 estilos', 'Sin competir'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-047', 'name' => 'Nexo — Consultoría tecnológica', 'category' => 'Agencia', 'tags' => ['Cloud', 'Migración', 'Alta disponibilidad'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-048', 'name' => 'Sal — Cocina de mercado', 'category' => 'Restaurante', 'tags' => ['Menú diario', '13 €', 'Mercado'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-049', 'name' => 'Prisma — Fotografía de producto', 'category' => 'Agencia', 'tags' => ['Plató propio', '72 horas', 'Fondo blanco'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-050', 'name' => 'Roble — Reformas integrales', 'category' => 'Servicios', 'tags' => ['Precio cerrado', 'Fecha con penalización', 'Jefe de obra'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-051', 'name' => 'Colmena — Miel y apicultura', 'category' => 'E-commerce', 'tags' => ['Cruda', 'Monofloral', 'Trazable'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-052', 'name' => 'Fragua Sur — Herrería y cerrajería', 'category' => 'Servicios', 'tags' => ['Forja', 'Soldadura certificada', 'Medición en obra'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-053', 'name' => 'Aula — Autoescuela', 'category' => 'Servicios', 'tags' => ['1 hora real', 'Tasas incluidas', 'Aprobado a la 1ª'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-054', 'name' => 'Tinta Negra — Editorial independiente', 'category' => 'Cultura', 'tags' => ['12 al año', 'Traducción propia', 'Contrato justo'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-055', 'name' => 'Rebote — Club de pádel', 'category' => 'Fitness', 'tags' => ['8 pistas', 'Cubiertas', 'App de reservas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-056', 'name' => 'Vega — Aceite de oliva', 'category' => 'E-commerce', 'tags' => ['Picual', 'Cosecha temprana', 'Almazara propia'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-057', 'name' => 'Circuito — Mantenimiento industrial', 'category' => 'SaaS', 'tags' => ['GMAO', 'Móvil', 'Preventivo'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-058', 'name' => 'Duna — Escuela de buceo', 'category' => 'Turismo', 'tags' => ['PADI', 'Grupos de 4', 'Todo el año'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-059', 'name' => 'Tela — Tapicería y restauración', 'category' => 'E-commerce', 'tags' => ['Estructura revisada', 'Crin natural', 'Diseño'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-060', 'name' => 'Cauteloso — Notaría', 'category' => 'Servicios', 'tags' => ['Cita en 7 días', 'Explicación previa', 'Online'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-061', 'name' => 'Alambique — Coctelería de autor', 'category' => 'Restaurante', 'tags' => ['12 cócteles', 'Destilado propio', 'Hielo tallado'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-062', 'name' => 'Cumbre Ski — Escuela de esquí', 'category' => 'Turismo', 'tags' => ['Grupos de 6', 'Material incluido', 'Titulados'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-063', 'name' => 'Sonda — Monitorización de infraestructura', 'category' => 'SaaS', 'tags' => ['Métricas', 'Logs', 'Trazas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-064', 'name' => 'Arcilla — Escuela de cerámica', 'category' => 'Servicios', 'tags' => ['Grupos de 8', 'Material incluido', 'Horno propio'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-065', 'name' => 'Panal — Marketing para hostelería', 'category' => 'Agencia', 'tags' => ['Solo hostelería', 'Reserva directa', 'Reseñas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-066', 'name' => 'Litoral — Alquiler vacacional', 'category' => 'Inmobiliaria', 'tags' => ['Gestión integral', 'Precio dinámico', 'Limpieza'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-067', 'name' => 'Bordón — Luthier de guitarras', 'category' => 'Cultura', 'tags' => ['Artesanal', 'Clásica y flamenca', 'Reparación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-068', 'name' => 'Norte Frío — Climatización', 'category' => 'Servicios', 'tags' => ['Aerotermia', 'Cálculo de cargas', 'Mantenimiento'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-069', 'name' => 'Cardumen — Conservas artesanales', 'category' => 'Restaurante', 'tags' => ['Ría de Vigo', 'A mano', 'Sin aditivos'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-070', 'name' => 'Vector — Desarrollo a medida', 'category' => 'Agencia', 'tags' => ['A medida', 'Integraciones', 'Código tuyo'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-071', 'name' => 'Mistral — Vuelo en parapente', 'category' => 'Turismo', 'tags' => ['Biplaza', 'Sin experiencia', 'Vídeo incluido'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-072', 'name' => 'Grano — Cervecería artesanal', 'category' => 'Restaurante', 'tags' => ['8 grifos', 'Sin pasteurizar', 'Fábrica propia'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-073', 'name' => 'Perfil — Selección de personal', 'category' => 'Agencia', 'tags' => ['Perfiles técnicos', 'Prueba real', 'Garantía 12 meses'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-074', 'name' => 'Marfil — Peluquería', 'category' => 'Belleza', 'tags' => ['Diagnóstico previo', 'Color a medida', 'Sin amoníaco'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-075', 'name' => 'Rueda — Software para talleres', 'category' => 'SaaS', 'tags' => ['Recambios', 'Órdenes', 'Facturación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-076', 'name' => 'Duelo — Escuela de esgrima', 'category' => 'Cultura', 'tags' => ['Federados', 'Material incluido', 'Desde 7 años'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-077', 'name' => 'Sedimento — Estudios geotécnicos', 'category' => 'Servicios', 'tags' => ['Sondeos', 'Laboratorio', 'Informe firmado'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-078', 'name' => 'Cobijo — Protectora de animales', 'category' => 'Servicios', 'tags' => ['Chip y vacunas', 'Esterilizados', 'Acogida'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-079', 'name' => 'Trapo — Lavandería industrial', 'category' => 'Servicios', 'tags' => ['Recogida diaria', 'Trazabilidad', 'Sanidad'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-080', 'name' => 'Cauto — Firma electrónica', 'category' => 'SaaS', 'tags' => ['eIDAS', 'Validez UE', 'API'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-081', 'name' => 'Salvia — Herbolario y dietética', 'category' => 'Belleza', 'tags' => ['Dietista titulada', 'A granel', 'Sin milagros'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-082', 'name' => 'Cúspide — Tasación oficial', 'category' => 'Inmobiliaria', 'tags' => ['Homologada', 'Banco de España', '48-72 h'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-083', 'name' => 'Roncal — Quesería artesanal', 'category' => 'Restaurante', 'tags' => ['Leche cruda', 'Rebaño propio', 'Cueva natural'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-084', 'name' => 'Bitácora — Consultoría de producto', 'category' => 'Agencia', 'tags' => ['Discovery', 'Validación', 'Roadmap'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-085', 'name' => 'Nácar — Centro de estética avanzada', 'category' => 'Belleza', 'tags' => ['Diagnóstico previo', 'Aparatología médica', 'Sin promesas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-086', 'name' => 'Talud — Ingeniería civil', 'category' => 'Servicios', 'tags' => ['Estructura', 'Obra pública', 'Dirección'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-087', 'name' => 'Aljibe — Tratamiento de agua', 'category' => 'Servicios', 'tags' => ['Análisis previo', 'Ósmosis', 'Mantenimiento'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-088', 'name' => 'Escena — Teatro y sala', 'category' => 'Cultura', 'tags' => ['180 butacas', 'Contemporáneo', 'Residencias'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-089', 'name' => 'Tramo — Gestión de obra', 'category' => 'SaaS', 'tags' => ['Certificaciones', 'Parte diario', 'Coste real'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-090', 'name' => 'Sotobosque — Turismo micológico', 'category' => 'Turismo', 'tags' => ['Con micólogo', 'Permiso incluido', 'Comida'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-091', 'name' => 'Aristo — Diseño de interiores', 'category' => 'Agencia', 'tags' => ['Vivienda', 'Comercio', 'Dirección'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-092', 'name' => 'Reverso — Discos y vinilos', 'category' => 'E-commerce', 'tags' => ['12.000 refs', 'Escucha en tienda', 'Búsqueda'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-093', 'name' => 'Cerco — Ciberseguridad gestionada', 'category' => 'SaaS', 'tags' => ['SOC 24/7', 'Analista humano', 'Respuesta'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-094', 'name' => 'Palio — Organización de bodas', 'category' => 'Servicios', 'tags' => ['Coordinación', 'Proveedores', 'Presupuesto'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-095', 'name' => 'Cauce Digital — Analítica y conversión', 'category' => 'Agencia', 'tags' => ['CRO', 'Medición', 'Tests A/B'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-096', 'name' => 'Sillar — Restauración de patrimonio', 'category' => 'Cultura', 'tags' => ['Mínima intervención', 'Documentado', 'Patrimonio'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-097', 'name' => 'Vaivén — Estudio de pilates', 'category' => 'Fitness', 'tags' => ['Grupos de 6', 'Reformer', 'Valoración previa'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-098', 'name' => 'Adarve — Peritaje de seguros', 'category' => 'Servicios', 'tags' => ['Perito de parte', 'Contradictorio', 'A éxito'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-099', 'name' => 'Umbría — Camping y glamping', 'category' => 'Turismo', 'tags' => ['Reserva de la Biosfera', 'Cielo oscuro', 'Piscina natural'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx-100', 'name' => 'Ápice — Estudio de motion', 'category' => 'Agencia', 'tags' => ['Motion', '3D', 'Render propio'], 'premium' => true, 'kinetic' => true],

            // ══ Colección KX2 (2026-08-05), tanda 1 de 100 ═══════════════════
            // gen.py (la KX de arriba) variaba paleta y vídeo pero repetía
            // siempre el mismo esqueleto de página (lista + tarjetas/cifras +
            // FAQ + formulario) — eso es lo que hacía que 96 % de las
            // plantillas de una misma categoría fueran, en la práctica, la
            // misma. gen2.py varía además el GESTO del hero (carrusel de
            // escenas, sujeto+color, travelling, tarjetas escalonadas o
            // estático) y la composición del cuerpo (lista simple, con
            // marquesina o con cifras que suben al hacer scroll), y nunca
            // repite la misma combinación de gesto+cuerpo dentro de una
            // misma categoría. Ver scratchpad/gen2.py y manifest2.py.
            ['key' => 'kx2-faronorte', 'name' => 'Faro Norte — Inmobiliaria de lujo', 'category' => 'Inmobiliaria', 'tags' => ['inmobiliaria', 'lujo', 'inversión'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-vectorlegal', 'name' => 'Vector Legal — Despacho de abogados', 'category' => 'Servicios', 'tags' => ['abogados', 'mercantil', 'litigios'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-hub9', 'name' => 'Hub9 — Coworking y oficinas flexibles', 'category' => 'Servicios', 'tags' => ['coworking', 'oficinas', 'eventos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-puenteasoc', 'name' => 'Puente & Asociados — Consultoría estratégica', 'category' => 'Agencia', 'tags' => ['consultoría', 'estrategia', 'expansión'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-cardamomo', 'name' => 'Cardamomo — Cafetería de especialidad', 'category' => 'Restaurante', 'tags' => ['cafetería', 'especialidad', 'tueste propio'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-tintahueso', 'name' => 'Tinta & Hueso — Estudio de tatuaje', 'category' => 'Belleza', 'tags' => ['tatuaje', 'diseño propio', 'piercing'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-nebulalabs', 'name' => 'Nébula Labs — Plataforma de IA para soporte', 'category' => 'SaaS', 'tags' => ['ia', 'atención al cliente', 'saas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-combatenorte', 'name' => 'Combate Norte — Box de boxeo', 'category' => 'Fitness', 'tags' => ['boxeo', 'entrenamiento', 'clases'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cristalum', 'name' => 'Cristalum — Joyería de autor', 'category' => 'E-commerce', 'tags' => ['joyería', 'hecho a mano', 'piezas únicas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-vorticesonoro', 'name' => 'Vórtice Sonoro — Estudio de grabación', 'category' => 'Música', 'tags' => ['estudio', 'grabación', 'producción'], 'premium' => false, 'kinetic' => true],

            // ── KX2 tanda 2 (2026-08-05): agota los últimos vídeos de Pexels sin
            // usar (sobre todo 'ciudad') y completa con heroes k3d. Comprobado a
            // mano que ninguna combinación categoría+gesto+cuerpo se repite ni
            // dentro de esta tanda ni con la tanda 1 (ver scripts/templates/README.md).
            ['key' => 'kx2-perspectiva', 'name' => 'Perspectiva — Estudio de interiorismo', 'category' => 'Agencia', 'tags' => ['interiorismo', 'reforma', 'proyecto llave en mano'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-clausula', 'name' => 'Cláusula — Despacho de patentes y marcas', 'category' => 'Servicios', 'tags' => ['patentes', 'marcas', 'propiedad industrial'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-capital9', 'name' => 'Capital Nueve — Consultora de RRHH y selección', 'category' => 'SaaS', 'tags' => ['selección', 'rrhh', 'headhunting'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-oposita', 'name' => 'Oposita — Academia de oposiciones', 'category' => 'Servicios', 'tags' => ['oposiciones', 'temario', 'preparadores'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-rutalog', 'name' => 'RutaLog — Software de logística de última milla', 'category' => 'SaaS', 'tags' => ['logística', 'rutas', 'reparto'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-nortealto', 'name' => 'Norte Alto — Agencia de publicidad exterior', 'category' => 'Agencia', 'tags' => ['publicidad exterior', 'ooh', 'campañas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-tatami', 'name' => 'Tatami — Academia de artes marciales', 'category' => 'Fitness', 'tags' => ['artes marciales', 'mma', 'defensa personal'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-piellibre', 'name' => 'Piel Libre — Estudio de piercing y micropigmentación', 'category' => 'Belleza', 'tags' => ['piercing', 'micropigmentación', 'material estéril'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-calcografia', 'name' => 'Calcografía Once — Taller de grabado y estampación', 'category' => 'Cultura', 'tags' => ['grabado', 'estampación', 'edición limitada'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-heliogen', 'name' => 'Heliogen — Laboratorio de materiales avanzados', 'category' => 'SaaS', 'tags' => ['deeptech', 'i+d', 'materiales'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-crisantemo', 'name' => 'Crisantemo — Floristería de autor', 'category' => 'Servicios', 'tags' => ['floristería', 'temporada', 'eventos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-meridiano', 'name' => 'Meridiano — Relojería de autor', 'category' => 'E-commerce', 'tags' => ['relojería', 'edición limitada', 'mecánico'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-marejada', 'name' => 'Marejada — Agencia de viajes de aventura', 'category' => 'Turismo', 'tags' => ['aventura', 'expediciones', 'grupos reducidos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-rodadura', 'name' => 'Rodadura — Taller de reparación de bicicletas', 'category' => 'Servicios', 'tags' => ['bicicletas', 'reparación', 'a domicilio'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-barronegro', 'name' => 'Barro Negro — Estudio de cerámica', 'category' => 'E-commerce', 'tags' => ['cerámica', 'torno', 'pieza única'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cerilla', 'name' => 'Cerilla — Velas artesanales de cera de soja', 'category' => 'E-commerce', 'tags' => ['velas', 'cera de soja', 'hecho a mano'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-clave7', 'name' => 'Clave Siete — Academia de música para niños', 'category' => 'Música', 'tags' => ['música', 'niños', 'grupos pequeños'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-verdemedio', 'name' => 'Verde Medio — Consultora de sostenibilidad empresarial', 'category' => 'Servicios', 'tags' => ['sostenibilidad', 'csrd', 'huella de carbono'], 'premium' => true, 'kinetic' => true],

            // ── KX2 tanda 3 (2026-08-05): catálogo de vídeo agotado, todas con
            // hero k3d (variedad infinita de forma+color, sin asset externo).
            // Categorías que la tanda 1-2 no habían tocado (Salud, Portfolio,
            // Fintech, App, Blog, Boda, ONG, Evento, Utilidad).
            ['key' => 'kx2-huella4patas', 'name' => 'Huella — Clínica veterinaria de barrio', 'category' => 'Salud', 'tags' => ['veterinaria', 'urgencias', 'cita el mismo día'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-recupera', 'name' => 'Recupera — Fisioterapia deportiva', 'category' => 'Salud', 'tags' => ['fisioterapia', 'deporte', 'readaptación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-granotostado', 'name' => 'Nutria — Consulta de nutrición clínica', 'category' => 'Salud', 'tags' => ['nutrición', 'dietista', 'plan personalizado'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-platoabierto', 'name' => 'Plato Abierto — Fotógrafo de producto gastronómico', 'category' => 'Portfolio', 'tags' => ['fotografía', 'gastronomía', 'producto'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-trazolibre', 'name' => 'Trazo Libre — Ilustrador editorial', 'category' => 'Portfolio', 'tags' => ['ilustración', 'editorial', 'encargos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-arca', 'name' => 'Arca — Banca digital para autónomos', 'category' => 'Fintech', 'tags' => ['fintech', 'autónomos', 'facturación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-migaja', 'name' => 'Migaja — App para reducir el desperdicio de alimentos', 'category' => 'App', 'tags' => ['app', 'sostenibilidad', 'ahorro'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-pausa', 'name' => 'Pausa — App de meditación y respiración guiada', 'category' => 'App', 'tags' => ['meditación', 'bienestar', 'sueño'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-rutalenta', 'name' => 'Ruta Lenta — Blog de viajes sin prisa', 'category' => 'Blog', 'tags' => ['viajes', 'slow travel', 'guías'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-siemprejuntos', 'name' => 'Siempre Juntos — Wedding planner de bodas íntimas', 'category' => 'Boda', 'tags' => ['bodas', 'íntimas', 'coordinación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-mesacomun', 'name' => 'Mesa Común — Banco de alimentos local', 'category' => 'ONG', 'tags' => ['ong', 'banco de alimentos', 'voluntariado'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-solsticio', 'name' => 'Solsticio — Festival de música independiente', 'category' => 'Evento', 'tags' => ['festival', 'música en directo', 'local'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-tasacion', 'name' => 'Tasa Justa — Calculadora de hipoteca y tasación online', 'category' => 'Utilidad', 'tags' => ['hipoteca', 'calculadora', 'tasación'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-hueco', 'name' => 'Hueco — Plataforma de alquiler entre particulares', 'category' => 'Inmobiliaria', 'tags' => ['alquiler', 'particulares', 'sin agencia'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-doblehelice', 'name' => 'Doble Hélice — Laboratorio de análisis genéticos', 'category' => 'Salud', 'tags' => ['genética', 'análisis clínico', 'prevención'], 'premium' => true, 'kinetic' => true],

            // ── KX2 tanda 4 (2026-08-05): refuerza categorías con 1-2 plantillas.
            ['key' => 'kx2-clarete', 'name' => 'Clarete — Taberna de vinos naturales', 'category' => 'Restaurante', 'tags' => ['vinos naturales', 'tapas', 'productor local'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-gelatina', 'name' => 'Gelatina — Heladería artesanal', 'category' => 'Restaurante', 'tags' => ['helados', 'artesanal', 'temporada'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cauceabierto', 'name' => 'Cauce Abierto — Galería de arte contemporáneo', 'category' => 'Cultura', 'tags' => ['arte contemporáneo', 'galería', 'artistas emergentes'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-telondefondo', 'name' => 'Telón de Fondo — Escuela de teatro y expresión', 'category' => 'Cultura', 'tags' => ['teatro', 'interpretación', 'todas las edades'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cabanaverde', 'name' => 'Cabaña Verde — Casa rural de montaña', 'category' => 'Turismo', 'tags' => ['turismo rural', 'montaña', 'desconexión'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-tapeoguiado', 'name' => 'Tapeo Guiado — Tours gastronómicos a pie', 'category' => 'Turismo', 'tags' => ['tour gastronómico', 'tapas', 'grupos pequeños'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-hormiga', 'name' => 'Hormiga — App de ahorro automático', 'category' => 'Fintech', 'tags' => ['ahorro', 'automático', 'microinversión'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cobijo', 'name' => 'Cobijo Vital — Seguro de vida online', 'category' => 'Fintech', 'tags' => ['seguro de vida', 'sin intermediarios', 'contratación online'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-cuentaclara', 'name' => 'Cuenta Clara — Blog de finanzas personales', 'category' => 'Blog', 'tags' => ['finanzas personales', 'ahorro', 'educación financiera'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-instantesreales', 'name' => 'Instantes Reales — Fotógrafo de bodas documental', 'category' => 'Boda', 'tags' => ['fotografía de bodas', 'documental', 'sin poses'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-cuatropatas', 'name' => 'Cuatro Patas — Refugio de animales abandonados', 'category' => 'ONG', 'tags' => ['ong', 'protectora', 'adopciones'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-puestaenescena', 'name' => 'Puesta en Escena — Organización de eventos corporativos', 'category' => 'Evento', 'tags' => ['eventos corporativos', 'producción', 'llave en mano'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-facturafacil', 'name' => 'Factura Fácil — Generador de facturas online gratuito', 'category' => 'Utilidad', 'tags' => ['facturas', 'gratis', 'autónomos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cuerdafloja', 'name' => 'Cuerda Floja — Estudio de pilates con máquinas reformer', 'category' => 'Fitness', 'tags' => ['pilates', 'reformer', 'grupos reducidos'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-diapason', 'name' => 'Diapasón Vintage — Tienda de instrumentos vintage', 'category' => 'Música', 'tags' => ['instrumentos vintage', 'guitarras', 'compra-venta'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-cimientofirme', 'name' => 'Cimiento Firme — Estudio de arquitectura', 'category' => 'Portfolio', 'tags' => ['arquitectura', 'obra nueva', 'portfolio'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-hueconlibre', 'name' => 'Hueco Libre — App de reserva de aparcamiento', 'category' => 'App', 'tags' => ['aparcamiento', 'app', 'reserva anticipada'], 'premium' => false, 'kinetic' => true],

            // ── KX2 tanda 5 (2026-08-05): refuerza categorías que se habían
            // quedado en 2 (Inmobiliaria, Belleza, Blog, Boda, ONG, Evento, Utilidad).
            ['key' => 'kx2-cotarro', 'name' => 'Cotarro — Gestión de alquiler vacacional', 'category' => 'Inmobiliaria', 'tags' => ['alquiler vacacional', 'gestión integral', 'propietarios'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-parcela0', 'name' => 'Parcela Cero — Venta de terrenos y suelo rústico', 'category' => 'Inmobiliaria', 'tags' => ['terrenos', 'suelo rústico', 'inversión'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-piedecal', 'name' => 'Piel de Cal — Spa urbano y masajes terapéuticos', 'category' => 'Belleza', 'tags' => ['spa', 'masajes', 'bienestar'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-lineafina', 'name' => 'Línea Fina — Clínica de medicina estética', 'category' => 'Belleza', 'tags' => ['medicina estética', 'tratamientos faciales', 'resultados naturales'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-interesconpuntos', 'name' => 'Interés con Puntos — Blog de inversión para principiantes', 'category' => 'Blog', 'tags' => ['inversión', 'bolsa', 'educación financiera'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-diariodeabordo', 'name' => 'Diario de a Bordo — Blog de vida en barco y navegación', 'category' => 'Blog', 'tags' => ['navegación', 'vida a bordo', 'vela'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-anillodeplata', 'name' => 'Anillo de Plata — Alianzas y joyería nupcial a medida', 'category' => 'Boda', 'tags' => ['alianzas', 'joyería nupcial', 'a medida'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-mesaparados', 'name' => 'Mesa para Dos — Catering de bodas de autor', 'category' => 'Boda', 'tags' => ['catering', 'bodas', 'menú a medida'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-tejadoseguro', 'name' => 'Tejado Seguro — Asociación contra el sinhogarismo', 'category' => 'ONG', 'tags' => ['ong', 'sinhogarismo', 'reinserción'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-manoamano', 'name' => 'Mano a Mano — Cooperación al desarrollo', 'category' => 'ONG', 'tags' => ['cooperación', 'desarrollo', 'proyectos internacionales'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-feriadeoficios', 'name' => 'Feria de Oficios — Feria de artesanía y oficios tradicionales', 'category' => 'Evento', 'tags' => ['feria', 'artesanía', 'oficios'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-noctambula', 'name' => 'Noctámbula — Sala de conciertos y clubes nocturnos', 'category' => 'Evento', 'tags' => ['sala de conciertos', 'música en directo', 'clubbing'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-firmalibre', 'name' => 'Firma Libre — Firma electrónica de documentos gratuita', 'category' => 'Utilidad', 'tags' => ['firma electrónica', 'documentos', 'gratis'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-medidajusta', 'name' => 'Medida Justa — Comparador de precios de supermercado', 'category' => 'Utilidad', 'tags' => ['comparador', 'supermercado', 'ahorro'], 'premium' => false, 'kinetic' => true],

            // ── KX2 tanda 6, FINAL (2026-08-05): cierra las 100. Reparte lo que
            // quedaba entre las 20 categorías para dejarlas parejas.
            ['key' => 'kx2-ecosonoro', 'name' => 'Eco Sonoro — Estudio de branding sonoro', 'category' => 'Agencia', 'tags' => ['audio branding', 'identidad sonora', 'jingles'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-puentedecrisis', 'name' => 'Puente — Consultora de comunicación de crisis', 'category' => 'Agencia', 'tags' => ['comunicación de crisis', 'reputación', 'portavocía'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-hablafacil', 'name' => 'Habla Fácil — App de idiomas con IA conversacional', 'category' => 'App', 'tags' => ['idiomas', 'ia conversacional', 'práctica oral'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-trueca', 'name' => 'Trueca — App de trueque e intercambio de objetos', 'category' => 'App', 'tags' => ['trueque', 'segunda mano', 'economía circular'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-navajaclasica', 'name' => 'Navaja Clásica — Barbería de barrio sin cita previa', 'category' => 'Belleza', 'tags' => ['barbería', 'sin cita', 'afeitado clásico'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-restosconarte', 'name' => 'Restos con Arte — Blog de cocina de aprovechamiento', 'category' => 'Blog', 'tags' => ['cocina de aprovechamiento', 'recetas', 'desperdicio cero'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-tocaybaila', 'name' => 'Toca y Baila — DJ y música en directo para bodas', 'category' => 'Boda', 'tags' => ['dj de bodas', 'música en directo', 'ambientación'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-oficiantecivil', 'name' => 'Palabras Propias — Oficiante de ceremonias civiles', 'category' => 'Boda', 'tags' => ['ceremonia civil', 'maestro de ceremonias', 'boda personalizada'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-contrapunto', 'name' => 'Contrapunto — Escuela de música clásica', 'category' => 'Cultura', 'tags' => ['música clásica', 'conservatorio', 'instrumento'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-laberintooculto', 'name' => 'Laberinto Oculto — Sala de escape temática', 'category' => 'Cultura', 'tags' => ['escape room', 'juego en equipo', 'temáticas originales'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-hojasueltas', 'name' => 'Hojas Sueltas — Tienda de té e infusiones a granel', 'category' => 'E-commerce', 'tags' => ['té a granel', 'infusiones', 'origen único'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-trazoypapel', 'name' => 'Trazo y Papel — Papelería de diseño', 'category' => 'E-commerce', 'tags' => ['papelería', 'diseño', 'material de oficina'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-ultimanoche', 'name' => 'Última Noche Libre — Organización de despedidas de soltero/a', 'category' => 'Evento', 'tags' => ['despedidas de soltero', 'eventos a medida', 'grupos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-ladrillovivo', 'name' => 'Ladrillo Vivo — Plataforma de crowdfunding inmobiliario', 'category' => 'Fintech', 'tags' => ['crowdfunding inmobiliario', 'inversión', 'desde 500€'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-partecomun', 'name' => 'Parte Común — App para dividir gastos entre amigos', 'category' => 'Fintech', 'tags' => ['gastos compartidos', 'app', 'viajes en grupo'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-terrenoduro', 'name' => 'Terreno Duro — Entrenamiento funcional al aire libre', 'category' => 'Fitness', 'tags' => ['funcional', 'aire libre', 'grupos reducidos'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-rentasegura', 'name' => 'Renta Segura — Consultoría de inversión en vivienda de alquiler', 'category' => 'Inmobiliaria', 'tags' => ['inversión inmobiliaria', 'alquiler', 'rentabilidad'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-platinamix', 'name' => 'Platina Mix — Escuela de DJ y producción musical', 'category' => 'Música', 'tags' => ['dj', 'producción musical', 'estudio propio'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-platoazul', 'name' => 'Plato Azul — Comedor social infantil', 'category' => 'ONG', 'tags' => ['comedor social', 'infancia', 'voluntariado'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-lineaygrano', 'name' => 'Línea y Grano — Estudio de diseño gráfico freelance', 'category' => 'Portfolio', 'tags' => ['diseño gráfico', 'identidad visual', 'freelance'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-vermuderelojes', 'name' => 'El Reloj Parado — Bar de vermú y aperitivo', 'category' => 'Restaurante', 'tags' => ['vermú', 'aperitivo', 'tapas de barra'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-turnofacil', 'name' => 'Turno Fácil — Software de gestión de turnos para restaurantes', 'category' => 'SaaS', 'tags' => ['gestión de turnos', 'restaurantes', 'reservas'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-pasofirme', 'name' => 'Paso Firme — Clínica de podología deportiva', 'category' => 'Salud', 'tags' => ['podología', 'deporte', 'plantillas a medida'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-mudanzasnorte', 'name' => 'Mudanzas Norte Global — Mudanzas internacionales', 'category' => 'Servicios', 'tags' => ['mudanzas internacionales', 'logística', 'seguro incluido'], 'premium' => true, 'kinetic' => true],
            ['key' => 'kx2-caravanalibre', 'name' => 'Caravana Libre — Alquiler de autocaravanas', 'category' => 'Turismo', 'tags' => ['autocaravana', 'alquiler', 'roadtrip'], 'premium' => false, 'kinetic' => true],
            ['key' => 'kx2-cambiajusto', 'name' => 'Cambia Justo — Conversor de divisas para viajeros', 'category' => 'Utilidad', 'tags' => ['conversor de divisas', 'viajes', 'gratis'], 'premium' => false, 'kinetic' => true],

            // ── Serie VÍDEO (kv-*): hero de vídeo en bucle a pantalla completa.
            // Es el patrón exacto de las referencias del sector: el nivel no lo
            // da el código sino el plano de vídeo detrás de una tipografía
            // contenida. El <video> se reproduce sin JS, así que la miniatura de
            // la galería también se ve en movimiento.
            ['key' => 'kv-yacht',     'name' => 'Azure Horizon — Club náutico',     'category' => 'Turismo',         'tags' => ['vídeo', 'náutica', 'lujo', 'cinematográfica'],  'premium' => true, 'kinetic' => true],

            ['key' => 'kin-jack',     'name' => 'Jack — Portfolio con avatar 3D',   'category' => 'Portfolio',       'tags' => ['personaje', '3d', 'portfolio', 'animada'],      'premium' => true, 'kinetic' => true],
            ['key' => 'kin-iris',     'name' => 'Nova — IA con iris animado',       'category' => 'SaaS',            'tags' => ['ia', 'saas', 'partículas', 'oscura'],           'premium' => true, 'kinetic' => true],
            ['key' => 'kin-crew',     'name' => 'Cuadrilla — Equipo de agentes IA', 'category' => 'SaaS',            'tags' => ['personajes', 'ia', 'equipo', 'animada'],        'premium' => true, 'kinetic' => true],
            ['key' => 'kin-vanguard', 'name' => 'Vanguard — Agencia de carácter',   'category' => 'Agencia',         'tags' => ['agencia', 'cartel', 'personaje', 'audaz'],      'premium' => true, 'kinetic' => true],
            ['key' => 'kin-atlas',    'name' => 'Atlas — Datos y territorio',       'category' => 'SaaS',            'tags' => ['datos', 'malla 3d', 'analítica'],               'premium' => true, 'kinetic' => true],
            ['key' => 'kin-sentinel', 'name' => 'Sentinel — Ciberseguridad',        'category' => 'Servicios',       'tags' => ['ciberseguridad', 'radar', 'oscura'],            'premium' => true, 'kinetic' => true],
            ['key' => 'kin-forge',    'name' => 'Fragua — Gimnasio de fuerza',      'category' => 'Fitness',         'tags' => ['gimnasio', 'personaje', 'fuerza'],              'premium' => true, 'kinetic' => true],
            ['key' => 'kin-plate',    'name' => 'Brasa — Restaurante de fuego',     'category' => 'Restaurante',     'tags' => ['restaurante', 'brasa', 'reservas'],             'premium' => true, 'kinetic' => true],
            ['key' => 'kin-bloom',    'name' => 'Bruma — Estética y bienestar',     'category' => 'Belleza',         'tags' => ['belleza', 'spa', 'clara', 'suave'],             'premium' => true, 'kinetic' => true],
            ['key' => 'kin-nest',     'name' => 'Umbral — Inmobiliaria isométrica', 'category' => 'Inmobiliaria',    'tags' => ['inmobiliaria', 'isométrico', 'viviendas'],      'premium' => true, 'kinetic' => true],
            ['key' => 'kin-lens',     'name' => 'Lente — Fotografía de autor',      'category' => 'Portfolio',       'tags' => ['fotografía', 'diafragma', 'oscura'],            'premium' => true, 'kinetic' => true],
            ['key' => 'kin-petal',    'name' => 'Pétalo — Invitación de boda',      'category' => 'Boda',            'tags' => ['boda', 'invitación', 'personajes', 'clara'],    'premium' => true, 'kinetic' => true],
            ['key' => 'kin-drop',     'name' => 'Kadence — Drop de edición limitada','category' => 'E-commerce',     'tags' => ['tienda', 'calzado', 'carrito', 'drop'],         'premium' => true, 'kinetic' => true],
            ['key' => 'kin-wave',     'name' => 'Onda Mayor — Banda de música',     'category' => 'Música',          'tags' => ['música', 'banda', 'ecualizador', 'gira'],       'premium' => true, 'kinetic' => true],
            ['key' => 'kin-brief',    'name' => 'Arana & Soto — Bufete de abogados','category' => 'Servicios',       'tags' => ['abogados', 'legal', 'balanza', 'sobria'],       'premium' => true, 'kinetic' => true],
            ['key' => 'kin-scholar',  'name' => 'Aprendo — Formación online',       'category' => 'Servicios',       'tags' => ['formación', 'cursos', 'personaje'],             'premium' => true, 'kinetic' => true],
            ['key' => 'kin-pulse',    'name' => 'Vera — Centro médico',             'category' => 'Salud',           'tags' => ['clínica', 'salud', 'citas', 'clara'],           'premium' => true, 'kinetic' => true],
            ['key' => 'kin-paws',     'name' => 'Patas Arriba — Veterinaria',       'category' => 'Salud',           'tags' => ['veterinaria', 'mascotas', 'personaje'],         'premium' => true, 'kinetic' => true],
            ['key' => 'kin-brew',     'name' => 'Tueste Lento — Cafetería',         'category' => 'Restaurante',     'tags' => ['cafetería', 'tostadero', 'tienda', 'clara'],    'premium' => true, 'kinetic' => true],
            // Ojo: el nombre no puede chocar con el de 'aventura-montana' (más
            // abajo), porque el seeder hace updateOrCreate por nombre y la
            // última entrada pisaría a la primera.
            ['key' => 'kin-terra',    'name' => 'Cresta — Turismo de aventura',     'category' => 'Turismo',         'tags' => ['aventura', 'montaña', 'parallax', 'rutas'],     'premium' => true, 'kinetic' => true],
            ['key' => 'kin-glass',    'name' => 'Vidrio — Estudio de producto',     'category' => 'Agencia',         'tags' => ['agencia', 'cristal', 'producto', 'oscura'],     'premium' => true, 'kinetic' => true],
            ['key' => 'kin-flux',     'name' => 'Flujo — Analítica de negocio',     'category' => 'SaaS',            'tags' => ['analítica', 'panel', 'gráficos', 'datos'],      'premium' => true, 'kinetic' => true],
            ['key' => 'kin-404',      'name' => 'Órbita Perdida — Página 404',      'category' => 'Utilidad',        'tags' => ['404', 'error', 'astronauta', 'personaje'],      'premium' => true, 'kinetic' => true],
            ['key' => 'kin-barber',   'name' => 'Navaja — Barbería clásica',        'category' => 'Belleza',         'tags' => ['barbería', 'peluquería', 'personaje'],          'premium' => true, 'kinetic' => true],
            ['key' => 'kin-orbit',    'name' => 'Órbita — Equipos en remoto',       'category' => 'Agencia',         'tags' => ['agencia', 'desarrollo', 'remoto', 'oscura'],    'premium' => true, 'kinetic' => true],
            ['key' => 'kin-strata',   'name' => 'Estratos — Museo y exposición',    'category' => 'Cultura',         'tags' => ['museo', 'exposición', 'cultura', 'editorial'],  'premium' => true, 'kinetic' => true],
            ['key' => 'kin-retro',    'name' => 'Hiperspacio — Startup técnica',    'category' => 'SaaS',            'tags' => ['startup', 'retro', 'rejilla 3d', 'oscura'],     'premium' => true, 'kinetic' => true],
            ['key' => 'kin-boutique', 'name' => 'Hilo — Boutique de moda lenta',    'category' => 'E-commerce',      'tags' => ['moda', 'boutique', 'tienda', 'carrito'],        'premium' => true, 'kinetic' => true],
            ['key' => 'kin-spark',    'name' => 'Semilla — Fundación y ONG',        'category' => 'ONG',             'tags' => ['ong', 'fundación', 'donaciones'],               'premium' => true, 'kinetic' => true],
            ['key' => 'kin-craft',    'name' => 'Torno — Cerámica artesanal',       'category' => 'E-commerce',      'tags' => ['artesanía', 'cerámica', 'taller', 'tienda'],    'premium' => true, 'kinetic' => true],

            // ── Plantillas 3D premium (modernas, oscuras, hero WebGL) — al frente ──
            ['key' => 'nebula', 'name' => 'Nebula — IA / SaaS 3D',        'category' => 'SaaS',        'tags' => ['ia', 'saas', '3d', 'moderna'],        'premium' => true],
            ['key' => 'prisma', 'name' => 'Prisma — Estudio creativo 3D', 'category' => 'Portfolio',   'tags' => ['agencia', 'portfolio', '3d', 'diseño'], 'premium' => true],
            ['key' => 'vault',  'name' => 'Vault — Fintech / Web3 3D',    'category' => 'Fintech',     'tags' => ['fintech', 'cripto', 'web3', '3d'],      'premium' => true],
            ['key' => 'pulse',  'name' => 'Pulse — Gimnasio / Fitness 3D','category' => 'Fitness',     'tags' => ['gimnasio', 'fitness', '3d', 'moderna'], 'premium' => true],
            ['key' => 'ember',  'name' => 'Ember — Restaurante 3D',       'category' => 'Restaurante', 'tags' => ['restaurante', 'carta', '3d', 'premium'],'premium' => true],
            ['key' => 'aura',   'name' => 'Aura — Belleza / Estética 3D', 'category' => 'Belleza',     'tags' => ['belleza', 'estética', 'spa', '3d'],     'premium' => true],
            ['key' => 'vista',  'name' => 'Vista — Inmobiliaria 3D',      'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'propiedades', '3d'],    'premium' => true],

            ['key' => 'restaurante', 'name' => 'Restaurante con pedidos', 'category' => 'Restaurante', 'tags' => ['restaurante', 'carta', 'pedidos', 'tienda']],
            ['key' => 'tienda',      'name' => 'Tienda online',          'category' => 'E-commerce',  'tags' => ['tienda', 'productos', 'carrito']],
            ['key' => 'servicios',   'name' => 'Empresa de servicios',   'category' => 'Servicios',   'tags' => ['servicios', 'empresa', 'agencia']],
            ['key' => 'clinica',     'name' => 'Clínica / Salud',        'category' => 'Salud',       'tags' => ['clínica', 'salud', 'citas']],
            ['key' => 'gimnasio',    'name' => 'Gimnasio / Fitness',     'category' => 'Fitness',     'tags' => ['gimnasio', 'fitness', 'cuotas']],
            ['key' => 'belleza',     'name' => 'Peluquería y estética',  'category' => 'Belleza',     'tags' => ['belleza', 'peluquería', 'reservas', 'tienda']],
            ['key' => 'inmobiliaria','name' => 'Inmobiliaria',           'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'propiedades', 'venta']],
            ['key' => 'abogados',    'name' => 'Bufete de abogados',     'category' => 'Servicios',   'tags' => ['abogados', 'legal', 'profesional']],
            ['key' => 'fotografo',   'name' => 'Fotógrafo / Portfolio',  'category' => 'Portfolio',   'tags' => ['fotografía', 'portfolio', 'galería']],
            ['key' => 'cafeteria',   'name' => 'Cafetería con tienda',   'category' => 'Restaurante', 'tags' => ['cafetería', 'café', 'pedidos', 'tienda']],
            ['key' => 'saas',        'name' => 'App / SaaS',             'category' => 'SaaS',        'tags' => ['saas', 'startup', 'software']],
            ['key' => 'coach',       'name' => 'Coach / Formación',      'category' => 'Servicios',   'tags' => ['coach', 'formación', 'cursos']],

            // ── Recuperadas de producción (2026-07-30): eran las únicas 7 realmente
            // distintas de las 20 que había en el servidor real — el resto eran una
            // misma plantilla genérica de relleno duplicada 13 veces con el nombre
            // cambiado. Son autocontenidas (su propio CSS/JS, sistema anterior a
            // base.css/engine.js): 'standalone' => true evita mezclarlas con el motor
            // compartido, que rompería su diseño original.
            ['key' => 'prod-landing-saas',        'name' => 'Landing SaaS',         'category' => 'SaaS',        'tags' => ['oscuro', 'moderno', 'startup'],       'standalone' => true],
            ['key' => 'prod-portfolio-creativo',  'name' => 'Portfolio Creativo',    'category' => 'Portfolio',   'tags' => ['minimalista', 'creativo', 'diseñador'],'standalone' => true],
            ['key' => 'prod-restaurante-elegante','name' => 'Restaurante Elegante',  'category' => 'Restaurante', 'tags' => ['gastronomía', 'elegante', 'reservas'], 'standalone' => true],
            ['key' => 'prod-agencia-marketing',   'name' => 'Agencia de Marketing',  'category' => 'Agencia',     'tags' => ['colorida', 'stats', 'marketing'],      'standalone' => true],
            ['key' => 'prod-app-movil',           'name' => 'App Móvil',             'category' => 'App',         'tags' => ['app', 'móvil', 'startup', 'producto'], 'standalone' => true],
            ['key' => 'prod-gimnasio-fitness',    'name' => 'Gimnasio Fitness',      'category' => 'Fitness',     'tags' => ['gym', 'fitness', 'energía', 'deporte'],'standalone' => true],
            ['key' => 'prod-blog-personal',       'name' => 'Blog Personal',         'category' => 'Blog',        'tags' => ['blog', 'escritura', 'personal'],       'standalone' => true],

            // ── Reemplazo de las 13 duplicadas genéricas de producción (2026-07-30):
            // diseño y copy reales y distintos por sector, no relleno.
            ['key' => 'cv-resume',            'name' => 'CV / Portfolio personal',   'category' => 'Portfolio',   'tags' => ['currículum', 'personal', 'freelance']],
            ['key' => 'boda',                 'name' => 'Invitación de boda',        'category' => 'Boda',        'tags' => ['boda', 'invitación', 'pareja']],
            ['key' => 'tienda-tech',          'name' => 'Tienda de tecnología',      'category' => 'E-commerce',  'tags' => ['tienda', 'tecnología', 'gadgets']],
            ['key' => 'inmobiliaria-listados','name' => 'Inmobiliaria con listados', 'category' => 'Inmobiliaria','tags' => ['inmobiliaria', 'listados', 'venta']],
            ['key' => 'clinica-dental',       'name' => 'Clínica dental',            'category' => 'Salud',       'tags' => ['salud', 'dental', 'citas']],
            ['key' => 'consultoria-legal',    'name' => 'Consultoría legal',         'category' => 'Servicios',   'tags' => ['legal', 'consultoría', 'abogada']],
            ['key' => 'startup-tech',         'name' => 'Startup tecnológica',       'category' => 'SaaS',        'tags' => ['startup', 'tech', 'producto']],
            ['key' => 'fotografo-bodas',      'name' => 'Fotógrafo de bodas',        'category' => 'Portfolio',   'tags' => ['fotografía', 'bodas', 'portfolio']],
            ['key' => 'musico-artista',       'name' => 'Músico / Banda',            'category' => 'Música',      'tags' => ['música', 'artista', 'conciertos']],
            ['key' => 'ong-fundacion',        'name' => 'ONG / Fundación',           'category' => 'ONG',         'tags' => ['ong', 'fundación', 'donaciones']],
            ['key' => 'moda-boutique',        'name' => 'Boutique de moda',          'category' => 'E-commerce',  'tags' => ['moda', 'boutique', 'ropa']],
            ['key' => 'conferencia',          'name' => 'Evento / Conferencia',      'category' => 'Evento',      'tags' => ['evento', 'conferencia', 'congreso']],
            ['key' => 'barberia',             'name' => 'Barbería',                  'category' => 'Belleza',     'tags' => ['peluquería', 'barbería', 'estilo']],

            // ── Estreno del motor de foto cinemática (hero-photo.js, 2026-07-31):
            // Ken Burns en reposo + "vuelo hacia dentro" ligado al scroll sobre una
            // fotografía real, con profundidad de capas. Reutilizable en cualquier
            // plantilla vía <div class="hero-photo" data-hero-photo>.
            ['key' => 'aventura-montana', 'name' => 'Cumbre Viva — Turismo de aventura', 'category' => 'Turismo', 'tags' => ['aventura', 'montaña', 'rutas', 'cinemática'], 'premium' => true],
        ];
    }

    public function run(): void
    {
        $dir        = database_path('templates');
        $base       = @file_get_contents("{$dir}/base.css") ?: '';
        // Renderizador 3D por raymarching: fabrica las escenas 3D de los heroes
        // sin depender de ningún asset externo (ver kx3d.js).

        // El alta se hace con updateOrCreate por NOMBRE, así que dos entradas
        // con el mismo nombre no dan error: la segunda pisa a la primera y una
        // plantilla desaparece en silencio. Mejor romper aquí que descubrirlo
        // contando filas en producción.
        $manifest  = $this->manifest();
        $allNames  = array_column($manifest, 'name');
        $duplicate = array_unique(array_diff_assoc($allNames, array_unique($allNames)));
        if ($duplicate !== []) {
            throw new \RuntimeException(
                'TemplateSeeder: nombres de plantilla repetidos en el manifiesto: '
                . implode(', ', $duplicate)
            );
        }

        $names = [];
        foreach ($manifest as $t) {
            $html = @file_get_contents("{$dir}/{$t['key']}.html");
            $css  = @file_get_contents("{$dir}/{$t['key']}.css");

            // Si aún no existen los archivos de la plantilla, se omite (no rompe el deploy).
            if ($html === false || $css === false) {
                continue;
            }

            // Tres motores posibles:
            //  · kinetic    → kinetic.css + kinetic.js (plantillas premium animadas)
            //  · standalone → CSS/JS propios, sin motor compartido
            //  · por defecto→ base.css + engine.js + hero3d.js + hero-photo.js
            $standalone = $t['standalone'] ?? false;
            $kinetic    = $t['kinetic'] ?? false;

            // Solo se guarda el CSS/JS PROPIO de la plantilla. El motor
            // compartido (kinetic.css/js, base.css, engine.js…) vive una sola
            // vez en disco y se sirve como archivo cacheable: duplicarlo en
            // cada fila costaba ~110 KB por plantilla y llegó a tumbar la
            // galería al serializarla entera. Ver App\Services\TemplateEngine.
            if ($kinetic) {
                $engine   = 'kinetic';
                $finalCss = $css;
                $finalJs  = '';
            } elseif ($standalone) {
                $engine   = null;
                $finalCss = $css;
                $finalJs  = @file_get_contents("{$dir}/{$t['key']}.js") ?: '';
            } else {
                $engine   = 'classic';
                $finalCss = $css;
                $finalJs  = '';
            }

            // is_active NO se pisa en una fila ya existente: templates como los
            // duplicados desactivados a mano (mismo esqueleto+categoría que
            // otro) deben seguir ocultos tras un redeploy, no reaparecer solo
            // por volver a correr el seeder.
            $existing = Template::where('name', $t['name'])->first();

            Template::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category'   => $t['category'],
                    'tags'       => $t['tags'],
                    'html'       => $html,
                    'css'        => $finalCss,
                    'js'         => $finalJs,
                    'engine'     => $engine,
                    'is_premium' => $t['premium'] ?? false,
                    'is_active'  => $existing->is_active ?? true,
                ]
            );
            $names[] = $t['name'];
        }

        // Elimina plantillas antiguas que ya no forman parte del set (sin romper
        // la FK: primero desvincula los proyectos que las usaban).
        if (!empty($names)) {
            $obsolete = Template::whereNotIn('name', $names)->pluck('id');
            if ($obsolete->isNotEmpty()) {
                \App\Models\Project::whereIn('template_id', $obsolete)->update(['template_id' => null]);
                Template::whereIn('id', $obsolete)->delete();
            }
        }
    }
}

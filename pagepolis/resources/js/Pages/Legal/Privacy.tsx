import LegalLayout, { LegalSection } from '@/Components/LegalLayout';

export default function Privacy() {
    return (
        <LegalLayout title="Política de privacidad" updated="9 de junio de 2026">
            <p className="text-[15px]">
                En <strong className="text-white">Pagepolis</strong> nos tomamos en serio la
                protección de tus datos personales. Esta política explica qué datos tratamos, con qué
                finalidad y qué derechos tienes, conforme al Reglamento (UE) 2016/679 (RGPD) y la
                Ley Orgánica 3/2018 (LOPDGDD).
            </p>

            <LegalSection n={1} title="Responsable del tratamiento">
                <p>
                    Responsable: <strong className="text-white">[NOMBRE O RAZÓN SOCIAL]</strong>, NIF
                    <strong className="text-white"> [NIF/DNI]</strong>, domicilio en
                    <strong className="text-white"> [DIRECCIÓN]</strong>. Contacto:
                    <a href="mailto:hola@pagepolis.com" className="text-violet-400 hover:underline"> hola@pagepolis.com</a>.
                </p>
            </LegalSection>

            <LegalSection n={2} title="Datos que tratamos">
                <ul className="list-disc pl-6 space-y-1.5">
                    <li><strong className="text-white">Datos de cuenta:</strong> nombre, correo electrónico y contraseña (cifrada).</li>
                    <li><strong className="text-white">Datos de contacto opcionales:</strong> número de WhatsApp si lo facilitas.</li>
                    <li><strong className="text-white">Datos de facturación:</strong> gestionados por Stripe; nosotros conservamos identificadores y los últimos dígitos de la tarjeta, no el número completo.</li>
                    <li><strong className="text-white">Contenido:</strong> los proyectos y webs que creas.</li>
                    <li><strong className="text-white">Datos de uso:</strong> registros técnicos y estadísticas agregadas de visitas a las webs publicadas.</li>
                </ul>
            </LegalSection>

            <LegalSection n={3} title="Finalidades y base jurídica">
                <ul className="list-disc pl-6 space-y-1.5">
                    <li><strong className="text-white">Prestar el Servicio</strong> (ejecución del contrato).</li>
                    <li><strong className="text-white">Gestionar pagos y suscripciones</strong> (ejecución del contrato y obligación legal).</li>
                    <li><strong className="text-white">Atención al cliente y comunicaciones del servicio</strong> (interés legítimo / contrato).</li>
                    <li><strong className="text-white">Seguridad y prevención del fraude</strong> (interés legítimo).</li>
                    <li><strong className="text-white">Comunicaciones comerciales</strong>, solo si das tu consentimiento, revocable en cualquier momento.</li>
                </ul>
            </LegalSection>

            <LegalSection n={4} title="Destinatarios y encargados del tratamiento">
                <p>Para prestar el Servicio compartimos datos, en lo estrictamente necesario, con:</p>
                <ul className="list-disc pl-6 space-y-1.5">
                    <li><strong className="text-white">Stripe</strong> — procesamiento de pagos.</li>
                    <li><strong className="text-white">Anthropic</strong> — generación de contenido con IA (se le envían tus instrucciones y el contenido de la web a editar).</li>
                    <li><strong className="text-white">Cloudflare</strong> y el proveedor de hosting — entrega y alojamiento de los sitios.</li>
                    <li><strong className="text-white">Dinahosting</strong> — registro de dominios, cuando contratas uno.</li>
                    <li><strong className="text-white">Proveedor de correo electrónico</strong> — envío de notificaciones del servicio.</li>
                </ul>
                <p>No vendemos tus datos personales a terceros.</p>
            </LegalSection>

            <LegalSection n={5} title="Conservación">
                <p>
                    Conservamos tus datos mientras tu cuenta esté activa y, tras su baja, durante los
                    plazos legalmente exigibles (por ejemplo, obligaciones fiscales). Después se
                    eliminan o anonimizan.
                </p>
            </LegalSection>

            <LegalSection n={6} title="Tus derechos">
                <p>
                    Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición,
                    limitación y portabilidad escribiendo a
                    <a href="mailto:hola@pagepolis.com" className="text-violet-400 hover:underline"> hola@pagepolis.com</a>.
                    Desde <strong className="text-white">Mi perfil → Exportar mis datos</strong> puedes
                    descargar en cualquier momento una copia de tus datos en formato JSON. También
                    puedes eliminar tu cuenta desde el propio panel.
                </p>
            </LegalSection>

            <LegalSection n={7} title="Cookies">
                <p>
                    Utilizamos cookies técnicas necesarias para el inicio de sesión y el
                    funcionamiento del Servicio. No utilizamos cookies publicitarias de terceros. El
                    idioma seleccionado se guarda localmente en tu navegador.
                </p>
            </LegalSection>

            <LegalSection n={8} title="Seguridad">
                <p>
                    Aplicamos medidas técnicas y organizativas razonables para proteger tus datos,
                    como el cifrado de contraseñas y conexiones seguras (HTTPS). Ningún sistema es
                    100% infalible, pero trabajamos para minimizar los riesgos.
                </p>
            </LegalSection>

            <LegalSection n={9} title="Transferencias internacionales">
                <p>
                    Algunos proveedores pueden tratar datos fuera del Espacio Económico Europeo. En
                    tal caso, se aplican garantías adecuadas (como las Cláusulas Contractuales Tipo de
                    la Comisión Europea).
                </p>
            </LegalSection>

            <LegalSection n={10} title="Cambios y reclamaciones">
                <p>
                    Podemos actualizar esta política; los cambios relevantes se comunicarán por medios
                    razonables. Si consideras que no hemos tratado tus datos correctamente, puedes
                    reclamar ante la Agencia Española de Protección de Datos (
                    <a href="https://www.aepd.es" target="_blank" rel="noreferrer" className="text-violet-400 hover:underline">www.aepd.es</a>).
                </p>
            </LegalSection>
        </LegalLayout>
    );
}

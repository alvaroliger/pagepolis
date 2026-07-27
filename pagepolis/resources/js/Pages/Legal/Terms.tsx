import { usePage } from '@inertiajs/react';
import LegalLayout, { LegalSection } from '@/Components/LegalLayout';

export default function Terms() {
    const email = (usePage().props as any).support?.email ?? 'soporte@pagepolis.com';
    return (
        <LegalLayout title="Términos de servicio" updated="9 de junio de 2026">
            <p className="text-[15px]">
                Estos Términos de Servicio (en adelante, los “Términos”) regulan el acceso y uso de
                la plataforma <strong className="text-white">Pagepolis</strong>, accesible en
                pagepolis.com (el “Servicio”). Al registrarte o utilizar el Servicio aceptas estos
                Términos en su totalidad. Si no estás de acuerdo, no utilices el Servicio.
            </p>

            <LegalSection n={1} title="Titular del servicio">
                <p>
                    En cumplimiento de la Ley 34/2002 (LSSI-CE), el titular del Servicio es
                    <strong className="text-white"> [NOMBRE O RAZÓN SOCIAL]</strong>, con NIF
                    <strong className="text-white"> [NIF/DNI]</strong> y domicilio en
                    <strong className="text-white"> [DIRECCIÓN]</strong>. Correo de contacto:
                    <a href={`mailto:${email}`} className="text-violet-400 hover:underline"> {email}</a>.
                </p>
            </LegalSection>

            <LegalSection n={2} title="Descripción del servicio">
                <p>
                    Pagepolis permite crear, editar y publicar páginas web con ayuda de inteligencia
                    artificial. Ofrece un plan gratuito (publicación en una subruta de pagepolis.com
                    con un sello identificativo) y planes de pago que añaden dominio propio o
                    subdominio, retirada del sello y límites de uso ampliados.
                </p>
            </LegalSection>

            <LegalSection n={3} title="Registro y cuenta">
                <p>
                    Para usar el Servicio debes crear una cuenta con datos veraces y mantener la
                    confidencialidad de tus credenciales. Eres responsable de toda la actividad que
                    ocurra bajo tu cuenta. Debes ser mayor de edad o contar con autorización legal.
                </p>
            </LegalSection>

            <LegalSection n={4} title="Planes, precios y pagos">
                <p>
                    Los precios de los planes de pago se muestran en el Servicio e incluyen los
                    impuestos aplicables cuando corresponda. Los pagos se procesan a través de
                    <strong className="text-white"> Stripe</strong>; al suscribirte aceptas sus
                    condiciones. Las suscripciones se renuevan automáticamente al final de cada
                    periodo salvo que las canceles antes de la renovación.
                </p>
            </LegalSection>

            <LegalSection n={5} title="Periodo de prueba, cancelación y reembolsos">
                <p>
                    Algunos planes incluyen un periodo de prueba. Puedes cancelar en cualquier momento
                    desde tu panel; la cancelación surte efecto al final del periodo ya pagado y
                    conservas el acceso hasta entonces. Salvo obligación legal, los importes ya
                    abonados no son reembolsables. Tras una cancelación o impago, tus sitios con
                    dominio propio pueden suspenderse transcurrido el periodo de gracia.
                </p>
            </LegalSection>

            <LegalSection n={6} title="Uso aceptable">
                <p>No está permitido utilizar el Servicio para:</p>
                <ul className="list-disc pl-6 space-y-1.5">
                    <li>Publicar contenido ilícito, difamatorio, fraudulento o que infrinja derechos de terceros.</li>
                    <li>Distribuir malware, phishing, spam o contenido engañoso.</li>
                    <li>Publicar material sexual con menores, incitación al odio o violencia.</li>
                    <li>Vulnerar la seguridad del Servicio o eludir sus límites técnicos.</li>
                </ul>
                <p>El incumplimiento puede conllevar la suspensión o eliminación de la cuenta sin previo aviso.</p>
            </LegalSection>

            <LegalSection n={7} title="Contenido del usuario">
                <p>
                    Conservas la titularidad del contenido que crees o subas. Nos concedes una
                    licencia limitada para alojarlo y mostrarlo con el fin de prestar el Servicio.
                    Eres el único responsable del contenido publicado y de que dispongas de los
                    derechos necesarios sobre el mismo.
                </p>
            </LegalSection>

            <LegalSection n={8} title="Contenido generado con IA">
                <p>
                    El Servicio utiliza modelos de inteligencia artificial de terceros para generar
                    código y textos. Dicho contenido puede contener errores o imprecisiones; debes
                    revisarlo antes de publicarlo. No garantizamos su idoneidad para un fin concreto
                    ni que esté libre de coincidencias con contenidos de terceros.
                </p>
            </LegalSection>

            <LegalSection n={9} title="Dominios">
                <p>
                    El registro y la gestión de dominios propios se realizan a través de proveedores
                    externos y están sujetos a sus condiciones y tarifas. La disponibilidad de un
                    dominio no está garantizada hasta su registro efectivo.
                </p>
            </LegalSection>

            <LegalSection n={10} title="Disponibilidad y limitación de responsabilidad">
                <p>
                    El Servicio se presta “tal cual” y “según disponibilidad”, sin garantías de
                    ningún tipo, expresas o implícitas, incluidas las de comerciabilidad, idoneidad
                    para un fin concreto o funcionamiento ininterrumpido y libre de errores.
                </p>
                <p>
                    En la máxima medida permitida por la ley, no seremos responsables de:
                </p>
                <ul className="list-disc pl-5 space-y-1">
                    <li>
                        Daños indirectos, lucro cesante, pérdida de datos, de negocio, de clientes o
                        de reputación derivados del uso o de la imposibilidad de uso del Servicio.
                    </li>
                    <li>
                        <strong>Incidentes de seguridad</strong> —accesos no autorizados, intrusiones,
                        <em> hackeos</em>, robo o filtración de datos, suplantación, fraude o
                        denegación de servicio— que afecten a tu cuenta, a tus webs publicadas o a
                        los datos de tus visitantes, <strong>aunque no los hubiéramos detectado ni te
                        hubiéramos avisado</strong>, o el aviso no te hubiera llegado o llegara tarde.
                    </li>
                    <li>
                        Contenido que publiques, código que edites o modificaciones que introduzcas,
                        así como del uso que hagan terceros de tus webs publicadas.
                    </li>
                    <li>
                        Fallos, cambios o suspensiones de <strong>terceros</strong> de los que depende el
                        Servicio (alojamiento, registradores de dominios, DNS, correo, pasarela de
                        pago o proveedores de modelos de IA).
                    </li>
                    <li>Interrupciones o pérdidas por causas de fuerza mayor o ajenas a nuestro control razonable.</li>
                </ul>
                <p>
                    Realizamos esfuerzos razonables para proteger el Servicio y mantener copias de
                    seguridad, pero <strong>ningún sistema es completamente seguro</strong>: te recomendamos
                    usar contraseñas robustas, mantener tus datos de contacto al día y exportar y
                    conservar tu contenido periódicamente.
                </p>
                <p>
                    <strong>Tope de responsabilidad:</strong> si pese a lo anterior se declarase alguna
                    responsabilidad, quedará limitada de forma agregada al importe efectivamente
                    abonado por ti por el Servicio durante los tres (3) meses anteriores al hecho que
                    la origine.
                </p>
                <p>
                    Nada en estos Términos excluye responsabilidades que no puedan excluirse
                    legalmente (dolo o culpa grave, daños personales, ni los derechos imperativos que
                    la normativa reconozca a los consumidores).
                </p>
            </LegalSection>

            <LegalSection n={11} title="Modificaciones">
                <p>
                    Podemos modificar el Servicio o estos Términos. Los cambios sustanciales se
                    comunicarán por medios razonables. El uso continuado del Servicio tras su entrada
                    en vigor implica su aceptación.
                </p>
            </LegalSection>

            <LegalSection n={12} title="Ley aplicable y jurisdicción">
                <p>
                    Estos Términos se rigen por la legislación española. Para cualquier controversia,
                    las partes se someten a los juzgados y tribunales que correspondan conforme a la
                    normativa de consumidores aplicable.
                </p>
            </LegalSection>
        </LegalLayout>
    );
}

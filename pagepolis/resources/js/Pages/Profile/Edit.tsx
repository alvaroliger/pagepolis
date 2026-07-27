import { FormEventHandler, useEffect, useState } from 'react';
import { Head, useForm, usePage, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { FadeIn } from '@/Components/Motion';

// Cierra con Escape (mismo patrón que el menú de avatar de AuthenticatedLayout).
function useEscapeToClose(onClose: () => void) {
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);
}

function DeleteAccountModal({ onConfirm, onCancel }: { onConfirm: () => void; onCancel: () => void }) {
    useEscapeToClose(onCancel);
    return (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 px-4">
            <FadeIn y={12} className="bg-gray-900 border border-red-900/40 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
                <h3 className="text-white font-semibold mb-2">Eliminar tu cuenta</h3>
                <p className="text-gray-400 text-sm mb-5">
                    ¿Seguro que quieres eliminar tu cuenta?
                    <span className="block mt-2 text-red-400">Esta acción eliminará tu cuenta y todos tus proyectos y dominios de forma permanente.</span>
                </p>
                <div className="flex gap-3">
                    <button onClick={onCancel} className="flex-1 border border-gray-700 text-gray-300 hover:text-white py-2 rounded-lg text-sm transition-colors">
                        Cancelar
                    </button>
                    <button onClick={onConfirm} className="flex-1 bg-red-700 hover:bg-red-600 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                        Eliminar mi cuenta
                    </button>
                </div>
            </FadeIn>
        </div>
    );
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    whatsapp_phone: string | null;
}

export default function ProfileEdit({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth, errors: pageErrors } = usePage<{ auth: { user: User } }>().props;

    const { data, setData, patch, errors, processing } = useForm({
        name:            auth.user.name,
        email:           auth.user.email,
        whatsapp_phone:  auth.user.whatsapp_phone ?? '',
    });

    // /password y /perfil (borrado) validan con error bags con nombre
    // ("updatePassword" y "userDeletion"), así que sus errores llegan
    // anidados en pageErrors en vez de en el `errors` plano de cada useForm.
    const { data: pwData, setData: setPwData, put: putPw, processing: pwProcessing, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updateProfile: FormEventHandler = (e) => {
        e.preventDefault();
        patch('/perfil');
    };

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();
        putPw('/password', { onSuccess: () => reset() });
    };

    const { delete: deleteAccount, data: delData, setData: setDelData, processing: delProcessing } = useForm({ password: '' });
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const destroyAccount: FormEventHandler = (e) => {
        e.preventDefault();
        setShowDeleteModal(true);
    };

    const confirmDestroyAccount = () => {
        setShowDeleteModal(false);
        deleteAccount('/perfil');
    };

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">Mi perfil</h1>}>
            <Head title="Mi perfil" />

            {showDeleteModal && (
                <DeleteAccountModal
                    onConfirm={confirmDestroyAccount}
                    onCancel={() => setShowDeleteModal(false)}
                />
            )}

            <div className="max-w-2xl mx-auto px-4 py-8 space-y-6">
                {/* Datos personales */}
                <div className="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                    <h2 className="text-lg font-bold text-white mb-5">Datos personales</h2>

                    {status && <div className="mb-4 text-sm text-green-400 bg-green-900/20 border border-green-800 rounded-lg p-3">{status}</div>}

                    <form onSubmit={updateProfile} className="space-y-4">
                        <div>
                            <label className="block text-sm text-gray-400 mb-1">Nombre</label>
                            <input
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-400">{errors.name}</p>}
                        </div>
                        <div>
                            <label className="block text-sm text-gray-400 mb-1">Email</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={e => setData('email', e.target.value)}
                                className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                            />
                            {errors.email && <p className="mt-1 text-sm text-red-400">{errors.email}</p>}
                        </div>
                        <div>
                            <label className="block text-sm text-gray-400 mb-1">WhatsApp (opcional)</label>
                            <input
                                type="tel"
                                value={data.whatsapp_phone}
                                onChange={e => setData('whatsapp_phone', e.target.value)}
                                placeholder="+34 612 345 678"
                                className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                            />
                            <p className="mt-1 text-xs text-gray-600">
                                Vincula tu WhatsApp para editar tu web enviando mensajes.
                            </p>
                            {errors.whatsapp_phone && <p className="mt-1 text-sm text-red-400">{errors.whatsapp_phone}</p>}
                        </div>
                        {mustVerifyEmail && !auth.user.email_verified_at && (
                            <p className="text-sm text-yellow-400">Tu email no está verificado.</p>
                        )}
                        <button type="submit" disabled={processing} className="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">
                            {processing ? 'Guardando…' : 'Guardar cambios'}
                        </button>
                    </form>
                </div>

                {/* Contraseña */}
                <div className="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                    <h2 className="text-lg font-bold text-white mb-5">Cambiar contraseña</h2>
                    <form onSubmit={updatePassword} className="space-y-4">
                        {(['current_password', 'password', 'password_confirmation'] as const).map((field, i) => (
                            <div key={field}>
                                <label className="block text-sm text-gray-400 mb-1">
                                    {['Contraseña actual', 'Nueva contraseña', 'Confirmar contraseña'][i]}
                                </label>
                                <input
                                    type="password"
                                    value={pwData[field]}
                                    onChange={e => setPwData(field, e.target.value)}
                                    className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                                />
                                {pageErrors.updatePassword?.[field] && <p className="mt-1 text-sm text-red-400">{pageErrors.updatePassword[field]}</p>}
                            </div>
                        ))}
                        <button type="submit" disabled={pwProcessing} className="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">
                            {pwProcessing ? 'Actualizando...' : 'Cambiar contraseña'}
                        </button>
                    </form>
                </div>

                {/* Privacidad y datos */}
                <div className="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                    <h2 className="text-lg font-bold text-white mb-2">Privacidad y datos</h2>
                    <p className="text-gray-400 text-sm mb-4">
                        Descarga una copia de todos tus datos (perfil, proyectos y dominios) en
                        formato JSON, conforme al RGPD.
                    </p>
                    <a
                        href="/perfil/exportar"
                        className="inline-block bg-gray-800 hover:bg-gray-700 border border-gray-700 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors"
                    >
                        Exportar mis datos
                    </a>
                </div>

                {/* Eliminar cuenta */}
                <div className="bg-gray-900 border border-red-900/30 rounded-2xl p-6">
                    <h2 className="text-lg font-bold text-red-400 mb-2">Zona de peligro</h2>
                    <p className="text-gray-400 text-sm mb-4">Eliminar tu cuenta es irreversible. Todos tus proyectos y dominios serán eliminados.</p>
                    <form onSubmit={destroyAccount} className="space-y-3">
                        <input
                            type="password"
                            value={delData.password}
                            onChange={e => setDelData('password', e.target.value)}
                            placeholder="Confirma tu contraseña"
                            className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-red-500"
                        />
                        {pageErrors.userDeletion?.password && <p className="text-sm text-red-400">{pageErrors.userDeletion.password}</p>}
                        <button type="submit" disabled={delProcessing} className="bg-red-700 hover:bg-red-600 disabled:opacity-50 text-white px-6 py-2.5 rounded-lg font-semibold transition-colors">
                            {delProcessing ? 'Eliminando...' : 'Eliminar mi cuenta'}
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

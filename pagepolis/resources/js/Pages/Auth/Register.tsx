import { FormEventHandler, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Register() {
    const [acceptedTos, setAcceptedTos] = useState(false);
    const [confirmTouched, setConfirmTouched] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const passwordsMismatch = confirmTouched
        && data.password_confirmation.length > 0
        && data.password !== data.password_confirmation;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!acceptedTos) return;
        post('/register');
    };

    return (
        <GuestLayout>
            <Head title="Crear cuenta" />
            <h2 className="text-2xl font-bold text-white mb-6">Crear cuenta gratis</h2>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="name" className="block text-sm text-gray-400 mb-1">Nombre</label>
                    <input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={e => setData('name', e.target.value)}
                        autoComplete="name"
                        aria-invalid={!!errors.name}
                        aria-describedby={errors.name ? 'name-error' : undefined}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500 transition-colors"
                        required
                    />
                    {errors.name && <p id="name-error" className="mt-1 text-sm text-red-400">{errors.name}</p>}
                </div>

                <div>
                    <label htmlFor="email" className="block text-sm text-gray-400 mb-1">Email</label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={e => setData('email', e.target.value)}
                        autoComplete="email"
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500 transition-colors"
                        required
                    />
                    {errors.email && <p id="email-error" className="mt-1 text-sm text-red-400">{errors.email}</p>}
                </div>

                <div>
                    <label htmlFor="password" className="block text-sm text-gray-400 mb-1">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={e => setData('password', e.target.value)}
                        autoComplete="new-password"
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500 transition-colors"
                        required
                        minLength={8}
                    />
                    {errors.password && <p id="password-error" className="mt-1 text-sm text-red-400">{errors.password}</p>}
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="block text-sm text-gray-400 mb-1">Confirmar contraseña</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={e => setData('password_confirmation', e.target.value)}
                        onBlur={() => setConfirmTouched(true)}
                        autoComplete="new-password"
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500 transition-colors"
                        required
                    />
                    {(passwordsMismatch || errors.password_confirmation) && (
                        <p className="mt-1 text-sm text-red-400">
                            {errors.password_confirmation || 'Las contraseñas no coinciden.'}
                        </p>
                    )}
                </div>

                <label className="flex items-start gap-3 cursor-pointer group">
                    <input
                        type="checkbox"
                        checked={acceptedTos}
                        onChange={e => setAcceptedTos(e.target.checked)}
                        className="mt-0.5 w-4 h-4 accent-violet-500 flex-shrink-0"
                        required
                    />
                    <span className="text-sm text-gray-400 group-hover:text-gray-300 transition-colors leading-snug">
                        Acepto los{' '}
                        <a href="/terminos" target="_blank" className="text-violet-400 hover:underline">términos de servicio</a>
                        {' '}y la{' '}
                        <a href="/privacidad" target="_blank" className="text-violet-400 hover:underline">política de privacidad</a>
                    </span>
                </label>

                <button
                    type="submit"
                    disabled={processing || !acceptedTos}
                    className="w-full bg-gradient-to-r from-violet-600 to-cyan-500 text-white py-3 rounded-lg font-semibold hover:opacity-90 disabled:opacity-50 transition-opacity"
                >
                    {processing ? 'Creando cuenta…' : 'Crear cuenta gratis'}
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-500">
                ¿Ya tienes cuenta?{' '}
                <Link href="/login" className="text-violet-400 hover:text-violet-300 font-medium">
                    Iniciar sesión
                </Link>
            </p>
        </GuestLayout>
    );
}

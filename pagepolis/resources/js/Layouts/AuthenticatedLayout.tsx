import { useState, PropsWithChildren, ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';

interface AuthLayoutProps extends PropsWithChildren {
    header?: ReactNode;
}

export default function AuthenticatedLayout({ header, children }: AuthLayoutProps) {
    const { auth } = usePage<{ auth: { user: { name: string; email: string; role: string } } }>().props;
    const [menuOpen, setMenuOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-950 text-white">
            <nav className="border-b border-gray-800 bg-gray-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex items-center gap-8">
                            <Link href="/dashboard">
                                <span className="text-xl font-black bg-gradient-to-r from-violet-500 to-cyan-400 bg-clip-text text-transparent">
                                    Pagepolis
                                </span>
                            </Link>
                            <div className="hidden md:flex gap-4">
                                <Link href="/dashboard" className="text-gray-400 hover:text-white text-sm transition-colors">
                                    Dashboard
                                </Link>
                                <Link href="/analytics" className="text-gray-400 hover:text-white text-sm transition-colors">
                                    Analítica
                                </Link>
                                <Link href="/plantillas" className="text-gray-400 hover:text-white text-sm transition-colors">
                                    Plantillas
                                </Link>
                                {auth.user.role === 'admin' && (
                                    <Link href="/admin" className="text-gray-400 hover:text-violet-400 text-sm transition-colors">
                                        Admin
                                    </Link>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <span className="text-sm text-gray-400 hidden sm:block">{auth.user.name}</span>
                            <div className="relative">
                                <button
                                    onClick={() => setMenuOpen(!menuOpen)}
                                    className="w-8 h-8 rounded-full bg-violet-600 text-white text-sm font-bold flex items-center justify-center"
                                >
                                    {auth.user.name[0].toUpperCase()}
                                </button>
                                {menuOpen && (
                                    <div className="absolute right-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50">
                                        <Link href="/perfil" className="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-t-lg">
                                            Mi perfil
                                        </Link>
                                        <Link href="/facturacion/portal" className="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                            Suscripción
                                        </Link>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            className="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-700 rounded-b-lg"
                                        >
                                            Cerrar sesión
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-gray-900 border-b border-gray-800">
                    <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}

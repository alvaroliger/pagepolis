import { useState, useEffect, useRef, PropsWithChildren, ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import PagepolisLogo from '@/Components/PagepolisLogo';
import FlashBanner from '@/Components/FlashBanner';

interface AuthLayoutProps extends PropsWithChildren {
    header?: ReactNode;
}

function NavLink({ href, active, children, className = '' }: PropsWithChildren<{ href: string; active: boolean; className?: string }>) {
    return (
        <Link
            href={href}
            className={`relative text-sm transition-colors py-1 ${active ? 'text-white font-semibold' : 'text-gray-400 hover:text-white'} ${className}`}
        >
            {children}
            {active && (
                <span className="absolute -bottom-[18px] left-0 right-0 h-0.5 bg-gradient-to-r from-violet-500 to-fuchsia-500 rounded-full" aria-hidden="true" />
            )}
        </Link>
    );
}

export default function AuthenticatedLayout({ header, children }: AuthLayoutProps) {
    const page = usePage<{ auth: { user: { name: string; email: string; role: string } }; leadsUnread?: number }>();
    const auth = page.props.auth;
    const leadsUnread = page.props.leadsUnread ?? 0;
    const url = page.url;
    const [menuOpen, setMenuOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const reduce = useReducedMotion();

    useEffect(() => {
        if (!menuOpen) return;
        const close = (e: MouseEvent) => {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) setMenuOpen(false);
        };
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setMenuOpen(false); };
        document.addEventListener('mousedown', close);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', close);
            document.removeEventListener('keydown', onKey);
        };
    }, [menuOpen]);

    const isActive = (prefix: string) => url === prefix || url.startsWith(prefix + '/') || url.startsWith(prefix + '?');

    return (
        <div className="min-h-screen bg-gray-950 text-white">
            <nav className="sticky top-0 z-40 border-b border-white/5 bg-gray-950/85 backdrop-blur-xl">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex items-center gap-8">
                            <Link href="/dashboard" className="flex items-center gap-2">
                                <PagepolisLogo size={28} />
                                <span className="text-xl font-black tracking-tight bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                                    pagepolis
                                </span>
                            </Link>
                            <div className="hidden md:flex items-center gap-6">
                                <NavLink href="/dashboard" active={isActive('/dashboard')}>Dashboard</NavLink>
                                <NavLink href="/analytics" active={isActive('/analytics')}>Analítica</NavLink>
                                <NavLink href="/mensajes" active={isActive('/mensajes')} className="flex items-center gap-1.5">
                                    Mensajes
                                    {leadsUnread > 0 && (
                                        <span className="bg-violet-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center leading-none">
                                            {leadsUnread}
                                        </span>
                                    )}
                                </NavLink>
                                <NavLink href="/plantillas" active={isActive('/plantillas')}>Plantillas</NavLink>
                                {auth.user.role === 'admin' && (
                                    <NavLink href="/admin" active={isActive('/admin')}>Admin</NavLink>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <span className="text-sm text-gray-400 hidden sm:block">{auth.user.name}</span>
                            <div className="relative" ref={menuRef}>
                                <button
                                    onClick={() => setMenuOpen(!menuOpen)}
                                    aria-haspopup="menu"
                                    aria-expanded={menuOpen}
                                    className="w-8 h-8 rounded-full bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white text-sm font-bold flex items-center justify-center ring-2 ring-transparent hover:ring-violet-500/40 transition-shadow"
                                >
                                    {auth.user.name[0].toUpperCase()}
                                </button>
                                <AnimatePresence>
                                    {menuOpen && (
                                        <motion.div
                                            initial={reduce ? false : { opacity: 0, y: -6, scale: 0.97 }}
                                            animate={{ opacity: 1, y: 0, scale: 1 }}
                                            exit={reduce ? undefined : { opacity: 0, y: -6, scale: 0.97 }}
                                            transition={{ duration: 0.16, ease: 'easeOut' }}
                                            className="absolute right-0 mt-2 w-48 origin-top-right bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50 overflow-hidden"
                                            role="menu"
                                        >
                                            <Link href="/perfil" role="menuitem" className="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors">
                                                Mi perfil
                                            </Link>
                                            <Link href="/facturacion/portal" role="menuitem" className="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors">
                                                Suscripción
                                            </Link>
                                            <Link
                                                href="/logout"
                                                method="post"
                                                as="button"
                                                role="menuitem"
                                                className="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-700 transition-colors"
                                            >
                                                Cerrar sesión
                                            </Link>
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <FlashBanner />

            {header && (
                <header className="bg-gray-900/60 border-b border-gray-800">
                    <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}

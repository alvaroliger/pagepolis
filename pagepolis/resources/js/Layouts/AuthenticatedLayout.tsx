import { useState, useEffect, useRef, PropsWithChildren, ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import PagepolisLogo from '@/Components/PagepolisLogo';

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
    const [mobileNavOpen, setMobileNavOpen] = useState(false);
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

    useEffect(() => {
        if (!mobileNavOpen) return;
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setMobileNavOpen(false); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [mobileNavOpen]);

    useEffect(() => {
        setMobileNavOpen(false);
    }, [url]);

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
                            <button
                                onClick={() => setMobileNavOpen(!mobileNavOpen)}
                                aria-label={mobileNavOpen ? 'Cerrar menú' : 'Abrir menú'}
                                aria-expanded={mobileNavOpen}
                                aria-controls="mobile-nav-panel"
                                className="md:hidden w-8 h-8 flex items-center justify-center text-gray-300 hover:text-white transition-colors"
                            >
                                {mobileNavOpen ? (
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="w-6 h-6" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                ) : (
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="w-6 h-6" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                                    </svg>
                                )}
                            </button>
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

                <AnimatePresence>
                    {mobileNavOpen && (
                        <motion.div
                            id="mobile-nav-panel"
                            initial={reduce ? false : { opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={reduce ? undefined : { opacity: 0, height: 0 }}
                            transition={{ duration: 0.16, ease: 'easeOut' }}
                            className="md:hidden border-t border-white/5 overflow-hidden"
                        >
                            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col gap-1">
                                <Link href="/dashboard" className={`rounded-lg px-3 py-2.5 text-sm ${isActive('/dashboard') ? 'bg-white/10 text-white font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                                    Dashboard
                                </Link>
                                <Link href="/analytics" className={`rounded-lg px-3 py-2.5 text-sm ${isActive('/analytics') ? 'bg-white/10 text-white font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                                    Analítica
                                </Link>
                                <Link href="/mensajes" className={`rounded-lg px-3 py-2.5 text-sm flex items-center gap-1.5 ${isActive('/mensajes') ? 'bg-white/10 text-white font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                                    Mensajes
                                    {leadsUnread > 0 && (
                                        <span className="bg-violet-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center leading-none">
                                            {leadsUnread}
                                        </span>
                                    )}
                                </Link>
                                <Link href="/plantillas" className={`rounded-lg px-3 py-2.5 text-sm ${isActive('/plantillas') ? 'bg-white/10 text-white font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                                    Plantillas
                                </Link>
                                {auth.user.role === 'admin' && (
                                    <Link href="/admin" className={`rounded-lg px-3 py-2.5 text-sm ${isActive('/admin') ? 'bg-white/10 text-white font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white'}`}>
                                        Admin
                                    </Link>
                                )}
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </nav>

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

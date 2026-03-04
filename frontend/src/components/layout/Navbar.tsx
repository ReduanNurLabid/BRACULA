import { Link } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase'
import { Menu, X, Home, MessageSquare } from 'lucide-react'
import { useShoutbox } from '../../contexts/ShoutboxContext'

export const Navbar = () => {
    const { user } = useAuth()
    const [avatarUrl, setAvatarUrl] = useState<string | null>(null)
    const [menuOpen, setMenuOpen] = useState(false)
    const { hasUnread } = useShoutbox()

    useEffect(() => {
        if (!user) { setAvatarUrl(null); return }
        const fetchAvatar = async () => {
            const { data } = await supabase.from('profiles').select('avatar_url').eq('id', user.id).single()
            if (data?.avatar_url) setAvatarUrl(data.avatar_url)
        }
        fetchAvatar()
    }, [user])

    const closeMenu = () => setMenuOpen(false)

    return (
        <>
            <nav className="glass-panel navbar-container">
                <Link to="/" style={{ fontWeight: 800, fontSize: '1.5rem', letterSpacing: '-0.5px' }} className="text-gradient hover-lift">
                    BRACULA
                </Link>

                {/* Desktop Nav */}
                <div className="nav-desktop">
                    <div style={{ display: 'flex', gap: '1.5rem', color: 'var(--text-secondary)' }}>
                        <Link to="/" style={{ color: 'var(--text-secondary)', textDecoration: 'none', fontWeight: 500, display: 'flex', alignItems: 'center', gap: '0.35rem' }} className="hover-white"><Home size={18} /> Home</Link>
                        <Link to="/study" style={{ color: 'var(--text-secondary)', textDecoration: 'none', fontWeight: 500 }} className="hover-white">Study</Link>
                        <Link to="/rides" style={{ color: 'var(--text-secondary)', textDecoration: 'none', fontWeight: 500 }} className="hover-white">Rides</Link>
                        <Link to="/clubs" style={{ color: 'var(--text-secondary)', textDecoration: 'none', fontWeight: 500 }} className="hover-white">Clubs</Link>
                        <Link to="/to-let" style={{ color: 'var(--text-secondary)', textDecoration: 'none', fontWeight: 500 }} className="hover-white">To-Let</Link>
                        <Link to="/shoutbox" style={{ color: 'var(--text-secondary)', textDecoration: 'none', fontWeight: 500, display: 'flex', alignItems: 'center', gap: '0.35rem', position: 'relative' }} className="hover-white">
                            <MessageSquare size={18} />
                            Shoutbox
                            {hasUnread && (
                                <span style={{ position: 'absolute', top: '-2px', right: '-8px', width: '8px', height: '8px', background: '#ef4444', borderRadius: '50%', boxShadow: '0 0 10px rgba(239, 68, 68, 0.5)' }}></span>
                            )}
                        </Link>
                    </div>
                    {user ? (
                        <Link to="/profile" className="hover-lift" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', textDecoration: 'none' }}>
                            {avatarUrl ? (
                                <img src={avatarUrl} alt="Profile" style={{ width: '34px', height: '34px', borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--accent-primary)' }} />
                            ) : (
                                <div style={{ flexShrink: 0, overflow: 'hidden', width: '34px', height: '34px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, color: 'white', fontSize: '0.85rem', border: '2px solid var(--accent-primary)' }}>
                                    {user.email?.charAt(0).toUpperCase()}
                                </div>
                            )}
                        </Link>
                    ) : (
                        <Link to="/login" className="btn-primary" style={{ padding: '0.5rem 1.25rem' }}>Login</Link>
                    )}
                </div>

                {/* Mobile Hamburger */}
                <div className="nav-mobile-toggle">
                    {user && (
                        <Link to="/profile" onClick={closeMenu}>
                            {avatarUrl ? (
                                <img src={avatarUrl} alt="Profile" style={{ width: '30px', height: '30px', borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--accent-primary)' }} />
                            ) : (
                                <div style={{ flexShrink: 0, overflow: 'hidden', width: '30px', height: '30px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, color: 'white', fontSize: '0.75rem' }}>
                                    {user.email?.charAt(0).toUpperCase()}
                                </div>
                            )}
                        </Link>
                    )}
                    <button onClick={() => setMenuOpen(!menuOpen)} style={{ background: 'none', border: 'none', color: 'white', padding: '0.25rem' }}>
                        {menuOpen ? <X size={24} /> : <Menu size={24} />}
                    </button>
                </div>
            </nav>

            {/* Mobile Dropdown Menu */}
            {menuOpen && (
                <div className="nav-mobile-menu open glass-panel" onClick={closeMenu}>
                    <Link to="/" className="nav-mobile-link">🏠 Home</Link>
                    <Link to="/study" className="nav-mobile-link">📚 Study Materials</Link>
                    <Link to="/rides" className="nav-mobile-link">🚗 Ride Sharing</Link>
                    <Link to="/clubs" className="nav-mobile-link">🎭 Clubs</Link>
                    <Link to="/to-let" className="nav-mobile-link">🏠 To-Let</Link>
                    <Link to="/shoutbox" className="nav-mobile-link" style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        💬 Shoutbox
                        {hasUnread && (
                            <span style={{ width: '8px', height: '8px', background: '#ef4444', borderRadius: '50%', boxShadow: '0 0 10px rgba(239, 68, 68, 0.5)' }}></span>
                        )}
                    </Link>
                    <Link to="/feedback" className="nav-mobile-link">💡 Feedback</Link>
                    <div style={{ borderTop: '1px solid var(--border-glass)', marginTop: '0.25rem', paddingTop: '0.5rem' }}>
                        {user ? (
                            <Link to="/profile" className="nav-mobile-link">👤 Profile</Link>
                        ) : (
                            <Link to="/login" className="nav-mobile-link" style={{ color: 'var(--accent-primary)' }}>🔑 Login</Link>
                        )}
                    </div>
                </div>
            )}
        </>
    )
}

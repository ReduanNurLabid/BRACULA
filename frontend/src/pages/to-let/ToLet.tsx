import { Home } from 'lucide-react'

export const ToLet = () => {
    return (
        <div className="container animate-fade-in-up" style={{ padding: '2rem 1.5rem', flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', textAlign: 'center', minHeight: '60vh' }}>
            <div className="glass-panel" style={{ padding: '4rem 2rem', maxWidth: '600px', width: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1.5rem' }}>
                <div style={{ background: 'var(--accent-glow)', padding: '1.5rem', borderRadius: '50%', marginBottom: '1rem' }}>
                    <Home size={48} color="var(--accent-primary)" />
                </div>
                <h1 className="text-gradient" style={{ fontSize: '3rem', margin: 0 }}>To-Let</h1>
                <p style={{ color: 'var(--text-secondary)', fontSize: '1.2rem', maxWidth: '400px', lineHeight: 1.6 }}>
                    Find student housing, sublets, and roommates near BRAC University.
                </p>

                <div style={{ marginTop: '2rem', padding: '1rem 2rem', background: 'rgba(255,255,255,0.05)', borderRadius: 'var(--radius-lg)', border: '1px solid var(--border-glass)' }}>
                    <h2 style={{ color: 'var(--text-primary)', fontSize: '1.5rem', marginBottom: '0.5rem' }}>Coming Soon</h2>
                    <p style={{ color: 'var(--text-muted)', margin: 0 }}>
                        We're currently perfecting the core features for our beta launch. The housing hub will be available in the next major update!
                    </p>
                </div>
            </div>
        </div>
    )
}

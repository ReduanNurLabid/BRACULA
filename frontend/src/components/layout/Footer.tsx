import { Link } from 'react-router-dom'

export const Footer = () => {
    return (
        <footer style={{
            marginTop: 'auto',
            padding: '2rem',
            textAlign: 'center',
            borderTop: '1px solid var(--border-glass)',
            background: 'var(--bg-glass-strong)'
        }}>
            <div style={{ maxWidth: '1200px', margin: '0 auto', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '0.75rem' }}>
                <div style={{ display: 'flex', gap: '1.5rem', fontSize: '0.85rem' }}>
                    <Link to="/feedback" style={{ color: 'var(--accent-primary)', textDecoration: 'none', fontWeight: 600 }}>💬 Send Feedback</Link>
                    <Link to="/clubs" style={{ color: 'var(--text-secondary)', textDecoration: 'none' }}>Clubs</Link>
                    <Link to="/rides" style={{ color: 'var(--text-secondary)', textDecoration: 'none' }}>Rides</Link>
                    <Link to="/study" style={{ color: 'var(--text-secondary)', textDecoration: 'none' }}>Study</Link>
                </div>
                <div style={{ color: 'var(--text-secondary)', fontSize: '0.8rem' }}>
                    © {new Date().getFullYear()} BRACULA — BRAC University Community. Built for students, by students.
                </div>
            </div>
        </footer>
    )
}

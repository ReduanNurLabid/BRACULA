
export const Footer = () => {
    return (
        <footer style={{
            padding: '0.75rem 1rem',
            textAlign: 'center',
            borderTop: '1px solid var(--border-glass)',
            background: 'var(--bg-glass-strong)'
        }}>
            <div style={{ color: 'var(--text-secondary)', fontSize: '0.75rem' }}>
                © {new Date().getFullYear()} BRACULA — BRAC University Community. Built for students, by students.
            </div>
        </footer>
    )
}

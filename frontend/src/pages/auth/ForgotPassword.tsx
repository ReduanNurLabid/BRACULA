import { useState } from 'react'
import { supabase } from '../../lib/supabase'
import { Link } from 'react-router-dom'

export const ForgotPassword = () => {
    const [email, setEmail] = useState('')
    const [message, setMessage] = useState('')
    const [loading, setLoading] = useState(false)

    const handleReset = async (e: React.FormEvent) => {
        e.preventDefault()
        setLoading(true)
        setMessage('')

        try {
            const { error } = await supabase.auth.resetPasswordForEmail(email, {
                redirectTo: 'http://localhost:5173/reset-password', // Target route for the new password input
            })

            if (error) throw error
            setMessage('Password reset instructions have been sent to your email.')
        } catch (err: any) {
            setMessage(err.message || 'An error occurred.')
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="container animate-fade-in-up" style={{ padding: '6rem 1.5rem', display: 'flex', justifyContent: 'center' }}>
            <div className="glass-panel" style={{ padding: '3rem', width: '100%', maxWidth: '450px' }}>
                <h2 className="text-gradient" style={{ fontSize: '2rem', marginBottom: '0.5rem', textAlign: 'center' }}>
                    Reset Password
                </h2>
                <p style={{ color: 'var(--text-secondary)', textAlign: 'center', marginBottom: '2rem' }}>
                    Enter your student email to receive a reset link
                </p>

                <form onSubmit={handleReset} style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>

                    <div>
                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                            Student Email
                        </label>
                        <input
                            type="email"
                            required
                            placeholder="e.g. john.doe@g.bracu.ac.bd"
                            className="input-glass"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                        />
                    </div>

                    <button type="submit" className="btn-primary" disabled={loading} style={{ width: '100%' }}>
                        {loading ? 'Sending...' : 'Send Reset Link'}
                    </button>

                    <div style={{ textAlign: 'center', marginTop: '1rem' }}>
                        <Link to="/login" style={{ color: 'var(--accent-primary)', textDecoration: 'none', fontSize: '0.9rem' }}>
                            Back to Login
                        </Link>
                    </div>
                </form>

                {message && (
                    <div style={{
                        marginTop: '1.5rem',
                        padding: '1rem',
                        borderRadius: 'var(--radius-sm)',
                        background: message.includes('sent') ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)',
                        color: message.includes('sent') ? '#34d399' : '#f87171',
                        textAlign: 'center',
                        fontSize: '0.9rem'
                    }}>
                        {message}
                    </div>
                )}
            </div>
        </div>
    )
}

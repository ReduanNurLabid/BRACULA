import { useState, useEffect } from 'react'
import { supabase } from '../../lib/supabase'
import { useNavigate } from 'react-router-dom'

export const ResetPassword = () => {
    const [password, setPassword] = useState('')
    const [message, setMessage] = useState('')
    const [loading, setLoading] = useState(false)
    const navigate = useNavigate()

    useEffect(() => {
        // Supabase will automatically parse the hash fragment and establish a session 
        // if the recovery link is clicked. We just need to check if there is an active session limit.
        supabase.auth.onAuthStateChange(async (event) => {
            if (event === 'PASSWORD_RECOVERY') {
                setMessage('Please enter a new password below.')
            }
        })
    }, [])

    const handleUpdate = async (e: React.FormEvent) => {
        e.preventDefault()
        setLoading(true)
        setMessage('')

        try {
            const { error } = await supabase.auth.updateUser({ password })

            if (error) throw error

            setMessage('Password updated successfully! Redirecting...')
            setTimeout(() => navigate('/login'), 2000)
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
                    Type New Password
                </h2>

                <form onSubmit={handleUpdate} style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem', marginTop: '2rem' }}>

                    <div>
                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                            New Password
                        </label>
                        <input
                            type="password"
                            required
                            placeholder="••••••••"
                            className="input-glass"
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                        />
                    </div>

                    <button type="submit" className="btn-primary" disabled={loading} style={{ width: '100%' }}>
                        {loading ? 'Updating...' : 'Update Password'}
                    </button>

                </form>

                {message && (
                    <div style={{
                        marginTop: '1.5rem',
                        padding: '1rem',
                        borderRadius: 'var(--radius-sm)',
                        background: message.includes('success') ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)',
                        color: message.includes('success') ? '#34d399' : '#f87171',
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

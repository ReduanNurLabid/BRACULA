import { useState } from 'react'
import { supabase } from '../../lib/supabase'
import { useNavigate, Link } from 'react-router-dom'

export const Login = () => {
    const [isSignUp, setIsSignUp] = useState(false)
    const [email, setEmail] = useState('')
    const [password, setPassword] = useState('')
    const [fullName, setFullName] = useState('')
    const [message, setMessage] = useState('')
    const [loading, setLoading] = useState(false)
    const navigate = useNavigate()

    const handleAuth = async (e: React.FormEvent) => {
        e.preventDefault()
        setLoading(true)
        setMessage('')

        try {
            // Validate BRACU domain
            if (!email.endsWith('@g.bracu.ac.bd')) {
                setMessage('Please use your BRACU student email (@g.bracu.ac.bd)')
                setLoading(false)
                return
            }

            if (isSignUp) {
                // Register User via Supabase directly 
                // Note: Email verification is usually required by default in Supabase
                const { error } = await supabase.auth.signUp({
                    email,
                    password,
                    options: {
                        data: {
                            full_name: fullName
                        }
                    }
                })

                if (error) throw error
                // The trigger we created in SQL `on_auth_user_created` will auto-create the profile
                setMessage('Registration successful! Please check your email to verify your account before logging in.')
            } else {
                // Login User
                const { error } = await supabase.auth.signInWithPassword({
                    email,
                    password
                })

                if (error) throw error

                // Redirect to Materials page on success
                navigate('/study')
            }
        } catch (err: any) {
            setMessage(err.message || 'An error occurred during authentication.')
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="container animate-fade-in-up" style={{ padding: '6rem 1.5rem', display: 'flex', justifyContent: 'center' }}>
            <div className="glass-panel" style={{ padding: '3rem', width: '100%', maxWidth: '450px' }}>
                <h2 className="text-gradient" style={{ fontSize: '2rem', marginBottom: '0.5rem', textAlign: 'center' }}>
                    {isSignUp ? 'Join BRACULA' : 'Welcome Back'}
                </h2>
                <p style={{ color: 'var(--text-secondary)', textAlign: 'center', marginBottom: '2rem' }}>
                    {isSignUp ? 'Create your student account' : 'Sign in to your account'}
                </p>

                <form onSubmit={handleAuth} style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>

                    {isSignUp && (
                        <div>
                            <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                                Full Name
                            </label>
                            <input
                                type="text"
                                required
                                placeholder="e.g. John Doe"
                                className="input-glass"
                                value={fullName}
                                onChange={e => setFullName(e.target.value)}
                            />
                        </div>
                    )}

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

                    <div>
                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                            Password
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

                    {!isSignUp && (
                        <Link
                            to="/forgot-password"
                            style={{ color: 'var(--text-secondary)', fontSize: '0.9rem', marginTop: '-0.5rem', alignSelf: 'flex-start', textDecoration: 'none' }}
                            onMouseOver={((e: React.MouseEvent<HTMLAnchorElement>) => e.currentTarget.style.color = 'var(--accent-primary)') as any}
                            onMouseOut={((e: React.MouseEvent<HTMLAnchorElement>) => e.currentTarget.style.color = 'var(--text-secondary)') as any}
                        >
                            Forgot password?
                        </Link>
                    )}

                    <button type="submit" className="btn-primary" disabled={loading} style={{ width: '100%' }}>
                        {loading ? 'Processing...' : (isSignUp ? 'Sign Up' : 'Login')}
                    </button>

                    <div style={{ textAlign: 'center', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                        {isSignUp ? 'Already have an account? ' : "Don't have an account? "}
                        <span
                            style={{ color: 'var(--accent-primary)', cursor: 'pointer', fontWeight: 600 }}
                            onClick={() => { setIsSignUp(!isSignUp); setMessage('') }}
                        >
                            {isSignUp ? 'Login here' : 'Sign up here'}
                        </span>
                    </div>
                </form>

                {message && (
                    <div style={{
                        marginTop: '1.5rem',
                        padding: '1rem',
                        borderRadius: 'var(--radius-sm)',
                        background: message.includes('successful') ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)',
                        color: message.includes('successful') ? '#34d399' : '#f87171',
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

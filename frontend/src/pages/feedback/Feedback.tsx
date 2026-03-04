import { useState } from 'react'
import { supabase } from '../../lib/supabase'
import { useAuth } from '../../contexts/AuthContext'
import { Send } from 'lucide-react'
import { toast } from 'react-hot-toast'

type FeedbackType = 'suggestion' | 'bug' | 'praise' | 'other'

const TYPES: { value: FeedbackType; label: string; emoji: string; color: string }[] = [
    { value: 'suggestion', label: 'Suggestion', emoji: '💡', color: '#f59e0b' },
    { value: 'bug', label: 'Bug Report', emoji: '🐛', color: '#ef4444' },
    { value: 'praise', label: 'Praise', emoji: '❤️', color: '#ec4899' },
    { value: 'other', label: 'Other', emoji: '💬', color: '#3b82f6' },
]

export const Feedback = () => {
    const { user } = useAuth()
    const [feedbackType, setFeedbackType] = useState<FeedbackType>('suggestion')
    const [message, setMessage] = useState('')
    const [contactEmail, setContactEmail] = useState('')
    const [wantsToContribute, setWantsToContribute] = useState(false)
    const [submitting, setSubmitting] = useState(false)
    const [submitted, setSubmitted] = useState(false)

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!message.trim()) return

        setSubmitting(true)
        try {
            const { error } = await supabase.from('feedback').insert({
                user_id: user?.id || null,
                type: feedbackType,
                message: message.trim(),
                contact_email: wantsToContribute ? contactEmail.trim() : null,
                wants_to_contribute: wantsToContribute
            })
            if (error) throw error
            setSubmitted(true)
        } catch (err: any) {
            toast.error(err.message)
        } finally {
            setSubmitting(false)
        }
    }

    if (submitted) {
        return (
            <div className="container animate-fade-in-up" style={{ padding: '4rem 1.5rem', display: 'flex', justifyContent: 'center' }}>
                <div className="glass-panel" style={{ padding: '4rem 3rem', textAlign: 'center', maxWidth: '500px', width: '100%' }}>
                    <div style={{ fontSize: '4rem', marginBottom: '1rem' }}>🎉</div>
                    <h2 className="text-gradient" style={{ fontSize: '2rem', marginBottom: '1rem' }}>Thank You!</h2>
                    <p style={{ color: 'var(--text-secondary)', fontSize: '1.1rem', lineHeight: 1.6, marginBottom: '2rem' }}>
                        Your feedback helps us build a better platform for the BRACU community. We truly appreciate your time!
                    </p>
                    <button className="btn-primary" onClick={() => { setSubmitted(false); setMessage(''); setContactEmail(''); setWantsToContribute(false) }}>
                        Submit Another
                    </button>
                </div>
            </div>
        )
    }

    return (
        <div className="container animate-fade-in-up" style={{ padding: '4rem 1.5rem', display: 'flex', justifyContent: 'center' }}>
            <div style={{ width: '100%', maxWidth: '600px' }}>

                <div style={{ textAlign: 'center', marginBottom: '2.5rem' }}>
                    <h1 className="text-gradient" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Share Your Feedback</h1>
                    <p style={{ color: 'var(--text-secondary)', fontSize: '1.1rem' }}>
                        BRACULA is in <span style={{ color: 'var(--accent-primary)', fontWeight: 700 }}>Beta</span> — your voice shapes the future of this platform.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="glass-panel" style={{ padding: '2rem', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>

                    {/* Feedback Type Picker */}
                    <div>
                        <label style={{ display: 'block', marginBottom: '0.75rem', fontSize: '0.9rem', color: 'var(--text-secondary)', fontWeight: 600 }}>What kind of feedback?</label>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.5rem' }}>
                            {TYPES.map(t => (
                                <button
                                    key={t.value}
                                    type="button"
                                    onClick={() => setFeedbackType(t.value)}
                                    style={{
                                        padding: '0.75rem',
                                        borderRadius: 'var(--radius-md)',
                                        border: `1.5px solid ${feedbackType === t.value ? t.color : 'var(--border-glass)'}`,
                                        background: feedbackType === t.value ? `${t.color}15` : 'rgba(255,255,255,0.02)',
                                        color: feedbackType === t.value ? t.color : 'var(--text-secondary)',
                                        cursor: 'pointer',
                                        transition: 'all 0.2s',
                                        fontWeight: feedbackType === t.value ? 700 : 400,
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem',
                                        fontSize: '0.9rem'
                                    }}
                                >
                                    <span>{t.emoji}</span> {t.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Message */}
                    <div>
                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)', fontWeight: 600 }}>Your Message</label>
                        <textarea
                            className="input-glass"
                            placeholder="Tell us what you think, what you'd love to see, or what's broken..."
                            rows={5}
                            value={message}
                            onChange={e => setMessage(e.target.value)}
                            required
                            style={{ resize: 'vertical' }}
                        />
                    </div>

                    {/* Contribute Toggle */}
                    <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1.25rem', borderRadius: 'var(--radius-md)' }}>
                        <label style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', cursor: 'pointer' }}>
                            <input
                                type="checkbox"
                                checked={wantsToContribute}
                                onChange={e => setWantsToContribute(e.target.checked)}
                                style={{ width: '18px', height: '18px', accentColor: 'var(--accent-primary)' }}
                            />
                            <div>
                                <span style={{ fontWeight: 600, fontSize: '0.95rem' }}>I'd like to contribute to BRACULA</span>
                                <p style={{ margin: '0.2rem 0 0', color: 'var(--text-muted)', fontSize: '0.8rem' }}>Share your email so the developer can reach out to you</p>
                            </div>
                        </label>

                        {wantsToContribute && (
                            <input
                                type="email"
                                className="input-glass"
                                placeholder="your.email@g.bracu.ac.bd"
                                value={contactEmail}
                                onChange={e => setContactEmail(e.target.value)}
                                required={wantsToContribute}
                                style={{ marginTop: '0.75rem' }}
                            />
                        )}
                    </div>

                    <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.85rem', fontSize: '1rem', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem' }} disabled={submitting}>
                        <Send size={18} />
                        {submitting ? 'Sending...' : 'Send Feedback'}
                    </button>
                </form>
            </div>
        </div>
    )
}

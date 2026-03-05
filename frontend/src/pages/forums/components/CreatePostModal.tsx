import { useState, useEffect } from 'react'
import { createPortal } from 'react-dom'
import { supabase } from '../../../lib/supabase'

interface CreatePostModalProps {
    onClose: () => void
    onPostCreated: () => void
    preselectedCommunityId: string | null
}

export const CreatePostModal = ({ onClose, onPostCreated, preselectedCommunityId }: CreatePostModalProps) => {
    const [title, setTitle] = useState('')
    const [content, setContent] = useState('')
    const [communityId, setCommunityId] = useState(preselectedCommunityId || '')
    const [communities, setCommunities] = useState<{ id: string, name: string }[]>([])
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState('')

    useEffect(() => {
        const fetchCommunities = async () => {
            const { data } = await supabase.from('communities').select('id, name')
            if (data) {
                setCommunities(data)
                if (!communityId && data.length > 0) {
                    setCommunityId(data[0].id)
                }
            }
        }
        fetchCommunities()
    }, [])

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault()
        setLoading(true)
        setError('')

        const { data: { user } } = await supabase.auth.getUser()

        if (!user) {
            setError('You must be logged in to post.')
            setLoading(false)
            return
        }

        const { error: insertError } = await supabase.from('posts').insert({
            title,
            content,
            community_id: communityId,
            author_id: user.id
        })

        setLoading(false)

        if (insertError) {
            setError(insertError.message)
        } else {
            onPostCreated()
        }
    }

    return createPortal(
        <div style={{
            position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
            background: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            zIndex: 9999, padding: '1rem'
        }}>
            <div className="glass-panel animate-fade-in-up" style={{ width: '100%', maxWidth: '600px', padding: '2rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                    <h2 style={{ fontSize: '1.5rem', fontWeight: 600 }}>Create a Post</h2>
                    <button onClick={onClose} style={{ background: 'transparent', border: 'none', color: 'var(--text-secondary)', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>

                    <div>
                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Community</label>
                        <select
                            value={communityId}
                            onChange={e => setCommunityId(e.target.value)}
                            className="input-glass"
                            required
                            style={{ backgroundColor: 'var(--bg-secondary)', color: 'var(--text-primary)', appearance: 'auto' }}
                        >
                            {communities.map(c => (
                                <option key={c.id} value={c.id} style={{ backgroundColor: 'var(--bg-secondary)', color: 'var(--text-primary)' }}>{c.name}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <input
                            type="text"
                            placeholder="Title of your post"
                            className="input-glass"
                            value={title}
                            onChange={e => setTitle(e.target.value)}
                            required
                            maxLength={300}
                            style={{ fontSize: '1.1rem', fontWeight: 500 }}
                        />
                    </div>

                    <div>
                        <textarea
                            placeholder="What are your thoughts?"
                            className="input-glass"
                            value={content}
                            onChange={e => setContent(e.target.value)}
                            required
                            rows={6}
                            style={{ resize: 'vertical' }}
                        />
                    </div>

                    {error && (
                        <div style={{ color: '#f87171', fontSize: '0.9rem', padding: '0.75rem', background: 'rgba(239, 68, 68, 0.1)', borderRadius: 'var(--radius-sm)' }}>
                            {error}
                        </div>
                    )}

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '1rem', marginTop: '0.5rem' }}>
                        <button type="button" onClick={onClose} className="btn-secondary" style={{ padding: '0.6rem 1.25rem' }}>Cancel</button>
                        <button type="submit" className="btn-primary" disabled={loading} style={{ padding: '0.6rem 1.5rem' }}>
                            {loading ? 'Posting...' : 'Post'}
                        </button>
                    </div>
                </form>
            </div>
        </div>,
        document.body
    )
}

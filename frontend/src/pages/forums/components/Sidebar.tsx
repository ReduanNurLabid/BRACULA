import { useEffect, useState } from 'react'
import { supabase } from '../../../lib/supabase'
import { useAuth } from '../../../contexts/AuthContext'
import { ChevronDown, ChevronUp } from 'lucide-react'
import { toast } from 'react-hot-toast'

type Community = {
    id: string
    name: string
    is_general: boolean
}

interface SidebarProps {
    onSelectCommunity: (id: string | null) => void
    activeCommunity: string | null
}

export const Sidebar = ({ onSelectCommunity, activeCommunity }: SidebarProps) => {
    const { user } = useAuth()
    const [communities, setCommunities] = useState<Community[]>([])
    const [showCreate, setShowCreate] = useState(false)
    const [newName, setNewName] = useState('')
    const [newDesc, setNewDesc] = useState('')
    const [creating, setCreating] = useState(false)
    const [expanded, setExpanded] = useState(false)

    const fetchCommunities = async () => {
        const { data } = await supabase.from('communities').select('*').order('is_general', { ascending: false }).order('name')
        if (data) setCommunities(data)
    }

    useEffect(() => {
        fetchCommunities()
    }, [])

    const handleCreate = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!user) { toast.error('You must be logged in to create a community.'); return }
        if (!newName.trim()) return

        setCreating(true)
        try {
            const { error } = await supabase.from('communities').insert({
                name: newName.trim(),
                description: newDesc.trim() || null,
                created_by: user.id,
                is_general: false
            })
            if (error) throw error
            setNewName('')
            setNewDesc('')
            setShowCreate(false)
            fetchCommunities()
        } catch (err: any) {
            toast.error(err.message)
        } finally {
            setCreating(false)
        }
    }

    const activeName = activeCommunity
        ? communities.find(c => c.id === activeCommunity)?.name || 'Community'
        : 'All Posts'

    return (
        <div className="glass-panel" style={{ padding: '1.25rem' }}>
            {/* Header — always visible, clickable toggle */}
            <button
                onClick={() => setExpanded(!expanded)}
                style={{
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                    width: '100%', background: 'none', border: 'none', cursor: 'pointer',
                    padding: '0.25rem 0', color: 'var(--text-primary)'
                }}
            >
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    <span style={{ fontSize: '1.1rem', fontWeight: 600 }}>Communities</span>
                    <span style={{
                        fontSize: '0.75rem', padding: '0.1rem 0.5rem', borderRadius: 'var(--radius-full)',
                        background: 'rgba(255,255,255,0.08)', color: 'var(--text-secondary)'
                    }}>
                        {activeName}
                    </span>
                </div>
                {expanded ? <ChevronUp size={18} color="var(--text-secondary)" /> : <ChevronDown size={18} color="var(--text-secondary)" />}
            </button>

            {/* Expandable content */}
            {expanded && (
                <div style={{ marginTop: '1rem' }}>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.25rem' }}>
                        <button
                            onClick={() => { onSelectCommunity(null); setExpanded(false) }}
                            style={{
                                textAlign: 'left', padding: '0.6rem 0.75rem', borderRadius: 'var(--radius-sm)',
                                background: activeCommunity === null ? 'rgba(255,255,255,0.1)' : 'transparent',
                                color: activeCommunity === null ? 'white' : 'var(--text-secondary)',
                                border: 'none', cursor: 'pointer', transition: 'all 0.2s',
                                fontWeight: activeCommunity === null ? 600 : 400
                            }}
                        >
                            🌍 All Posts
                        </button>

                        {communities.map((c) => (
                            <button
                                key={c.id}
                                onClick={() => { onSelectCommunity(c.id); setExpanded(false) }}
                                style={{
                                    textAlign: 'left', padding: '0.6rem 0.75rem', borderRadius: 'var(--radius-sm)',
                                    background: activeCommunity === c.id ? 'rgba(255,255,255,0.1)' : 'transparent',
                                    color: activeCommunity === c.id ? 'white' : 'var(--text-secondary)',
                                    border: 'none', cursor: 'pointer', transition: 'all 0.2s',
                                    fontWeight: activeCommunity === c.id ? 600 : 400,
                                    display: 'flex', alignItems: 'center', gap: '0.5rem'
                                }}
                            >
                                <span style={{ opacity: 0.7 }}>{c.is_general ? '🔹' : '🔸'}</span>
                                {c.name}
                            </button>
                        ))}
                    </div>

                    <div style={{ marginTop: '1rem', paddingTop: '1rem', borderTop: '1px solid rgba(255,255,255,0.05)' }}>
                        {!showCreate ? (
                            <button className="btn-secondary" style={{ width: '100%', fontSize: '0.85rem' }} onClick={() => { if (!user) { toast.error('Please login first.'); return } setShowCreate(true) }}>
                                + Create Community
                            </button>
                        ) : (
                            <form onSubmit={handleCreate} style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                                <input type="text" className="input-glass" placeholder="Community name" value={newName} onChange={e => setNewName(e.target.value)} required maxLength={50} autoFocus style={{ fontSize: '0.9rem' }} />
                                <input type="text" className="input-glass" placeholder="Short description (optional)" value={newDesc} onChange={e => setNewDesc(e.target.value)} maxLength={150} style={{ fontSize: '0.85rem' }} />
                                <div style={{ display: 'flex', gap: '0.5rem' }}>
                                    <button type="submit" className="btn-primary" style={{ flex: 1, fontSize: '0.85rem', padding: '0.5rem' }} disabled={creating}>
                                        {creating ? 'Creating...' : 'Create'}
                                    </button>
                                    <button type="button" className="btn-secondary" style={{ fontSize: '0.85rem', padding: '0.5rem 0.75rem' }} onClick={() => { setShowCreate(false); setNewName(''); setNewDesc('') }}>
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            )}
        </div>
    )
}

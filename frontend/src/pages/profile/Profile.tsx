import { useEffect, useState } from 'react'
import { useAuth } from '../../contexts/AuthContext'
import { supabase } from '../../lib/supabase'
import { useNavigate, useParams } from 'react-router-dom'
import { Star, Edit3, Save, X, Palette } from 'lucide-react'
import { toast } from 'react-hot-toast'
import { useTheme } from '../../contexts/ThemeContext'

const DEPARTMENTS = [
    { label: 'CSE', color: '#3b82f6' },
    { label: 'CS', color: '#0ea5e9' },
    { label: 'EEE', color: '#f59e0b' },
    { label: 'ECE', color: '#fbbf24' },
    { label: 'BBA', color: '#ef4444' },
    { label: 'Economics', color: '#10b981' },
    { label: 'Architecture', color: '#8b5cf6' },
    { label: 'Pharmacy', color: '#ec4899' },
    { label: 'English', color: '#14b8a6' },
    { label: 'Law', color: '#f97316' },
    { label: 'Mathematics', color: '#6366f1' },
    { label: 'Physics', color: '#06b6d4' },
    { label: 'Microbiology', color: '#84cc16' },
    { label: 'Biotechnology', color: '#22d3ee' },
    { label: 'Anthropology', color: '#a855f7' },
    { label: 'Disaster Mgmt', color: '#d946ef' },
    { label: 'Public Health', color: '#f43f5e' },
    { label: 'Dev. Studies', color: '#0d9488' },
]

export const Profile = () => {
    const { user, signOut } = useAuth()
    const { theme, setTheme } = useTheme()
    const navigate = useNavigate()
    const { userId } = useParams<{ userId: string }>()

    const [profileData, setProfileData] = useState<any>(null)
    const [loading, setLoading] = useState(true)

    // Determine if viewing own profile
    const isOwnProfile = !userId || userId === user?.id

    // Edit mode
    const [editingAvatar, setEditingAvatar] = useState(false)
    const [avatarUrlInput, setAvatarUrlInput] = useState('')
    const [savingAvatar, setSavingAvatar] = useState(false)

    // Department edit
    const [editingDept, setEditingDept] = useState(false)
    const [savingDept, setSavingDept] = useState(false)

    useEffect(() => {
        const targetId = userId || user?.id
        if (!targetId) {
            navigate('/login')
            return
        }

        const fetchProfile = async () => {
            const { data, error } = await supabase
                .from('profiles')
                .select('*')
                .eq('id', targetId)
                .single()
            if (error) {
                console.error("Error fetching profile:", error)
                if (!isOwnProfile) navigate('/')
            }
            if (data) {
                setProfileData(data)
                setAvatarUrlInput(data.avatar_url || '')
            }
            setLoading(false)
        }

        fetchProfile()
    }, [userId, user, navigate])

    const handleLogout = async () => {
        await signOut()
        navigate('/')
    }

    const saveAvatar = async () => {
        if (!user) return
        setSavingAvatar(true)
        try {
            const { error } = await supabase
                .from('profiles')
                .update({ avatar_url: avatarUrlInput })
                .eq('id', user.id)

            if (error) throw error
            setProfileData({ ...profileData, avatar_url: avatarUrlInput })
            setEditingAvatar(false)
        } catch (err: any) {
            toast.error(err.message)
        } finally {
            setSavingAvatar(false)
        }
    }

    const saveDepartment = async (dept: string) => {
        if (!user) return
        setSavingDept(true)
        try {
            const currentDept = profileData?.department || ''
            // Toggle: if already selected, remove it; otherwise set it
            const newDept = currentDept === dept ? '' : dept

            const { error } = await supabase
                .from('profiles')
                .update({ department: newDept })
                .eq('id', user.id)

            if (error) throw error
            setProfileData({ ...profileData, department: newDept })
        } catch (err: any) {
            toast.error(err.message)
        } finally {
            setSavingDept(false)
        }
    }

    if (loading) {
        return <div className="container" style={{ padding: '6rem 1.5rem', textAlign: 'center', color: 'var(--text-secondary)' }}>Loading profile...</div>
    }

    if (!profileData) {
        return <div className="container" style={{ padding: '6rem 1.5rem', textAlign: 'center', color: 'var(--text-secondary)' }}>Profile not found.</div>
    }

    const avatarUrl = profileData?.avatar_url
    const initials = (profileData?.full_name || '?')
        .split(' ')
        .map((n: string) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)

    const deptInfo = DEPARTMENTS.find(d => d.label === profileData?.department)

    return (
        <div className="container animate-fade-in-up" style={{ padding: '4rem 1.5rem', display: 'flex', justifyContent: 'center' }}>
            <div className="glass-panel" style={{ padding: '3rem', width: '100%', maxWidth: '550px' }}>

                {/* Avatar Section */}
                <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', marginBottom: '2rem' }}>
                    <div style={{
                        width: '120px', height: '120px', borderRadius: '50%',
                        background: avatarUrl ? 'transparent' : 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        border: '3px solid var(--accent-primary)',
                        boxShadow: '0 0 25px var(--accent-glow)',
                        position: 'relative', overflow: 'hidden',
                        fontSize: '2.5rem', fontWeight: 800, color: 'white',
                        letterSpacing: '2px'
                    }}>
                        {avatarUrl ? (
                            <img loading="lazy" src={avatarUrl} alt="Profile" style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%' }} />
                        ) : initials}
                    </div>

                    {/* Edit Avatar Button (own profile only) */}
                    {isOwnProfile && (
                        !editingAvatar ? (
                            <button onClick={() => setEditingAvatar(true)} style={{
                                marginTop: '0.75rem', background: 'none', border: 'none',
                                color: 'var(--accent-primary)', cursor: 'pointer',
                                display: 'flex', alignItems: 'center', gap: '0.3rem', fontSize: '0.9rem'
                            }}>
                                <Edit3 size={14} /> Change Photo
                            </button>
                        ) : (
                            <div style={{ marginTop: '0.75rem', display: 'flex', gap: '0.5rem', alignItems: 'center', width: '100%', maxWidth: '350px' }}>
                                <input
                                    type="url"
                                    className="input-glass"
                                    placeholder="Paste image URL here..."
                                    value={avatarUrlInput}
                                    onChange={e => setAvatarUrlInput(e.target.value)}
                                    style={{ flex: 1, fontSize: '0.85rem' }}
                                />
                                <button onClick={saveAvatar} disabled={savingAvatar} style={{
                                    background: 'rgba(46, 204, 113, 0.2)', color: '#2ecc71',
                                    border: 'none', padding: '0.5rem', borderRadius: '6px', cursor: 'pointer'
                                }}>
                                    <Save size={16} />
                                </button>
                                <button onClick={() => { setEditingAvatar(false); setAvatarUrlInput(profileData?.avatar_url || '') }} style={{
                                    background: 'rgba(231, 76, 60, 0.2)', color: '#e74c3c',
                                    border: 'none', padding: '0.5rem', borderRadius: '6px', cursor: 'pointer'
                                }}>
                                    <X size={16} />
                                </button>
                            </div>
                        )
                    )}

                    <h2 className="text-gradient" style={{ fontSize: '1.8rem', marginTop: '1rem', marginBottom: '0.25rem', textAlign: 'center' }}>
                        {profileData?.full_name || 'N/A'}
                    </h2>

                    {/* Department Tag */}
                    {deptInfo && (
                        <span style={{
                            marginTop: '0.5rem', padding: '0.3rem 1rem', borderRadius: 'var(--radius-full)',
                            background: `${deptInfo.color}20`, color: deptInfo.color,
                            fontWeight: 700, fontSize: '0.9rem', border: `1px solid ${deptInfo.color}40`,
                            display: 'inline-block'
                        }}>
                            {deptInfo.label}
                        </span>
                    )}
                    {!deptInfo && !isOwnProfile && (
                        <span style={{ marginTop: '0.5rem', color: 'var(--text-muted)', fontSize: '0.9rem' }}>No department set</span>
                    )}

                    {!isOwnProfile && (
                        <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem', margin: '0.25rem 0 0 0' }}>
                            Member since {new Date(profileData?.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}
                        </p>
                    )}
                    {isOwnProfile && (
                        <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem', margin: '0.25rem 0 0 0' }}>{user?.email}</p>
                    )}
                </div>

                {/* Department Picker (own profile) */}
                {isOwnProfile && (
                    <div style={{ marginBottom: '2rem' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' }}>
                            <span style={{ color: 'var(--text-secondary)', fontSize: '0.9rem', fontWeight: 600 }}>Department</span>
                            {!editingDept ? (
                                <button onClick={() => setEditingDept(true)} style={{ background: 'none', border: 'none', color: 'var(--accent-primary)', cursor: 'pointer', fontSize: '0.85rem', display: 'flex', alignItems: 'center', gap: '0.25rem' }}>
                                    <Edit3 size={12} /> {profileData?.department ? 'Change' : 'Set'}
                                </button>
                            ) : (
                                <button onClick={() => setEditingDept(false)} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', fontSize: '0.85rem' }}>
                                    Done
                                </button>
                            )}
                        </div>

                        {editingDept ? (
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
                                {DEPARTMENTS.map(dept => {
                                    const isSelected = profileData?.department === dept.label
                                    return (
                                        <button
                                            key={dept.label}
                                            onClick={() => saveDepartment(dept.label)}
                                            disabled={savingDept}
                                            style={{
                                                padding: '0.35rem 0.85rem',
                                                borderRadius: 'var(--radius-full)',
                                                border: `1.5px solid ${isSelected ? dept.color : 'var(--border-glass)'}`,
                                                background: isSelected ? `${dept.color}20` : 'rgba(255,255,255,0.03)',
                                                color: isSelected ? dept.color : 'var(--text-secondary)',
                                                fontWeight: isSelected ? 700 : 400,
                                                cursor: 'pointer',
                                                transition: 'all 0.2s ease',
                                                fontSize: '0.85rem'
                                            }}
                                        >
                                            {dept.label}
                                        </button>
                                    )
                                })}
                            </div>
                        ) : (
                            deptInfo ? (
                                <span style={{
                                    padding: '0.3rem 0.85rem', borderRadius: 'var(--radius-full)',
                                    background: `${deptInfo.color}20`, color: deptInfo.color,
                                    fontWeight: 600, fontSize: '0.85rem', border: `1px solid ${deptInfo.color}40`
                                }}>
                                    {deptInfo.label}
                                </span>
                            ) : (
                                <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>Not set yet — click "Set" to choose your department</span>
                            )
                        )}
                    </div>
                )}

                {/* Stats Row */}
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '2rem' }}>
                    {isOwnProfile && (
                        <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1.25rem', borderRadius: 'var(--radius-md)', textAlign: 'center' }}>
                            <span style={{ fontSize: '2rem' }}>🪙</span>
                            <div style={{ fontSize: '1.5rem', fontWeight: 700, color: 'var(--accent-primary)', marginTop: '0.25rem' }}>
                                {profileData?.credit_balance ?? 0}
                            </div>
                            <span style={{ color: 'var(--text-secondary)', fontSize: '0.85rem' }}>Credits</span>
                        </div>
                    )}
                    <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1.25rem', borderRadius: 'var(--radius-md)', textAlign: 'center', gridColumn: isOwnProfile ? undefined : '1 / -1' }}>
                        <div style={{ display: 'flex', justifyContent: 'center', gap: '0.2rem', marginBottom: '0.25rem' }}>
                            {[1, 2, 3, 4, 5].map(s => (
                                <Star key={s} size={16} fill={s <= Math.round(profileData?.trust_score || 0) ? '#f1c40f' : 'transparent'} color={s <= Math.round(profileData?.trust_score || 0) ? '#f1c40f' : 'var(--border-glass)'} />
                            ))}
                        </div>
                        <div style={{ fontSize: '1.5rem', fontWeight: 700, color: '#f1c40f', marginTop: '0.25rem' }}>
                            {profileData?.trust_score ? Number(profileData.trust_score).toFixed(1) : '0.0'}
                        </div>
                        <span style={{ color: 'var(--text-secondary)', fontSize: '0.85rem' }}>
                            Trust Score ({profileData?.total_reviews ?? 0} reviews)
                        </span>
                    </div>
                </div>

                {/* Theme Picker (own profile) */}
                {isOwnProfile && (
                    <div style={{ marginBottom: '2rem' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem', color: 'var(--text-secondary)', fontSize: '0.9rem', fontWeight: 600 }}>
                            <Palette size={16} /> App Theme
                        </div>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))', gap: '0.5rem' }}>
                            {[
                                { id: 'default', label: 'BRACULA Dark' },
                                { id: 'minimal', label: 'Minimal Dark' },
                                { id: 'high-contrast', label: 'High Contrast' },
                                { id: 'light', label: 'Light Mode' }
                            ].map(t => (
                                <button
                                    key={t.id}
                                    onClick={() => setTheme(t.id as any)}
                                    style={{
                                        padding: '0.75rem',
                                        borderRadius: 'var(--radius-md)',
                                        border: `1.5px solid ${theme === t.id ? 'var(--accent-primary)' : 'var(--border-glass)'}`,
                                        background: theme === t.id ? 'var(--accent-glow)' : 'rgba(255,255,255,0.02)',
                                        color: theme === t.id ? 'white' : 'var(--text-secondary)',
                                        fontWeight: theme === t.id ? 700 : 500,
                                        cursor: 'pointer',
                                        transition: 'all 0.2s',
                                        textAlign: 'center',
                                        fontSize: '0.85rem'
                                    }}
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Info Cards (own profile) */}
                {isOwnProfile && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', marginBottom: '2rem' }}>
                        <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: 'var(--radius-md)' }}>
                            <span style={{ color: 'var(--text-secondary)', fontSize: '0.85rem', display: 'block' }}>Full Name</span>
                            <span style={{ fontSize: '1.1rem', fontWeight: 500 }}>{profileData?.full_name || 'N/A'}</span>
                        </div>
                        <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: 'var(--radius-md)' }}>
                            <span style={{ color: 'var(--text-secondary)', fontSize: '0.85rem', display: 'block' }}>Email</span>
                            <span style={{ fontSize: '1.1rem', fontWeight: 500 }}>{user?.email}</span>
                        </div>
                        <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: 'var(--radius-md)' }}>
                            <span style={{ color: 'var(--text-secondary)', fontSize: '0.85rem', display: 'block' }}>Member Since</span>
                            <span style={{ fontSize: '1.1rem', fontWeight: 500 }}>{new Date(profileData?.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>
                )}

                {isOwnProfile && (
                    <button
                        onClick={handleLogout}
                        className="btn-primary"
                        style={{ width: '100%', background: 'rgba(239, 68, 68, 0.1)', color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.2)' }}
                    >
                        Log Out
                    </button>
                )}
            </div>
        </div>
    )
}

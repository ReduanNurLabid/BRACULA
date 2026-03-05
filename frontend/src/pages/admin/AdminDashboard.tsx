import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase'
import { useAuth } from '../../contexts/AuthContext'
import { useNavigate } from 'react-router-dom'
import { Users, MessageSquare, Car, BookOpen, Star, Trash2, ChevronDown, ChevronUp, AlertTriangle, CheckCircle } from 'lucide-react'
import { toast } from 'react-hot-toast'
import { useConfirm } from '../../contexts/ConfirmContext'

// Admin emails — add your accounts here
const ADMIN_EMAILS = [
    'reduan.nur.labid@g.bracu.ac.bd',
    'nurreduan.pp@gmail.com',
]

type FeedbackItem = {
    id: string
    type: string
    message: string
    contact_email: string | null
    wants_to_contribute: boolean
    created_at: string
    profiles?: { full_name: string; avatar_url: string | null } | null
}

type MaterialReport = {
    id: string
    material_id: string
    reporter_id: string
    status: string
    created_at: string
    materials: { title: string; course_code: string; is_gdrive: boolean }
    profiles: { full_name: string; email: string }
}

type PostReport = {
    id: string
    post_id: string
    reporter_id: string
    reason: string
    status: string
    created_at: string
    posts: { id: string; title: string; content: string; author_id: string }
    profiles: { full_name: string; email: string }
}

export const AdminDashboard = () => {
    const { user, loading: authLoading } = useAuth()
    const navigate = useNavigate()
    const [loading, setLoading] = useState(true)
    const [stats, setStats] = useState({ users: 0, posts: 0, rides: 0, materials: 0, feedbacks: 0 })
    const [feedbacks, setFeedbacks] = useState<FeedbackItem[]>([])
    const [reports, setReports] = useState<MaterialReport[]>([])
    const [postReports, setPostReports] = useState<PostReport[]>([])
    const [expandedFeedback, setExpandedFeedback] = useState<string | null>(null)
    const [expandedPostReport, setExpandedPostReport] = useState<string | null>(null)
    const [filterType, setFilterType] = useState<string>('all')
    const [refundingId, setRefundingId] = useState<string | null>(null)
    const [actioningPostId, setActioningPostId] = useState<string | null>(null)
    const confirm = useConfirm()

    useEffect(() => {
        if (authLoading) return // Wait for auth to finish loading

        if (!user || !ADMIN_EMAILS.includes(user.email || '')) {
            navigate('/')
            return
        }
        fetchAll()
    }, [user, authLoading, navigate])

    const fetchAll = async () => {
        setLoading(true)

        // Fetch counts in parallel
        const [usersRes, postsRes, ridesRes, materialsRes, feedbacksRes] = await Promise.all([
            supabase.from('profiles').select('id', { count: 'exact', head: true }),
            supabase.from('posts').select('id', { count: 'exact', head: true }),
            supabase.from('rides').select('id', { count: 'exact', head: true }),
            supabase.from('materials').select('id', { count: 'exact', head: true }),
            supabase.from('feedback').select('id', { count: 'exact', head: true }),
        ])

        setStats({
            users: usersRes.count || 0,
            posts: postsRes.count || 0,
            rides: ridesRes.count || 0,
            materials: materialsRes.count || 0,
            feedbacks: feedbacksRes.count || 0,
        })

        // Fetch feedback details
        const { data: fbData } = await supabase
            .from('feedback')
            .select('*, profiles:user_id(full_name, avatar_url)')
            .order('created_at', { ascending: false })

        if (fbData) setFeedbacks(fbData)

        // Fetch material reports
        const { data: reportsData } = await supabase
            .from('material_reports')
            .select('*, materials(title, course_code, is_gdrive), profiles:reporter_id(full_name, email)')
            .eq('status', 'pending')
            .order('created_at', { ascending: false })

        if (reportsData) setReports(reportsData as any)

        // Fetch post reports
        const { data: postReportsData } = await supabase
            .from('post_reports')
            .select('*, posts(id, title, content, author_id), profiles:reporter_id(full_name, email)')
            .eq('status', 'pending')
            .order('created_at', { ascending: false })

        if (postReportsData) setPostReports(postReportsData as any)

        setLoading(false)
    }

    const handleRefund = async (report: MaterialReport) => {
        if (!await confirm('Refund 1 download to this user and resolve report?')) return
        setRefundingId(report.id)
        try {
            const { error } = await supabase.rpc('rpc_refund_download', {
                p_report_id: report.id,
                p_reporter_id: report.reporter_id,
                p_material_id: report.material_id
            })
            if (error) throw error
            setReports(reports.filter(r => r.id !== report.id))
            toast.success('Refunded 1 Download successfully.')
        } catch (err: any) {
            console.error(err)
            toast.error('Failed to refund: ' + err.message)
        } finally {
            setRefundingId(null)
        }
    }

    const handleDismissPostReport = async (reportId: string) => {
        if (!await confirm('Dismiss this report? The post will remain visible.')) return;
        setActioningPostId(reportId)
        try {
            const { error } = await supabase.from('post_reports').update({ status: 'dismissed' }).eq('id', reportId);
            if (error) throw error;
            setPostReports(prev => prev.filter(r => r.id !== reportId));
            toast.success('Report dismissed.');
        } catch (err: any) {
            toast.error('Failed to dismiss: ' + err.message);
        } finally {
            setActioningPostId(null);
        }
    }

    const handleDeleteReportedPost = async (reportId: string, postId: string) => {
        if (!await confirm('Are you sure you want to permanently DELETE the reported post?')) return;
        setActioningPostId(reportId)
        try {
            // Delete post (this should cascade and delete the report automatically or we can manually mark it)
            const { error: deleteError } = await supabase.from('posts').delete().eq('id', postId);
            if (deleteError) throw deleteError;

            // Also explicitly update report status to deleted just in case cascade is off
            await supabase.from('post_reports').update({ status: 'deleted_post' }).eq('id', reportId);

            setPostReports(prev => prev.filter(r => r.id !== reportId));
            toast.success('Post successfully deleted.');
        } catch (err: any) {
            toast.error('Failed to delete post: ' + err.message);
        } finally {
            setActioningPostId(null);
        }
    }

    const deleteFeedback = async (id: string) => {
        if (!await confirm('Delete this feedback?')) return
        await supabase.from('feedback').delete().eq('id', id)
        setFeedbacks(feedbacks.filter(f => f.id !== id))
        toast.success("Feedback deleted.")
    }

    const filteredFeedbacks = filterType === 'all' ? feedbacks : feedbacks.filter(f => f.type === filterType)

    const typeColor = (type: string) => {
        switch (type) {
            case 'suggestion': return '#f59e0b'
            case 'bug': return '#ef4444'
            case 'praise': return '#ec4899'
            default: return '#3b82f6'
        }
    }

    const typeEmoji = (type: string) => {
        switch (type) {
            case 'suggestion': return '💡'
            case 'bug': return '🐛'
            case 'praise': return '❤️'
            default: return '💬'
        }
    }

    if (loading) {
        return <div className="container" style={{ padding: '6rem 1.5rem', textAlign: 'center', color: 'var(--text-secondary)' }}>Loading dashboard...</div>
    }

    const STAT_CARDS = [
        { label: 'Total Users', value: stats.users, icon: Users, color: '#3b82f6' },
        { label: 'Forum Posts', value: stats.posts, icon: MessageSquare, color: '#10b981' },
        { label: 'Rides Created', value: stats.rides, icon: Car, color: '#f59e0b' },
        { label: 'Study Materials', value: stats.materials, icon: BookOpen, color: '#8b5cf6' },
        { label: 'Feedbacks', value: stats.feedbacks, icon: Star, color: '#ec4899' },
    ]

    return (
        <div className="container animate-fade-in-up" style={{ padding: '3rem 1.5rem' }}>
            <div style={{ marginBottom: '2.5rem' }}>
                <h1 className="text-gradient" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Admin Dashboard</h1>
                <p style={{ color: 'var(--text-secondary)' }}>Welcome back, admin. Here's an overview of BRACULA.</p>
            </div>

            {/* Stats Grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 160px), 1fr))', gap: '1rem', marginBottom: '3rem' }}>
                {STAT_CARDS.map(card => {
                    const Icon = card.icon
                    return (
                        <div key={card.label} className="glass-panel hover-lift" style={{ padding: '1.5rem', textAlign: 'center' }}>
                            <div style={{ width: '48px', height: '48px', borderRadius: '50%', background: `${card.color}20`, display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 0.75rem' }}>
                                <Icon size={24} color={card.color} />
                            </div>
                            <div style={{ fontSize: '2rem', fontWeight: 800, color: card.color }}>{card.value}</div>
                            <div style={{ color: 'var(--text-secondary)', fontSize: '0.85rem', marginTop: '0.25rem' }}>{card.label}</div>
                        </div>
                    )
                })}
            </div>

            {/* Reports Section */}
            {reports.length > 0 && (
                <div style={{ marginBottom: '3rem' }}>
                    <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#ef4444' }}>
                        <AlertTriangle /> Pending Link Reports ({reports.length})
                    </h2>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                        {reports.map(report => (
                            <div key={report.id} className="glass-panel" style={{ padding: '1.25rem', borderLeft: '4px solid #ef4444' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '1rem' }}>
                                    <div>
                                        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', marginBottom: '0.25rem' }}>
                                            <span style={{ fontSize: '0.85rem', color: '#ef4444', fontWeight: 600, background: 'rgba(239, 68, 68, 0.1)', padding: '0.1rem 0.5rem', borderRadius: '4px' }}>
                                                Not Accessible
                                            </span>
                                            <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>
                                                {new Date(report.created_at).toLocaleDateString()}
                                            </span>
                                        </div>
                                        <h3 style={{ fontSize: '1.1rem', marginBottom: '0.25rem' }}>{report.materials?.title} ({report.materials?.course_code})</h3>
                                        <div style={{ fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                                            Reported by: <strong style={{ color: 'var(--text-primary)' }}>{report.profiles?.full_name}</strong> ({report.profiles?.email})
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => handleRefund(report)}
                                        disabled={refundingId === report.id}
                                        style={{ background: 'rgba(16, 185, 129, 0.15)', color: '#10b981', border: '1px solid rgba(16, 185, 129, 0.3)', padding: '0.5rem 1rem', borderRadius: 'var(--radius-full)', cursor: refundingId === report.id ? 'default' : 'pointer', fontSize: '0.9rem', display: 'flex', alignItems: 'center', gap: '0.4rem', fontWeight: 600, transition: 'all 0.2s' }}
                                    >
                                        <CheckCircle size={16} />
                                        {refundingId === report.id ? 'Processing...' : 'Refund 1 Download & Resolve'}
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Post Reports Section */}
            {postReports.length > 0 && (
                <div style={{ marginBottom: '3rem' }}>
                    <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#f59e0b' }}>
                        <AlertTriangle /> Reported Forum Posts ({postReports.length})
                    </h2>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                        {postReports.map(report => {
                            const isExpanded = expandedPostReport === report.id;

                            // Handle null posts gracefully (in case the post was deleted by author before report was reviewed)
                            if (!report.posts) {
                                return (
                                    <div key={report.id} className="glass-panel" style={{ padding: '1.25rem', borderLeft: '4px solid gray' }}>
                                        <div style={{ color: 'var(--text-secondary)' }}>This reported post has already been deleted by its author.</div>
                                        <button onClick={() => handleDismissPostReport(report.id)} className="btn-secondary" style={{ marginTop: '0.5rem' }}>Dismiss Report</button>
                                    </div>
                                )
                            }

                            return (
                                <div key={report.id} className="glass-panel" style={{ padding: '1.25rem', borderLeft: '4px solid #f59e0b', cursor: 'pointer' }} onClick={() => setExpandedPostReport(isExpanded ? null : report.id)}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '1rem' }}>
                                        <div style={{ flex: 1 }}>
                                            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', marginBottom: '0.25rem' }}>
                                                <span style={{ fontSize: '0.85rem', color: '#f59e0b', fontWeight: 600, background: 'rgba(245, 158, 11, 0.1)', padding: '0.1rem 0.5rem', borderRadius: '4px' }}>
                                                    Post Report
                                                </span>
                                                <span style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>
                                                    {new Date(report.created_at).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <h3 style={{ fontSize: '1.1rem', marginBottom: '0.25rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                                {report.posts.title}
                                                {isExpanded ? <ChevronUp size={16} color="var(--text-muted)" /> : <ChevronDown size={16} color="var(--text-muted)" />}
                                            </h3>
                                            <div style={{ fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                                                Reported by: <strong style={{ color: 'var(--text-primary)' }}>{report.profiles?.full_name}</strong>
                                                <span style={{ display: 'block', marginTop: '0.25rem', color: '#f87171' }}>Reason: "{report.reason}"</span>
                                            </div>
                                        </div>
                                    </div>

                                    {isExpanded && (
                                        <div style={{ marginTop: '1rem', paddingTop: '1rem', borderTop: '1px solid var(--border-glass)' }} onClick={e => e.stopPropagation()}>
                                            <div style={{ background: 'rgba(0,0,0,0.2)', padding: '1rem', borderRadius: 'var(--radius-md)', marginBottom: '1.5rem', overflow: 'hidden' }}>
                                                <h4 style={{ margin: '0 0 0.5rem 0', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Post Content Preview:</h4>
                                                <p style={{ margin: 0, whiteSpace: 'pre-wrap', color: 'var(--text-primary)', lineHeight: 1.5, fontSize: '0.95rem' }}>{report.posts.content}</p>
                                            </div>
                                            <div style={{ display: 'flex', gap: '1rem' }}>
                                                <button
                                                    onClick={(e) => { e.stopPropagation(); handleDismissPostReport(report.id) }}
                                                    disabled={actioningPostId === report.id}
                                                    className="btn-secondary"
                                                    style={{ flex: 1, padding: '0.5rem', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '0.5rem' }}
                                                >
                                                    {actioningPostId === report.id ? '...' : 'Dismiss Report'}
                                                </button>
                                                <button
                                                    onClick={(e) => { e.stopPropagation(); handleDeleteReportedPost(report.id, report.posts.id) }}
                                                    disabled={actioningPostId === report.id}
                                                    style={{ flex: 1, background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', border: '1px solid rgba(239, 68, 68, 0.3)', padding: '0.5rem', borderRadius: 'var(--radius-full)', cursor: actioningPostId === report.id ? 'default' : 'pointer', fontSize: '0.9rem', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.4rem', fontWeight: 600, transition: 'all 0.2s' }}
                                                >
                                                    <Trash2 size={16} />
                                                    {actioningPostId === report.id ? '...' : 'Delete Post'}
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )
                        })}
                    </div>
                </div>
            )}

            {/* Feedback Section */}
            <div style={{ marginBottom: '2rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem', flexWrap: 'wrap', gap: '1rem' }}>
                    <h2 style={{ fontSize: '1.5rem' }}>User Feedback ({filteredFeedbacks.length})</h2>
                    <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                        {['all', 'suggestion', 'bug', 'praise', 'other'].map(t => (
                            <button
                                key={t}
                                onClick={() => setFilterType(t)}
                                style={{
                                    padding: '0.35rem 0.85rem', borderRadius: 'var(--radius-full)',
                                    border: `1px solid ${filterType === t ? 'var(--accent-primary)' : 'var(--border-glass)'}`,
                                    background: filterType === t ? 'rgba(78, 204, 163, 0.1)' : 'rgba(255,255,255,0.03)',
                                    color: filterType === t ? 'var(--accent-primary)' : 'var(--text-secondary)',
                                    cursor: 'pointer', fontSize: '0.85rem', fontWeight: filterType === t ? 600 : 400,
                                    transition: 'all 0.2s', textTransform: 'capitalize'
                                }}
                            >
                                {t}
                            </button>
                        ))}
                    </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                    {filteredFeedbacks.length === 0 && (
                        <div className="glass-panel" style={{ padding: '3rem', textAlign: 'center', color: 'var(--text-secondary)' }}>
                            No feedback yet. Users will submit feedback from the Feedback page.
                        </div>
                    )}
                    {filteredFeedbacks.map(fb => {
                        const isExpanded = expandedFeedback === fb.id
                        return (
                            <div key={fb.id} className="glass-panel" style={{ padding: '1.25rem', cursor: 'pointer' }} onClick={() => setExpandedFeedback(isExpanded ? null : fb.id)}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                                        <span style={{
                                            padding: '0.2rem 0.6rem', borderRadius: 'var(--radius-full)',
                                            background: `${typeColor(fb.type)}20`, color: typeColor(fb.type),
                                            fontSize: '0.8rem', fontWeight: 600
                                        }}>
                                            {typeEmoji(fb.type)} {fb.type}
                                        </span>
                                        {fb.profiles?.avatar_url ? (
                                            <img loading="lazy" src={fb.profiles.avatar_url} alt="" style={{ width: '22px', height: '22px', borderRadius: '50%', objectFit: 'cover' }} />
                                        ) : (
                                            <span style={{ width: '22px', height: '22px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.6rem', fontWeight: 700, color: 'white' }}>
                                                {(fb.profiles?.full_name || '?')[0]}
                                            </span>
                                        )}
                                        <span style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
                                            {fb.profiles?.full_name || 'Anonymous'}
                                        </span>
                                        {fb.wants_to_contribute && (
                                            <span style={{ background: 'rgba(46, 204, 113, 0.15)', color: '#2ecc71', padding: '0.1rem 0.4rem', borderRadius: '4px', fontSize: '0.7rem', fontWeight: 600 }}>
                                                Wants to contribute
                                            </span>
                                        )}
                                    </div>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                                        <span style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>
                                            {new Date(fb.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}
                                        </span>
                                        {isExpanded ? <ChevronUp size={16} color="var(--text-muted)" /> : <ChevronDown size={16} color="var(--text-muted)" />}
                                    </div>
                                </div>

                                {isExpanded && (
                                    <div style={{ marginTop: '1rem', paddingTop: '1rem', borderTop: '1px solid var(--border-glass)' }} onClick={e => e.stopPropagation()}>
                                        <p style={{ color: 'var(--text-secondary)', lineHeight: 1.6, margin: '0 0 1rem', whiteSpace: 'pre-wrap' }}>{fb.message}</p>
                                        {fb.contact_email && (
                                            <div style={{ background: 'rgba(255,255,255,0.03)', padding: '0.5rem 0.75rem', borderRadius: '6px', fontSize: '0.85rem', marginBottom: '1rem' }}>
                                                <strong>Contact:</strong> <a href={`mailto:${fb.contact_email}`} style={{ color: 'var(--accent-primary)' }}>{fb.contact_email}</a>
                                            </div>
                                        )}
                                        <button onClick={() => deleteFeedback(fb.id)} style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.2)', padding: '0.4rem 0.75rem', borderRadius: '6px', cursor: 'pointer', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '0.3rem' }}>
                                            <Trash2 size={14} /> Delete
                                        </button>
                                    </div>
                                )}
                            </div>
                        )
                    })}
                </div>
            </div>
        </div>
    )
}

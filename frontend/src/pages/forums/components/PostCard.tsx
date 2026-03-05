import { useState, useEffect } from 'react'
import { supabase } from '../../../lib/supabase'
import { useAuth } from '../../../contexts/AuthContext'
import { timeAgo } from '../../../utils/dateFormatter'
import { Link } from 'react-router-dom'
import { Edit2, Trash2, MoreVertical, AlertTriangle, X } from 'lucide-react'
import { toast } from 'react-hot-toast'
import { useConfirm } from '../../../contexts/ConfirmContext'
import { createPortal } from 'react-dom'

interface PostCardProps {
    post: any
    onVoteChanged: () => void
}

export const PostCard = ({ post, onVoteChanged }: PostCardProps) => {
    const { user } = useAuth()
    const [loadingVote, setLoadingVote] = useState(false)
    const [isExpanded, setIsExpanded] = useState(false)
    const [comments, setComments] = useState<any[]>([])
    const [loadingComments, setLoadingComments] = useState(false)
    const [newComment, setNewComment] = useState('')
    const [submittingComment, setSubmittingComment] = useState(false)
    const [isDeleted, setIsDeleted] = useState(false)
    const [showDropdown, setShowDropdown] = useState(false)
    const confirm = useConfirm()

    // Edit Post State
    const [isEditingPost, setIsEditingPost] = useState(false)
    const [editPostTitle, setEditPostTitle] = useState(post.title || '')
    const [editPostContent, setEditPostContent] = useState(post.content || '')

    // Edit Comment State
    const [editingCommentId, setEditingCommentId] = useState<string | null>(null)
    const [editCommentContent, setEditCommentContent] = useState('')

    // History Modal State
    const [showHistoryModal, setShowHistoryModal] = useState<'post' | string | null>(null)

    // Report Modal State
    const [showReportModal, setShowReportModal] = useState(false)
    const [reportReason, setReportReason] = useState('')
    const [submittingReport, setSubmittingReport] = useState(false)

    const [userVote, setUserVote] = useState<number>(0)

    useEffect(() => {
        if (!user) return;
        const fetchUserVote = async () => {
            const { data } = await supabase
                .from('post_votes')
                .select('vote_type')
                .eq('post_id', post.id)
                .eq('user_id', user.id)
                .single()
            if (data) {
                setUserVote(data.vote_type)
            }
        }
        fetchUserVote()
    }, [user, post.id])

    const netVotes = (post.upvotes || 0) - (post.downvotes || 0)

    const handleVote = async (voteType: 1 | -1) => {
        if (!user) {
            toast.error("You must be logged in to vote!")
            return
        }

        setLoadingVote(true)

        const oldVote = userVote;
        setUserVote(oldVote === voteType ? 0 : voteType);

        try {
            const { error } = await supabase.rpc('handle_vote', {
                p_post_id: post.id,
                p_user_id: user.id,
                p_vote_type: voteType
            })

            if (error) throw error

            // Tell parent to refresh the feed
            onVoteChanged()
        } catch (error: any) {
            console.error("Error voting:", error.message)
            setUserVote(oldVote)
            toast.error("Failed to submit vote. Please try again.")
        } finally {
            setLoadingVote(false)
        }
    }

    const fetchComments = async () => {
        setLoadingComments(true)
        const { data, error } = await supabase
            .from('comments')
            .select('*, profiles:author_id(full_name, avatar_url)')
            .eq('post_id', post.id)
            .order('created_at', { ascending: true })

        if (!error && data) {
            setComments(data)
        }
        setLoadingComments(false)
    }

    const handleExpandToggle = () => {
        const newExpanded = !isExpanded
        setIsExpanded(newExpanded)
        if (newExpanded && comments.length === 0) {
            fetchComments()
        }
    }

    const handleAddComment = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!user || !newComment.trim()) return

        setSubmittingComment(true)
        const { error } = await supabase.from('comments').insert({
            post_id: post.id,
            author_id: user.id,
            content: newComment.trim()
        })

        if (!error) {
            setNewComment('')
            fetchComments() // Refresh comments list
            onVoteChanged() // Refresh comment count in parent feed (or we can optimistically update it)
        }
        setSubmittingComment(false)
    }

    const handleDeletePost = async () => {
        if (!await confirm("Are you sure you want to delete this post?")) return;
        setIsDeleted(true); // Optimistic UI
        try {
            const { error } = await supabase.from('posts').delete().eq('id', post.id);
            if (error) throw error;
            onVoteChanged();
        } catch (err: any) {
            setIsDeleted(false);
            toast.error("Delete failed: " + err.message);
        }
    }

    const handleUpdatePost = async (e: React.FormEvent) => {
        e.preventDefault();

        // Optimistic UI state directly applied by the next re-render via updated props? 
        // We'll trust the parent re-fetch for true sync, but immediately close form.
        setIsEditingPost(false);

        try {
            const { error: rpcError } = await supabase.rpc('rpc_edit_post', {
                p_post_id: post.id,
                p_title: editPostTitle,
                p_content: editPostContent,
                p_user_id: user?.id
            });
            if (rpcError) throw rpcError;
            onVoteChanged();
        } catch (err: any) {
            setIsEditingPost(true);
            toast.error("Update failed: " + err.message);
        }
    }

    const handleReportPost = () => {
        setShowDropdown(false); // Close dropdown
        if (!user) {
            toast.error("You must be logged in to report a post.");
            return;
        }
        setShowReportModal(true);
    }

    const submitReport = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!user || !reportReason.trim()) return;

        setSubmittingReport(true);
        try {
            const { error } = await supabase.from('post_reports').insert({
                post_id: post.id,
                reporter_id: user.id,
                reason: reportReason.trim()
            });

            if (error) {
                if (error.code === '23505') { // Unique violation if we added one, or just general err
                    toast.error("You have already reported this post.");
                } else {
                    throw error;
                }
            } else {
                toast.success("Post reported to administrators.");
                setShowReportModal(false);
                setReportReason('');
            }
        } catch (err: any) {
            toast.error("Failed to report post: " + err.message);
        } finally {
            setSubmittingReport(false);
        }
    }

    const handleDeleteComment = async (id: string) => {
        if (!await confirm("Are you sure you want to delete this comment?")) return;

        // Optimistic UI removal
        setComments(prev => prev.filter(c => c.id !== id));

        try {
            const { error } = await supabase.from('comments').delete().eq('id', id);
            if (error) throw error;
            onVoteChanged();
        } catch (err: any) {
            fetchComments(); // Revert
            toast.error("Delete failed: " + err.message);
        }
    }

    const handleUpdateComment = async (e: React.FormEvent, id: string) => {
        e.preventDefault();

        // Optimistic UI text change
        setComments(prev => prev.map(c => c.id === id ? { ...c, content: editCommentContent } : c));
        setEditingCommentId(null);

        try {
            const { error: rpcError } = await supabase.rpc('rpc_edit_comment', {
                p_comment_id: id,
                p_content: editCommentContent,
                p_user_id: user?.id
            });
            if (rpcError) throw rpcError;
            fetchComments(); // Fetch accurate changes behind the scenes
        } catch (err: any) {
            fetchComments(); // Revert
            toast.error("Update failed: " + err.message);
        }
    }

    if (isDeleted) return null;

    return (
        <div className="glass-panel" style={{ padding: '1.25rem', display: 'flex', flexDirection: 'column', gap: '1rem', transition: 'transform 0.2s', cursor: 'pointer' }} onClick={handleExpandToggle} onMouseOver={e => e.currentTarget.style.transform = isExpanded ? 'none' : 'translateY(-2px)'} onMouseOut={e => e.currentTarget.style.transform = 'translateY(0)'}>

            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.15rem', fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                            <span style={{ fontWeight: 600, color: 'var(--text-primary)', background: 'rgba(255,255,255,0.05)', padding: '0.1rem 0.4rem', borderRadius: '4px' }}>
                                {post.communities?.name || 'Unknown Community'}
                            </span>
                            <span>•</span>
                            {post.profiles?.avatar_url ? (
                                <img src={post.profiles.avatar_url} alt="" style={{ width: '18px', height: '18px', borderRadius: '50%', objectFit: 'cover' }} />
                            ) : (
                                <span style={{ width: '18px', height: '18px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.55rem', fontWeight: 700, color: 'var(--text-primary)' }}>
                                    {(post.profiles?.full_name || '?')[0]}
                                </span>
                            )}
                            <span><Link to={`/profile/${post.author_id}`} style={{ color: 'var(--text-primary)', textDecoration: 'none', fontWeight: 700 }} onClick={e => e.stopPropagation()} onMouseOver={e => (e.currentTarget.style.textDecoration = 'underline')} onMouseOut={e => (e.currentTarget.style.textDecoration = 'none')}>{post.profiles?.full_name || 'Anonymous'}</Link></span>
                            {post.profiles?.department && (
                                <span style={{ fontSize: '0.7rem', padding: '0.1rem 0.4rem', borderRadius: 'var(--radius-full)', background: 'rgba(109, 40, 217, 0.15)', color: 'var(--accent-primary)', fontWeight: 600 }}>{post.profiles.department}</span>
                            )}
                        </div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{timeAgo(post.created_at)}</span>
                            {post.edit_history && post.edit_history.length > 0 && (
                                <span onClick={(e) => { e.stopPropagation(); setShowHistoryModal('post'); }} style={{ fontSize: '0.75rem', color: 'var(--text-muted)', cursor: 'pointer', fontStyle: 'italic' }} onMouseEnter={e => e.currentTarget.style.textDecoration = 'underline'} onMouseLeave={e => e.currentTarget.style.textDecoration = 'none'}>(edited)</span>
                            )}
                        </div>
                    </div>
                    {user && (
                        <div style={{ position: 'relative' }}>
                            <button
                                onClick={(e) => { e.stopPropagation(); setShowDropdown(!showDropdown); }}
                                style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', padding: '0.2rem', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                onMouseOver={e => e.currentTarget.style.background = 'rgba(255,255,255,0.05)'}
                                onMouseOut={e => e.currentTarget.style.background = 'none'}
                            >
                                <MoreVertical size={16} />
                            </button>

                            {showDropdown && (
                                <>
                                    <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 9 }} onClick={(e) => { e.stopPropagation(); setShowDropdown(false); }} />
                                    <div className="glass-panel animate-fade-in-up" style={{ position: 'absolute', top: '100%', right: 0, zIndex: 10, minWidth: '150px', padding: '0.5rem', display: 'flex', flexDirection: 'column', gap: '0.25rem', border: '1px solid rgba(255,255,255,0.1)' }}>
                                        {user.id === post.author_id ? (
                                            <>
                                                <button onClick={(e) => { e.stopPropagation(); setShowDropdown(false); setIsEditingPost(true); setEditPostTitle(post.title); setEditPostContent(post.content); }} style={{ background: 'none', border: 'none', color: 'var(--text-primary)', cursor: 'pointer', padding: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem', width: '100%', textAlign: 'left', borderRadius: 'var(--radius-sm)' }} onMouseOver={e => e.currentTarget.style.background = 'rgba(255,255,255,0.05)'} onMouseOut={e => e.currentTarget.style.background = 'none'}>
                                                    <Edit2 size={14} /> Edit Post
                                                </button>
                                                <button onClick={(e) => { e.stopPropagation(); setShowDropdown(false); handleDeletePost(); }} style={{ background: 'none', border: 'none', color: '#ef4444', cursor: 'pointer', padding: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem', width: '100%', textAlign: 'left', borderRadius: 'var(--radius-sm)' }} onMouseOver={e => e.currentTarget.style.background = 'rgba(239, 68, 68, 0.1)'} onMouseOut={e => e.currentTarget.style.background = 'none'}>
                                                    <Trash2 size={14} /> Delete Post
                                                </button>
                                            </>
                                        ) : (
                                            <button onClick={(e) => { e.stopPropagation(); handleReportPost(); }} style={{ background: 'none', border: 'none', color: '#eab308', cursor: 'pointer', padding: '0.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem', width: '100%', textAlign: 'left', borderRadius: 'var(--radius-sm)' }} onMouseOver={e => e.currentTarget.style.background = 'rgba(234, 179, 8, 0.1)'} onMouseOut={e => e.currentTarget.style.background = 'none'}>
                                                <AlertTriangle size={14} /> Report Post
                                            </button>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    )}
                </div>

                {isEditingPost ? (
                    <form onSubmit={handleUpdatePost} style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', marginTop: '0.5rem' }} onClick={e => e.stopPropagation()}>
                        <input type="text" className="input-glass" value={editPostTitle} onChange={e => setEditPostTitle(e.target.value)} placeholder="Title" required />
                        <textarea className="input-glass" value={editPostContent} onChange={e => setEditPostContent(e.target.value)} placeholder="Content" rows={4} required style={{ resize: 'vertical' }} />
                        <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end' }}>
                            <button type="button" onClick={() => setIsEditingPost(false)} className="btn-secondary" style={{ padding: '0.4rem 0.8rem', fontSize: '0.85rem' }}>Cancel</button>
                            <button type="submit" className="btn-primary" style={{ padding: '0.4rem 0.8rem', fontSize: '0.85rem' }}>Save</button>
                        </div>
                    </form>
                ) : (
                    <>
                        <h3 style={{ fontSize: '1.2rem', margin: '0.25rem 0 0.5rem 0', fontWeight: 600 }}>{post.title}</h3>
                        <p style={{ whiteSpace: 'pre-wrap', color: 'var(--text-secondary)', fontSize: '0.95rem', lineHeight: 1.5, ...(!isExpanded ? { display: '-webkit-box', WebkitLineClamp: 3, WebkitBoxOrient: 'vertical', overflow: 'hidden' } : {}) }}>
                            {post.content}
                        </p>
                    </>
                )}

                {/* Action Bar (Votes & Comments) */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '1.25rem', marginTop: '0.5rem', fontSize: '0.85rem', color: 'var(--text-secondary)' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', background: 'rgba(255,255,255,0.05)', padding: '0.2rem 0.5rem', borderRadius: 'var(--radius-full)' }}>
                        <button
                            disabled={loadingVote}
                            onClick={(e) => { e.stopPropagation(); handleVote(1) }}
                            style={{ background: 'none', border: 'none', color: userVote === 1 ? '#22c55e' : 'var(--text-secondary)', cursor: 'pointer', fontSize: '1.1rem', padding: '0.2rem', display: 'flex', alignItems: 'center' }}
                            onMouseOver={e => e.currentTarget.style.color = '#22c55e'} // Green for upvote
                            onMouseOut={e => e.currentTarget.style.color = userVote === 1 ? '#22c55e' : 'var(--text-secondary)'}
                        >
                            ▲
                        </button>
                        <span style={{ fontWeight: 700, fontSize: '0.9rem', color: userVote === 1 ? '#22c55e' : userVote === -1 ? '#ef4444' : 'var(--text-primary)' }}>
                            {netVotes}
                        </span>
                        <button
                            disabled={loadingVote}
                            onClick={(e) => { e.stopPropagation(); handleVote(-1) }}
                            style={{ background: 'none', border: 'none', color: userVote === -1 ? '#ef4444' : 'var(--text-secondary)', cursor: 'pointer', fontSize: '1.1rem', padding: '0.2rem', display: 'flex', alignItems: 'center' }}
                            onMouseOver={e => e.currentTarget.style.color = '#ef4444'} // Red for downvote
                            onMouseOut={e => e.currentTarget.style.color = userVote === -1 ? '#ef4444' : 'var(--text-secondary)'}
                        >
                            ▼
                        </button>
                    </div>

                    <span style={{ display: 'flex', alignItems: 'center', gap: '0.3rem', cursor: 'pointer', padding: '0.2rem 0.5rem', borderRadius: 'var(--radius-full)' }} onMouseOver={e => e.currentTarget.style.background = 'rgba(255,255,255,0.05)'} onMouseOut={e => e.currentTarget.style.background = 'transparent'}>
                        💬 {post.comments?.[0]?.count || 0} Comments
                    </span>
                </div>

                {isExpanded && (
                    <div style={{ marginTop: '1.5rem', paddingTop: '1.5rem', borderTop: '1px solid rgba(255,255,255,0.05)', display: 'flex', flexDirection: 'column', gap: '1rem' }} onClick={e => e.stopPropagation()}>

                        {/* New Comment Input */}
                        {user ? (
                            <form onSubmit={handleAddComment} style={{ display: 'flex', gap: '0.5rem' }}>
                                <input
                                    type="text"
                                    className="input-glass"
                                    placeholder="Add a comment..."
                                    style={{ flex: 1, padding: '0.5rem 1rem', fontSize: '0.9rem' }}
                                    value={newComment}
                                    onChange={e => setNewComment(e.target.value)}
                                    disabled={submittingComment}
                                    required
                                />
                                <button type="submit" className="btn-primary" disabled={submittingComment || !newComment.trim()} style={{ padding: '0.5rem 1rem', fontSize: '0.9rem' }}>
                                    {submittingComment ? '...' : 'Reply'}
                                </button>
                            </form>
                        ) : (
                            <div style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>
                                Please log in to comment.
                            </div>
                        )}

                        {/* Comments List */}
                        {loadingComments ? (
                            <div style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>Loading comments...</div>
                        ) : comments.length > 0 ? (
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '0.5rem' }}>
                                {comments.map(c => (
                                    <div key={c.id} style={{ display: 'flex', flexDirection: 'column', gap: '0.2rem', padding: '0.75rem', background: 'rgba(255,255,255,0.02)', borderRadius: 'var(--radius-sm)' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                                            {c.profiles?.avatar_url ? (
                                                <img src={c.profiles.avatar_url} alt="" style={{ width: '18px', height: '18px', borderRadius: '50%', objectFit: 'cover' }} />
                                            ) : (
                                                <span style={{ width: '18px', height: '18px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.55rem', fontWeight: 700, color: 'white' }}>
                                                    {(c.profiles?.full_name || '?')[0]}
                                                </span>
                                            )}
                                            <Link to={`/profile/${c.author_id}`} style={{ fontWeight: 600, color: 'var(--text-primary)', textDecoration: 'none' }} onClick={e => e.stopPropagation()} onMouseOver={e => (e.currentTarget.style.textDecoration = 'underline')} onMouseOut={e => (e.currentTarget.style.textDecoration = 'none')}>{c.profiles?.full_name || 'Anonymous'}</Link>
                                            <span>•</span>
                                            <span>{timeAgo(c.created_at)}</span>
                                            {c.edit_history && c.edit_history.length > 0 && (
                                                <span onClick={(e) => { e.stopPropagation(); setShowHistoryModal(c.id); }} style={{ fontSize: '0.7rem', color: 'var(--text-muted)', cursor: 'pointer', fontStyle: 'italic' }} onMouseEnter={e => e.currentTarget.style.textDecoration = 'underline'} onMouseLeave={e => e.currentTarget.style.textDecoration = 'none'}>(edited)</span>
                                            )}
                                            {user && user.id === c.author_id && (
                                                <div style={{ display: 'flex', gap: '0.5rem', marginLeft: 'auto' }}>
                                                    <button onClick={(e) => { e.stopPropagation(); setEditingCommentId(c.id); setEditCommentContent(c.content); }} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', padding: '0.1rem' }}><Edit2 size={12} /></button>
                                                    <button onClick={(e) => { e.stopPropagation(); handleDeleteComment(c.id); }} style={{ background: 'none', border: 'none', color: '#ef4444', cursor: 'pointer', padding: '0.1rem' }}><Trash2 size={12} /></button>
                                                </div>
                                            )}
                                        </div>
                                        {editingCommentId === c.id ? (
                                            <form onSubmit={(e) => handleUpdateComment(e, c.id)} style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', marginTop: '0.2rem' }} onClick={e => e.stopPropagation()}>
                                                <input type="text" className="input-glass" value={editCommentContent} onChange={e => setEditCommentContent(e.target.value)} required />
                                                <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end' }}>
                                                    <button type="button" onClick={() => setEditingCommentId(null)} className="btn-secondary" style={{ padding: '0.2rem 0.5rem', fontSize: '0.75rem' }}>Cancel</button>
                                                    <button type="submit" className="btn-primary" style={{ padding: '0.2rem 0.5rem', fontSize: '0.75rem' }}>Save</button>
                                                </div>
                                            </form>
                                        ) : (
                                            <p style={{ whiteSpace: 'pre-wrap', fontSize: '0.9rem', color: 'var(--text-secondary)', lineHeight: 1.4, margin: 0 }}>
                                                {c.content}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>No comments yet.</div>
                        )}

                    </div>
                )}
            </div>

            {/* Edit History Modal */}
            {showHistoryModal && createPortal(
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100, padding: '1rem' }} onClick={(e) => { e.stopPropagation(); setShowHistoryModal(null); }}>
                    <div className="glass-panel animate-fade-in-up" style={{ width: '100%', maxWidth: '500px', padding: '2rem', display: 'flex', flexDirection: 'column', gap: '1rem', maxHeight: '80vh', overflowY: 'auto' }} onClick={e => e.stopPropagation()}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0 }}>Edit History</h3>
                            <button onClick={() => setShowHistoryModal(null)} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', fontSize: '1.2rem' }}>✕</button>
                        </div>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '1rem' }}>
                            {showHistoryModal === 'post' ? (
                                post.edit_history?.slice().reverse().map((hist: any, i: number) => (
                                    <div key={i} style={{ background: 'rgba(255,255,255,0.03)', padding: '1.25rem', borderRadius: 'var(--radius-md)' }}>
                                        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>{new Date(hist.edited_at).toLocaleString()}</div>
                                        <h4 style={{ fontSize: '1.1rem', marginBottom: '0.25rem', marginTop: 0 }}>{hist.title}</h4>
                                        <p style={{ whiteSpace: 'pre-wrap', fontSize: '0.9rem', color: 'var(--text-secondary)', margin: 0, lineHeight: 1.5 }}>{hist.content}</p>
                                    </div>
                                ))
                            ) : (
                                comments.find(c => c.id === showHistoryModal)?.edit_history?.slice().reverse().map((hist: any, i: number) => (
                                    <div key={i} style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: 'var(--radius-md)' }}>
                                        <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>{new Date(hist.edited_at).toLocaleString()}</div>
                                        <p style={{ whiteSpace: 'pre-wrap', fontSize: '0.9rem', color: 'var(--text-secondary)', margin: 0, lineHeight: 1.4 }}>{hist.content}</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>,
                document.body
            )}

            {/* Report Modal */}
            {showReportModal && createPortal(
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(10, 10, 14, 0.85)', backdropFilter: 'blur(8px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100, padding: '1rem' }} onClick={(e) => { e.stopPropagation(); setShowReportModal(false); setReportReason(''); }}>
                    <div className="glass-panel animate-fade-in-up" style={{ width: '100%', maxWidth: '450px', padding: '2rem', display: 'flex', flexDirection: 'column', gap: '1.5rem', border: '1px solid var(--border-glass)', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)' }} onClick={e => e.stopPropagation()}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.5rem', fontWeight: 700, display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#eab308' }}>
                                <AlertTriangle size={24} /> Report Post
                            </h3>
                            <button onClick={() => { setShowReportModal(false); setReportReason(''); }} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', width: '32px', height: '32px', borderRadius: '50%', transition: 'all 0.2s' }} className="hover-white"><X size={18} /></button>
                        </div>
                        <form onSubmit={submitReport} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Why are you reporting this post?</label>
                                <textarea
                                    className="input-glass"
                                    value={reportReason}
                                    onChange={e => setReportReason(e.target.value)}
                                    placeholder="Please provide details about why this post violates our community guidelines..."
                                    rows={4}
                                    required
                                    style={{ resize: 'vertical', width: '100%', padding: '0.75rem' }}
                                    autoFocus
                                />
                            </div>
                            <div style={{ display: 'flex', gap: '1rem', justifyContent: 'flex-end', marginTop: '0.5rem' }}>
                                <button type="button" onClick={() => { setShowReportModal(false); setReportReason(''); }} className="btn-secondary" style={{ padding: '0.6rem 1.2rem', fontSize: '0.95rem' }} disabled={submittingReport}>Cancel</button>
                                <button type="submit" className="btn-primary" style={{ padding: '0.6rem 1.2rem', fontSize: '0.95rem', background: 'rgba(234, 179, 8, 0.15)', color: '#eab308', border: '1px solid rgba(234, 179, 8, 0.3)' }} disabled={submittingReport || !reportReason.trim()}>
                                    {submittingReport ? 'Submitting...' : 'Submit Report'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>,
                document.body
            )}
        </div>
    )
}

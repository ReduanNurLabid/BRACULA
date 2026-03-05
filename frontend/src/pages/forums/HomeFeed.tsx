import { useState, useEffect } from 'react'
import { useInfiniteQuery } from '@tanstack/react-query'
import { useInView } from 'react-intersection-observer'
import { supabase } from '../../lib/supabase'
import { Sidebar } from './components/Sidebar'
import { PostCard } from './components/PostCard'
import { CreatePostModal } from './components/CreatePostModal'
import { useAuth } from '../../contexts/AuthContext'
import { useNavigate } from 'react-router-dom'

export const HomeFeed = () => {
    const { user } = useAuth()
    const navigate = useNavigate()

    const [avatarUrl, setAvatarUrl] = useState<string | null>(null)
    const [activeCommunityId, setActiveCommunityId] = useState<string | null>(null)
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)

    const { ref, inView } = useInView()

    const {
        data,
        isLoading,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage,
        refetch: fetchPosts
    } = useInfiniteQuery({
        queryKey: ['posts', activeCommunityId],
        queryFn: async ({ pageParam = 0 }) => {
            const limit = 10;
            let query = supabase
                .from('posts')
                .select(`
                    *,
                    profiles:author_id(full_name, avatar_url, department),
                    communities:community_id(name),
                    comments(count)
                `)
                .order('created_at', { ascending: false })
                .range(pageParam * limit, (pageParam + 1) * limit - 1)

            if (activeCommunityId) {
                query = query.eq('community_id', activeCommunityId)
            }

            const { data, error } = await query
            if (error) throw error
            return data || []
        },
        getNextPageParam: (lastPage, allPages) => {
            return lastPage.length === 10 ? allPages.length : undefined
        },
        initialPageParam: 0
    })

    useEffect(() => {
        if (inView && hasNextPage) {
            fetchNextPage()
        }
    }, [inView, hasNextPage, fetchNextPage])

    const posts = data?.pages.flat() || []

    useEffect(() => {
        if (!user) { setAvatarUrl(null); return }
        const fetchAvatar = async () => {
            const { data } = await supabase.from('profiles').select('avatar_url').eq('id', user.id).single()
            if (data?.avatar_url) setAvatarUrl(data.avatar_url)
        }
        fetchAvatar()
    }, [user])

    const handleCreateClick = () => {
        if (!user) {
            // Must be logged in to create
            navigate('/login')
            return
        }
        setIsCreateModalOpen(true)
    }

    return (
        <div className="animate-fade-in-up" style={{ padding: '2rem 0', width: 'calc(100% - 2rem)', maxWidth: '1200px', margin: '0 auto', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>

            {/* Left Sidebar */}
            <Sidebar onSelectCommunity={setActiveCommunityId} activeCommunity={activeCommunityId} />

            {/* Main Feed */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem', minWidth: 0 }}>

                {/* Create Post Banner */}
                <div className="glass-panel" style={{ padding: '1rem 1.5rem', display: 'flex', gap: '1rem', alignItems: 'center' }}>
                    {avatarUrl ? (
                        <img loading="lazy" src={avatarUrl} alt="Profile" style={{ flexShrink: 0, width: '40px', height: '40px', borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--accent-primary)' }} />
                    ) : (
                        <div style={{ flexShrink: 0, overflow: 'hidden', width: '40px', height: '40px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 'bold' }}>
                            {user ? user.email?.charAt(0).toUpperCase() : '?'}
                        </div>
                    )}
                    <input
                        type="text"
                        placeholder="Create a new post..."
                        className="input-glass"
                        style={{ flex: 1, cursor: 'text' }}
                        onClick={handleCreateClick}
                        readOnly
                    />
                    <button className="btn-primary" onClick={handleCreateClick}>Post</button>
                </div>

                {/* Posts Feed */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    {isLoading ? (
                        <div style={{ textAlign: 'center', padding: '3rem', color: 'var(--text-secondary)' }}>Loading feed...</div>
                    ) : posts.length === 0 ? (
                        <div className="glass-panel" style={{ textAlign: 'center', padding: '4rem', color: 'var(--text-secondary)' }}>
                            <p style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>No posts found here yet.</p>
                            <p>Be the first to start a conversation!</p>
                        </div>
                    ) : (
                        <>
                            {posts.map(post => (
                                <PostCard key={post.id} post={post} onVoteChanged={() => fetchPosts()} />
                            ))}
                            <div ref={ref} style={{ textAlign: 'center', padding: '1rem', color: 'var(--text-secondary)' }}>
                                {isFetchingNextPage
                                    ? 'Loading more...'
                                    : hasNextPage
                                        ? 'Load More'
                                        : 'You have reached the end of the feed.'}
                            </div>
                        </>
                    )}
                </div>

            </div>

            {isCreateModalOpen && (
                <CreatePostModal
                    onClose={() => setIsCreateModalOpen(false)}
                    onPostCreated={() => {
                        setIsCreateModalOpen(false)
                        fetchPosts()
                    }}
                    preselectedCommunityId={activeCommunityId}
                />
            )}
        </div>
    )
}

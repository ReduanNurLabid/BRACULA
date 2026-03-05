import { useState, useEffect, useRef } from 'react';
import { supabase } from '../../lib/supabase';
import { useAuth } from '../../contexts/AuthContext';
import { toast } from 'react-hot-toast';
import { Send, Clock, UserCircle, MessageSquare } from 'lucide-react';

type ShoutboxMessage = {
    id: string;
    author_id: string;
    shoutbox_username: string;
    content: string;
    created_at: string;
};

export const Shoutbox = () => {
    const { user } = useAuth();
    const [messages, setMessages] = useState<ShoutboxMessage[]>([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);

    // Username state
    const [username, setUsername] = useState<string | null>(null);
    const [showUsernameSetup, setShowUsernameSetup] = useState(false);
    const [newUsernameInput, setNewUsernameInput] = useState('');
    const [settingUsername, setSettingUsername] = useState(false);

    // Cooldown state
    const [cooldown, setCooldown] = useState(0);

    const messagesEndRef = useRef<HTMLDivElement>(null);

    const fetchUserProfile = async () => {
        if (!user) return;
        try {
            const { data, error } = await supabase
                .from('profiles')
                .select('shoutbox_username')
                .eq('id', user.id)
                .single();
            if (error) throw error;

            if (data?.shoutbox_username) {
                setUsername(data.shoutbox_username);
            } else {
                setShowUsernameSetup(true);
            }
        } catch (err: any) {
            console.error('Error fetching profile:', err.message);
        }
    };

    const fetchMessages = async () => {
        try {
            // Policies restrict this to last 6 hours automatically on the backend
            const { data, error } = await supabase
                .from('shoutbox_messages')
                .select('*')
                .order('created_at', { ascending: false })
                .limit(100);

            if (error) throw error;
            // Reverse so oldest is top, newest is bottom
            setMessages((data || []).reverse());
        } catch (err: any) {
            toast.error('Failed to load messages');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchUserProfile();
        fetchMessages();

        // Subscribe to real-time messages
        const channel = supabase.channel('public:shoutbox_messages')
            .on(
                'postgres_changes',
                { event: 'INSERT', schema: 'public', table: 'shoutbox_messages' },
                (payload: any) => {
                    setMessages((current) => [...current, payload.new as ShoutboxMessage]);
                }
            )
            .subscribe();

        return () => {
            supabase.removeChannel(channel);
        };
    }, [user]);

    // Auto-scroll to bottom on new message
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Cooldown timer effect
    useEffect(() => {
        let timer: ReturnType<typeof setTimeout>;
        if (cooldown > 0) {
            timer = setTimeout(() => setCooldown(c => c - 1), 1000);
        }
        return () => clearTimeout(timer);
    }, [cooldown]);

    const handleSetUsername = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newUsernameInput.trim() || !user) return;

        setSettingUsername(true);
        try {
            const { error } = await supabase.rpc('rpc_set_shoutbox_username', {
                p_user_id: user.id,
                p_username: newUsernameInput.trim()
            });

            if (error) throw error;

            setUsername(newUsernameInput.trim());
            setShowUsernameSetup(false);
            toast.success("Shoutbox username locked in for 7 days!");
        } catch (err: any) {
            toast.error(err.message || 'Failed to set username');
        } finally {
            setSettingUsername(false);
        }
    };

    const handleSendMessage = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!user || !username) {
            toast.error("Please set a username first.");
            return;
        }
        if (!newMessage.trim() || cooldown > 0 || sending) return;

        setSending(true);
        try {
            const { error } = await supabase.rpc('rpc_send_shout', {
                p_user_id: user.id,
                p_content: newMessage.trim()
            });

            if (error) throw error;

            setNewMessage('');
            setCooldown(30); // Start 30 second local cooldown UI
        } catch (err: any) {
            toast.error(err.message || "Could not send message.");
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="container animate-fade-in-up" style={{ padding: 'clamp(1rem, 3vh, 2rem) clamp(0.5rem, 3vw, 1.5rem)', flex: 1, display: 'flex', flexDirection: 'column', height: '100%' }}>
            <div style={{ marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '1rem', flexShrink: 0 }}>
                <div style={{ padding: '0.75rem', background: 'rgba(99, 102, 241, 0.1)', borderRadius: 'var(--radius-lg)', display: 'none' /* Hide icon on mobile? Keep it for now, just flexShrink */ }}>
                    <MessageSquare size={24} color="var(--accent-primary)" />
                </div>
                <div>
                    <h1 style={{ fontSize: '1.75rem', fontWeight: 700, margin: 0 }}>Anondopur Shoutbox</h1>
                    <p style={{ color: 'var(--text-secondary)', margin: '0.25rem 0 0 0', fontSize: '0.95rem' }}>
                        Global chat room. Slowmode 30 seconds. Be respectful to each other.
                    </p>
                </div>
            </div>

            {/* Username Setup Modal Overlay */}
            {showUsernameSetup && user && (
                <div style={{
                    position: 'absolute', top: 0, left: 0, right: 0, bottom: 0,
                    background: 'rgba(10, 10, 15, 0.8)', zIndex: 50,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    backdropFilter: 'blur(5px)', borderRadius: 'var(--radius-lg)'
                }}>
                    <div className="glass-panel" style={{ padding: '2rem', maxWidth: '400px', width: '90%' }}>
                        <div style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
                            <UserCircle size={48} color="var(--accent-primary)" style={{ margin: '0 auto 1rem auto' }} />
                            <h2>Choose Your Identity</h2>
                            <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem', marginTop: '0.5rem' }}>
                                This generic name will be your mask in the shoutbox. <br /><strong style={{ color: 'var(--accent)' }}>You cannot change it again for 7 days!</strong>
                            </p>
                        </div>
                        <form onSubmit={handleSetUsername} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                            <input
                                autoFocus
                                type="text"
                                className="input-glass"
                                placeholder="e.g. Shadow Ninja, Happy Hippocamous"
                                value={newUsernameInput}
                                onChange={(e) => setNewUsernameInput(e.target.value)}
                                required
                                maxLength={25}
                            />
                            <button type="submit" className="btn-primary" disabled={settingUsername || !newUsernameInput.trim()}>
                                {settingUsername ? 'Locking in...' : 'Enter Shoutbox'}
                            </button>
                        </form>
                    </div>
                </div>
            )}

            <div className="glass-panel" style={{ flex: 1, display: 'flex', flexDirection: 'column', overflow: 'hidden', position: 'relative' }}>

                {!user ? (
                    <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--text-secondary)' }}>
                        Please login to view and send messages.
                    </div>
                ) : loading ? (
                    <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--text-secondary)' }}>
                        Loading latest shouts...
                    </div>
                ) : (
                    <>
                        {/* Messages Area */}
                        <div style={{ flex: 1, overflowY: 'auto', padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                            {messages.length === 0 ? (
                                <div style={{ textAlign: 'center', color: 'var(--text-secondary)', marginTop: '2rem' }}>
                                    No recent shouts. Be the first!
                                </div>
                            ) : (
                                messages.map((msg) => {
                                    const isMe = msg.author_id === user.id;
                                    return (
                                        <div key={msg.id} style={{
                                            alignSelf: isMe ? 'flex-end' : 'flex-start',
                                            maxWidth: '80%',
                                            display: 'flex',
                                            flexDirection: 'column',
                                            alignItems: isMe ? 'flex-end' : 'flex-start'
                                        }}>
                                            <span style={{ fontSize: '0.8rem', color: 'var(--text-secondary)', marginBottom: '0.25rem', padding: '0 0.5rem' }}>
                                                {isMe ? 'You' : msg.shoutbox_username}
                                                <span style={{ opacity: 0.5, marginLeft: '0.5rem' }}>
                                                    {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                </span>
                                            </span>
                                            <div style={{
                                                padding: '0.75rem 1rem',
                                                borderRadius: '1.5rem',
                                                borderBottomLeftRadius: isMe ? '1.5rem' : '0.25rem',
                                                borderBottomRightRadius: isMe ? '0.25rem' : '1.5rem',
                                                background: isMe ? 'var(--accent-primary)' : 'rgba(255,255,255,0.05)',
                                                border: isMe ? 'none' : '1px solid rgba(255,255,255,0.1)',
                                                color: 'var(--text-primary)',
                                                wordBreak: 'break-word',
                                                lineHeight: 1.5
                                            }}>
                                                {msg.content}
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Input Area */}
                        <div style={{ padding: '1rem', borderTop: '1px solid rgba(255,255,255,0.05)', background: 'rgba(0,0,0,0.2)' }}>
                            <form onSubmit={handleSendMessage} style={{ display: 'flex', gap: '0.5rem' }}>
                                <input
                                    type="text"
                                    className="input-glass"
                                    style={{ flex: 1, background: 'rgba(255,255,255,0.05)', minWidth: 0 /* Prevents input from overflowing flex on small screens */ }}
                                    placeholder="Shout something..."
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    maxLength={500}
                                    disabled={cooldown > 0 || !username}
                                />
                                <button
                                    type="submit"
                                    className={cooldown > 0 ? "btn-secondary" : "btn-primary"}
                                    disabled={!newMessage.trim() || cooldown > 0 || sending || !username}
                                    style={{ padding: '0 1rem', display: 'flex', alignItems: 'center', gap: '0.5rem', transition: 'all 0.3s', whiteSpace: 'nowrap', flexShrink: 0 }}
                                >
                                    {cooldown > 0 ? (
                                        <>
                                            <Clock size={16} /> {cooldown}s
                                        </>
                                    ) : sending ? (
                                        'Sending...'
                                    ) : (
                                        <>
                                            <Send size={16} /> Send
                                        </>
                                    )}
                                </button>
                            </form>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
};

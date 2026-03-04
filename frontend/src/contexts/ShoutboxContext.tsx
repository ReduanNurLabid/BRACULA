import React, { createContext, useContext, useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { supabase } from '../lib/supabase';
import { useAuth } from './AuthContext';

interface ShoutboxContextType {
    hasUnread: boolean;
    clearUnread: () => void;
}

const ShoutboxContext = createContext<ShoutboxContextType | undefined>(undefined);

export const ShoutboxProvider = ({ children }: { children: React.ReactNode }) => {
    const { user } = useAuth();
    const location = useLocation();
    const [hasUnread, setHasUnread] = useState(false);

    // Any time we route TO /shoutbox, clear the indicator instantly
    useEffect(() => {
        if (location.pathname === '/shoutbox') {
            setHasUnread(false);
        }
    }, [location.pathname]);

    useEffect(() => {
        if (!user) return; // Optional: Only show unread counts if logged in so we don't bleed guests too much realtime

        const channel = supabase.channel('shoutbox_indicator')
            .on(
                'postgres_changes',
                { event: 'INSERT', schema: 'public', table: 'shoutbox_messages' },
                (payload) => {
                    // Only trigger if we aren't currently viewing the shoutbox AND the message wasn't sent by us
                    if (location.pathname !== '/shoutbox' && payload.new.author_id !== user.id) {
                        setHasUnread(true);
                    }
                }
            )
            .subscribe((_status, err) => {
                if (err) console.error("Realtime subscription error for Shoutbox Indicator:", err);
            });

        return () => {
            supabase.removeChannel(channel);
        };
    }, [user, location.pathname]); // Need pathname in dep array so the closure knows if we are actively viewing it

    const clearUnread = () => setHasUnread(false);

    return (
        <ShoutboxContext.Provider value={{ hasUnread, clearUnread }}>
            {children}
        </ShoutboxContext.Provider>
    );
};

export const useShoutbox = () => {
    const context = useContext(ShoutboxContext);
    if (context === undefined) {
        throw new Error('useShoutbox must be used within a ShoutboxProvider');
    }
    return context;
};

import { createContext, useContext, useState, useCallback } from 'react';
import type { ReactNode } from 'react';

interface ConfirmContextType {
    confirm: (message: string) => Promise<boolean>;
}

const ConfirmContext = createContext<ConfirmContextType | undefined>(undefined);

export const ConfirmProvider = ({ children }: { children: ReactNode }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [message, setMessage] = useState('');
    const [resolver, setResolver] = useState<(value: boolean) => void>();

    const confirm = useCallback((msg: string) => {
        setMessage(msg);
        setIsOpen(true);
        return new Promise<boolean>((resolve) => {
            setResolver(() => resolve);
        });
    }, []);

    const handleConfirm = () => {
        setIsOpen(false);
        if (resolver) resolver(true);
    };

    const handleCancel = () => {
        setIsOpen(false);
        if (resolver) resolver(false);
    };

    return (
        <ConfirmContext.Provider value={{ confirm }}>
            {children}
            {isOpen && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    backgroundColor: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999,
                    padding: '1rem'
                }}>
                    <div className="glass-panel animate-fade-in-up" style={{ width: '100%', maxWidth: '400px', padding: '1.5rem', textAlign: 'center', display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
                        <div>
                            <h3 style={{ marginBottom: '0.5rem', fontSize: '1.25rem' }}>Confirm Action</h3>
                            <p style={{ color: 'var(--text-secondary)', margin: 0 }}>{message}</p>
                        </div>
                        <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'center' }}>
                            <button className="btn-secondary" onClick={handleCancel}>Cancel</button>
                            <button className="btn-primary" style={{ background: '#ef4444' }} onClick={handleConfirm}>Yes, I'm sure</button>
                        </div>
                    </div>
                </div>
            )}
        </ConfirmContext.Provider>
    );
};

export const useConfirm = () => {
    const context = useContext(ConfirmContext);
    if (!context) {
        throw new Error('useConfirm must be used within a ConfirmProvider');
    }
    return context.confirm;
};

import { useState } from 'react'
import { Search, Users, BookOpen, Trophy, Globe, Code, Palette, Microscope } from 'lucide-react'

// Dummy/Hardcoded data from BRACU website
const CLUBS = [
    { id: 1, name: 'Business & Economics Forum', abbreviation: 'BUBeF', category: 'Academic', icon: BookOpen },
    { id: 2, name: 'Business Club', abbreviation: 'BIZBEE', category: 'Academic', icon: Users },
    { id: 3, name: 'Computer Club', abbreviation: 'BUCC', category: 'Academic', icon: Code },
    { id: 4, name: 'Economics Club', abbreviation: 'BUEC', category: 'Academic', icon: Globe },
    { id: 5, name: 'Electrical & Electronic Club', abbreviation: 'BUEEC', category: 'Academic', icon: Code },
    { id: 6, name: 'Finance and Accounting Club', abbreviation: 'FINACT', category: 'Academic', icon: BookOpen },
    { id: 7, name: 'Law Society', abbreviation: 'BULC', category: 'Academic', icon: BookOpen },
    { id: 8, name: 'Marketing Association', abbreviation: 'BUMA', category: 'Academic', icon: Globe },
    { id: 9, name: 'Natural Science', abbreviation: 'BUNSC', category: 'Academic', icon: Microscope },
    { id: 10, name: 'Pharmacy Society', abbreviation: 'BUPS', category: 'Academic', icon: Microscope },
    { id: 11, name: 'Robotics Club', abbreviation: 'ROBU', category: 'Academic', icon: Code },
    { id: 12, name: 'Society For Biotechnology', abbreviation: 'BUSB', category: 'Academic', icon: Microscope },

    { id: 13, name: 'Adventure Club', abbreviation: 'BUAC', category: 'Non-Academic', icon: Globe },
    { id: 14, name: 'Art & Photography Society', abbreviation: 'BUAPS', category: 'Non-Academic', icon: Palette },
    { id: 15, name: 'Community Service Club', abbreviation: 'BUCSC', category: 'Non-Academic', icon: Users },
    { id: 16, name: 'Cultural Club', abbreviation: 'BUCuC', category: 'Non-Academic', icon: Users },
    { id: 17, name: 'Debating Club', abbreviation: 'BUDC', category: 'Non-Academic', icon: Users },
    { id: 18, name: 'Drama and Theater Forum', abbreviation: 'BUDTF', category: 'Non-Academic', icon: Palette },
    { id: 19, name: 'Entrepreneurship Forum', abbreviation: 'BUEDF', category: 'Non-Academic', icon: Globe },
    { id: 20, name: 'Film Club', abbreviation: 'BUFC', category: 'Non-Academic', icon: Palette },
    { id: 21, name: 'Response Team', abbreviation: 'BURT', category: 'Non-Academic', icon: Users },
    { id: 22, name: 'Leadership Development Forum', abbreviation: 'BULDF', category: 'Non-Academic', icon: Users },

    { id: 23, name: 'Cricket Club', abbreviation: 'CBU', category: 'Sports', icon: Trophy },
    { id: 24, name: 'Football Club', abbreviation: 'FCBU', category: 'Sports', icon: Trophy },
    { id: 25, name: 'Indoor Games Club', abbreviation: 'BUIGC', category: 'Sports', icon: Trophy },
]

export const Clubs = () => {
    const [searchQuery, setSearchQuery] = useState('')
    const [activeTab, setActiveTab] = useState<'All' | 'Academic' | 'Non-Academic' | 'Sports'>('All')

    const filteredClubs = CLUBS.filter(club => {
        const matchesSearch = club.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            club.abbreviation.toLowerCase().includes(searchQuery.toLowerCase())
        const matchesCategory = activeTab === 'All' || club.category === activeTab

        return matchesSearch && matchesCategory
    })

    return (
        <div className="container animate-fade-in-up" style={{ padding: '2rem 1.5rem', flex: 1 }}>
            <div style={{ marginBottom: '2rem', textAlign: 'center' }}>
                <h1 className="text-gradient" style={{ fontSize: '3rem', marginBottom: '0.5rem' }}>BRACU Clubs & Societies</h1>
                <p style={{ color: 'var(--text-secondary)', fontSize: '1.2rem', maxWidth: '600px', margin: '0 auto' }}>
                    Discover your passion, build your skills, and connect with like-minded peers across 30+ active clubs.
                </p>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1.5rem', marginBottom: '3rem' }}>
                <div style={{ position: 'relative', width: '100%', maxWidth: '600px' }}>
                    <Search style={{ position: 'absolute', left: '1rem', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} size={20} />
                    <input
                        type="text"
                        className="input-glass"
                        placeholder="Search clubs by name or abbreviation (e.g. 'BUCC')..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        style={{ paddingLeft: '3rem', height: '3.5rem', fontSize: '1.1rem', borderRadius: 'var(--radius-full)' }}
                    />
                </div>

                <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap', justifyContent: 'center' }}>
                    {['All', 'Academic', 'Non-Academic', 'Sports'].map(tab => (
                        <button
                            key={tab}
                            onClick={() => setActiveTab(tab as any)}
                            style={{
                                padding: '0.5rem 1.25rem',
                                borderRadius: 'var(--radius-full)',
                                border: '1px solid',
                                borderColor: activeTab === tab ? 'var(--accent-primary)' : 'var(--border-glass)',
                                background: activeTab === tab ? 'rgba(78, 204, 163, 0.1)' : 'rgba(255,255,255,0.03)',
                                color: activeTab === tab ? 'var(--accent-primary)' : 'var(--text-secondary)',
                                cursor: 'pointer',
                                transition: 'all 0.2s ease',
                                fontWeight: activeTab === tab ? 600 : 400
                            }}
                        >
                            {tab}
                        </button>
                    ))}
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 250px), 1fr))', gap: '1.5rem' }}>
                {filteredClubs.map(club => {
                    const Icon = club.icon
                    const isAcademic = club.category === 'Academic'
                    const isSports = club.category === 'Sports'

                    const color = isAcademic ? 'var(--accent-primary)' : isSports ? '#f1c40f' : '#e74c3c'
                    const bgColor = isAcademic ? 'rgba(78, 204, 163, 0.1)' : isSports ? 'rgba(241, 196, 15, 0.1)' : 'rgba(231, 76, 60, 0.1)'

                    return (
                        <div key={club.id} className="glass-panel hover-lift" style={{ padding: '2rem 1.5rem', display: 'flex', flexDirection: 'column', alignItems: 'center', textAlign: 'center', gap: '1rem', cursor: 'pointer' }}>
                            <div style={{ width: '64px', height: '64px', borderRadius: '50%', background: bgColor, display: 'flex', alignItems: 'center', justifyContent: 'center', color: color }}>
                                <Icon size={32} />
                            </div>
                            <div>
                                <h3 style={{ fontSize: '1.25rem', marginBottom: '0.25rem' }}>{club.name}</h3>
                                <p style={{ color: color, fontWeight: 700, margin: 0 }}>{club.abbreviation}</p>
                            </div>
                            <div style={{ marginTop: 'auto', paddingTop: '1rem' }}>
                                <span style={{ padding: '0.25rem 0.75rem', borderRadius: 'var(--radius-full)', background: 'rgba(255,255,255,0.05)', fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                                    {club.category}
                                </span>
                            </div>
                        </div>
                    )
                })}
            </div>

            {filteredClubs.length === 0 && (
                <div style={{ textAlign: 'center', padding: '4rem 2rem', color: 'var(--text-secondary)', border: '1px dashed var(--border-glass)', borderRadius: 'var(--radius-lg)' }}>
                    <div style={{ marginBottom: '1rem' }}><Search size={48} style={{ opacity: 0.2, margin: '0 auto' }} /></div>
                    <h3>No clubs found</h3>
                    <p>Try adjusting your search or category filters.</p>
                </div>
            )}
        </div>
    )
}

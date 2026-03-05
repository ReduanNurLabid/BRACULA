import { useState, useEffect, useDeferredValue } from 'react'
import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query'
import { useInView } from 'react-intersection-observer'
import { Car, MapPin, Clock, Users, PlusCircle, Check, X, Star } from 'lucide-react'
import { supabase } from '../../lib/supabase'
import { toast } from 'react-hot-toast'
import { useConfirm } from '../../contexts/ConfirmContext'
import { useAuth } from '../../contexts/AuthContext'
import { createPortal } from 'react-dom'

export const Rides = () => {
    const { user: currentUser } = useAuth()
    const queryClient = useQueryClient()

    const [activeTab, setActiveTab] = useState<'find' | 'offered' | 'joined'>('find')
    const [searchQuery, setSearchQuery] = useState('')

    // Create Ride Modal
    const [showCreate, setShowCreate] = useState(false)
    const [vehicleType, setVehicleType] = useState('Rickshaw')
    const [startLocation, setStartLocation] = useState('')
    const [endLocation, setEndLocation] = useState('')
    const [departureTime, setDepartureTime] = useState('')
    const [totalSeats, setTotalSeats] = useState(1)
    const [pricePerSeat, setPricePerSeat] = useState(0)
    const [pricingType, setPricingType] = useState('exact')
    const [driverContact, setDriverContact] = useState('')
    const [creating, setCreating] = useState(false)
    const confirm = useConfirm()

    // Request Ride Modal
    const [requestingRideId, setRequestingRideId] = useState<string | null>(null)
    const [passengerContact, setPassengerContact] = useState('')
    const [submittingRequest, setSubmittingRequest] = useState(false)

    // Rate User Modal
    const [rateModalTarget, setRateModalTarget] = useState<{ rideId: string, revieweeId: string, revieweeName: string } | null>(null)
    const [rating, setRating] = useState(5)
    const [reviewComment, setReviewComment] = useState('')
    const [submittingReview, setSubmittingReview] = useState(false)

    // Find Rides Query
    const { ref: findRef, inView: findInView } = useInView()
    const {
        data: ridesData,
        isLoading: loadingRides,
        fetchNextPage: fetchNextRides,
        hasNextPage: hasNextRides,
        isFetchingNextPage: isFetchingNextRides
    } = useInfiniteQuery({
        queryKey: ['rides'],
        queryFn: async ({ pageParam = 0 }) => {
            const limit = 10;
            const { data, error } = await supabase
                .from('rides')
                .select('*, profiles(full_name, email, trust_score, total_reviews, avatar_url)')
                .eq('status', 'open')
                .gte('departure_time', new Date().toISOString())
                .order('departure_time', { ascending: true })
                .range(pageParam * limit, (pageParam + 1) * limit - 1)
            if (error) throw error
            return data || []
        },
        getNextPageParam: (lastPage, allPages) => lastPage.length === 10 ? allPages.length : undefined,
        initialPageParam: 0
    })

    // My Offered Rides Query
    const { ref: offeredRef, inView: offeredInView } = useInView()
    const {
        data: offeredData,
        isLoading: loadingOffered,
        fetchNextPage: fetchNextOffered,
        hasNextPage: hasNextOffered,
        isFetchingNextPage: isFetchingNextOffered
    } = useInfiniteQuery({
        queryKey: ['myOfferedRides', currentUser?.id],
        enabled: !!currentUser,
        queryFn: async ({ pageParam = 0 }) => {
            const limit = 10;
            const { data, error } = await supabase
                .from('rides')
                .select(`
                    *,
                    ride_requests(
                        *,
                        profiles(full_name, email, trust_score, total_reviews, avatar_url)
                    )
                `)
                .eq('driver_id', currentUser!.id)
                .order('created_at', { ascending: false })
                .range(pageParam * limit, (pageParam + 1) * limit - 1)
            if (error) throw error
            return data || []
        },
        getNextPageParam: (lastPage, allPages) => lastPage.length === 10 ? allPages.length : undefined,
        initialPageParam: 0
    })

    // My Joined Rides Query
    const { ref: joinedRef, inView: joinedInView } = useInView()
    const {
        data: joinedData,
        isLoading: loadingJoined,
        fetchNextPage: fetchNextJoined,
        hasNextPage: hasNextJoined,
        isFetchingNextPage: isFetchingNextJoined
    } = useInfiniteQuery({
        queryKey: ['myJoinedRides', currentUser?.id],
        enabled: !!currentUser,
        queryFn: async ({ pageParam = 0 }) => {
            const limit = 10;
            const { data, error } = await supabase
                .from('ride_requests')
                .select(`
                    *,
                    rides(
                        *,
                        profiles(full_name, email, trust_score, total_reviews, avatar_url)
                    )
                `)
                .eq('passenger_id', currentUser!.id)
                .order('created_at', { ascending: false })
                .range(pageParam * limit, (pageParam + 1) * limit - 1)
            if (error) throw error
            return data || []
        },
        getNextPageParam: (lastPage, allPages) => lastPage.length === 10 ? allPages.length : undefined,
        initialPageParam: 0
    })

    useEffect(() => {
        if (activeTab === 'find' && findInView && hasNextRides) fetchNextRides()
        if (activeTab === 'offered' && offeredInView && hasNextOffered) fetchNextOffered()
        if (activeTab === 'joined' && joinedInView && hasNextJoined) fetchNextJoined()
    }, [activeTab, findInView, hasNextRides, offeredInView, hasNextOffered, joinedInView, hasNextJoined, fetchNextRides, fetchNextOffered, fetchNextJoined])

    const rides = ridesData?.pages.flat() || []
    const myOfferedRides = offeredData?.pages.flat() || []
    const myJoinedRides = joinedData?.pages.flat() || []

    const loading = loadingRides || loadingOffered || loadingJoined

    const handleCreateRide = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!currentUser) {
            toast.error("Please login first")
            return
        }

        setCreating(true)
        try {
            const { error } = await supabase
                .from('rides')
                .insert({
                    driver_id: currentUser.id,
                    vehicle_type: vehicleType,
                    start_location: startLocation,
                    end_location: endLocation,
                    departure_time: new Date(departureTime).toISOString(),
                    total_seats: totalSeats,
                    available_seats: totalSeats,
                    price_per_seat: pricingType === 'exact' ? pricePerSeat : 0,
                    pricing_type: pricingType,
                    contact_number: driverContact
                })

            if (error) throw error

            toast.success('Ride offered successfully!')
            setShowCreate(false) // Changed from setShowCreateModal
            // Reset form
            setStartLocation('')
            setEndLocation('')
            setDepartureTime('')

            queryClient.invalidateQueries({ queryKey: ['rides'] })
            queryClient.invalidateQueries({ queryKey: ['myOfferedRides', currentUser?.id] })

        } catch (error: any) { // Changed err to error for consistency
            toast.error(error.message)
        } finally {
            setCreating(false)
        }
    }

    const requestRide = (rideId: string) => {
        if (!currentUser) {
            toast.error("Please login first")
            return
        }
        setRequestingRideId(rideId)
    }

    const submitRequest = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!requestingRideId || !currentUser) return

        setSubmittingRequest(true)
        try {
            const { error } = await supabase
                .from('ride_requests')
                .insert({
                    ride_id: requestingRideId,
                    passenger_id: currentUser.id,
                    contact_number: passengerContact
                })

            if (error) {
                if (error.code === '23505') toast.error('You already requested to join this ride.')
                else throw error
            } else {
                toast.success('Request sent successfully! You can track it in My Joined Rides.')
                setRequestingRideId(null)
                setPassengerContact('')
                queryClient.invalidateQueries({ queryKey: ['rides'] })
            }
        } catch (error: any) {
            toast.error(error.message)
        } finally {
            setSubmittingRequest(false)
        }
    }

    const respondToRequest = async (requestId: string, approve: boolean) => {
        try {
            const rpcName = approve ? 'rpc_accept_ride_request' : 'rpc_reject_ride_request'
            const { data, error } = await supabase.rpc(rpcName, { p_request_id: requestId })

            if (error) throw error
            if (approve && data === false) {
                toast.error("Cannot accept: No available seats left.")
            } else {
                toast.success(approve ? "Request Accepted" : "Request Rejected")
                queryClient.invalidateQueries({ queryKey: ['myOfferedRides', currentUser?.id] })
            }
        } catch (err: any) {
            toast.error(err.message)
        }
    }

    const submitReview = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!rateModalTarget) return

        setSubmittingReview(true)
        try {
            const { error } = await supabase.rpc('rpc_submit_ride_review', {
                p_ride_id: rateModalTarget.rideId,
                p_reviewee_id: rateModalTarget.revieweeId,
                p_rating: rating,
                p_comment: reviewComment
            })

            if (error) {
                if (error.message.includes('already reviewed') || error.code === '23505') {
                    toast.error('You have already reviewed this user for this ride.')
                } else {
                    throw error
                }
            } else {
                toast.success('Review submitted successfully!')
                setRateModalTarget(null)
                setRating(5)
                setReviewComment('')
                queryClient.invalidateQueries({ queryKey: ['myOfferedRides', currentUser?.id] })
                queryClient.invalidateQueries({ queryKey: ['myJoinedRides', currentUser?.id] })
            }
        } catch (error: any) {
            toast.error(error.message)
        } finally {
            setSubmittingReview(false)
        }
    }

    const cancelRide = async (rideId: string) => {
        if (!currentUser) return;
        if (!await confirm("WARNING: Cancelling a ride will deduct 5 points from your Trust Score. Do you want to proceed?")) return;

        try {
            const { error } = await supabase.rpc('rpc_cancel_ride', {
                p_ride_id: rideId,
                p_driver_id: currentUser.id
            });

            if (error) throw error;
            toast.success("Ride has been cancelled.");
            queryClient.invalidateQueries({ queryKey: ['myOfferedRides', currentUser?.id] });
        } catch (err: any) {
            toast.error("Failed to cancel ride: " + err.message);
        }
    }

    const cancelRequest = async (requestId: string, currentStatus: string) => {
        if (!currentUser) return;

        let message = "Are you sure you want to cancel this request?";
        if (currentStatus === 'accepted') {
            message = "WARNING: Cancelling an accepted request will deduct 3 points from your Trust Score. Do you want to proceed?";
        }

        if (!await confirm(message)) return;

        try {
            const { error } = await supabase.rpc('rpc_cancel_ride_request', {
                p_request_id: requestId,
                p_passenger_id: currentUser.id
            });

            if (error) throw error;
            toast.success("Request cancelled.");
            queryClient.invalidateQueries({ queryKey: ['myJoinedRides', currentUser?.id] });
        } catch (err: any) {
            toast.error("Failed to cancel request: " + err.message);
        }
    }

    const deferredSearchQuery = useDeferredValue(searchQuery)

    const filteredRides = rides.filter(ride =>
        ride.start_location.toLowerCase().includes(deferredSearchQuery.toLowerCase()) ||
        ride.end_location.toLowerCase().includes(deferredSearchQuery.toLowerCase())
    )

    return (
        <div className="container animate-fade-in-up" style={{ padding: '2rem 1.5rem', flex: 1 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem', flexWrap: 'wrap', gap: '1rem' }}>
                <div>
                    <h1 className="text-gradient" style={{ fontSize: '2.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <Car /> Ride Sharing
                    </h1>
                    <p style={{ color: 'var(--text-secondary)' }}>Share rides, save money, and make friends.</p>
                </div>
                <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
                    <button className="btn-primary" onClick={() => setShowCreate(true)}>
                        <PlusCircle size={18} /> Offer a Ride
                    </button>
                </div>
            </div>

            {/* Tabs */}
            <div style={{ display: 'flex', gap: '1rem', marginBottom: '2rem', borderBottom: '1px solid var(--border-glass)' }}>
                {['find', 'offered', 'joined'].map((tab) => (
                    <button
                        key={tab}
                        onClick={() => setActiveTab(tab as any)}
                        style={{
                            background: 'none', border: 'none', padding: '1rem 0',
                            color: activeTab === tab ? 'var(--accent-primary)' : 'var(--text-secondary)',
                            borderBottom: activeTab === tab ? '2px solid var(--accent-primary)' : '2px solid transparent',
                            cursor: 'pointer', fontSize: '1rem', fontWeight: activeTab === tab ? 600 : 400,
                            textTransform: 'capitalize'
                        }}
                    >
                        {tab === 'find' ? 'Find Rides' : `My ${tab} Rides`}
                    </button>
                ))}
            </div>

            {/* Create Ride Modal Overlay */}
            {showCreate && createPortal(
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    backgroundColor: 'rgba(10, 10, 14, 0.85)', backdropFilter: 'blur(8px)',
                    zIndex: 100, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem'
                }}>
                    <div className="glass-panel animate-fade-in-up" style={{ padding: '2.5rem', width: '100%', maxWidth: '550px', maxHeight: '90vh', overflowY: 'auto', border: '1px solid var(--border-glass)', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.5)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
                            <h2 style={{ fontSize: '1.75rem', fontWeight: 700 }} className="text-gradient">Offer a Ride</h2>
                            <button onClick={() => setShowCreate(false)} style={{ background: 'rgba(255, 255, 255, 0.1)', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', width: '32px', height: '32px', borderRadius: '50%', transition: 'all 0.2s' }} className="hover-white"><X size={18} /></button>
                        </div>
                        <form onSubmit={handleCreateRide} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>Vehicle Type</label>
                                <select className="input-glass" value={vehicleType} onChange={e => setVehicleType(e.target.value)} required style={{ appearance: 'none' }}>
                                    <option value="Rickshaw" style={{ background: '#0A0A0E', color: 'var(--text-primary)' }}>Rickshaw (Max 2)</option>
                                    <option value="Bike" style={{ background: '#0A0A0E', color: 'var(--text-primary)' }}>Bike (Max 1)</option>
                                    <option value="Car/CNG" style={{ background: '#0A0A0E', color: 'var(--text-primary)' }}>Car / CNG (Max 3/4)</option>
                                </select>
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1.25rem' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>From (Pick up)</label>
                                    <input type="text" className="input-glass" placeholder="e.g. Merul Badda" value={startLocation} onChange={e => setStartLocation(e.target.value)} required />
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>To (Drop off)</label>
                                    <input type="text" className="input-glass" placeholder="e.g. Mohakhali" value={endLocation} onChange={e => setEndLocation(e.target.value)} required />
                                </div>
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>Departure Time</label>
                                <input type="datetime-local" className="input-glass" value={departureTime} onChange={e => setDepartureTime(e.target.value)} required />
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 150px), 1fr))', gap: '1.25rem' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>Seats Available</label>
                                    <input type="number" min="1" max="4" className="input-glass" value={totalSeats} onChange={e => setTotalSeats(parseInt(e.target.value))} required />
                                </div>
                                <div style={{ display: 'flex', flexDirection: 'column' }}>
                                    <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>Pricing Type</label>
                                    <select className="input-glass" value={pricingType} onChange={e => setPricingType(e.target.value)} required style={{ appearance: 'none', flex: 1 }}>
                                        <option value="exact" style={{ background: '#0A0A0E', color: 'var(--text-primary)' }}>Exact Amount</option>
                                        <option value="free" style={{ background: '#0A0A0E', color: 'var(--text-primary)' }}>Free</option>
                                        <option value="split" style={{ background: '#0A0A0E', color: 'var(--text-primary)' }}>Split Cost</option>
                                    </select>
                                </div>
                                {pricingType === 'exact' && (
                                    <div>
                                        <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>Price / Seat (TK)</label>
                                        <input type="number" min="0" className="input-glass" value={pricePerSeat} onChange={e => setPricePerSeat(parseInt(e.target.value))} required />
                                    </div>
                                )}
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.4rem', fontSize: '0.85rem', fontWeight: 500, color: 'var(--text-secondary)' }}>Your Contact Number</label>
                                <input type="tel" className="input-glass" placeholder="e.g. 01XXXXXXXXX" value={driverContact} onChange={e => setDriverContact(e.target.value)} required />
                            </div>
                            <button type="submit" className="btn-primary hover-lift" style={{ marginTop: '1.5rem', width: '100%', padding: '0.8rem', fontSize: '1rem', fontWeight: 600, background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', border: 'none', boxShadow: '0 4px 15px rgba(109, 40, 217, 0.4)' }} disabled={creating}>
                                {creating ? 'Offering Ride...' : 'Offer Ride'}
                            </button>
                        </form>
                    </div>
                </div>,
                document.body
            )}

            {loading ? (
                <div style={{ textAlign: 'center', padding: '3rem', color: 'var(--text-secondary)' }}>Loading rides...</div>
            ) : (
                <>
                    {/* TAB: FIND RIDES */}
                    {activeTab === 'find' && (
                        <>
                            <div style={{ marginBottom: '2rem' }}>
                                <input
                                    type="text"
                                    placeholder="Search by location (e.g. 'Badda', 'Dhanmondi')..."
                                    className="input-glass"
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    style={{ width: '100%', maxWidth: '600px' }}
                                />
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 300px), 1fr))', gap: '1.5rem' }}>
                                {filteredRides.map(ride => (
                                    <div key={ride.id} className="glass-panel hover-lift" style={{ padding: 0, display: 'flex', flexDirection: 'column', overflow: 'hidden', border: '1px solid var(--border-glass)' }}>
                                        {/* Card Header (Gradient background) */}
                                        <div style={{ padding: '1.25rem 1.5rem', background: 'linear-gradient(to right, rgba(109, 40, 217, 0.1), rgba(236, 72, 153, 0.05))', borderBottom: '1px solid var(--border-glass)', display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                                            <div>
                                                <span style={{ background: 'var(--accent-glow)', color: 'var(--accent-primary)', padding: '0.2rem 0.6rem', borderRadius: '4px', fontSize: '0.75rem', fontWeight: 700, display: 'inline-block', marginBottom: '0.75rem', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                    {ride.vehicle_type}
                                                </span>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                                                    {ride.profiles?.avatar_url ? (
                                                        <img loading="lazy" src={ride.profiles.avatar_url} alt="" style={{ width: '40px', height: '40px', borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--accent-primary)' }} />
                                                    ) : (
                                                        <div style={{ width: '40px', height: '40px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '1rem', fontWeight: 700, color: 'var(--text-primary)', border: '2px solid var(--accent-primary)', flexShrink: 0 }}>
                                                            {(ride.profiles?.full_name || '?')[0]}
                                                        </div>
                                                    )}
                                                    <div>
                                                        <h3 style={{ fontSize: '1.1rem', margin: 0, fontWeight: 600, color: 'var(--text-primary)' }}>{ride.profiles?.full_name || 'Anonymous User'}</h3>
                                                        {(ride.profiles?.total_reviews || 0) > 0 && (
                                                            <div style={{ display: 'flex', alignItems: 'center', fontSize: '0.8rem', color: '#f1c40f', marginTop: '0.2rem' }}>
                                                                <Star size={12} fill="#f1c40f" style={{ marginRight: '4px' }} /> {ride.profiles?.trust_score}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            <div style={{ textAlign: 'right', display: 'flex', flexDirection: 'column', alignItems: 'flex-end', background: 'rgba(0,0,0,0.3)', padding: '0.5rem 0.75rem', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }}>
                                                {ride.pricing_type === 'free' ? (
                                                    <span style={{ fontSize: '1.1rem', fontWeight: 800, color: '#2ecc71', background: 'rgba(46, 204, 113, 0.1)', padding: '0.2rem 0.6rem', borderRadius: '4px' }}>Free</span>
                                                ) : ride.pricing_type === 'split' ? (
                                                    <span style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--accent-primary)', background: 'rgba(109, 40, 217, 0.1)', padding: '0.2rem 0.6rem', borderRadius: '4px', textAlign: 'center' }}>Split<br />Cost</span>
                                                ) : (
                                                    <div style={{ fontSize: '1.25rem', fontWeight: 800, color: 'var(--accent-secondary)' }}>
                                                        ৳{ride.price_per_seat}
                                                    </div>
                                                )}
                                                {ride.pricing_type === 'exact' && (
                                                    <div style={{ fontSize: '0.7rem', color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: '0.5px', marginTop: '0.2rem' }}>per seat</div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Card Body (Details) */}
                                        <div style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem', flex: 1 }}>
                                            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', color: 'var(--text-secondary)' }}>
                                                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '0.75rem' }}><MapPin size={18} color="var(--accent-secondary)" style={{ marginTop: '2px', flexShrink: 0 }} /> <div><span style={{ fontSize: '0.8rem', textTransform: 'uppercase', color: 'var(--text-muted)', display: 'block', marginBottom: '2px' }}>Pick Up</span> <strong style={{ color: 'var(--text-primary)' }}>{ride.start_location}</strong></div></div>
                                                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '0.75rem' }}><MapPin size={18} color="var(--accent-primary)" style={{ marginTop: '2px', flexShrink: 0 }} /> <div><span style={{ fontSize: '0.8rem', textTransform: 'uppercase', color: 'var(--text-muted)', display: 'block', marginBottom: '2px' }}>Drop Off</span> <strong style={{ color: 'var(--text-primary)' }}>{ride.end_location}</strong></div></div>
                                                <div style={{ height: '1px', background: 'var(--border-glass)', margin: '0.25rem 0' }}></div>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}><Clock size={16} color="var(--text-secondary)" /> <span style={{ color: 'var(--text-primary)' }}>{new Date(ride.departure_time).toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}</span></div>
                                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}><Users size={16} color="var(--text-secondary)" /> <span style={{ color: 'var(--text-primary)' }}><strong style={{ color: 'var(--text-primary)' }}>{ride.available_seats}</strong> of {ride.total_seats} seats remaining</span></div>
                                            </div>

                                            {ride.driver_id !== currentUser?.id && (
                                                <button onClick={() => requestRide(ride.id)} className="btn-primary" style={{ marginTop: 'auto', background: 'rgba(255,255,255,0.05)', color: 'var(--text-primary)', border: '1px solid rgba(255,255,255,0.1)', padding: '0.75rem', width: '100%', fontWeight: 600, transition: 'all 0.2s' }} onMouseOver={e => e.currentTarget.style.background = 'var(--accent-primary)'} onMouseOut={e => e.currentTarget.style.background = 'rgba(255,255,255,0.05)'}>
                                                    Request to Join
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                {filteredRides.length === 0 && (
                                    <div style={{ gridColumn: '1 / -1', textAlign: 'center', padding: '3rem', color: 'var(--text-secondary)', border: '1px dashed var(--border-glass)', borderRadius: 'var(--radius-lg)' }}>
                                        No open rides found matching that location.
                                    </div>
                                )}
                                <div ref={findRef} style={{ gridColumn: '1 / -1', textAlign: 'center', padding: '1rem', color: 'var(--text-secondary)' }}>
                                    {isFetchingNextRides ? 'Loading more...' : hasNextRides ? 'Load More' : rides.length > 0 ? 'End of list.' : ''}
                                </div>
                            </div>
                        </>
                    )}

                    {/* TAB: MY OFFERED RIDES */}
                    {activeTab === 'offered' && (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
                            {myOfferedRides.map(ride => (
                                <div key={ride.id} className="glass-panel hover-lift" style={{ padding: 0, overflow: 'hidden', border: '1px solid var(--border-glass)' }}>
                                    {/* Card Header */}
                                    <div style={{ padding: '1.25rem 1.5rem', background: 'linear-gradient(to right, rgba(109, 40, 217, 0.1), rgba(236, 72, 153, 0.05))', borderBottom: '1px solid var(--border-glass)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <div>
                                            <h3 style={{ fontSize: '1.25rem', marginBottom: '0.25rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--text-primary)' }}>
                                                {ride.start_location} <span style={{ color: 'var(--accent-primary)' }}>➔</span> {ride.end_location}
                                            </h3>
                                            <span style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>
                                                {new Date(ride.departure_time).toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })} • <strong style={{ color: 'var(--text-primary)' }}>{ride.available_seats}</strong> seats left
                                            </span>
                                        </div>
                                        <div style={{ textAlign: 'right', display: 'flex', flexDirection: 'column', gap: '0.5rem', alignItems: 'flex-end' }}>
                                            <span style={{ padding: '0.4rem 0.8rem', borderRadius: 'var(--radius-full)', fontSize: '0.75rem', background: ride.status === 'open' ? 'rgba(46, 204, 113, 0.15)' : 'rgba(255, 255, 255, 0.05)', color: ride.status === 'open' ? '#2ecc71' : 'var(--text-secondary)', fontWeight: 800, textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                {ride.status}
                                            </span>
                                            {ride.status === 'open' && (
                                                <button onClick={() => cancelRide(ride.id)} style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', border: '1px solid rgba(239, 68, 68, 0.2)', padding: '0.3rem 0.6rem', borderRadius: '4px', fontSize: '0.75rem', cursor: 'pointer', transition: 'all 0.2s', fontWeight: 600 }} className="hover-lift">
                                                    Cancel Ride
                                                </button>
                                            )}
                                        </div>
                                    </div>

                                    {/* Card Body (Requests) */}
                                    <div style={{ padding: '1.5rem' }}>

                                        <h4 style={{ fontSize: '1rem', color: 'var(--text-secondary)', marginBottom: '1rem' }}>Ride Partner Requests ({ride.ride_requests?.length || 0})</h4>

                                        {ride.ride_requests?.length > 0 ? (
                                            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                                                {ride.ride_requests.map((req: any) => (
                                                    <div key={req.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'rgba(255,255,255,0.03)', padding: '0.75rem 1rem', borderRadius: '8px' }}>
                                                        <div>
                                                            <div style={{ fontWeight: 600, display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                                                {req.profiles?.avatar_url ? (
                                                                    <img loading="lazy" src={req.profiles.avatar_url} alt="" style={{ width: '22px', height: '22px', borderRadius: '50%', objectFit: 'cover' }} />
                                                                ) : (
                                                                    <span style={{ width: '22px', height: '22px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.6rem', fontWeight: 700, color: 'white', flexShrink: 0 }}>
                                                                        {(req.profiles?.full_name || '?')[0]}
                                                                    </span>
                                                                )}
                                                                {req.profiles?.full_name}
                                                                {(req.profiles?.total_reviews || 0) > 0 && (
                                                                    <span style={{ display: 'flex', alignItems: 'center', fontSize: '0.8rem', background: 'rgba(241, 196, 15, 0.15)', color: '#f1c40f', padding: '0.1rem 0.3rem', borderRadius: '4px' }}>
                                                                        <Star size={10} fill="#f1c40f" style={{ marginRight: '2px' }} /> {req.profiles?.trust_score}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            {req.status === 'accepted' && <div style={{ fontSize: '0.85rem', color: 'var(--accent-secondary)', marginTop: '0.2rem' }}>Contact: {req.contact_number}</div>}
                                                        </div>

                                                        {req.status === 'pending' ? (
                                                            <div style={{ display: 'flex', gap: '0.5rem' }}>
                                                                <button onClick={() => respondToRequest(req.id, true)} style={{ background: 'rgba(46, 204, 113, 0.2)', color: '#2ecc71', border: 'none', padding: '0.4rem 0.8rem', borderRadius: '4px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '0.2rem' }}><Check size={14} /> Accept</button>
                                                                <button onClick={() => respondToRequest(req.id, false)} style={{ background: 'rgba(231, 76, 60, 0.2)', color: '#e74c3c', border: 'none', padding: '0.4rem 0.8rem', borderRadius: '4px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '0.2rem' }}><X size={14} /> Reject</button>
                                                            </div>
                                                        ) : req.status === 'accepted' ? (
                                                            <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                                                                <span style={{ fontSize: '0.85rem', textTransform: 'uppercase', fontWeight: 'bold', color: '#2ecc71' }}>
                                                                    {req.status}
                                                                </span>
                                                                <button onClick={() => setRateModalTarget({ rideId: ride.id, revieweeId: req.passenger_id, revieweeName: req.profiles?.full_name })} className="btn-primary" style={{ padding: '0.3rem 0.6rem', fontSize: '0.8rem' }}>
                                                                    Rate Partner
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <span style={{ fontSize: '0.85rem', textTransform: 'uppercase', fontWeight: 'bold', color: 'var(--text-secondary)' }}>
                                                                {req.status}
                                                            </span>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div style={{ fontSize: '0.9rem', color: 'var(--text-muted)', background: 'rgba(255,255,255,0.02)', padding: '1rem', borderRadius: '8px', textAlign: 'center', border: '1px dashed var(--border-glass)' }}>No requests yet.</div>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {myOfferedRides.length === 0 && <div style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-secondary)' }}>You haven't offered any rides yet.</div>}
                            <div ref={offeredRef} style={{ textAlign: 'center', padding: '1rem', color: 'var(--text-secondary)' }}>
                                {isFetchingNextOffered ? 'Loading more...' : hasNextOffered ? 'Load More' : ''}
                            </div>
                        </div>
                    )}

                    {/* TAB: JOINED RIDES */}
                    {activeTab === 'joined' && (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
                            {myJoinedRides.map(req => (
                                <div key={req.id} className="glass-panel hover-lift" style={{ padding: 0, overflow: 'hidden', border: '1px solid var(--border-glass)' }}>
                                    {/* Card Header */}
                                    <div style={{ padding: '1.25rem 1.5rem', background: 'linear-gradient(to right, rgba(109, 40, 217, 0.1), rgba(236, 72, 153, 0.05))', borderBottom: '1px solid var(--border-glass)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                        <div>
                                            <h3 style={{ fontSize: '1.25rem', marginBottom: '0.25rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--text-primary)' }}>
                                                {req.rides?.start_location} <span style={{ color: 'var(--accent-primary)' }}>➔</span> {req.rides?.end_location}
                                            </h3>
                                            <div style={{ fontSize: '0.85rem', color: 'var(--text-secondary)', display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
                                                <Clock size={14} /> {new Date(req.rides?.departure_time).toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}
                                            </div>
                                        </div>
                                        <div style={{ textAlign: 'right', display: 'flex', flexDirection: 'column', gap: '0.5rem', alignItems: 'flex-end' }}>
                                            <span style={{ padding: '0.4rem 0.8rem', borderRadius: 'var(--radius-full)', fontSize: '0.75rem', background: req.status === 'accepted' ? 'rgba(46, 204, 113, 0.15)' : req.status === 'pending' ? 'rgba(241, 196, 15, 0.15)' : req.status.includes('cancelled') ? 'rgba(255, 255, 255, 0.05)' : 'rgba(231, 76, 60, 0.15)', color: req.status === 'accepted' ? '#2ecc71' : req.status === 'pending' ? '#f1c40f' : req.status.includes('cancelled') ? 'var(--text-secondary)' : '#e74c3c', fontWeight: 800, textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                                {req.status === 'cancelled_by_passenger' ? 'Cancelled (You)' : req.status === 'cancelled_by_driver' ? 'Cancelled (Driver)' : req.status}
                                            </span>
                                            {(req.status === 'pending' || req.status === 'accepted') && (
                                                <button onClick={() => cancelRequest(req.id, req.status)} style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', border: '1px solid rgba(239, 68, 68, 0.2)', padding: '0.3rem 0.6rem', borderRadius: '4px', fontSize: '0.75rem', cursor: 'pointer', transition: 'all 0.2s', fontWeight: 600 }} className="hover-lift">
                                                    Cancel Request
                                                </button>
                                            )}
                                        </div>
                                    </div>

                                    {/* Card Body */}
                                    <div style={{ padding: '1.5rem' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1rem', background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: '8px' }}>
                                            {req.rides?.profiles?.avatar_url ? (
                                                <img loading="lazy" src={req.rides.profiles.avatar_url} alt="" style={{ width: '36px', height: '36px', borderRadius: '50%', objectFit: 'cover', border: '2px solid var(--accent-secondary)' }} />
                                            ) : (
                                                <div style={{ width: '36px', height: '36px', borderRadius: '50%', background: 'linear-gradient(135deg, var(--accent-primary), var(--accent-secondary))', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '1rem', fontWeight: 700, color: 'white', flexShrink: 0 }}>
                                                    {(req.rides?.profiles?.full_name || '?')[0]}
                                                </div>
                                            )}
                                            <div>
                                                <span style={{ fontSize: '0.75rem', textTransform: 'uppercase', color: 'var(--text-muted)', display: 'block', letterSpacing: '0.5px' }}>Ride Partner</span>
                                                <div style={{ fontWeight: 600, color: 'var(--text-primary)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                                    {req.rides?.profiles?.full_name}
                                                    {(req.rides?.profiles?.total_reviews || 0) > 0 && (
                                                        <span style={{ display: 'flex', alignItems: 'center', fontSize: '0.8rem', color: '#f1c40f' }}>
                                                            <Star size={12} fill="#f1c40f" style={{ marginRight: '2px' }} /> {req.rides?.profiles?.trust_score}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {req.status === 'accepted' && (
                                            <div style={{ background: 'linear-gradient(to right, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05))', border: '1px solid rgba(46, 204, 113, 0.2)', padding: '1rem', borderRadius: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                                <div>
                                                    <strong style={{ color: '#2ecc71', display: 'block', fontSize: '0.8rem', textTransform: 'uppercase', letterSpacing: '0.5px', marginBottom: '2px' }}>Partner Contact</strong>
                                                    <span style={{ fontSize: '1.1rem', fontWeight: 600, color: 'var(--text-primary)' }}>{req.rides?.contact_number}</span>
                                                </div>
                                                <button onClick={() => setRateModalTarget({ rideId: req.ride_id, revieweeId: req.rides?.driver_id, revieweeName: req.rides?.profiles?.full_name })} className="btn-primary" style={{ padding: '0.5rem 1rem', fontSize: '0.85rem', background: 'var(--accent-primary)' }}>
                                                    Rate Partner
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {myJoinedRides.length === 0 && <div style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-secondary)' }}>You haven't requested to join any rides yet.</div>}
                            <div ref={joinedRef} style={{ textAlign: 'center', padding: '1rem', color: 'var(--text-secondary)' }}>
                                {isFetchingNextJoined ? 'Loading more...' : hasNextJoined ? 'Load More' : ''}
                            </div>
                        </div>
                    )}
                </>
            )}

            {/* Request Ride Modal Overlay */}
            {requestingRideId && createPortal(
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    backgroundColor: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)',
                    zIndex: 100, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem'
                }}>
                    <div className="glass-panel animate-fade-in-up" style={{ padding: '2rem', width: '100%', maxWidth: '400px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                            <h2 style={{ fontSize: '1.5rem' }}>Request to Join</h2>
                            <button onClick={() => setRequestingRideId(null)} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', fontSize: '1.5rem' }}>&times;</button>
                        </div>
                        <p style={{ color: 'var(--text-secondary)', marginBottom: '1.5rem', fontSize: '0.95rem' }}>
                            Provide your contact number so the driver can reach out when they accept your request.
                        </p>
                        <form onSubmit={submitRequest} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Contact Number</label>
                                <input type="tel" className="input-glass" placeholder="e.g. 01XXXXXXXXX" value={passengerContact} onChange={e => setPassengerContact(e.target.value)} required />
                            </div>
                            <button type="submit" className="btn-primary" style={{ marginTop: '0.5rem', width: '100%' }} disabled={submittingRequest}>
                                {submittingRequest ? 'Sending Request...' : 'Send Request'}
                            </button>
                        </form>
                    </div>
                </div>,
                document.body
            )}

            {/* Rate User Modal Overlay */}
            {rateModalTarget && createPortal(
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    backgroundColor: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)',
                    zIndex: 100, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '1rem'
                }}>
                    <div className="glass-panel animate-fade-in-up" style={{ padding: '2rem', width: '100%', maxWidth: '400px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                            <h2 style={{ fontSize: '1.5rem' }}>Rate {rateModalTarget.revieweeName}</h2>
                            <button onClick={() => setRateModalTarget(null)} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer', fontSize: '1.5rem' }}>&times;</button>
                        </div>
                        <form onSubmit={submitReview} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Rating (1-5 Stars)</label>
                                <div style={{ display: 'flex', gap: '0.5rem', fontSize: '1.5rem' }}>
                                    {[1, 2, 3, 4, 5].map(star => (
                                        <Star key={star} size={28} fill={star <= rating ? "#f1c40f" : "transparent"} color={star <= rating ? "#f1c40f" : "var(--border-glass)"} style={{ cursor: 'pointer' }} onClick={() => setRating(star)} />
                                    ))}
                                </div>
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Review Comment (Optional)</label>
                                <textarea className="input-glass" rows={3} placeholder="How was the ride?" value={reviewComment} onChange={e => setReviewComment(e.target.value)} />
                            </div>
                            <button type="submit" className="btn-primary" style={{ marginTop: '0.5rem', width: '100%' }} disabled={submittingReview}>
                                {submittingReview ? 'Submitting...' : 'Submit Review'}
                            </button>
                        </form>
                    </div>
                </div>,
                document.body
            )}
        </div>
    )
}

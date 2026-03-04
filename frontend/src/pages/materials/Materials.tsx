import { useState, useEffect } from 'react'
import { Layers, Upload, Download, Search, Star, ExternalLink, Link as LinkIcon, AlertTriangle, Clock, X, MessageSquarePlus, Edit2, Trash2 } from 'lucide-react'
import { supabase } from '../../lib/supabase'
import { useAuth } from '../../contexts/AuthContext'
import { toast } from 'react-hot-toast'
import { useConfirm } from '../../contexts/ConfirmContext'

// Star Rating Component
const StarRating = ({ materialId, onRate }: { materialId: string; onRate: () => void }) => {
    const [userRating, setUserRating] = useState<number | null>(null)
    const [avgRating, setAvgRating] = useState<number>(0)
    const [totalRatings, setTotalRatings] = useState(0)
    const [hoverStar, setHoverStar] = useState<number>(0)
    const [canRate, setCanRate] = useState(false)
    const [submitting, setSubmitting] = useState(false)

    useEffect(() => {
        fetchRatings()
        checkCanRate()
    }, [materialId])

    const fetchRatings = async () => {
        const { data } = await supabase
            .from('material_ratings')
            .select('rating')
            .eq('material_id', materialId)

        if (data && data.length > 0) {
            const avg = data.reduce((sum: number, r: any) => sum + r.rating, 0) / data.length
            setAvgRating(Math.round(avg * 10) / 10)
            setTotalRatings(data.length)
        }

        const { data: { user } } = await supabase.auth.getUser()
        if (user) {
            const { data: myRating } = await supabase
                .from('material_ratings')
                .select('rating')
                .eq('material_id', materialId)
                .eq('user_id', user.id)
                .single()
            if (myRating) setUserRating(myRating.rating)
        }
    }

    const checkCanRate = async () => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return

        const { data } = await supabase
            .from('material_downloads')
            .select('id')
            .eq('material_id', materialId)
            .eq('user_id', user.id)
            .limit(1)

        if (data && data.length > 0) setCanRate(true)
    }

    const submitRating = async (stars: number) => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user || !canRate) return

        setSubmitting(true)
        try {
            const { error } = await supabase
                .from('material_ratings')
                .upsert({
                    material_id: materialId,
                    user_id: user.id,
                    rating: stars
                }, { onConflict: 'material_id,user_id' })

            if (error) throw error
            setUserRating(stars)
            fetchRatings()
            onRate()
        } catch (err: any) {
            toast.error(err.message)
        } finally {
            setSubmitting(false)
        }
    }

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', flexWrap: 'wrap' }}>
            <div style={{ display: 'flex', gap: '2px' }}>
                {[1, 2, 3, 4, 5].map(star => (
                    <button
                        key={star}
                        disabled={!canRate || submitting}
                        onClick={(e) => { e.stopPropagation(); submitRating(star) }}
                        onMouseEnter={() => canRate && setHoverStar(star)}
                        onMouseLeave={() => setHoverStar(0)}
                        style={{
                            background: 'none', border: 'none', padding: '0 1px',
                            cursor: canRate ? 'pointer' : 'default',
                            opacity: canRate ? 1 : 0.5,
                            transition: 'transform 0.15s',
                            transform: hoverStar === star ? 'scale(1.2)' : 'scale(1)'
                        }}
                    >
                        <Star
                            size={16}
                            fill={(hoverStar || userRating || 0) >= star ? '#f59e0b' : 'transparent'}
                            color={(hoverStar || userRating || 0) >= star ? '#f59e0b' : 'var(--text-muted)'}
                        />
                    </button>
                ))}
            </div>
            <span style={{ fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                {avgRating > 0 ? `${avgRating} (${totalRatings})` : 'No ratings'}
            </span>
            {!canRate && (
                <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)', fontStyle: 'italic' }}>
                    Download to rate
                </span>
            )}
        </div>
    )
}

export const Materials = () => {
    const [materials, setMaterials] = useState<any[]>([])
    const [requests, setRequests] = useState<any[]>([])
    const [loading, setLoading] = useState(true)
    const [balance, setBalance] = useState(0)
    const [downloadedIds, setDownloadedIds] = useState<Set<string>>(new Set())

    // Upload Form State
    const [showUpload, setShowUpload] = useState(false)
    const [isGDrive, setIsGDrive] = useState(false)
    const [title, setTitle] = useState('')
    const [courseCode, setCourseCode] = useState('')
    const [semester, setSemester] = useState('')
    const [file, setFile] = useState<File | null>(null)
    const [driveLink, setDriveLink] = useState('')
    const [uploading, setUploading] = useState(false)
    const [searchQuery, setSearchQuery] = useState('')
    const [fulfillingRequestId, setFulfillingRequestId] = useState<string | null>(null)

    // Request Form State
    const [showRequestForm, setShowRequestForm] = useState(false)
    const [requestCourseCode, setRequestCourseCode] = useState('')
    const [requestDescription, setRequestDescription] = useState('')
    const [requesting, setRequesting] = useState(false)

    // Edit Request State
    const [editingRequestId, setEditingRequestId] = useState<string | null>(null)
    const [editRequestCourseCode, setEditRequestCourseCode] = useState('')
    const [editRequestDescription, setEditRequestDescription] = useState('')

    // Modal State
    const [selectedMaterial, setSelectedMaterial] = useState<any | null>(null)
    const [reportingId, setReportingId] = useState<string | null>(null)
    const confirm = useConfirm()

    useEffect(() => {
        fetchMaterials()
        fetchRequests()
        fetchBalance()
        fetchDownloaded()
    }, [])

    const fetchDownloaded = async () => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return
        const { data } = await supabase
            .from('material_downloads')
            .select('material_id')
            .eq('user_id', user.id)
        if (data) setDownloadedIds(new Set(data.map((d: any) => d.material_id)))
    }

    const fetchMaterials = async () => {
        try {
            const { data, error } = await supabase
                .from('materials')
                .select('*, profiles:uploader_id(full_name)')
                .order('created_at', { ascending: false })

            if (error) throw error
            if (data) setMaterials(data)
        } catch (err) {
            console.error("Error fetching materials:", err)
        } finally {
            setLoading(false)
        }
    }

    const fetchRequests = async () => {
        try {
            const { data, error } = await supabase
                .from('material_requests')
                .select('*, profiles:requester_id(full_name)')
                .eq('status', 'open')
                .order('created_at', { ascending: false })

            if (error) throw error
            if (data) setRequests(data)
        } catch (err) {
            console.error("Error fetching requests:", err)
        }
    }

    const { user } = useAuth()

    const fetchBalance = async () => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return

        const { data } = await supabase
            .from('profiles')
            .select('credit_balance')
            .eq('id', user.id)
            .single()

        if (data) setBalance(data.credit_balance)
    }

    const handleUpload = async (e: React.FormEvent) => {
        e.preventDefault()
        if (!isGDrive && !file) return
        if (isGDrive && !driveLink) return

        const { data: { user } } = await supabase.auth.getUser()
        if (!user) {
            toast.error("Please login first")
            return
        }

        setUploading(true)

        try {
            let fileUrl = ''
            let fileType = ''

            if (isGDrive) {
                fileUrl = driveLink
                fileType = 'gdrive'
            } else {
                const fileExt = file!.name.split('.').pop()
                const fileName = `${Math.random().toString(36).substring(2)}_${Date.now()}.${fileExt}`
                const filePath = `${user.id}/${fileName}`

                const { error: uploadError } = await supabase.storage
                    .from('study_materials')
                    .upload(filePath, file!)

                if (uploadError) throw uploadError

                const { data: { publicUrl } } = supabase.storage
                    .from('study_materials')
                    .getPublicUrl(filePath)

                fileUrl = publicUrl
                fileType = file!.type || fileExt || 'unknown'
            }

            const { error: rpcError } = await supabase.rpc('rpc_upload_material', {
                p_title: title,
                p_course_code: courseCode,
                p_semester: semester,
                p_file_url: fileUrl,
                p_file_type: fileType,
                p_user_id: user.id,
                p_is_gdrive: isGDrive,
                p_request_id: fulfillingRequestId
            })

            if (rpcError) throw rpcError

            toast.success("Material uploaded successfully! +2 Downloads awarded.")
            setShowUpload(false)
            setFulfillingRequestId(null)
            setTitle('')
            setCourseCode('')
            setSemester('')
            setFile(null)
            setDriveLink('')
            setIsGDrive(false)
            fetchMaterials()
            fetchRequests()
            fetchBalance()
        } catch (err: any) {
            console.error(err)
            toast.error("Upload failed: " + err.message)
        } finally {
            setUploading(false)
        }
    }

    const handlePurchase = async (mat: any) => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) { toast.error("Please login first to access materials"); return }

        try {
            const { data: success, error } = await supabase.rpc('rpc_download_material', {
                p_material_id: mat.id,
                p_user_id: user.id
            })
            if (error) throw error
            if (!success) {
                toast.error("Insufficient Available Downloads! Please upload study materials to get more downloads.");
                return
            }

            await supabase.from('material_downloads').insert({
                material_id: mat.id,
                user_id: user.id
            })

            setDownloadedIds(prev => new Set(prev).add(mat.id))
            fetchBalance()
            fetchMaterials()

            // If they purchased from the modal, update the selected material object
            setSelectedMaterial({ ...mat, downloads_count: mat.downloads_count + 1, last_accessed_at: new Date().toISOString() })

            if (mat.is_gdrive) {
                window.open(mat.file_url, '_blank')
            }
        } catch (err: any) {
            console.error(err)
            toast.error("Purchase failed: " + err.message)
        }
    }

    const handleOpen = (mat: any) => {
        window.open(mat.file_url, '_blank')
    }

    const handleRequestMaterial = async (e: React.FormEvent) => {
        e.preventDefault()
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) { toast.error("Please login first to make a request"); return }

        setRequesting(true)
        try {
            const { error } = await supabase.from('material_requests').insert({
                requester_id: user.id,
                course_code: requestCourseCode,
                description: requestDescription
            })
            if (error) throw error

            toast.success("Material request submitted successfully!")
            setShowRequestForm(false)
            setRequestCourseCode('')
            setRequestDescription('')
            fetchRequests()
        } catch (err: any) {
            console.error(err)
            toast.error("Failed to submit request: " + err.message)
        } finally {
            setRequesting(false)
        }
    }

    const startFulfillingRequest = (request: any) => {
        setFulfillingRequestId(request.id)
        setCourseCode(request.course_code)
        setTitle(`Requested: ${request.description.substring(0, 30)}...`)
        setShowUpload(true)
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    const handleDeleteRequest = async (id: string) => {
        if (!await confirm("Are you sure you want to delete this request?")) return;
        try {
            const { error } = await supabase.from('material_requests').delete().eq('id', id);
            if (error) throw error;
            fetchRequests();
            toast.success("Request deleted successfully!");
        } catch (err: any) {
            toast.error("Failed to delete request: " + err.message);
        }
    }

    const handleUpdateRequest = async (e: React.FormEvent, id: string) => {
        e.preventDefault();
        try {
            const { error } = await supabase.from('material_requests').update({
                course_code: editRequestCourseCode,
                description: editRequestDescription
            }).eq('id', id);
            if (error) throw error;
            setEditingRequestId(null);
            fetchRequests();
            toast.success("Request updated successfully!");
        } catch (err: any) {
            toast.error("Failed to update request: " + err.message);
        }
    }

    const handleReport = async (matId: string) => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) { toast.error("Please login first"); return }

        if (!await confirm("Report this link as not accessible? An admin will review it and refund your download if true.")) return;

        setReportingId(matId)
        try {
            const { error } = await supabase.from('material_reports').insert({
                material_id: matId,
                reporter_id: user.id
            })
            if (error) throw error
            toast.success("Report submitted successfully.")
        } catch (err: any) {
            console.error(err)
            toast.error("Report failed: " + err.message)
        } finally {
            setReportingId(null)
        }
    }

    const filteredMaterials = materials.filter(mat =>
        mat.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        mat.course_code.toLowerCase().includes(searchQuery.toLowerCase())
    )

    const gDriveLinks = filteredMaterials.filter(m => m.is_gdrive)
    const standardFiles = filteredMaterials.filter(m => !m.is_gdrive)

    const MaterialCard = ({ mat }: { mat: any }) => (
        <div
            className="glass-panel hover-lift"
            style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem', cursor: 'pointer' }}
            onClick={() => setSelectedMaterial(mat)}
        >
            <div>
                <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'center', flexWrap: 'wrap' }}>
                    <span style={{ background: mat.is_gdrive ? 'rgba(46, 204, 113, 0.2)' : 'var(--accent-glow)', color: mat.is_gdrive ? '#2ecc71' : 'var(--accent-primary)', padding: '0.4rem 0.8rem', borderRadius: '6px', fontSize: '1rem', fontWeight: 700, letterSpacing: '0.5px' }}>
                        {mat.course_code}
                    </span>
                    {mat.semester && (
                        <span style={{ background: 'rgba(255,255,255,0.05)', color: 'var(--text-secondary)', padding: '0.3rem 0.6rem', borderRadius: '6px', fontSize: '0.85rem' }}>
                            {mat.semester}
                        </span>
                    )}
                </div>
                <h3 style={{ marginTop: '1rem', fontSize: '1.25rem', lineHeight: 1.3, display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                    {mat.is_gdrive && <LinkIcon size={18} color="#2ecc71" />}
                    {mat.title}
                </h3>
                <p style={{ color: 'var(--text-secondary)', fontSize: '0.9rem', marginTop: '0.4rem' }}>
                    By {mat.profiles?.full_name || 'Anonymous Student'}
                </p>
            </div>

            <StarRating materialId={mat.id} onRate={fetchMaterials} />

            <div style={{ marginTop: 'auto', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderTop: '1px solid var(--border-glass)', paddingTop: '1rem' }}>
                <span style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>{mat.downloads_count} accesses</span>
                {downloadedIds.has(mat.id) ? (
                    <span style={{ color: '#10b981', fontSize: '0.85rem', fontWeight: 600 }}>Unlocked</span>
                ) : (
                    <span style={{ color: 'var(--accent-primary)', fontSize: '0.85rem', fontWeight: 600 }}>1 Download</span>
                )}
            </div>
        </div>
    )

    return (
        <>
            <div className="container animate-fade-in-up" style={{ padding: '2rem 1.5rem', flex: 1, position: 'relative' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem', flexWrap: 'wrap', gap: '1rem' }}>
                    <div>
                        <h1 className="text-gradient" style={{ fontSize: '2.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <Layers /> Study Materials
                        </h1>
                        <p style={{ color: 'var(--text-secondary)' }}>Upload to earn Available Downloads. Spend to access.</p>
                    </div>

                    <div style={{ display: 'flex', gap: '1rem', alignItems: 'center', flexWrap: 'wrap' }}>
                        <div className="glass-panel" style={{ padding: '0.5rem 1rem', display: 'flex', alignItems: 'center', gap: '0.5rem', borderRadius: 'var(--radius-full)' }}>
                            <span style={{ color: 'var(--accent-secondary)', fontWeight: 'bold' }}>{balance}</span> Available Downloads
                        </div>
                        <button className="btn-secondary" onClick={() => { setShowRequestForm(!showRequestForm); setShowUpload(false) }} style={{ border: '1px solid var(--accent-secondary)', color: 'white' }}>
                            <MessageSquarePlus size={18} /> Request Material
                        </button>
                        <button className="btn-primary" onClick={() => { setShowUpload(!showUpload); setShowRequestForm(false) }}>
                            <Upload size={18} /> Upload (+2)
                        </button>
                    </div>
                </div>

                {/* Request Form */}
                {showRequestForm && (
                    <div className="glass-panel animate-fade-in-up" style={{ padding: '2rem', marginBottom: '2rem', border: '1px solid var(--accent-secondary)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                            <h3 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}><MessageSquarePlus size={20} color="var(--accent-secondary)" /> Request a Material</h3>
                            <button onClick={() => setShowRequestForm(false)} style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}><X size={20} /></button>
                        </div>
                        <form onSubmit={handleRequestMaterial} style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 3fr', gap: '1.5rem', alignItems: 'start' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Course Code</label>
                                    <input type="text" required className="input-glass" placeholder="e.g. CSE110" value={requestCourseCode} onChange={e => setRequestCourseCode(e.target.value.toUpperCase())} disabled={requesting} />
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Description / Specifics</label>
                                    <input type="text" required className="input-glass" placeholder="e.g. Final exam past papers from Fall 2025" value={requestDescription} onChange={e => setRequestDescription(e.target.value)} disabled={requesting} />
                                </div>
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                                <button type="submit" className="btn-secondary" disabled={requesting} style={{ background: 'var(--accent-secondary)' }}>
                                    {requesting ? 'Submitting...' : 'Submit Request'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Upload Form */}
                {showUpload && (
                    <div className="glass-panel animate-fade-in-up" style={{ padding: '2rem', marginBottom: '2rem' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                            <h3>{fulfillingRequestId ? 'Fulfill Material Request' : 'Upload Material'}</h3>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                                {fulfillingRequestId && (
                                    <button onClick={() => { setFulfillingRequestId(null); setTitle(''); setCourseCode(''); }} style={{ background: 'none', border: 'none', color: '#ef4444', cursor: 'pointer', fontSize: '0.85rem' }}>
                                        Cancel Fulfillment
                                    </button>
                                )}
                                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                    <span style={{ fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Link to Google Drive?</span>
                                    <input type="checkbox" checked={isGDrive} onChange={e => setIsGDrive(e.target.checked)} style={{ width: '18px', height: '18px', cursor: 'pointer' }} />
                                </div>
                                <button onClick={() => { setShowUpload(false); setFulfillingRequestId(null); }} style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}><X size={20} /></button>
                            </div>
                        </div>
                        <form onSubmit={handleUpload} style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '1.5rem' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Title</label>
                                <input type="text" required className="input-glass" placeholder="e.g. Midterm Notes" value={title} onChange={e => setTitle(e.target.value)} disabled={uploading} />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Course Code</label>
                                <input type="text" required className="input-glass" placeholder="e.g. CSE110" value={courseCode} onChange={e => setCourseCode(e.target.value.toUpperCase())} disabled={uploading} />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>Semester</label>
                                <input type="text" required className="input-glass" placeholder="e.g. Spring 26" value={semester} onChange={e => setSemester(e.target.value)} disabled={uploading} />
                            </div>
                            <div style={{ gridColumn: '1 / -1' }}>
                                {isGDrive ? (
                                    <>
                                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>
                                            Google Drive Folder Link
                                        </label>
                                        <input
                                            type="url"
                                            required
                                            className="input-glass"
                                            placeholder="https://drive.google.com/..."
                                            value={driveLink}
                                            onChange={e => setDriveLink(e.target.value)}
                                            disabled={uploading}
                                        />
                                        <p style={{ marginTop: '0.5rem', fontSize: '0.85rem', color: '#f59e0b', display: 'flex', gap: '0.3rem', alignItems: 'center' }}>
                                            <AlertTriangle size={14} /> Ensure the link sharing is set to "Anyone with the link can view".
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <label style={{ display: 'block', marginBottom: '0.5rem', fontSize: '0.9rem', color: 'var(--text-secondary)' }}>File (PDF, DOCX, ZIP)</label>
                                        <input type="file" required className="input-glass" style={{ padding: '0.5rem' }} onChange={e => setFile(e.target.files?.[0] || null)} disabled={uploading} />
                                    </>
                                )}
                            </div>
                            <div style={{ gridColumn: '1 / -1', display: 'flex', justifyContent: 'flex-end' }}>
                                <button type="submit" className="btn-primary" disabled={uploading}>
                                    {uploading ? 'Uploading...' : 'Submit Upload'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {loading ? (
                    <div style={{ textAlign: 'center', padding: '3rem', color: 'var(--text-secondary)' }}>Loading materials...</div>
                ) : (
                    <>
                        {/* Open Requests Banner */}
                        {requests.length > 0 && (
                            <div style={{ marginBottom: '3rem' }}>
                                <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--accent-secondary)' }}>
                                    <MessageSquarePlus color="var(--accent-secondary)" /> Open Material Requests ({requests.length})
                                </h2>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: '1rem' }}>
                                    {requests.map(request => (
                                        <div key={request.id} className="glass-panel hover-lift" style={{ padding: '1.25rem', borderLeft: '4px solid var(--accent-secondary)', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                                            {editingRequestId === request.id ? (
                                                <form onSubmit={(e) => handleUpdateRequest(e, request.id)} style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                                                    <input type="text" className="input-glass" value={editRequestCourseCode} onChange={e => setEditRequestCourseCode(e.target.value.toUpperCase())} placeholder="Course Code" required />
                                                    <input type="text" className="input-glass" value={editRequestDescription} onChange={e => setEditRequestDescription(e.target.value)} placeholder="Description" required />
                                                    <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end' }}>
                                                        <button type="button" onClick={() => setEditingRequestId(null)} className="btn-secondary" style={{ padding: '0.3rem 0.6rem', fontSize: '0.8rem' }}>Cancel</button>
                                                        <button type="submit" className="btn-primary" style={{ padding: '0.3rem 0.6rem', fontSize: '0.8rem', background: 'var(--accent-secondary)' }}>Save</button>
                                                    </div>
                                                </form>
                                            ) : (
                                                <>
                                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                                                        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                                                            <span style={{ background: 'rgba(56, 189, 248, 0.1)', color: 'var(--accent-secondary)', padding: '0.2rem 0.6rem', borderRadius: '4px', fontSize: '0.9rem', fontWeight: 600 }}>
                                                                {request.course_code}
                                                            </span>
                                                            <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>{new Date(request.created_at).toLocaleDateString()}</span>
                                                        </div>
                                                        {user && user.id === request.requester_id && (
                                                            <div style={{ display: 'flex', gap: '0.5rem' }}>
                                                                <button onClick={() => { setEditingRequestId(request.id); setEditRequestCourseCode(request.course_code); setEditRequestDescription(request.description); }} style={{ background: 'none', border: 'none', color: 'var(--text-secondary)', cursor: 'pointer' }}><Edit2 size={16} /></button>
                                                                <button onClick={() => handleDeleteRequest(request.id)} style={{ background: 'none', border: 'none', color: '#ef4444', cursor: 'pointer' }}><Trash2 size={16} /></button>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <p style={{ color: 'var(--text-primary)', fontSize: '0.95rem' }}>"{request.description}"</p>
                                                    <div style={{ marginTop: 'auto', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderTop: '1px solid var(--border-glass)', paddingTop: '0.75rem' }}>
                                                        <span style={{ fontSize: '0.85rem', color: 'var(--text-secondary)' }}>Requested by {request.profiles?.full_name}</span>
                                                        {(!user || user.id !== request.requester_id) && (
                                                            <button onClick={() => startFulfillingRequest(request)} style={{ background: 'none', border: '1px solid var(--accent-secondary)', color: 'var(--accent-secondary)', padding: '0.4rem 0.8rem', borderRadius: 'var(--radius-full)', cursor: 'pointer', fontSize: '0.85rem', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
                                                                <Upload size={14} /> I have this!
                                                            </button>
                                                        )}
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div style={{ marginBottom: '2rem', display: 'flex', alignItems: 'center', background: 'var(--bg-glass)', border: '1px solid var(--border-glass)', borderRadius: 'var(--radius-md)', padding: '0.75rem 1.25rem' }}>
                            <Search size={22} color="var(--text-secondary)" style={{ marginRight: '0.75rem' }} />
                            <input
                                type="text"
                                placeholder="Search by course code or title..."
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                style={{ background: 'transparent', border: 'none', color: 'white', width: '100%', outline: 'none', fontSize: '1.05rem' }}
                            />
                        </div>

                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '2rem' }}>
                            {/* LEFT COLUMN: Google Drive Links */}
                            <div style={{ flex: '1 1 350px' }}>
                                <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                    <LinkIcon color="#2ecc71" /> Shared Drive Folders
                                </h2>
                                <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
                                    {gDriveLinks.length === 0 ? (
                                        <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-secondary)', border: '1px dashed var(--border-glass)', borderRadius: 'var(--radius-lg)' }}>
                                            No Google Drive links shared yet.
                                        </div>
                                    ) : (
                                        gDriveLinks.map(mat => <MaterialCard key={mat.id} mat={mat} />)
                                    )}
                                </div>
                            </div>

                            {/* RIGHT COLUMN: Standard Uploads */}
                            <div style={{ flex: '2 1 400px' }}>
                                <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                    <Layers color="var(--accent-primary)" /> Uploaded Files
                                </h2>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '1.5rem' }}>
                                    {standardFiles.length === 0 ? (
                                        <div style={{ gridColumn: '1/-1', padding: '2rem', textAlign: 'center', color: 'var(--text-secondary)', border: '1px dashed var(--border-glass)', borderRadius: 'var(--radius-lg)' }}>
                                            No uploaded files found.
                                        </div>
                                    ) : (
                                        standardFiles.map(mat => <MaterialCard key={mat.id} mat={mat} />)
                                    )}
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </div>

            {/* MATERIAL MODAL */}
            {selectedMaterial && (
                <div style={{
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
                    backgroundColor: 'rgba(0,0,0,0.6)', backdropFilter: 'blur(4px)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100,
                    padding: '1rem'
                }}>
                    <div className="glass-panel animate-fade-in-up" style={{ width: '100%', maxWidth: '500px', padding: '2rem', position: 'relative' }}>
                        <button onClick={() => setSelectedMaterial(null)} style={{ position: 'absolute', top: '1.5rem', right: '1.5rem', color: 'var(--text-secondary)', cursor: 'pointer' }}>
                            <X size={20} />
                        </button>

                        <div style={{ marginBottom: '1.5rem' }}>
                            <span style={{ background: selectedMaterial.is_gdrive ? 'rgba(46, 204, 113, 0.2)' : 'var(--accent-glow)', color: selectedMaterial.is_gdrive ? '#2ecc71' : 'var(--accent-primary)', padding: '0.4rem 0.8rem', borderRadius: '6px', fontSize: '1rem', fontWeight: 700 }}>
                                {selectedMaterial.course_code}
                            </span>
                            <h2 style={{ marginTop: '1rem', fontSize: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                                {selectedMaterial.is_gdrive && <LinkIcon size={20} color="#2ecc71" />}
                                {selectedMaterial.title}
                            </h2>
                            <p style={{ color: 'var(--text-secondary)', fontSize: '0.95rem', marginTop: '0.4rem' }}>
                                Uploaded by {selectedMaterial.profiles?.full_name || 'Anonymous'}
                            </p>
                        </div>

                        {selectedMaterial.is_gdrive && (
                            <div style={{ background: 'rgba(255,255,255,0.03)', padding: '1rem', borderRadius: 'var(--radius-md)', display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1.5rem' }}>
                                <Clock size={16} color="var(--text-secondary)" />
                                <div>
                                    <div style={{ fontSize: '0.8rem', color: 'var(--text-secondary)' }}>Last Accessed</div>
                                    <div style={{ fontSize: '0.95rem' }}>
                                        {selectedMaterial.last_accessed_at ? new Date(selectedMaterial.last_accessed_at).toLocaleString() : 'Never accessed'}
                                    </div>
                                </div>
                            </div>
                        )}

                        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '2rem' }}>
                            {downloadedIds.has(selectedMaterial.id) ? (
                                <>
                                    <button onClick={() => handleOpen(selectedMaterial)} className="btn-primary" style={{ width: '100%', justifyContent: 'center' }}>
                                        <ExternalLink size={18} /> Open Link
                                    </button>
                                    <button
                                        onClick={() => handleReport(selectedMaterial.id)}
                                        disabled={reportingId === selectedMaterial.id}
                                        style={{ width: '100%', padding: '0.75rem', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem', color: '#ef4444', background: 'rgba(239, 68, 68, 0.1)', border: '1px solid rgba(239, 68, 68, 0.2)', borderRadius: 'var(--radius-full)', fontWeight: 600, transition: 'all 0.2s', cursor: reportingId === selectedMaterial.id ? 'default' : 'pointer' }}
                                    >
                                        <AlertTriangle size={16} /> {reportingId === selectedMaterial.id ? 'Reporting...' : 'Report Not Accessible'}
                                    </button>
                                </>
                            ) : (
                                <button onClick={() => handlePurchase(selectedMaterial)} className="btn-secondary" style={{ width: '100%', justifyContent: 'center', border: '1px solid var(--accent-primary)', color: 'white' }}>
                                    <Download size={18} /> Access (-1 Download)
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </>
    )
}

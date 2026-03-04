import { Hono } from 'hono'
import { createAdminClient } from '../lib/supabase.js'

export const materials = new Hono()

// COST DEFINITIONS
const UPLOAD_REWARD = 5
const DOWNLOAD_COST = 2

// List all materials
materials.get('/', async (c) => {
    try {
        const supabase = createAdminClient()
        const { data, error } = await supabase
            .from('materials')
            .select(`
                id, title, course_code, description, file_type, downloads_count, created_at,
                profiles:uploader_id (full_name)
            `)
            .order('created_at', { ascending: false })

        if (error) throw error
        return c.json({ materials: data })
    } catch (err: any) {
        return c.json({ error: 'Failed to fetch materials' }, 500)
    }
})

// Upload a material (Awards Credits)
materials.post('/upload', async (c) => {
    try {
        const userId = c.req.header('x-user-id') // Extracted by auth middleware in reality
        if (!userId) return c.json({ error: 'Unauthorized' }, 401)

        const body = await c.req.parseBody()
        const title = body['title'] as string
        const courseCode = body['course_code'] as string
        const description = body['description'] as string
        const file = body['file'] as File

        if (!title || !courseCode || !file) {
            return c.json({ error: 'Title, Course Code, and File are required' }, 400)
        }

        const supabase = createAdminClient()

        // 1. Upload file to Storage Bucket
        const uniqueFileName = `${userId}/${Date.now()}_${file.name}`
        const { data: storageData, error: storageError } = await supabase
            .storage
            .from('study_materials')
            .upload(uniqueFileName, file)

        if (storageError) throw storageError

        // Get public URL
        const { data: { publicUrl } } = supabase.storage.from('study_materials').getPublicUrl(uniqueFileName)

        // 2. Insert into DB
        const { data: materialData, error: dbError } = await supabase
            .from('materials')
            .insert({
                uploader_id: userId,
                title,
                course_code: courseCode,
                description,
                file_url: publicUrl,
                file_type: file.type || 'unknown'
            })
            .select()
            .single()

        if (dbError) throw dbError

        // 3. Award Credits
        const { error: creditError } = await supabase.rpc('increment_credit', {
            user_id_param: userId,
            amount_param: UPLOAD_REWARD
        })

        // 4. Log Transaction
        await supabase.from('transactions').insert({
            user_id: userId,
            amount: UPLOAD_REWARD,
            reason: 'upload_material',
            reference_id: materialData.id
        })

        return c.json({ message: 'File uploaded successfully! +5 Credits awarded.', material: materialData })
    } catch (err: any) {
        console.error(err)
        return c.json({ error: 'Failed to upload material' }, 500)
    }
})

// Request Download (Burns Credits)
materials.post('/:id/download', async (c) => {
    try {
        const materialId = c.req.param('id')
        const userId = c.req.header('x-user-id')
        if (!userId) return c.json({ error: 'Unauthorized' }, 401)

        const supabase = createAdminClient()

        // 1. Check User Balance
        const { data: profile } = await supabase
            .from('profiles')
            .select('credit_balance')
            .eq('id', userId)
            .single()

        if (!profile || profile.credit_balance < DOWNLOAD_COST) {
            return c.json({ error: 'Insufficient credits. Upload materials to earn more!' }, 402) // Payment required
        }

        // 2. Fetch Material Link
        const { data: material } = await supabase
            .from('materials')
            .select('file_url')
            .eq('id', materialId)
            .single()

        if (!material) return c.json({ error: 'Material not found' }, 404)

        // 3. Deduct Credits
        await supabase.rpc('increment_credit', {
            user_id_param: userId,
            amount_param: -DOWNLOAD_COST
        })

        // 4. Log Transaction & Increase Download Count
        await Promise.all([
            supabase.from('transactions').insert({
                user_id: userId,
                amount: -DOWNLOAD_COST,
                reason: 'download_material',
                reference_id: materialId
            }),
            supabase.rpc('increment_download_count', { material_id_param: materialId })
        ])

        return c.json({
            message: 'Download unlocked! -2 Credits.',
            downloadUrl: material.file_url
        })
    } catch (err: any) {
        console.error(err)
        return c.json({ error: 'Failed to process download' }, 500)
    }
})

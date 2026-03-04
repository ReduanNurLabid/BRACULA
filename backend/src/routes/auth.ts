import { Hono } from 'hono'

export const auth = new Hono()

// NOTE: Student Login and Registration are now handled natively on the Frontend
// via the @supabase/supabase-js library (Email + Password).
// This removes the need for a server-side /login endpoint for standard users.

// Endpoint for alumni to request manual verification (Future Implementation)
auth.post('/alumni-request', async (c) => {
    const body = await c.req.json()
    const { name, email, graduationYear, program } = body

    if (!name || !email || !graduationYear) {
        return c.json({ error: 'Missing required fields' }, 400)
    }

    return c.json({ message: 'Alumni request submitted successfully. Pending manual review.' })
})

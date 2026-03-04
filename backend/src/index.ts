import { serve } from '@hono/node-server'
import { Hono } from 'hono'

const app = new Hono()

import { auth } from './routes/auth.js'
import { materials } from './routes/materials.js'

app.route('/api/auth', auth)
app.route('/api/materials', materials)

app.get('/', (c) => {
  return c.text('Hello Hono!')
})

if (process.env.NODE_ENV !== 'development') {
  serve({
    fetch: app.fetch,
    port: 3000
  }, (info) => {
    console.log(`Server is running on http://localhost:${info.port}`)
  })
}

export default app

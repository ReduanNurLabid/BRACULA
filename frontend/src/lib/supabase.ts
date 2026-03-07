import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

// Create a custom fetch wrapper that appends a dummy timestamp to GET requests
// This bypasses iOS Safari's extremely aggressive caching bugs without triggering CORS OPTIONS preflight errors
const customFetch = (url: RequestInfo | URL, options?: RequestInit) => {
    let finalUrl = url.toString();
    if (options?.method === 'GET') {
        const separator = finalUrl.includes('?') ? '&' : '?';
        finalUrl = `${finalUrl}${separator}t=${Date.now()}`;
    }
    return fetch(finalUrl, options);
};

export const supabase = createClient(supabaseUrl, supabaseAnonKey, {
    global: {
        fetch: customFetch
    }
})

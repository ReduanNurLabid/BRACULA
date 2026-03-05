import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext.tsx'
import { ConfirmProvider } from './contexts/ConfirmContext.tsx'
import { ThemeProvider } from './contexts/ThemeContext.tsx'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import App from './App.tsx'
import './index.css'

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5, // 5 minutes fresh
            refetchOnWindowFocus: false,
        },
    },
})

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <QueryClientProvider client={queryClient}>
            <ThemeProvider>
                <BrowserRouter>
                    <AuthProvider>
                        <ConfirmProvider>
                            <App />
                        </ConfirmProvider>
                    </AuthProvider>
                </BrowserRouter>
            </ThemeProvider>
        </QueryClientProvider>
    </StrictMode>,
)

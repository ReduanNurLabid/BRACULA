import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext.tsx'
import { ConfirmProvider } from './contexts/ConfirmContext.tsx'
import { ThemeProvider } from './contexts/ThemeContext.tsx'
import App from './App.tsx'
import './index.css'

createRoot(document.getElementById('root')!).render(
    <StrictMode>
        <ThemeProvider>
            <BrowserRouter>
                <AuthProvider>
                    <ConfirmProvider>
                        <App />
                    </ConfirmProvider>
                </AuthProvider>
            </BrowserRouter>
        </ThemeProvider>
    </StrictMode>,
)

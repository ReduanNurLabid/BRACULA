import { Routes, Route } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'

import { Navbar } from './components/layout/Navbar'
import { Footer } from './components/layout/Footer'

import { HomeFeed } from './pages/forums/HomeFeed'

import { Login } from './pages/auth/Login'
import { ForgotPassword } from './pages/auth/ForgotPassword'
import { ResetPassword } from './pages/auth/ResetPassword'
import { Materials } from './pages/materials/Materials'
import { Rides } from './pages/rides/Rides'
import { ToLet } from './pages/to-let/ToLet'
import { Clubs } from './pages/clubs/Clubs'
import { Profile } from './pages/profile/Profile'
import { Feedback } from './pages/feedback/Feedback'
import { AdminDashboard } from './pages/admin/AdminDashboard'

const App = () => {
    return (
        <>
            <Toaster
                position="top-center"
                toastOptions={{
                    style: {
                        background: 'rgba(30, 30, 40, 0.95)',
                        color: '#fff',
                        backdropFilter: 'blur(10px)',
                        border: '1px solid var(--border-glass)',
                        boxShadow: '0 8px 32px rgba(0,0,0,0.3)',
                    }
                }}
            />
            <div className="bg-blob bg-blob-1"></div>
            <div className="bg-blob bg-blob-2"></div>

            <Navbar />

            <main style={{ paddingTop: '5rem', minHeight: 'calc(100vh - 100px)', display: 'flex', flexDirection: 'column' }}>
                <Routes>
                    <Route path="/" element={<HomeFeed />} />
                    <Route path="/login" element={<Login />} />
                    <Route path="/forgot-password" element={<ForgotPassword />} />
                    <Route path="/reset-password" element={<ResetPassword />} />
                    <Route path="/study" element={<Materials />} />
                    <Route path="/rides" element={<Rides />} />
                    <Route path="/to-let" element={<ToLet />} />
                    <Route path="/clubs" element={<Clubs />} />
                    <Route path="/profile" element={<Profile />} />
                    <Route path="/profile/:userId" element={<Profile />} />
                    <Route path="/feedback" element={<Feedback />} />
                    <Route path="/admin" element={<AdminDashboard />} />
                </Routes>
            </main>

            <Footer />
        </>
    )
}

export default App

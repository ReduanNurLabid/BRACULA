<p align="center">
  <img src="bracua.png" alt="BRACULA Logo" width="500" />
</p>

<h1 align="center">BRACULA — BRAC University Student Hub</h1>

<p align="center">
  <b>A full-stack community platform built for BRAC University students to share resources, collaborate, and connect.</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=white" />
  <img src="https://img.shields.io/badge/TypeScript-5.9-3178C6?logo=typescript&logoColor=white" />
  <img src="https://img.shields.io/badge/Hono-4.x-E36002?logo=hono&logoColor=white" />
  <img src="https://img.shields.io/badge/Supabase-BaaS-3FCF8E?logo=supabase&logoColor=white" />
  <img src="https://img.shields.io/badge/Deployed-Vercel-000?logo=vercel&logoColor=white" />
</p>

---

## 📌 Overview

**BRACULA** (BRACU + Dracula 🧛) is a feature-rich student community web application designed to serve as a one-stop digital hub for BRAC University students. It combines study material sharing, community forums, ride-sharing, housing listings, and real-time messaging into a single, cohesive platform with a dark, glassmorphic UI.

---

## ✨ Key Features

| Module | Description |
|---|---|
| **📚 Study Materials** | Upload & download notes, slides, and past papers with a **credit-based economy** — earn credits by uploading, spend them to download. Supports both file uploads and Google Drive links. |
| **💬 Community Forums** | Reddit-style discussion boards with communities, posts, upvote/downvote voting, threaded comments, and full **edit history** tracking. |
| **🚗 Ride Sharing** | Post and find shared rides (Rickshaw, Bike, Car/CNG) with seat management, request/accept workflows, and post-ride reviews with ratings. |
| **🏠 To-Let Listings** | Browse and post housing/flat-share listings near campus. |
| **🏢 Clubs** | Discover and learn about university clubs and organizations. |
| **📢 Shoutbox** | Real-time, campus-wide chat room for quick broadcasts and conversations. |
| **👤 User Profiles** | Personal dashboards with activity history and credit balance tracking. |
| **🛡️ Admin Dashboard** | Moderation tools for managing reported materials, refunding credits, and overseeing platform activity. |
| **📝 Feedback** | In-app feedback system for users to submit suggestions and bug reports. |

---

## 🏗️ Tech Stack

### Frontend
- **React 19** with **TypeScript** — Modern component architecture with hooks
- **React Router v7** — Client-side routing with SPA rewrites
- **TanStack React Query** — Server-state management with caching & infinite scroll
- **Lucide React** — Icon system
- **React Hot Toast** — Notification system
- **Vanilla CSS** — Custom dark glassmorphic design system (no UI library)

### Backend
- **Hono** — Ultra-fast, lightweight web framework (runs on Node.js)
- **TypeScript** — End-to-end type safety

### Database & Auth (BaaS)
- **Supabase** — PostgreSQL database + Auth + File Storage + Realtime
- **Row-Level Security (RLS)** — Fine-grained access control at the database level
- **Stored Procedures (RPCs)** — Server-side transaction logic for credit operations, voting, and content moderation to prevent client-side manipulation

### Deployment
- **Vercel** — Frontend hosting with SPA rewrite rules

---

## 🗄️ Database Architecture

The platform uses a relational PostgreSQL schema (via Supabase) with **14+ tables** including:

- `profiles` — User accounts extending Supabase Auth with credit balances
- `materials` — Study resources with download counters and Google Drive support
- `transactions` — Auditable credit ledger (earn on upload, spend on download)
- `communities`, `posts`, `post_votes`, `comments` — Full forum system
- `rides`, `ride_requests`, `ride_reviews` — Ride-sharing lifecycle
- `material_reports`, `material_requests` — Moderation and request fulfillment
- `shoutbox_messages` — Real-time messaging

All sensitive operations (credit transfers, vote toggling, content moderation) are handled through **SECURITY DEFINER RPCs** to ensure data integrity.

---

## 🚀 Getting Started

### Prerequisites
- **Node.js** ≥ 18
- A **Supabase** project (free tier works)

### 1. Clone the repository
```bash
git clone https://github.com/ReduanNurLabid/BRACULA.git
cd BRACULA
```

### 2. Set up the database
Run the `supabase_schema.sql` file in your Supabase SQL Editor to create all tables, RLS policies, and stored procedures.

### 3. Configure environment variables
Create a `.env` file in the `backend/` directory:
```env
SUPABASE_URL=your_supabase_project_url
SUPABASE_SERVICE_KEY=your_supabase_service_role_key
```

### 4. Install & run

```bash
# Terminal 1 — Backend
cd backend
npm install
npm run dev

# Terminal 2 — Frontend
cd frontend
npm install
npm run dev
```

The frontend will be available at `http://localhost:5173` and the backend API at `http://localhost:3000`.

---

## 📁 Project Structure

```
BRACULA/
├── frontend/                  # React SPA
│   ├── src/
│   │   ├── components/        # Reusable UI components (Navbar, Footer)
│   │   ├── contexts/          # React Context providers (Auth, Shoutbox, etc.)
│   │   ├── pages/             # Feature modules
│   │   │   ├── admin/         # Admin moderation dashboard
│   │   │   ├── auth/          # Login, password reset flows
│   │   │   ├── clubs/         # University clubs listing
│   │   │   ├── feedback/      # User feedback form
│   │   │   ├── forums/        # Community forums (posts, voting, comments)
│   │   │   ├── materials/     # Study material upload/download
│   │   │   ├── profile/       # User profile & activity
│   │   │   ├── rides/         # Ride-sharing board
│   │   │   ├── shoutbox/      # Real-time campus chat
│   │   │   └── to-let/        # Housing listings
│   │   ├── lib/               # Supabase client initialization
│   │   └── utils/             # Helper utilities
│   └── vercel.json            # Vercel SPA rewrite config
│
├── backend/                   # Hono API server
│   └── src/
│       ├── routes/            # API route handlers
│       └── lib/               # Supabase admin client
│
├── supabase_schema.sql        # Full database schema + RLS + RPCs
└── update_db_*.sql            # Incremental migration scripts
```

---

## 🎨 Design Philosophy

BRACULA features a **dark, glassmorphic UI** with:
- Frosted-glass card effects with `backdrop-filter: blur()`
- Animated gradient background blobs
- Smooth micro-interactions and hover transitions
- A cohesive dark color palette inspired by the "Dracula" theme

---

## 👨‍💻 Author

**Reduan Nur Labid**

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

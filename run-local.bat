@echo off
echo Starting Backend Server...
start "Backend" cmd /k "cd backend && npm run dev"

echo Starting Frontend Server...
start "Frontend" cmd /k "cd frontend && npm run dev"

echo Both servers are starting in new command prompt windows!
echo Frontend will be running at http://localhost:5173
echo Backend will be running at http://localhost:3000

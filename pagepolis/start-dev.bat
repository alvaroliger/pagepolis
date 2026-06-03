@echo off
echo Iniciando InstaWeb en modo desarrollo...
echo.
echo Backend:  http://127.0.0.1:8000
echo Vite:     http://localhost:5173
echo.
echo Login admin:  admin@instaweb.com / admin123
echo Login demo:   demo@instaweb.com  / demo123
echo.
start "Laravel PHP Server" cmd /k "cd /d %~dp0 && php artisan serve --host=127.0.0.1 --port=8000"
start "Vite Dev Server" cmd /k "cd /d %~dp0 && npm run dev"
echo Servidores iniciados. Abre http://127.0.0.1:8000 en tu navegador.

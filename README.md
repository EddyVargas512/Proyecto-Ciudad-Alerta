# Ciudad Alerta

El repositorio está organizado de forma similar a TravelTheWorld. Git está en la raíz y controla ambos proyectos; no hay un repositorio Git independiente dentro del frontend.

- `Backend/ciudad-alerta/`: API y aplicación Laravel.
- `Frontend/`: interfaz React con Vite.

## Iniciar el backend

Abre una terminal en `Backend/ciudad-alerta/` y ejecuta:

```powershell
composer install
php artisan serve --host=127.0.0.1 --port=8000
```

La configuración local de Laravel está en `Backend/ciudad-alerta/.env`. En una instalación nueva, copia `.env.example` a `.env`, configura la conexión a la base de datos, genera la clave con `php artisan key:generate`, ejecuta las migraciones con `php artisan migrate` y crea el enlace de fotos con `php artisan storage:link`.

## Iniciar el frontend

Abre otra terminal en `Frontend/`. En una instalación nueva, copia `.env.example` a `.env`; luego ejecuta:

```powershell
npm install
npm run dev
```

La dirección de la API se configura en `Frontend/.env`:

- `VITE_API_URL=/api` es el prefijo de las rutas que consume React.
- `VITE_API_PROXY_TARGET=http://127.0.0.1:8000` indica a Vite dónde está Laravel durante el desarrollo.

Si cambias estos valores, reinicia el servidor de Vite para que los vuelva a cargar. No subas los archivos `.env` al repositorio; comparte únicamente los archivos `.env.example`.

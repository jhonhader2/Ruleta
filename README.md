# Ruleta de Colonias Colombianas

Asignación de colonias (Andina, Amazónica, Caribe, Pacífico, Orinoquía) mediante ruleta balanceada. El usuario ingresa su documento y recibe una colonia; ninguna supera en más de 2 integrantes a las demás.

## Requisitos

- PHP 8.2+ (local) o Docker (producción)
- MySQL 8

## Despliegue con Docker (VPS)

1. Subir el proyecto al VPS (git clone, scp, rsync)
2. `cd /ruta/ruleta`
3. `chmod +x deploy.sh`
4. `./deploy.sh` — la primera vez crea `.env` y pide definir `DB_PASS` y `MYSQL_ROOT_PASSWORD`
5. Editar `.env` y ejecutar `./deploy.sh` de nuevo

### NGINX + SSL (Hostinger, Ubuntu)

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo cp nginx/ruleta.dataguaviare.com.co.conf.example /etc/nginx/sites-available/ruleta.dataguaviare.com.co
sudo ln -s /etc/nginx/sites-available/ruleta.dataguaviare.com.co /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d ruleta.dataguaviare.com.co
```

## Variables (.env)

| Variable | Descripción |
|----------|-------------|
| PORT | Puerto expuesto (default 8080) |
| APP_ENV | production / development |
| APP_URL | URL pública (ej. https://ruleta.dataguaviare.com.co) |
| DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS | Conexión MySQL |
| MYSQL_ROOT_PASSWORD | Contraseña root de MySQL |

## Estructura

- `index.php` — Entrada
- `api/asignar.php` — Asignación ruleta
- `api/reporte.php` — Reporte JSON/CSV
- `config/colonias.php` — Definición de colonias
- `config/database.example.php` — Plantilla BD (copiar a `database.php`)

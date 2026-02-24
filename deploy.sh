#!/bin/bash
# Despliegue Ruleta en VPS con Docker
#
# Uso en el VPS:
#   1. Subir el proyecto (git clone, scp, rsync)
#   2. cd /ruta/ruleta
#   3. chmod +x deploy.sh
#   4. ./deploy.sh
#
# Primera vez: crea .env, valida variables y pide configurar si faltan.
# Siguientes: ./deploy.sh actualiza y reinicia.
#
# Con NGINX: usar nginx/ruleta.dataguaviare.com.co.conf.example como plantilla.
# Certbot para SSL: certbot --nginx -d ruleta.dataguaviare.com.co
#
set -e

echo "=== Ruleta - Despliegue Docker ==="

if [ ! -f .env ]; then
  echo "Creando .env desde .env.example..."
  cp .env.example .env
  echo ""
  echo "IMPORTANTE: Edita .env y define DB_PASS y MYSQL_ROOT_PASSWORD:"
  echo "  nano .env"
  echo ""
  echo "Luego ejecuta de nuevo: ./deploy.sh"
  exit 1
fi

# Cargar .env para validar
set -a
source .env 2>/dev/null || true
set +a

if [ -z "${DB_PASS}" ] || [ -z "${MYSQL_ROOT_PASSWORD}" ]; then
  echo "ERROR: DB_PASS y MYSQL_ROOT_PASSWORD deben estar definidos en .env"
  echo "  nano .env"
  exit 1
fi

echo "Construyendo y levantando contenedores..."
docker compose up -d --build

echo ""
echo "=== Estado ==="
docker compose ps

echo ""
if [ -n "${APP_URL}" ]; then
  echo "Listo. Accede a ${APP_URL}"
else
  echo "Listo. Define APP_URL en .env para ver la URL de acceso"
fi
echo "Para ver logs: docker compose logs -f"

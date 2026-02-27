# ðŸ›ï¸ RoupasBR - E-commerce Completo

## ðŸš€ Stack
- Backend: Laravel 11 + PHP 8.2
- Frontend: Next.js 14 + TypeScript
- Database: MySQL 8.0
- Cache: Redis
- Pagamentos: Stripe (Pix, Boleto, CartÃ£o)
- Infra: Docker + Nginx

## ðŸ“¦ InstalaÃ§Ã£o RÃ¡pida

```powershell
# 1. Subir containers
docker-compose up -d --build

# 2. Instalar dependÃªncias backend
docker exec roupasbr_backend composer install

# 3. Configurar Laravel
docker exec roupasbr_backend php artisan key:generate
docker exec roupasbr_backend php artisan migrate --seed

# 4. Acessar
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000/api
ðŸ”‘ Configurar Pagamentos
Crie conta no Stripe: https://stripe.com/br
Obtenha as chaves API
Edite backend/.env com suas chaves
ðŸ“„ LicenÃ§a
MIT License

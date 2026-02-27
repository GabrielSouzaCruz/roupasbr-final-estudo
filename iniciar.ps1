Write-Host "ðŸš€ Iniciando RoupasBR E-commerce..." -ForegroundColor Green
Verificar Docker
try {
$dockerVersion = docker --version
Write-Host "âœ… Docker encontrado: $dockerVersion" -ForegroundColor Green
} catch {
Write-Host "âŒ Docker nÃ£o encontrado. Instale Docker Desktop primeiro." -ForegroundColor Red
Write-Host "Baixe em: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
exit 1
}
Build e start
Write-Host "ðŸ“¦ Build e start dos containers..." -ForegroundColor Cyan
docker-compose up -d --build
Write-Host "â³ Aguardando serviÃ§os iniciarem (30 segundos)..." -ForegroundColor Cyan
Start-Sleep -Seconds 30
Instalar dependÃªncias
Write-Host "ðŸ“¦ Instalando dependÃªncias backend..." -ForegroundColor Cyan
docker exec roupasbr_backend composer install --no-interaction 2>$null
Gerar key e migrar
Write-Host "ðŸ”‘ Configurando Laravel..." -ForegroundColor Cyan
docker exec roupasbr_backend php artisan key:generate --force 2>$null
docker exec roupasbr_backend php artisan migrate --force 2>$null
Write-Host ""
Write-Host "âœ… Pronto!" -ForegroundColor Green
Write-Host "ðŸ“ Frontend: http://localhost:3000" -ForegroundColor Cyan
Write-Host "ðŸ“ API: http://localhost:8000/api" -ForegroundColor Cyan
Write-Host ""
Write-Host "PrÃ³ximos passos:" -ForegroundColor Yellow
Write-Host "1. Configure as chaves de pagamento em backend/.env"
Write-Host "2. Crie produtos no banco de dados"
Write-Host "3. Teste o checkout!"

# 🛍️ RoupasBR - E-commerce Completo para Moda

[![Laravel](https://img.shields.io/badge/Laravel-11.0-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Next.js](https://img.shields.io/badge/Next.js-14.2-000000?style=for-the-badge&logo=next.js)](https://nextjs.org)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.3-3178C6?style=for-the-badge&logo=typescript)](https://typescriptlang.org)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

> Plataforma completa de e-commerce desenvolvida para o segmento de moda no mercado brasileiro, com suporte a múltiplos gateways de pagamento, integração com ERP e conformidade com LGPD.

---

## 📋 Sumário

- [Visão Geral](#-visão-geral)
- [Funcionalidades](#-funcionalidades)
- [Arquitetura do Sistema](#-arquitetura-do-sistema)
- [Stack Tecnológica](#-stack-tecnológica)
- [Modelo de Dados](#-modelo-de-dados)
- [Documentação da API](#-documentação-da-api)
- [Guia de Deployment](#-guia-de-deployment)
- [Serviços e Integrações](#-serviços-e-integrações)
- [Jobs Assíncronos](#-jobs-assíncronos)
- [Conformidade LGPD](#-conformidade-lgpd)
- [Testes](#-testes)
- [Contribuição](#-contribuição)
- [Licença](#-licença)
- [Autor](#-autor)

---

## 🎯 Visão Geral

**RoupasBR** é uma plataforma de e-commerce completa desenvolvida especificamente para o segmento de moda e vestuário no mercado brasileiro. O sistema foi projetado com uma arquitetura moderna baseada em microsserviços containerizados, utilizando as tecnologias mais recentes e melhores práticas do mercado.

### Características Principais

- 🏪 **E-commerce Completo** - Do catálogo ao checkout
- 💳 **Múltiplos Pagamentos** - PIX, Boleto, Cartão de Crédito
- 📦 **Gestão de Estoque** - Variações de produtos (tamanho, cor)
- 🚚 **Integração Logística** - Cálculo de frete em tempo real
- 📊 **Painel Administrativo** - Filament PHP para gestão completa
- 🔒 **LGPD Compliant** - Exportação de dados e direito ao esquecimento
- 📧 **Recuperação de Vendas** - Carrinhos abandonados automatizados

---

## ✨ Funcionalidades

### E-commerce Core

| Funcionalidade | Descrição |
|----------------|-----------|
| 🏷️ **Catálogo de Produtos** | Produtos com variações (tamanho, cor, SKU único) |
| 🛒 **Carrinho Persistente** | Sincronização em tempo real entre dispositivos |
| 💰 **Checkout Completo** | Cálculo de frete, cupons, múltiplas formas de pagamento |
| 📦 **Gestão de Pedidos** | Acompanhamento de status e rastreamento |
| 🏠 **Múltiplos Endereços** | Entrega e cobrança em diferentes localidades |

### Marketing e Vendas

| Funcionalidade | Descrição |
|----------------|-----------|
| 🎫 **Cupons de Desconto** | Percentual ou valor fixo, com validade |
| ⭐ **Avaliações** | Sistema de reviews com rating e comentários |
| ❤️ **Lista de Desejos** | Wishlist compartilhável |
| 📧 **Carrinho Abandonado** | Recuperação automática via email |

### Pagamentos

| Gateway | Métodos Suportados |
|---------|-------------------|
| **Stripe** | Cartão de Crédito (Internacional) |
| **Pagar.me** | PIX, Boleto, Cartão de Crédito |

### Administração

| Funcionalidade | Descrição |
|----------------|-----------|
| 📊 **Dashboard** | Métricas de vendas e performance |
| 📦 **Gestão de Produtos** | CRUD completo com variações |
| 👥 **Gestão de Clientes** | Visualização e suporte |
| 📋 **Pedidos** | Alteração de status, emissão de NF-e |
| 🎫 **Cupons** | Criação e gestão de promoções |

---

## 🏗️ Arquitetura do Sistema

O sistema adota uma arquitetura de microsserviços containerizados, orquestrados via Docker Compose:

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENTE (Browser)                        │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                      NGINX (Reverse Proxy)                       │
│                    Port: 80/443 (SSL)                           │
└─────────────────────────────────────────────────────────────────┘
                    │                           │
                    ▼                           ▼
┌──────────────────────────┐    ┌──────────────────────────────┐
│    FRONTEND (Next.js)    │    │     BACKEND (Laravel)        │
│    Port: 3000            │◄──►│     Port: 8000               │
│    - React 18            │    │     - PHP 8.2                │
│    - TypeScript          │    │     - Laravel Sanctum        │
│    - TailwindCSS         │    │     - Filament Admin         │
│    - Zustand             │    │     - Eloquent ORM           │
└──────────────────────────┘    └──────────────────────────────┘
                    │                           │
                    │                           ▼
                    │           ┌──────────────────────────────┐
                    │           │      MySQL 8.0               │
                    │           │      (Database)              │
                    │           └──────────────────────────────┘
                    │                           │
                    │                           ▼
                    │           ┌──────────────────────────────┐
                    │           │      Redis (Cache/Queue)     │
                    │           └──────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SERVIÇOS EXTERNOS                             │
├──────────────────┬──────────────────┬───────────────────────────┤
│   Stripe API     │   Pagar.me API   │   Bling ERP API           │
│   (Pagamentos)   │   (Pagamentos)   │   (NF-e)                  │
└──────────────────┴──────────────────┴───────────────────────────┘
```

### Componentes Docker

| Serviço | Container | Porta | Descrição |
|---------|-----------|-------|-----------|
| `backend` | roupasbr_backend | 8000 | API Laravel PHP 8.2 |
| `frontend` | roupasbr_frontend | 3000 | Interface Next.js 14 |
| `db` | roupasbr_db | 3306 | Banco MySQL 8.0 |
| `redis` | roupasbr_redis | 6379 | Cache e Filas |

---

## 🛠️ Stack Tecnológica

### Backend (Laravel 11)

| Tecnologia | Versão | Propósito |
|------------|--------|-----------|
| PHP | 8.2 | Linguagem principal |
| Laravel Framework | 11.0 | Framework MVC |
| Laravel Sanctum | 4.0 | Autenticação SPA/API |
| Stripe PHP | 15.0 | Gateway de pagamento internacional |
| Pagar.me Laravel | 2.0 | Gateway de pagamento brasileiro |
| Filament | 3.x | Painel administrativo |
| MySQL | 8.0 | Banco de dados relacional |
| Redis | Alpine | Cache e filas |

### Frontend (Next.js 14)

| Tecnologia | Versão | Propósito |
|------------|--------|-----------|
| Next.js | 14.2.0 | Framework React com SSR/SSG |
| React | 18.2.0 | Biblioteca de UI components |
| TypeScript | 5.3.0 | Tipagem estática |
| TailwindCSS | 3.4.0 | Framework CSS utilitário |
| Zustand | 4.5.0 | Gerenciamento de estado |
| Stripe.js | 3.0.0 | SDK frontend para pagamentos |
| Axios | 1.6.0 | Cliente HTTP |
| Lucide React | 0.321.0 | Biblioteca de ícones |
| Cypress | latest | Testes E2E |

---

## 📊 Modelo de Dados

### Diagrama Entidade-Relacionamento

```
┌─────────────┐       ┌─────────────┐       ┌─────────────────┐
│   users     │       │  products   │       │ product_variants│
├─────────────┤       ├─────────────┤       ├─────────────────┤
│ id          │       │ id          │       │ id              │
│ name        │       │ name        │       │ product_id (FK) │
│ email       │       │ slug        │       │ size            │
│ password    │       │ description │       │ color           │
│ created_at  │       │ price       │       │ sku             │
│ updated_at  │       │ stock       │       │ stock           │
└──────┬──────┘       │ category    │       └────────┬────────┘
       │              │ featured    │                │
       │              └──────┬──────┘                │
       │                     │                       │
       ▼                     │                       │
┌─────────────┐              │                       │
│  addresses  │              │                       │
├─────────────┤              │                       │
│ id          │              │                       │
│ user_id(FK) │              │                       │
│ street      │              │                       │
│ city        │              │                       │
│ state       │              │                       │
│ zip_code    │              │                       │
└─────────────┘              │                       │
                             │                       │
       ┌─────────────────────┴───────────────────────┘
       │
       ▼
┌─────────────────┐       ┌─────────────────┐
│     orders      │       │   order_items   │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ user_id (FK)    │       │ order_id (FK)   │
│ total           │       │ product_variant │
│ status          │       │ quantity        │
│ payment_method  │       │ price           │
│ tracking_number │       └─────────────────┘
│ coupon_id (FK)  │
└────────┬────────┘
         │
         ├──────────────────┬──────────────────┐
         │                  │                  │
         ▼                  ▼                  ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│   coupons   │    │   reviews   │    │ abandoned_carts │
├─────────────┤    ├─────────────┤    ├─────────────────┤
│ id          │    │ id          │    │ id              │
│ code        │    │ user_id(FK) │    │ user_id (FK)    │
│ discount_%  │    │ product_id  │    │ cart_data (JSON)│
│ expires_at  │    │ rating      │    │ recovered       │
└─────────────┘    │ comment     │    └─────────────────┘
                   └─────────────┘
```

### Tabelas do Sistema

| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários do sistema (clientes e administradores) |
| `addresses` | Endereços de entrega e cobrança |
| `products` | Catálogo de produtos |
| `product_variants` | Variações de produtos (tamanho, cor, SKU) |
| `orders` | Pedidos realizados |
| `order_items` | Itens de cada pedido |
| `coupons` | Cupons de desconto |
| `reviews` | Avaliações de produtos |
| `wishlists` | Lista de desejos |
| `abandoned_carts` | Carrinhos abandonados |
| `return_items` | Solicitações de devolução |

---

## 📡 Documentação da API

### Base URL

```
Development: http://localhost:8000/api
Production: https://api.seudominio.com.br/api
```

### Autenticação

Todas as rotas protegidas requerem o header:

```http
Authorization: Bearer {token}
```

---

### 🔐 Autenticação

#### Registrar Usuário

```http
POST /api/auth/register
```

**Body:**
```json
{
  "name": "João Silva",
  "email": "joao@email.com",
  "password": "senhaSegura123",
  "password_confirmation": "senhaSegura123"
}
```

**Response (201):**
```json
{
  "message": "Usuário registrado com sucesso",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com"
  },
  "token": "1|abcdef123456..."
}
```

---

#### Login

```http
POST /api/auth/login
```

**Body:**
```json
{
  "email": "joao@email.com",
  "password": "senhaSegura123"
}
```

**Response (200):**
```json
{
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com"
  }
}
```

---

#### Logout

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Logout realizado com sucesso"
}
```

---

#### Obter Usuário Autenticado

```http
GET /api/auth/user
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@email.com",
  "created_at": "2025-01-15T10:30:00Z"
}
```

---

### 🛍️ Produtos

#### Listar Produtos

```http
GET /api/products
```

**Query Parameters:**
| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `page` | int | Número da página |
| `category` | string | Filtrar por categoria |
| `min_price` | float | Preço mínimo |
| `max_price` | float | Preço máximo |
| `sort` | string | Ordenação (price_asc, price_desc, newest) |

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Camiseta Premium",
      "slug": "camiseta-premium",
      "description": "Camiseta 100% algodão",
      "price": 89.90,
      "category": "camisetas",
      "featured": true,
      "image": "https://...",
      "variants": [
        {
          "id": 1,
          "size": "M",
          "color": "Preto",
          "sku": "CAM-PRE-M",
          "stock": 25
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 15
  }
}
```

---

#### Produtos em Destaque

```http
GET /api/products/featured
```

**Response (200):**
```json
[
  {
    "id": 1,
    "name": "Camiseta Premium",
    "slug": "camiseta-premium",
    "price": 89.90,
    "image": "https://..."
  }
]
```

---

#### Categorias

```http
GET /api/products/categories
```

**Response (200):**
```json
[
  { "id": 1, "name": "Camisetas", "slug": "camisetas", "count": 45 },
  { "id": 2, "name": "Calças", "slug": "calcas", "count": 32 },
  { "id": 3, "name": "Vestidos", "slug": "vestidos", "count": 28 }
]
```

---

#### Detalhes do Produto

```http
GET /api/products/{slug}
```

**Response (200):**
```json
{
  "id": 1,
  "name": "Camiseta Premium",
  "slug": "camiseta-premium",
  "description": "Camiseta 100% algodão premium...",
  "price": 89.90,
  "category": {
    "id": 1,
    "name": "Camisetas"
  },
  "images": ["https://...", "https://..."],
  "variants": [
    {
      "id": 1,
      "size": "P",
      "color": "Preto",
      "sku": "CAM-PRE-P",
      "stock": 15
    },
    {
      "id": 2,
      "size": "M",
      "color": "Preto",
      "sku": "CAM-PRE-M",
      "stock": 25
    }
  ],
  "reviews": {
    "average": 4.5,
    "count": 128
  }
}
```

---

### 📦 Pedidos

#### Listar Pedidos

```http
GET /api/orders
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-2025-00001",
      "total": 179.80,
      "status": "delivered",
      "payment_method": "stripe",
      "tracking_number": "BR123456789",
      "created_at": "2025-01-15T10:30:00Z",
      "items_count": 2
    }
  ]
}
```

---

#### Criar Pedido

```http
POST /api/orders
Authorization: Bearer {token}
```

**Body:**
```json
{
  "items": [
    {
      "variant_id": 1,
      "quantity": 2
    },
    {
      "variant_id": 5,
      "quantity": 1
    }
  ],
  "address_id": 1,
  "payment_method": "stripe",
  "coupon_code": "DESCONTO10"
}
```

**Response (201):**
```json
{
  "order_number": "ORD-2025-00002",
  "total": 179.80,
  "status": "pending",
  "payment_url": "https://checkout.stripe.com/...",
  "message": "Pedido criado com sucesso"
}
```

---

#### Detalhes do Pedido

```http
GET /api/orders/{orderNumber}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "id": 1,
  "order_number": "ORD-2025-00001",
  "status": "delivered",
  "total": 179.80,
  "subtotal": 199.80,
  "discount": 20.00,
  "shipping": 0,
  "tracking_number": "BR123456789",
  "tracking_url": "https://...",
  "address": {
    "street": "Rua das Flores, 123",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01234-567"
  },
  "items": [
    {
      "product_name": "Camiseta Premium",
      "variant": "M - Preto",
      "quantity": 2,
      "price": 89.90
    }
  ],
  "created_at": "2025-01-15T10:30:00Z"
}
```

---

### 📍 Endereços

#### Listar Endereços

```http
GET /api/addresses
Authorization: Bearer {token}
```

#### Criar Endereço

```http
POST /api/addresses
Authorization: Bearer {token}
```

**Body:**
```json
{
  "label": "Casa",
  "street": "Rua das Flores",
  "number": "123",
  "complement": "Apto 45",
  "neighborhood": "Centro",
  "city": "São Paulo",
  "state": "SP",
  "zip_code": "01234-567",
  "is_default": true
}
```

---

### 🛒 Carrinho Abandonado

#### Salvar Carrinho

```http
POST /api/abandoned-cart
Authorization: Bearer {token}
```

**Body:**
```json
{
  "items": [
    { "variant_id": 1, "quantity": 2 }
  ]
}
```

#### Recuperar Carrinho

```http
GET /api/abandoned-cart/recover
Authorization: Bearer {token}
```

---

### ⭐ Reviews

#### Criar Avaliação

```http
POST /api/reviews
Authorization: Bearer {token}
```

**Body:**
```json
{
  "product_id": 1,
  "rating": 5,
  "comment": "Produto excelente! Qualidade impressionante."
}
```

---

### ❤️ Wishlist

#### Listar Wishlist

```http
GET /api/wishlist
Authorization: Bearer {token}
```

#### Adicionar à Wishlist

```http
POST /api/wishlist
Authorization: Bearer {token}
```

**Body:**
```json
{
  "product_id": 1
}
```

#### Remover da Wishlist

```http
DELETE /api/wishlist/{productId}
Authorization: Bearer {token}
```

---

### 🔄 Devoluções

#### Solicitar Devolução

```http
POST /api/returns
Authorization: Bearer {token}
```

**Body:**
```json
{
  "order_id": 1,
  "item_id": 1,
  "reason": "Produto com defeito",
  "description": "A camiseta veio com um furo na região do ombro",
  "images": ["base64_image..."]
}
```

---

## 🚀 Guia de Deployment

### Pré-requisitos

- [Docker Engine](https://docs.docker.com/engine/install/) 24.0+
- [Docker Compose](https://docs.docker.com/compose/install/) v2.20+
- Conta no [Stripe](https://stripe.com/br) (pagamentos)
- Conta no [Pagar.me](https://pagar.me/) (opcional, pagamentos BR)
- Conta no [Bling](https://bling.com.br/) (NF-e)
- Bucket S3 na AWS (imagens - opcional)

---

### Instalação Rápida

#### 1. Clone o repositório

```bash
git clone https://github.com/GabrielSouzaCruz/roupasbr-final-estudo.git
cd roupasbr-final-estudo
```

#### 2. Configure as variáveis de ambiente

```bash
# Backend
cp backend/.env.example backend/.env

# Frontend
cp frontend/.env.local.example frontend/.env.local
```

#### 3. Edite o arquivo `backend/.env`

```env
APP_NAME="RoupasBR"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=roupasbr
DB_USERNAME=roupasbr
DB_PASSWORD=securepass2026

# Stripe
STRIPE_SECRET_KEY=sk_test_sua_chave_aqui
STRIPE_WEBHOOK_SECRET=whsec_seu_webhook_secret

# AWS (opcional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=sa-east-1
AWS_BUCKET=roupasbr-images

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
```

#### 4. Suba os containers

```bash
docker-compose up -d --build
```

#### 5. Instale as dependências do backend

```bash
docker exec roupasbr_backend composer install
```

#### 6. Configure o Laravel

```bash
# Gerar chave da aplicação
docker exec roupasbr_backend php artisan key:generate

# Executar migrations e seeders
docker exec roupasbr_backend php artisan migrate --seed

# Criar link simbólico para storage
docker exec roupasbr_backend php artisan storage:link
```

#### 7. Acesse a aplicação

| Serviço | URL |
|---------|-----|
| Frontend | http://localhost:3000 |
| Backend API | http://localhost:8000/api |
| Admin Panel | http://localhost:8000/admin |

---

### Configuração para Produção

#### Variáveis de Ambiente Obrigatórias

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seudominio.com.br
SANCTUM_STATEFUL_DOMAINS=seudominio.com.br
```

#### Configuração do Nginx (SSL)

```nginx
server {
    listen 443 ssl http2;
    server_name seudominio.com.br;

    ssl_certificate /etc/letsencrypt/live/seudominio.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seudominio.com.br/privkey.pem;

    location / {
        proxy_pass http://frontend:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    location /api {
        proxy_pass http://backend:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

#### Comandos de Otimização

```bash
# Otimizar autoloader
docker exec roupasbr_backend composer install --optimize-autoloader --no-dev

# Cache de configuração
docker exec roupasbr_backend php artisan config:cache
docker exec roupasbr_backend php artisan route:cache
docker exec roupasbr_backend php artisan view:cache

# Otimizar frontend
docker exec roupasbr_frontend npm run build
```

---

## 🔌 Serviços e Integrações

### Stripe

#### Configuração

1. Crie uma conta em [stripe.com/br](https://stripe.com/br)
2. Acesse Dashboard → Developers → API Keys
3. Copie a **Secret Key** para `STRIPE_SECRET_KEY`
4. Configure webhook em Dashboard → Developers → Webhooks
5. Adicione o endpoint: `https://seudominio.com.br/api/webhooks/stripe`
6. Copie o **Signing Secret** para `STRIPE_WEBHOOK_SECRET`

#### Eventos Suportados

- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `charge.refunded`

---

### Pagar.me

#### Configuração

```env
PAGARME_API_KEY=ak_test_sua_chave
PAGARME_ENCRYPTION_KEY=ek_test_sua_chave
```

#### Métodos de Pagamento

- **PIX**: QR Code dinâmico com expiração em 30 minutos
- **Boleto**: Vencimento em 3 dias úteis
- **Cartão**: Parcelamento em até 12x

---

### Bling ERP

#### Configuração

```env
BLING_API_KEY=sua_api_key
BLING_API_SECRET=sua_api_secret
```

#### Funcionalidades

- Emissão automática de NF-e
- Sincronização de estoque
- Atualização de pedidos

---

## ⚙️ Jobs Assíncronos

O sistema utiliza filas Redis para processamento assíncrono:

### EmitNfeJob

Emite Nota Fiscal Eletrônica através da API do Bling.

```php
// Executado automaticamente após confirmação do pagamento
dispatch(new EmitNfeJob($order));
```

### SendAbandonedCartEmail

Envia email de recuperação para carrinhos abandonados.

```php
// Agendado para executar a cada hora
Schedule::job(new SendAbandonedCartEmail)->hourly();
```

### Iniciar Worker de Filas

```bash
docker exec roupasbr_backend php artisan queue:work --daemon
```

---

## 🔒 Conformidade LGPD

O sistema está em conformidade com a Lei Geral de Proteção de Dados:

### Exportação de Dados

```http
GET /api/me/data-export
Authorization: Bearer {token}
```

**Response:**
```json
{
  "user": { ... },
  "addresses": [ ... ],
  "orders": [ ... ],
  "reviews": [ ... ],
  "exported_at": "2025-01-15T10:30:00Z"
}
```

### Direito ao Esquecimento

```http
POST /api/me/forget
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Seus dados foram removidos com sucesso"
}
```

### Atualização de Consentimento

```http
POST /api/me/consent
Authorization: Bearer {token}
```

**Body:**
```json
{
  "marketing_emails": true,
  "analytics_cookies": false,
  "third_party_sharing": false
}
```

---

## 🧪 Testes

### Backend (PHPUnit)

```bash
# Executar todos os testes
docker exec roupasbr_backend php artisan test

# Executar testes específicos
docker exec roupasbr_backend php artisan test --filter=ProductTest

# Executar com coverage
docker exec roupasbr_backend php artisan test --coverage
```

### Frontend (Cypress)

```bash
# Modo interativo
cd frontend
npx cypress open

# Modo headless
npx cypress run

# Teste específico
npx cypress run --spec "cypress/e2e/checkout.cy.ts"
```

---

## 📁 Estrutura do Projeto

```
roupasbr-final-estudo/
├── backend/                      # API Laravel
│   ├── app/
│   │   ├── Console/             # Comandos Artisan
│   │   ├── Filament/            # Painel Admin
│   │   │   ├── Pages/
│   │   │   └── Resources/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── ...
│   │   │   └── Middleware/
│   │   ├── Jobs/
│   │   │   ├── EmitNfeJob.php
│   │   │   └── SendAbandonedCartEmail.php
│   │   ├── Mail/
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Product.php
│   │   │   ├── Order.php
│   │   │   └── ...
│   │   ├── Providers/
│   │   └── Services/
│   │       ├── BlingService.php
│   │       ├── PaymentService.php
│   │       ├── ShippingService.php
│   │       └── Payment/
│   ├── config/
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   └── api.php
│   ├── tests/
│   ├── Dockerfile
│   └── composer.json
│
├── frontend/                     # Interface Next.js
│   ├── src/
│   │   ├── app/
│   │   │   ├── page.tsx         # Homepage
│   │   │   ├── layout.tsx       # Layout principal
│   │   │   ├── globals.css
│   │   │   └── conta/           # Área do cliente
│   │   ├── components/
│   │   │   ├── Header.tsx
│   │   │   ├── ProductCard.tsx
│   │   │   ├── ProductFilters.tsx
│   │   │   └── CookieConsent.tsx
│   │   ├── lib/                 # Utilitários
│   │   └── store/               # Zustand stores
│   │       ├── authStore.ts
│   │       └── cartStore.ts
│   ├── cypress/                  # Testes E2E
│   ├── Dockerfile
│   ├── package.json
│   ├── tailwind.config.js
│   ├── tsconfig.json
│   └── next.config.js
│
├── infra/                        # Infraestrutura
│   └── nginx/
│       └── nginx.conf
│
├── docs/                         # Documentação
│   └── README.md
│
├── load-test/                    # Testes de carga
├── docker-compose.yml
├── iniciar.ps1                   # Script de inicialização
├── setup-backend-code.ps1
├── setup-frontend-code.ps1
└── .gitignore
```

---

## 🤝 Contribuição

Contribuições são bem-vindas! Por favor, siga os passos abaixo:

1. Faça um **fork** do projeto
2. Crie uma **branch** para sua feature (`git checkout -b feature/NovaFeature`)
3. Faça **commit** das suas mudanças (`git commit -m 'Add: NovaFeature'`)
4. Faça **push** para a branch (`git push origin feature/NovaFeature`)
5. Abra um **Pull Request**

### Padrões de Código

- **PHP**: PSR-12
- **TypeScript**: ESLint + Prettier
- **Commits**: Conventional Commits

---

## 📄 Licença

Este projeto está licenciado sob a **MIT License** - veja o arquivo [LICENSE](LICENSE) para detalhes.

```
MIT License

Copyright (c) 2025 Gabriel de Souza Cruz

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 👤 Autor

**Gabriel de Souza Cruz**

- GitHub: [@GabrielSouzaCruz](https://github.com/GabrielSouzaCruz)
- Repositório: [roupasbr-final-estudo](https://github.com/GabrielSouzaCruz/roupasbr-final-estudo)

---

## 📊 Status do Projeto

| Métrica | Status |
|---------|--------|
| Linguagem Principal | PHP (55.2%) |
| PowerShell | 24.1% |
| TypeScript | 16.0% |
| JavaScript | 2.6% |
| Blade | 1.8% |
| Dockerfile | 0.6% |

---

<p align="center">
  Desenvolvido com ❤️ por Gabriel de Souza Cruz
</p>

<p align="center">
  <a href="#-sumário">⬆️ Voltar ao topo</a>
</p>

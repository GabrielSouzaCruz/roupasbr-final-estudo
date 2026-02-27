# setup-backend-corrigido.ps1 - Backend Laravel 11 com Segurança Production

Write-Host "🔒 Criando Backend Laravel 11 - Production Ready..." -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Green

# ==================== USER MODEL ====================
@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function defaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
'@ | Out-File -FilePath "backend/app/Models/User.php" -Encoding UTF8

Write-Host "✅ app/Models/User.php criado" -ForegroundColor Green

# ==================== CPF VALIDATION RULE ====================
New-Item -ItemType Directory -Force -Path "backend/app/Rules" | Out-Null

@'
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidCpf($value)) {
            $fail('O CPF informado é inválido.');
        }
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}
'@ | Out-File -FilePath "backend/app/Rules/CpfRule.php" -Encoding UTF8

Write-Host "✅ app/Rules/CpfRule.php criado" -ForegroundColor Green

# ==================== AUTH CONTROLLER ====================
@'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'cpf' => ['nullable', 'string', 'size:11', new CpfRule()],
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'cpf' => $request->cpf,
            'phone' => $request->phone,
            'role' => 'customer', // FORÇADO - previne privilege escalation
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);

        // Cookie HttpOnly + Secure
        $response->cookie('auth_token', $token, 60*24*7, '/', null, true, true, false, 'Strict');

        return $response;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);

        $response->cookie('auth_token', $token, 60*24*7, '/', null, true, true, false, 'Strict');

        return $response;
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        $response = response()->json(['message' => 'Logout realizado com sucesso']);
        $response->withCookie(cookie()->forget('auth_token'));
        
        return $response;
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
'@ | Out-File -FilePath "backend/app/Http/Controllers/Api/AuthController.php" -Encoding UTF8

Write-Host "✅ Controllers/Api/AuthController.php criado" -ForegroundColor Green

# ==================== ORDER CONTROLLER COM IDEMPOTÊNCIA ====================
@'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'nullable|string',
            'items.*.color' => 'nullable|string',
            'payment_method' => 'required|in:credit_card,pix,boleto',
            'payment_data' => 'required|array',
        ]);

        // Idempotência - previne pedidos duplicados
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Cache::has("order_{$idempotencyKey}")) {
            return Cache::get("order_{$idempotencyKey}");
        }

        return DB::transaction(function () use ($request, $idempotencyKey) {
            $user = $request->user();
            $items = $request->items;
            $subtotal = 0;
            $validatedItems = [];

            foreach ($items as $item) {
                // BLOQUEIO PESSIMISTA - previne race condition no estoque
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if (!$product->hasStock($item['quantity'])) {
                    throw new \Exception("Estoque insuficiente para {$product->name}");
                }

                $unitPrice = $product->price;
                $total = $unitPrice * $item['quantity'];
                $subtotal += $total;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $shippingCost = $subtotal > 299 ? 0 : 25.90;
            $total = $subtotal + $shippingCost;

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount' => 0,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
            ]);

            foreach ($validatedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);

                $item['product']->decreaseStock($item['quantity']);
            }

            $response = response()->json([
                'order' => $order->load('items', 'address'),
                'payment' => ['status' => 'pending'],
            ], 201);

            // Cache para idempotência (30 minutos)
            if ($idempotencyKey) {
                Cache::put("order_{$idempotencyKey}", $response, now()->addMinutes(30));
            }

            return $response;
        });
    }

    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product', 'address')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, string $orderNumber)
    {
        $order = $request->user()
            ->orders()
            ->where('order_number', $orderNumber)
            ->with('items.product', 'address')
            ->firstOrFail();

        return response()->json($order);
    }
}
'@ | Out-File -FilePath "backend/app/Http/Controllers/Api/OrderController.php" -Encoding UTF8

Write-Host "✅ Controllers/Api/OrderController.php criado" -ForegroundColor Green

# ==================== PAYMENT GATEWAY INTERFACE ====================
New-Item -ItemType Directory -Force -Path "backend/app/Services/Payments" | Out-Null

@'
<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function processPayment(Order $order, array $data, User $user): array;
    
    public function verifyWebhookSignature(Request $request): bool;
    
    public function handleWebhook(array $payload): array;
}
'@ | Out-File -FilePath "backend/app/Services/Payments/PaymentGatewayInterface.php" -Encoding UTF8

Write-Host "✅ Services/Payments/PaymentGatewayInterface.php criado" -ForegroundColor Green

# ==================== STRIPE GATEWAY ====================
@'
<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StripeGateway implements PaymentGatewayInterface
{
    protected $apiKey;
    protected $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret_key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
    }

    public function processPayment(Order $order, array $data, User $user): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/payment_intents', [
            'amount' => (int) ($order->total * 100),
            'currency' => 'brl',
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $paymentData = $response->json();

        return [
            'type' => 'credit_card',
            'client_secret' => $paymentData['client_secret'],
            'payment_intent_id' => $paymentData['id'],
            'status' => 'requires_confirmation',
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $sigHeader = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function handleWebhook(array $payload): array
    {
        $eventType = $payload['type'];
        
        if ($eventType === 'payment_intent.succeeded') {
            $orderId = $payload['data']['object']['metadata']['order_id'];
            $paymentId = $payload['data']['object']['id'];
            
            $order = Order::find($orderId);
            if ($order) {
                $order->markAsPaid($paymentId, $payload['data']['object']);
            }
            
            return ['status' => 'success', 'order_id' => $orderId];
        }

        return ['status' => 'ignored'];
    }
}
'@ | Out-File -FilePath "backend/app/Services/Payments/StripeGateway.php" -Encoding UTF8

Write-Host "✅ Services/Payments/StripeGateway.php criado" -ForegroundColor Green

# ==================== PAGAR.ME GATEWAY ====================
@'
<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PagarmeGateway implements PaymentGatewayInterface
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.pagarme.api_key');
    }

    public function processPayment(Order $order, array $data, User $user): array
    {
        return match ($data['type'] ?? 'pix') {
            'pix' => $this->processPix($order, $data, $user),
            'boleto' => $this->processBoleto($order, $data, $user),
            default => $this->processPix($order, $data, $user),
        };
    }

    private function processPix(Order $order, array $data, User $user): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type' => 'application/json',
        ])->post('https://api.pagar.me/core/v5/payments', [
            'amount' => (int) ($order->total * 100),
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'document' => $user->cpf,
            ],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $paymentData = $response->json();

        return [
            'type' => 'pix',
            'qr_code' => $paymentData['pix_qr_code'] ?? null,
            'qr_code_base64' => $paymentData['pix_qr_code_base64'] ?? null,
            'expires_at' => $paymentData['expires_at'] ?? null,
            'payment_id' => $paymentData['id'],
        ];
    }

    private function processBoleto(Order $order, array $data, User $user): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type' => 'application/json',
        ])->post('https://api.pagar.me/core/v5/payments', [
            'amount' => (int) ($order->total * 100),
            'currency' => 'BRL',
            'payment_method' => 'bank_transfer',
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'document' => $user->cpf,
            ],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $paymentData = $response->json();

        return [
            'type' => 'boleto',
            'barcode' => $paymentData['boleto']['barcode'] ?? null,
            'boleto_url' => $paymentData['boleto']['url'] ?? null,
            'expires_at' => $paymentData['boleto']['expires_at'] ?? null,
            'payment_id' => $paymentData['id'],
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $this->apiKey);
        
        return hash_equals($expectedSignature, $signature);
    }

    public function handleWebhook(array $payload): array
    {
        $status = $payload['status'] ?? null;
        
        if ($status === 'paid') {
            $orderId = $payload['metadata']['order_id'] ?? null;
            $paymentId = $payload['id'] ?? null;
            
            $order = Order::find($orderId);
            if ($order) {
                $order->markAsPaid($paymentId, $payload);
            }
            
            return ['status' => 'success', 'order_id' => $orderId];
        }

        return ['status' => 'ignored'];
    }
}
'@ | Out-File -FilePath "backend/app/Services/Payments/PagarmeGateway.php" -Encoding UTF8

Write-Host "✅ Services/Payments/PagarmeGateway.php criado" -ForegroundColor Green

# ==================== PAYMENT SERVICE (STRATEGY) ====================
@'
<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;

class PaymentService
{
    protected $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function setGateway(PaymentGatewayInterface $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function processPayment(Order $order, array $data, User $user): array
    {
        return $this->gateway->processPayment($order, $data, $user);
    }

    public function verifyWebhookSignature($request): bool
    {
        return $this->gateway->verifyWebhookSignature($request);
    }

    public function handleWebhook(array $payload): array
    {
        return $this->gateway->handleWebhook($payload);
    }
}
'@ | Out-File -FilePath "backend/app/Services/Payments/PaymentService.php" -Encoding UTF8

Write-Host "✅ Services/Payments/PaymentService.php criado" -ForegroundColor Green

# ==================== WEBHOOK CONTROLLER ====================
@'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function stripe(Request $request)
    {
        if (!$this->gateway->verifyWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $result = $this->gateway->handleWebhook($payload);

        return response()->json($result);
    }

    public function pagarme(Request $request)
    {
        if (!$this->gateway->verifyWebhookSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $result = $this->gateway->handleWebhook($payload);

        return response()->json($result);
    }
}
'@ | Out-File -FilePath "backend/app/Http/Controllers/Api/WebhookController.php" -Encoding UTF8

Write-Host "✅ Controllers/Api/WebhookController.php criado" -ForegroundColor Green

# ==================== API ROUTES COM RATE LIMIT ====================
@'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\WebhookController;

// Webhooks (sem autenticação)
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
Route::post('/webhooks/pagarme', [WebhookController::class, 'pagarme']);

// Auth com rate limiting agressivo (5 req/min)
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Produtos públicos
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/categories', [ProductController::class, 'categories']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Rotas protegidas com Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::apiResource('addresses', AddressController::class);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
});
'@ | Out-File -FilePath "backend/routes/api.php" -Encoding UTF8

Write-Host "✅ routes/api.php criado" -ForegroundColor Green

# ==================== BACKEND DOCKERFILE (NON-ROOT) ====================
@'
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Criar usuário não-root
RUN useradd -m -u 1001 appuser && chown -R appuser:appuser /var/www/html

USER appuser

RUN composer install --optimize-autoloader --no-dev 2>$null || true
RUN php artisan key:generate --force 2>$null || true

EXPOSE 9000
CMD ["php-fpm"]
'@ | Out-File -FilePath "backend/Dockerfile" -Encoding UTF8

Write-Host "✅ backend/Dockerfile (non-root) atualizado" -ForegroundColor Green

# ==================== DOCKER-COMPOSE COM HEALTHCHECKS ====================
@'
version: '3.8'

services:
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: roupasbr_backend
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./backend:/var/www/html
    networks:
      - roupasbr_network
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:9000"]
      interval: 30s
      timeout: 10s
      retries: 3

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: roupasbr_frontend
    restart: unless-stopped
    ports:
      - "3000:3000"
    networks:
      - roupasbr_network
    environment:
      - NEXT_PUBLIC_API_URL=http://backend:8000

  nginx:
    image: nginx:alpine
    container_name: roupasbr_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./infra/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./backend/storage/app/public:/var/www/html/storage:ro
    networks:
      - roupasbr_network
    depends_on:
      - backend
      - frontend

  db:
    image: mysql:8.0
    container_name: roupasbr_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: roupasbr
      MYSQL_USER: roupasbr
      MYSQL_PASSWORD: securepass2026
      MYSQL_ROOT_PASSWORD: rootsecure2026
    volumes:
      - mysql_/var/lib/mysql
    networks:
      - roupasbr_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:alpine
    container_name: roupasbr_redis
    restart: unless-stopped
    networks:
      - roupasbr_network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  worker:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: roupasbr_worker
    restart: unless-stopped
    working_dir: /var/www/html
    command: php artisan queue:work --sleep=3 --tries=3
    volumes:
      - ./backend:/var/www/html
    networks:
      - roupasbr_network
    depends_on:
      - db
      - redis

networks:
  roupasbr_network:
    driver: bridge

volumes:
  mysql_data:
  redis_
'@ | Out-File -FilePath "docker-compose.yml" -Encoding UTF8

Write-Host "✅ docker-compose.yml com healthchecks atualizado" -ForegroundColor Green

# ==================== NGINX COM SECURITY HEADERS ====================
@'
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Security Headers
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://js.stripe.com;" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/javascript image/svg+xml;

    server {
        listen 80;
        server_name _;

        location / {
            proxy_pass http://frontend:3000;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }

        location /api {
            proxy_pass http://backend:8000;
            proxy_http_version 1.1;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        }

        location /storage {
            alias /var/www/html/storage/app/public;
            expires 30d;
            add_header Cache-Control "public, immutable";
        }
    }
}
'@ | Out-File -FilePath "infra/nginx/nginx.conf" -Encoding UTF8

Write-Host "✅ infra/nginx/nginx.conf com security headers atualizado" -ForegroundColor Green

Write-Host ""
Write-Host "========================================================" -ForegroundColor Green
Write-Host "✅ Backend corrigido criado com sucesso!" -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Green
Write-Host ""
Write-Host "🔒 Melhorias de Segurança Incluídas:" -ForegroundColor Yellow
Write-Host "   ✅ Validação de CPF real" -ForegroundColor Cyan
Write-Host "   ✅ Cookie HttpOnly + Secure" -ForegroundColor Cyan
Write-Host "   ✅ Rate limiting (5 req/min)" -ForegroundColor Cyan
Write-Host "   ✅ Bloqueio pessimista no estoque" -ForegroundColor Cyan
Write-Host "   ✅ Idempotência de pedidos" -ForegroundColor Cyan
Write-Host "   ✅ Webhook signature verification" -ForegroundColor Cyan
Write-Host "   ✅ Container non-root" -ForegroundColor Cyan
Write-Host "   ✅ Security headers no Nginx" -ForegroundColor Cyan
Write-Host ""
Write-Host "Agora execute:" -ForegroundColor Yellow
Write-Host "   .\setup-frontend-corrigido.ps1" -ForegroundColor White
Write-Host ""
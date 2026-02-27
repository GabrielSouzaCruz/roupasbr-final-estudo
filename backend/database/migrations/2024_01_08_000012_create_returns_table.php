# Load Testing com k6

## Instalação

```bash
# macOS
brew install k6

# Windows
winget install k6

# Linux
sudo apt-get install k6


---

## 📦 **SCRIPT 10: Política de Troca/Devolução**

```powershell
Write-Host "🔄 Configurando Política de Troca/Devolução..." -ForegroundColor Green

# Criar migration de returns
$conteudo = @'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['requested', 'approved', 'shipped_back', 'received', 'refunded', 'rejected'])->default('requested');
            $table->text('reason');
            $table->string('images')->nullable(); // JSON com URLs das fotos
            $table->string('qr_code_url')->nullable(); // Etiqueta de postagem
            $table->string('tracking_code')->nullable();
            $table->decimal('refund_amount', 10, 2);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};

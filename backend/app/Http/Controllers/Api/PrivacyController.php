<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivacyController extends Controller
{
    public function exportData(Request $request)
    {
        $user = $request->user();

        $data = [
            'user' => $user,
            'orders' => $user->orders()->with('items.product', 'address')->get(),
            'addresses' => $user->addresses()->get(),
            'reviews' => $user->reviews()->get(),
            'abandoned_carts' => \App\Models\AbandonedCart::where('user_id', $user->id)->get(),
            'created_at' => $user->created_at,
            'last_login' => $user->updated_at,
        ];

        return response()->json([
            'message' => 'Dados exportados com sucesso',
            'data' => $data,
        ]);
    }

    public function forgetMe(Request $request)
    {
        $user = $request->user();

        // Anonimizar dados pessoais
        $user->update([
            'name' => 'Usuário Excluído',
            'email' => 'deleted_' . $user->id . '@deleted.com',
            'cpf' => null,
            'phone' => null,
            'password' => bcrypt(uniqid()),
            'email_verified_at' => null,
        ]);

        // Manter pedidos por obrigação fiscal (5 anos)
        // Mas remover dados pessoais dos endereços
        $user->addresses()->update([
            'street' => 'Dados removidos por LGPD',
            'neighborhood' => 'Dados removidos por LGPD',
            'city' => 'Dados removidos por LGPD',
        ]);

        return response()->json([
            'message' => 'Seus dados pessoais foram anonimizados conforme LGPD',
        ]);
    }

    public function updateConsent(Request $request)
    {
        $request->validate([
            'marketing_emails' => 'boolean',
            'data_processing' => 'required|accepted',
        ]);

        $user = $request->user();
        
        // Salvar consentimentos (criar tabela user_consents se necessário)
        $user->update([
            // Adicionar campos na tabela users ou criar tabela separada
        ]);

        return response()->json([
            'message' => 'Consentimentos atualizados',
        ]);
    }
}

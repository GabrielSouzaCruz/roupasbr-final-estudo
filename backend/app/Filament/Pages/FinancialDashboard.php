<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class FinancialDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.financial-dashboard';
    protected static ?string $title = 'Dashboard Financeiro';

    public function getStats(): array
    {
        $today = now()->startOfDay();
        
        $revenueToday = DB::table('orders')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $today)
            ->sum('total');

        $revenueMonth = DB::table('orders')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        $ordersToday = DB::table('orders')
            ->where('created_at', '>=', $today)
            ->count();

        $ticketMedium = $ordersToday > 0 ? $revenueToday / $ordersToday : 0;

        $pendingOrders = DB::table('orders')
            ->where('status', 'pending')
            ->count();

        return [
            'revenue_today' => number_format($revenueToday, 2, ',', '.'),
            'revenue_month' => number_format($revenueMonth, 2, ',', '.'),
            'orders_today' => $ordersToday,
            'ticket_medium' => number_format($ticketMedium, 2, ',', '.'),
            'pending_orders' => $pendingOrders,
        ];
    }

    public function getSalesChartData(): array
    {
        $data = DB::table('orders')
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => date('d/m', strtotime($d)))->toArray(),
            'revenue' => $data->pluck('total')->map(fn($v) => (float) $v)->toArray(),
            'orders' => $data->pluck('count')->map(fn($v) => (int) $v)->toArray(),
        ];
    }
}

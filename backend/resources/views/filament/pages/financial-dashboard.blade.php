<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <x-filament::card>
            <div class="text-sm text-gray-500">Faturamento Hoje</div>
            <div class="text-2xl font-bold text-green-600">R$ {{ $this->getStats()['revenue_today'] }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm text-gray-500">Faturamento Mês</div>
            <div class="text-2xl font-bold text-green-600">R$ {{ $this->getStats()['revenue_month'] }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm text-gray-500">Pedidos Hoje</div>
            <div class="text-2xl font-bold">{{ $this->getStats()['orders_today'] }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm text-gray-500">Ticket Médio</div>
            <div class="text-2xl font-bold text-purple-600">R$ {{ $this->getStats()['ticket_medium'] }}</div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-sm text-gray-500">Pedidos Pendentes</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $this->getStats()['pending_orders'] }}</div>
        </x-filament::card>
    </div>

    <x-filament::card>
        <h3 class="text-lg font-bold mb-4">Vendas dos Últimos 30 Dias</h3>
        <canvas id="salesChart" width="400" height="100"></canvas>
    </x-filament::card>
</x-filament-panels::page>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($this->getSalesChartData()['labels']),
            datasets: [{
                label: 'Faturamento (R$)',
                data: @json($this->getSalesChartData()['revenue']),
                borderColor: '#9333ea',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

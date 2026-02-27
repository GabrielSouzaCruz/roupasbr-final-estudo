import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

export let options = {
    stages: [
        { duration: '2m', target: 50 },   // Ramp-up para 50 usuários
        { duration: '5m', target: 200 },  // Pico de 200 usuários (simula campanha)
        { duration: '3m', target: 200 },  // Mantém pico
        { duration: '2m', target: 0 },    // Ramp-down
    ],
    thresholds: {
        http_req_duration: ['p(95)<800'], // 95% das requisições < 800ms
        http_req_failed: ['rate<0.01'],   // Menos de 1% de erros
        errors: ['rate<0.1'],             // Menos de 10% de erros custom
    },
    ext: {
        loadimpact: {
            projectID: 12345,
            name: 'RoupasBR Checkout Load Test',
        },
    },
};

// Dados de teste
const testUsers = [
    { email: 'test1@example.com', password: 'password123', token: 'xxx' },
    { email: 'test2@example.com', password: 'password123', token: 'xxx' },
];

export default function () {
    const user = testUsers[Math.floor(Math.random() * testUsers.length)];
    
    // Simular checkout
    const payload = JSON.stringify({
        address_id: 1,
        items: [
            { product_id: 1, quantity: 1, size: 'M', color: 'preto' },
        ],
        payment_method: 'pix',
        payment_data: { type: 'pix' },
        coupon_code: '',
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${user.token}`,
            'Idempotency-Key': `test-${__VU}-${__ITER}`,
        },
    };

    const res = http.post(`${__ENV.HOST}/api/orders`, payload, params);
    
    const success = check(res, {
        'status 201': (r) => r.status === 201,
        'has order number': (r) => JSON.parse(r.body).order?.order_number !== undefined,
        'response time < 1s': (r) => r.timings.duration < 1000,
    });

    errorRate.add(!success);

    sleep(1);
}

export function handleSummary(data) {
    return {
        'stdout': textSummary(data, { indent: ' ', enableColors: true }),
        'load-test-results.json': JSON.stringify(data),
    };
}

function textSummary(data, options) {
    return `\n
=====================================
LOAD TEST SUMMARY
=====================================
Total Requests: ${data.metrics.http_reqs.values.count}
Failed Requests: ${data.metrics.http_req_failed.values.rate * 100}%
Avg Response Time: ${data.metrics.http_req_duration.values.avg.toFixed(0)}ms
95th Percentile: ${data.metrics.http_req_duration.values['p(95)'].toFixed(0)}ms
=====================================\n`;
}

import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    vus: 100,
    duration: '5m',
    thresholds: {
        http_req_duration: ['p(95)<500'],
    },
};

export default function () {
    const searches = ['camiseta', 'calça', 'vestido', 'preto', 'algodão'];
    const search = searches[Math.floor(Math.random() * searches.length)];
    
    const res = http.get(`${__ENV.HOST}/api/products?search=${search}`);
    
    check(res, {
        'status 200': (r) => r.status === 200,
        'has products': (r) => JSON.parse(r.body).data?.length >= 0,
    });

    sleep(0.5);
}

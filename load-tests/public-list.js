import http from 'k6/http';
import { sleep } from 'k6';
import { fetchPublicCsrf, publicListUrl } from './lib/laravel.js';

export const options = {
  scenarios: {
    public_list_reads: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: __ENV.RAMP_UP || '1m', target: Number(__ENV.TARGET_VUS || 200) },
        { duration: __ENV.HOLD || '3m', target: Number(__ENV.TARGET_VUS || 200) },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '20s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{name:GET public list}': ['p(95)<800'],
  },
};

export default function publicListRead() {
  const jar = http.jar();

  fetchPublicCsrf(jar);

  http.get(publicListUrl(), {
    jar,
    tags: { name: 'GET public list (repeat)' },
  });

  sleep(Number(__ENV.SLEEP || 1));
}

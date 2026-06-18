import http from 'k6/http';
import { check, sleep } from 'k6';
import { loginAsOwner, ownerListUrl } from './lib/laravel.js';

export const options = {
  scenarios: {
    owner_dashboard: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: __ENV.RAMP_UP || '1m', target: Number(__ENV.TARGET_VUS || 100) },
        { duration: __ENV.HOLD || '3m', target: Number(__ENV.TARGET_VUS || 100) },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '20s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{name:GET owner list dashboard}': ['p(95)<1200'],
  },
};

let authenticated = false;

export default function ownerDashboard() {
  const jar = http.cookieJar();

  if (! authenticated) {
    loginAsOwner(jar);
    authenticated = true;
  }

  const response = http.get(ownerListUrl(), {
    jar,
    tags: { name: 'GET owner list dashboard' },
  });

  check(response, {
    'owner dashboard is 200': (res) => res.status === 200,
    'owner dashboard renders submissions': (res) => res.body.includes('dua') || res.body.includes('Dua'),
  });

  sleep(Number(__ENV.SLEEP || 2));
}

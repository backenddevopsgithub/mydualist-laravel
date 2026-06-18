import http from 'k6/http';
import { check, sleep } from 'k6';
import {
  fetchPublicCsrf,
  loginAsOwner,
  ownerListUrl,
} from './lib/laravel.js';

export const options = {
  vus: Number(__ENV.VUS || 5),
  duration: __ENV.DURATION || '30s',
  thresholds: {
    http_req_failed: ['rate<0.02'],
    http_req_duration: ['p(95)<1500'],
  },
};

export default function smokeTest() {
  const publicJar = http.jar();
  const { response } = fetchPublicCsrf(publicJar);

  check(response, {
    'public page contains submit form': (res) => res.body.includes('submit-dua') || res.body.includes('Submit'),
  });

  sleep(1);

  const ownerJar = http.jar();
  loginAsOwner(ownerJar);

  const ownerResponse = http.get(ownerListUrl(), {
    jar: ownerJar,
    tags: { name: 'GET owner list dashboard' },
  });

  check(ownerResponse, {
    'owner dashboard is 200': (res) => res.status === 200,
  });

  sleep(1);
}

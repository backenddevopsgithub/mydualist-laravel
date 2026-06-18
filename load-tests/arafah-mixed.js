import http from 'k6/http';
import { check, sleep } from 'k6';
import {
  fetchPublicCsrf,
  loginAsOwner,
  ownerListUrl,
  publicListUrl,
  submitDua,
} from './lib/laravel.js';

const targetVus = Number(__ENV.TARGET_VUS || 500);

export const options = {
  scenarios: {
    public_reads: {
      executor: 'ramping-vus',
      startVUs: 0,
      exec: 'publicRead',
      stages: [
        { duration: '2m', target: Math.round(targetVus * 0.7) },
        { duration: '5m', target: Math.round(targetVus * 0.7) },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '30s',
    },
    owner_refreshes: {
      executor: 'ramping-vus',
      startVUs: 0,
      exec: 'ownerRefresh',
      stages: [
        { duration: '2m', target: Math.round(targetVus * 0.2) },
        { duration: '5m', target: Math.round(targetVus * 0.2) },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '30s',
    },
    public_writes: {
      executor: 'constant-arrival-rate',
      exec: 'publicWrite',
      rate: Number(__ENV.SUBMIT_RPS || 10),
      timeUnit: '1s',
      duration: '7m',
      preAllocatedVUs: 50,
      maxVUs: 200,
      startTime: '30s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.03'],
    http_req_duration: ['p(95)<2000'],
  },
};

let ownerAuthenticated = false;

export function publicRead() {
  const jar = http.jar();
  fetchPublicCsrf(jar);
  http.get(publicListUrl(), { jar, tags: { name: 'GET public list (mixed)' } });
  sleep(1);
}

export function ownerRefresh() {
  const jar = http.cookieJar();

  if (! ownerAuthenticated) {
    loginAsOwner(jar);
    ownerAuthenticated = true;
  }

  const response = http.get(ownerListUrl(), {
    jar,
    tags: { name: 'GET owner list dashboard (mixed)' },
  });

  check(response, {
    'owner dashboard is 200': (res) => res.status === 200,
  });

  sleep(2);
}

export function publicWrite() {
  const jar = http.jar();
  const { token } = fetchPublicCsrf(jar);

  if (! token) {
    return;
  }

  const response = submitDua(jar, token, __VU, __ITER);

  check(response, {
    'submission accepted': (res) => res.status === 302 || res.status === 200,
  });
}

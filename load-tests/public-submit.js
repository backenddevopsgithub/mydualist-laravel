import http from 'k6/http';
import { check, sleep } from 'k6';
import { fetchPublicCsrf, submitDua } from './lib/laravel.js';

export const options = {
  scenarios: {
    public_submissions: {
      executor: 'ramping-arrival-rate',
      startRate: Number(__ENV.START_RPS || 2),
      timeUnit: '1s',
      preAllocatedVUs: Number(__ENV.PREALLOCATED_VUS || 50),
      maxVUs: Number(__ENV.MAX_VUS || 200),
      stages: [
        { duration: __ENV.RAMP_UP || '1m', target: Number(__ENV.TARGET_RPS || 20) },
        { duration: __ENV.HOLD || '3m', target: Number(__ENV.TARGET_RPS || 20) },
        { duration: '30s', target: 0 },
      },
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.05'],
    'http_req_duration{name:POST public submission}': ['p(95)<2000'],
  },
};

export default function publicSubmission() {
  const jar = http.jar();
  const { token } = fetchPublicCsrf(jar);

  if (! token) {
    return;
  }

  const response = submitDua(jar, token, __VU, __ITER);

  check(response, {
    'submission accepted or redirected': (res) => res.status === 302 || res.status === 200,
    'not rate limited': (res) => res.status !== 429,
  });

  sleep(Number(__ENV.SLEEP || 0.5));
}

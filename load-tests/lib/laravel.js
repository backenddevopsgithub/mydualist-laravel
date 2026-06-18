import http from 'k6/http';
import { check } from 'k6';

const manifest = JSON.parse(open('./fixtures/manifest.json'));

export const config = {
  baseUrl: __ENV.BASE_URL || manifest.base_url,
  listSlug: __ENV.LIST_SLUG || manifest.list.slug,
  listId: manifest.list.id,
  ownerEmail: __ENV.OWNER_EMAIL || manifest.owner.email,
  ownerPassword: __ENV.OWNER_PASSWORD || manifest.owner.password,
  publicPath: __ENV.PUBLIC_PATH || manifest.list.public_path,
  ownerPath: __ENV.OWNER_PATH || manifest.list.owner_path,
};

export function publicListUrl() {
  return `${config.baseUrl}${config.publicPath}`;
}

export function ownerListUrl() {
  return `${config.baseUrl}${config.ownerPath}`;
}

export function submitUrl() {
  return `${config.baseUrl}/${config.listSlug}/submissions`;
}

export function csrfFromHtml(html) {
  const match = html.match(/name="csrf-token"\s+content="([^"]+)"/);

  return match ? match[1] : null;
}

export function defaultHeaders(extra = {}) {
  return {
    Accept: 'text/html,application/xhtml+xml',
    'X-Requested-With': 'XMLHttpRequest',
    ...extra,
  };
}

export function loginAsOwner(jar) {
  const loginPage = http.get(`${config.baseUrl}/login`, {
    jar,
    tags: { name: 'GET /login' },
  });

  check(loginPage, {
    'login page is 200': (response) => response.status === 200,
  });

  const token = csrfFromHtml(loginPage.body);

  if (! token) {
    return null;
  }

  const loginResponse = http.post(
    `${config.baseUrl}/login`,
    {
      _token: token,
      email: config.ownerEmail,
      password: config.ownerPassword,
    },
    {
      jar,
      tags: { name: 'POST /login' },
      headers: defaultHeaders({
        Referer: `${config.baseUrl}/login`,
      }),
    },
  );

  check(loginResponse, {
    'login succeeds': (response) => response.status === 302 || response.status === 200,
  });

  return token;
}

export function fetchPublicCsrf(jar) {
  const response = http.get(publicListUrl(), {
    jar,
    tags: { name: 'GET public list' },
  });

  check(response, {
    'public list is 200': (res) => res.status === 200,
  });

  return {
    response,
    token: csrfFromHtml(response.body),
  };
}

export function buildSubmissionPayload(token, vu, iteration) {
  const suffix = `${vu}-${iteration}-${Date.now()}`;

  return {
    _token: token,
    first_name: 'Load',
    last_name: `Tester${suffix}`,
    email: `loadtest-${suffix}@example.com`,
    gender: 'male',
    terms: '1',
    content: `Load test dua request ${suffix}`,
  };
}

export function submitDua(jar, token, vu, iteration) {
  return http.post(submitUrl(), buildSubmissionPayload(token, vu, iteration), {
    jar,
    tags: { name: 'POST public submission' },
    headers: defaultHeaders({
      Referer: publicListUrl(),
    }),
    redirects: 0,
  });
}

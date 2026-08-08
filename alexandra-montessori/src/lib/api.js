// Website form submissions post to the WordPress REST API.
// - On the WordPress build the app is served from the WP site root, so the
//   root-relative "/wp-json/..." path is same-origin and just works.
// - In local `npm run dev`, vite proxies "/wp-json" to the Local WP site
//   (see vite.config.js) so forms can be tested end-to-end from :5173.
export const AM_API_BASE = "/wp-json/am/v1";

// One stable token is kept by each mounted form until a successful submission.
// If the browser retries after a timeout, WordPress returns the original record
// instead of creating a duplicate.
export function createSubmissionKey() {
  if (globalThis.crypto?.randomUUID) {
    return globalThis.crypto.randomUUID();
  }
  const random = Math.random().toString(36).slice(2);
  return `am-${Date.now().toString(36)}-${random}-${random}`;
}

// POST a plain object as JSON to an am/v1 endpoint (e.g. "enquiry").
// Resolves with the parsed JSON on success; throws Error(message) otherwise.
export async function submitForm(path, data, idempotencyKey) {
  const res = await fetch(`${AM_API_BASE}/${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-AM-Idempotency-Key": idempotencyKey || createSubmissionKey(),
    },
    body: JSON.stringify(data),
  });
  if (!res.ok) {
    let message = "Sorry, something went wrong. Please try again, or call us.";
    try {
      const body = await res.json();
      if (body && body.message) message = body.message;
    } catch {
      /* non-JSON error response — keep the friendly default */
    }
    throw new Error(message);
  }
  return res.json();
}

// Form submissions post to the LDRN WordPress REST API.
//
// - On the WordPress build the app is served from the LDRN site root, so the
//   root-relative "/wp-json/..." path is same-origin and needs no config.
// - Under `npm run dev`, vite proxies "/wp-json" to the local LDRN WordPress
//   site (see vite.config.js) so forms can be exercised end to end.
//
// This namespace belongs to LDRN alone. It never points at another client's
// site, and no recipient address is ever chosen in the browser — WordPress
// resolves routing server-side from approved settings.
export const LDRN_API_BASE = "/ldrn/api";

/**
 * Whether the nursery has switched on server-side submissions.
 *
 * Until the client supplies the data controller, retention policy and
 * approved recipient addresses, the theme leaves this off and every form
 * stays a local check that stores and sends nothing. Defaulting to `false`
 * means a missing or malformed contract can never silently start collecting
 * personal data.
 */
export function submissionsEnabled() {
  if (typeof window === "undefined") return false;
  return window.ldrnData?.submissions?.enabled === true;
}

/** One stable token per mounted form, so a retry cannot create a duplicate. */
export function createSubmissionKey() {
  if (globalThis.crypto?.randomUUID) {
    return globalThis.crypto.randomUUID();
  }
  const random = Math.random().toString(36).slice(2);
  return `ldrn-${Date.now().toString(36)}-${random}-${random}`;
}

/**
 * POSTs a plain object as JSON to an ldrn/v1 endpoint (e.g. "enquiry").
 * Resolves with the parsed JSON on success; throws Error(message) otherwise.
 */
export async function submitForm(path, data, idempotencyKey) {
  return post(path, {
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
    idempotencyKey,
  });
}

/**
 * POSTs a job application, which carries a CV attachment.
 *
 * Sent as multipart so the file streams rather than being base64-inflated
 * into JSON. WordPress stores the attachment outside the public Media
 * Library — a candidate's CV must never be reachable by URL.
 */
export async function submitFormWithFile(path, formData, idempotencyKey) {
  // No Content-Type header: the browser sets it with the multipart boundary.
  return post(path, { body: formData, idempotencyKey });
}

async function post(path, { headers = {}, body, idempotencyKey }) {
  const response = await fetch(`${LDRN_API_BASE}/${path}`, {
    method: "POST",
    headers: {
      ...headers,
      "X-LDRN-Idempotency-Key": idempotencyKey || createSubmissionKey(),
    },
    body,
  });

  if (!response.ok) {
    let message =
      "Sorry, something went wrong. Please try again, or call the nursery.";
    try {
      const body = await response.json();
      if (body?.message) message = body.message;
    } catch {
      // Non-JSON error response — keep the friendly default.
    }
    throw new Error(message);
  }

  return response.json();
}

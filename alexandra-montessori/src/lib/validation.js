// Shared form-validation helpers used by every website form.
// Client-side only (no OTP / paid verification): format checks, trimming, a
// disposable-domain blocklist and common-typo detection, with clear messages.
// A browser cannot confirm an inbox truly exists — this catches malformed,
// throwaway and mistyped addresses, which is the achievable, zero-cost goal.

export const EMAIL_ERROR = "Please enter a valid email address.";
export const PHONE_ERROR = "Please enter a valid phone number.";
export const EMAIL_DISPOSABLE_ERROR =
  "Please use a permanent email address — temporary inboxes are not accepted.";

// Trim surrounding whitespace users often paste around an address.
export function normalizeEmail(value) {
  return String(value ?? "").trim();
}

// Pragmatic single-@ pattern: local part, dotted domain, real TLD, no spaces.
const EMAIL_RE_SIMPLE = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
const EMAIL_RE =
  /^[^\s@]+(?:\.[^\s@]+)*@[^\s@.]+(?:\.[^\s@]+)*\.[a-zA-Z]{2,}$/;

export function isValidEmail(value) {
  const email = normalizeEmail(value);
  if (!email || email.length > 254) return false;
  if (/\.\./.test(email)) return false; // no consecutive dots
  return EMAIL_RE_SIMPLE.test(email) && EMAIL_RE.test(email);
}

// Known throwaway / temporary email providers (deliberate junk).
const DISPOSABLE_DOMAINS = new Set([
  "mailinator.com",
  "tempmail.com",
  "temp-mail.org",
  "tempmailo.com",
  "10minutemail.com",
  "guerrillamail.com",
  "guerrillamail.info",
  "sharklasers.com",
  "yopmail.com",
  "trashmail.com",
  "getnada.com",
  "dispostable.com",
  "fakeinbox.com",
  "maildrop.cc",
  "throwawaymail.com",
  "mintemail.com",
  "mohmal.com",
  "emailondeck.com",
  "tempinbox.com",
  "spam4.me",
  "moakt.com",
  "33mail.com",
  "mailnesia.com",
  "tempr.email",
  "discard.email",
  "getairmail.com",
  "mailcatch.com",
]);

// Popular provider "bodies" (first label) used for typo suggestions.
const KNOWN_PROVIDERS = [
  "gmail",
  "googlemail",
  "yahoo",
  "hotmail",
  "outlook",
  "live",
  "icloud",
  "aol",
  "msn",
  "protonmail",
  "proton",
  "ymail",
  "btinternet",
  "sky",
];

// Clear-cut typos of the ".com" TLD (safe to auto-correct). Deliberately
// excludes real ccTLDs like ".co" and ".om".
const TLD_TYPOS = {
  con: "com",
  cim: "com",
  vom: "com",
  xom: "com",
  comm: "com",
  ocm: "com",
  cmo: "com",
  cpm: "com",
  cin: "com",
  coom: "com",
  ccom: "com",
};

// Optimal String Alignment distance — like Levenshtein but counts an adjacent
// transposition (e.g. "gmial" → "gmail") as a single edit, since swapped
// letters are the single most common email typo.
function osaDistance(a, b) {
  const m = a.length;
  const n = b.length;
  const d = Array.from({ length: m + 1 }, () => new Array(n + 1).fill(0));
  for (let i = 0; i <= m; i += 1) d[i][0] = i;
  for (let j = 0; j <= n; j += 1) d[0][j] = j;
  for (let i = 1; i <= m; i += 1) {
    for (let j = 1; j <= n; j += 1) {
      const cost = a[i - 1] === b[j - 1] ? 0 : 1;
      d[i][j] = Math.min(
        d[i - 1][j] + 1,
        d[i][j - 1] + 1,
        d[i - 1][j - 1] + cost,
      );
      if (i > 1 && j > 1 && a[i - 1] === b[j - 2] && a[i - 2] === b[j - 1]) {
        d[i][j] = Math.min(d[i][j], d[i - 2][j - 2] + 1);
      }
    }
  }
  return d[m][n];
}

// Returns a suggested corrected address when a high-confidence typo is found,
// else "". Targets popular-provider body typos + obvious .com TLD typos only,
// so arbitrary company domains are never flagged.
export function suggestEmailFix(value) {
  const email = normalizeEmail(value).toLowerCase();
  const at = email.lastIndexOf("@");
  if (at < 1) return "";
  const local = email.slice(0, at);
  const labels = email.slice(at + 1).split(".");
  if (labels.length < 2) return "";

  let changed = false;

  // TLD typo (last label).
  const tldIndex = labels.length - 1;
  if (TLD_TYPOS[labels[tldIndex]]) {
    labels[tldIndex] = TLD_TYPOS[labels[tldIndex]];
    changed = true;
  }

  // Provider body typo (first label) — only a distance-1 near-miss.
  const body = labels[0];
  if (!KNOWN_PROVIDERS.includes(body) && body.length >= 4) {
    let best = null;
    let bestD = 99;
    for (const provider of KNOWN_PROVIDERS) {
      const d = osaDistance(body, provider);
      if (d < bestD) {
        bestD = d;
        best = provider;
      }
    }
    if (bestD === 1 && best) {
      labels[0] = best;
      changed = true;
    }
  }

  const fixed = `${local}@${labels.join(".")}`;
  return changed && fixed !== email ? fixed : "";
}

function emailDomain(email) {
  return email.slice(email.lastIndexOf("@") + 1).toLowerCase();
}

// Returns "" when acceptable, else the error/suggestion message.
export function emailError(value, { required = true } = {}) {
  const email = normalizeEmail(value);
  if (!email) return required ? EMAIL_ERROR : "";
  if (!isValidEmail(email)) return EMAIL_ERROR;
  if (DISPOSABLE_DOMAINS.has(emailDomain(email))) return EMAIL_DISPOSABLE_ERROR;
  const fix = suggestEmailFix(email);
  if (fix) return `Please check your email — did you mean ${fix}?`;
  return "";
}

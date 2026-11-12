// Shared form-validation helpers.
//
// These are convenience checks for the visitor, not a security boundary: a
// browser cannot confirm that an inbox exists, and anything typed here can be
// forged. WordPress re-validates every field server-side before a submission
// is stored or emailed. Keep the rules here and in inc/ldrn-validation.php in
// step so a visitor never passes one and fails the other.

export const EMAIL_ERROR = "Please enter a valid email address.";
export const PHONE_ERROR = "Please enter a valid phone number.";
export const EMAIL_DISPOSABLE_ERROR =
  "Please use a permanent email address rather than a temporary inbox.";

// Single @, dotted domain, real TLD, no spaces.
const EMAIL_RE = /^[^\s@]+(?:\.[^\s@]+)*@[^\s@.]+(?:\.[^\s@]+)*\.[a-zA-Z]{2,}$/;

// Known throwaway providers. An enquiry sent from one of these cannot be
// answered, so it is worth stopping at the point of entry.
const DISPOSABLE_DOMAINS = new Set([
  "10minutemail.com",
  "dispostable.com",
  "guerrillamail.com",
  "maildrop.cc",
  "mailinator.com",
  "temp-mail.org",
  "tempmail.com",
  "throwawaymail.com",
  "yopmail.com",
]);

export function normalizeEmail(value) {
  return String(value ?? "").trim();
}

/** Returns "" when the address is acceptable, otherwise the error message. */
export function emailError(value, { required = true } = {}) {
  const email = normalizeEmail(value);
  if (!email) return required ? EMAIL_ERROR : "";
  if (email.length > 254 || /\.\./.test(email) || !EMAIL_RE.test(email)) {
    return EMAIL_ERROR;
  }
  const domain = email.slice(email.lastIndexOf("@") + 1).toLowerCase();
  if (DISPOSABLE_DOMAINS.has(domain)) return EMAIL_DISPOSABLE_ERROR;
  return "";
}

export const isValidEmail = (value) => emailError(value) === "";

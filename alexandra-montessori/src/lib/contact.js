// Contact link helpers.

// Gmail compose in a new tab — opens a ready-to-write email straight in Gmail
// (with the address pre-filled) instead of launching the OS default app such
// as Outlook. Use with target="_blank" rel="noopener noreferrer".
export function gmailHref(email, subject = "", body = "") {
  const params = new URLSearchParams({ view: "cm", fs: "1", to: email });
  if (subject) params.set("su", subject);
  if (body) params.set("body", body);
  return `https://mail.google.com/mail/?${params.toString()}`;
}

// Normalise a display phone number ("0204 618 3477") into a tel: target.
export function telHref(phone) {
  return `tel:${phone.replace(/\s+/g, "")}`;
}

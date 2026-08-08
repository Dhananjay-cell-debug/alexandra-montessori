// Local-time date helpers for form date pickers (no past dates, T+2 rules).
// Uses the visitor's local calendar day, not UTC, so "today" is correct in the UK.

function toISO(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

// Today as YYYY-MM-DD (local).
export function todayISO() {
  return toISO(new Date());
}

// Today + n calendar days as YYYY-MM-DD (local). n may be negative.
export function addDaysISO(n) {
  const d = new Date();
  d.setDate(d.getDate() + n);
  return toISO(d);
}

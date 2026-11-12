/**
 * True when a content value is a `tbc()` placeholder rather than a confirmed
 * fact. Anything that renders a claim — copy, schema, contact routing — must
 * check this first so unverified values never reach the public page as if
 * they were approved.
 */
export const isTbc = (value) => !!value?.tbc;

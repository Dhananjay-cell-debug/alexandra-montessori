// Characters that would either close the surrounding <script> tag or break
// the JavaScript string when the JSON-LD payload is inlined into the page.
const UNSAFE = new RegExp("[<>&\u2028\u2029]", "g");
const escapeChar = (character) =>
  `\\u${character.charCodeAt(0).toString(16).padStart(4, "0")}`;

/**
 * Serialises a schema.org object for inlining in a
 * `<script type="application/ld+json">` tag without allowing markup to
 * escape the tag.
 */
export function safeJsonLd(value) {
  return JSON.stringify(value).replace(UNSAFE, escapeChar);
}

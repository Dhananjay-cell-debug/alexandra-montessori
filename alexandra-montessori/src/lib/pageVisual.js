// Builder annotations for pages other than Home.
//
// Home carries its own richer helper (`homeVisual.js`) because the React page
// reads Home's saved values directly. Every other route works the other way
// round: `site-runtime.js` writes saved values into the DOM after paint, so a
// page only has to say *which* nodes are editable and what to call them.
//
// Without an explicit annotation the runtime still finds elements, but it has
// to guess: keys become positional (`-text-3`) and labels come out as
// "Layout card 1". A positional key also moves the moment a paragraph is added
// above it, which silently reassigns a client's saved edit to the wrong
// element. Naming an element here fixes both.
//
// Keys must be unique within the page and stable for the life of the design.
// Prefer `<region>-<thing>`; never end a key with `-<type>-<number>`, which is
// the shape the runtime generates for anything left unnamed.

const TEXT_TYPES = ["text", "textarea", "button", "tabs"];

/**
 * @param {string} key      Stable identifier, e.g. "curriculum-philosophy-heading".
 * @param {string} label    Human name shown in the builder's element tree.
 * @param {string} type     text | textarea | button | image | logo | video | frame
 * @param {string} fallback Original copy, so the builder can offer a reset.
 */
export function editable(key, label, type = "text", fallback = "") {
  const props = {
    "data-am-vb-editable": key,
    "data-am-vb-edit-type": type,
    "data-am-vb-label": label,
    "data-am-vb-free-move": "true",
  };
  if (TEXT_TYPES.includes(type) && fallback) {
    props["data-am-vb-fallback"] = fallback;
  }
  return props;
}

/**
 * Annotation for an <Img>, which takes its builder props through `editable`
 * and adds the media class itself.
 */
export function editableImage(key, label, alt = "") {
  return editable(key, label || alt || "Photo", "image");
}

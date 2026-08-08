import { asset } from "./asset";

// Small runtime contract shared by the real Home page and the Local Visual
// Builder. WordPress supplies optional overrides; the React defaults remain the
// fallback so this bundle also works outside Local.
const model =
  typeof window !== "undefined" && window.amHomeDesign?.elements
    ? window.amHomeDesign.elements
    : {};

const homeModel =
  typeof window !== "undefined" && window.amHomeDesign
    ? window.amHomeDesign
    : {};

export function homeCollection(name) {
  return Array.isArray(homeModel.collections?.[name])
    ? homeModel.collections[name]
    : [];
}

export function homeCustomSections() {
  return Array.isArray(homeModel.customSections)
    ? homeModel.customSections
    : [];
}

export function homeElement(key) {
  return model[key] || {};
}

export function homeText(key, fallback) {
  const value = homeElement(key).value;
  return typeof value === "string" && value.trim() !== "" ? value : fallback;
}

export function homeMedia(key, fallbackSrc, fallbackAlt = "") {
  const item = homeElement(key);
  return {
    src:
      typeof item.src === "string" && item.src.trim() !== ""
        ? item.src
        : fallbackSrc,
    alt: typeof item.alt === "string" ? item.alt : fallbackAlt,
    srcSet: typeof item.srcSet === "string" ? item.srcSet : "",
  };
}

export function homeHref(key, fallback) {
  const value = homeElement(key).href;
  return typeof value === "string" && value.trim() !== "" ? value : fallback;
}

function hasSavedTransform(item) {
  return ["desktop", "tablet", "mobile"].some((device) => {
    const settings = item[device] || {};
    return (
      Number(settings.scale ?? 100) !== 100 ||
      Number(settings.offsetX ?? 0) !== 0 ||
      Number(settings.offsetY ?? 0) !== 0
    );
  });
}

function addSavedTransformStyles(style, item, prefix) {
  ["desktop", "tablet", "mobile"].forEach((device) => {
    const settings = item[device] || {};
    style[`--am-vb-saved-${prefix}-scale-${device}`] =
      Number(settings.scale ?? 100) / 100;
    style[`--am-vb-saved-${prefix}-offset-x-${device}`] =
      `${Number(settings.offsetX ?? 0)}px`;
    style[`--am-vb-saved-${prefix}-offset-y-${device}`] =
      `${Number(settings.offsetY ?? 0)}px`;
  });
}

export function editableTextProps(key, label, multiline = false, fallback = "") {
  const item = homeElement(key);
  const href = typeof item.href === "string" ? item.href.trim() : "";
  const linkDescription =
    typeof item.linkDescription === "string" ? item.linkDescription.trim() : "";
  const style = href ? { cursor: "pointer" } : {};
  ["desktop", "tablet", "mobile"].forEach((device) => {
    const settings = item[device] || {};
    style[`--am-element-scale-${device}`] = Number(settings.scale ?? 100) / 100;
    style[`--am-element-offset-x-${device}`] = `${Number(settings.offsetX ?? 0)}px`;
    style[`--am-element-offset-y-${device}`] = `${Number(settings.offsetY ?? 0)}px`;
  });
  addSavedTransformStyles(style, item, "element");
  const props = {
    "data-am-vb-editable": key,
    "data-am-vb-edit-type": multiline ? "textarea" : "text",
    "data-am-vb-label": label,
    "data-am-vb-fallback": fallback,
    "data-am-vb-free-move": "true",
    "data-am-vb-has-saved-transform": hasSavedTransform(item)
      ? "true"
      : undefined,
    style,
  };
  if (href) {
    props["data-am-vb-link-href"] = href;
    props.role = "link";
    props.tabIndex = 0;
    props.title = linkDescription || undefined;
    props.onClick = (event) => {
      if (typeof window === "undefined" || window.self !== window.top) return;
      if (event.currentTarget?.tagName === "A") return;
      event.preventDefault();
      event.stopPropagation();
      window.location.assign(href);
    };
    props.onKeyDown = (event) => {
      if (event.key !== "Enter" || typeof window === "undefined" || window.self !== window.top) return;
      event.preventDefault();
      window.location.assign(href);
    };
  }
  return props;
}

// A layout frame is a complete visual unit (for example a testimonial card).
// Its children can still be selected independently, while dragging empty space
// on the frame moves/resizes the whole unit.
export function editableFrameProps(key, label) {
  const item = homeElement(key);
  const style = {};
  ["desktop", "tablet", "mobile"].forEach((device) => {
    const settings = item[device] || {};
    style[`--am-element-scale-${device}`] = Number(settings.scale ?? 100) / 100;
    style[`--am-element-offset-x-${device}`] = `${Number(settings.offsetX ?? 0)}px`;
    style[`--am-element-offset-y-${device}`] = `${Number(settings.offsetY ?? 0)}px`;
  });
  addSavedTransformStyles(style, item, "element");
  return {
    "data-am-vb-editable": key,
    "data-am-vb-edit-type": "frame",
    "data-am-vb-free-move": "true",
    "data-am-vb-label": label,
    "data-am-vb-has-saved-transform": hasSavedTransform(item)
      ? "true"
      : undefined,
    style,
  };
}

export function editableMediaProps(
  key,
  label,
  mode = "crop",
  fallbackSrc = "",
  fallbackAlt = "",
) {
  const item = homeElement(key);
  const style = {
    "--am-media-position-x": `${Number(item.positionX ?? 50)}%`,
    "--am-media-position-y": `${Number(item.positionY ?? 50)}%`,
    "--am-vb-saved-media-position-x": `${Number(item.positionX ?? 50)}%`,
    "--am-vb-saved-media-position-y": `${Number(item.positionY ?? 50)}%`,
  };

  ["desktop", "tablet", "mobile"].forEach((device) => {
    const settings = item[device] || {};
    style[`--am-media-scale-${device}`] = Number(settings.scale ?? 100) / 100;
    style[`--am-media-offset-x-${device}`] = `${Number(settings.offsetX ?? 0)}px`;
    style[`--am-media-offset-y-${device}`] = `${Number(settings.offsetY ?? 0)}px`;
  });
  addSavedTransformStyles(style, item, "media");

  return {
    "data-am-vb-editable": key,
    "data-am-vb-edit-type": mode === "move" ? "logo" : "image",
    "data-am-vb-media-mode": mode,
    "data-am-vb-free-move": "true",
    "data-am-vb-has-saved-transform": hasSavedTransform(item)
      ? "true"
      : undefined,
    "data-am-vb-has-saved-media-position":
      Number(item.positionX ?? 50) !== 50 || Number(item.positionY ?? 50) !== 50
        ? "true"
        : undefined,
    "data-am-vb-label": label,
    "data-am-vb-fallback-src": asset(fallbackSrc),
    "data-am-vb-fallback-alt": fallbackAlt,
    style,
  };
}

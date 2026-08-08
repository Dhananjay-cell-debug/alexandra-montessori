import { createElement } from "react";

const preview = () =>
  typeof window !== "undefined" ? window.amVisualBuilderPreview : null;

function VisualElement({ element }) {
  const id = String(element.id || "").replace(/[^a-zA-Z0-9_-]/g, "");
  const type = element.type || "text";
  const className = `am-vb-node am-vb-node--${id} am-vb-node-type--${type}`;

  if (type === "text") {
    const tag = ["h1", "h2", "h3", "h4", "p", "div"].includes(element.tag)
      ? element.tag
      : "p";
    return createElement(tag, {
      className,
      dangerouslySetInnerHTML: { __html: element.content || "" },
    });
  }

  if (type === "button") {
    return (
      <a
        className={className}
        href={element.href || "#"}
        target={element.newWindow ? "_blank" : undefined}
        rel={element.newWindow ? "noopener" : undefined}
      >
        {element.label || "Button"}
      </a>
    );
  }

  if (type === "image") {
    if (!element.src) {
      return (
        <div className={`${className} am-vb-image-placeholder`}>
          <span>No image selected</span>
        </div>
      );
    }
    return (
      <img className={className} src={element.src} alt={element.alt || ""} />
    );
  }

  if (type === "shape") {
    return (
      <span
        aria-hidden="true"
        className={`${className} am-vb-shape--${element.shape || "rectangle"}`}
      />
    );
  }

  return (
    <div className={`${className} am-vb-unknown-node`}>
      <strong>Unsupported layer</strong>
      <span>{element.originalType || "unknown"}</span>
    </div>
  );
}

export default function VisualBuilderPreview() {
  const payload = preview();
  const document = payload?.document;

  if (!document) {
    return null;
  }

  return (
    <div className="am-vb-preview-body">
      <div className="am-vb-preview-notice">
        <span>
          <strong>{payload.label || "Local prototype preview"}</strong>
          {" — "}
          {payload.status || "draft"} content, not connected to the public website
        </span>
        <a href={payload.editUrl}>Back to editor</a>
      </div>
      <main className="am-vb-page am-vb-page--preview">
        {(document.sections || [])
          .filter((section) => section.visible !== false)
          .map((section) => {
          const id = String(section.id || "").replace(
            /[^a-zA-Z0-9_-]/g,
            "",
          );
          const layout = section.settings?.layout || "stack";
          const hiddenGroups = new Set(
            (section.groups || [])
              .filter((group) => group.visible === false)
              .map((group) => group.id),
          );
          return (
            <section
              className={`am-vb-section am-vb-section--${id} am-vb-layout--${layout}`}
              key={id}
            >
              {(section.elements || [])
                .filter(
                  (element) =>
                    element.visible !== false &&
                    !hiddenGroups.has(element.groupId),
                )
                .map((element) => (
                  <VisualElement element={element} key={element.id} />
                ))}
            </section>
          );
        })}
      </main>
    </div>
  );
}

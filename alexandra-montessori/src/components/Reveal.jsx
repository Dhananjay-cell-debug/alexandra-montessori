import { useEffect, useRef, useState } from "react";

// Lightweight scroll-reveal. Adds `.is-visible` to the `.reveal` element
// the first time it enters the viewport. Respects prefers-reduced-motion via CSS.
export default function Reveal({
  as: Tag = "div",
  delay = 0,
  className = "",
  children,
  ...rest
}) {
  const ref = useRef(null);
  const [state, setState] = useState(() => ({ armed: false, visible: true }));

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const reveal = () => setState({ armed: true, visible: true });
    // Start visible so above-fold content never waits for JavaScript. The
    // observer's first entry arms only elements that are genuinely below the
    // extended viewport; this avoids the synchronous getBoundingClientRect()
    // read that previously forced layout once for every Reveal instance.
    if (typeof IntersectionObserver === "undefined") {
      return undefined;
    }
    const obs = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            reveal();
            obs.unobserve(entry.target);
          } else {
            setState((current) =>
              current.armed ? current : { armed: true, visible: false },
            );
          }
        });
      },
      { threshold: 0, rootMargin: "120px 0px -10% 0px" },
    );
    obs.observe(el);
    return () => {
      obs.disconnect();
    };
  }, []);

  return (
    <Tag
      ref={ref}
      className={`reveal ${state.armed ? "reveal-ready" : ""} ${state.visible ? "is-visible" : ""} ${className}`}
      style={{ transitionDelay: `${delay}ms` }}
      {...rest}
    >
      {children}
    </Tag>
  );
}

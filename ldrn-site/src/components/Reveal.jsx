import { useEffect, useRef } from "react";

/**
 * Fades content up as it scrolls into view.
 *
 * `reveal-ready` is added from JavaScript rather than in the markup, so the
 * content is fully visible for crawlers, for print, and if the script never
 * runs. Visitors who prefer reduced motion skip the effect entirely.
 */
export default function Reveal({
  as: Tag = "div",
  className = "",
  delay = 0,
  children,
  ...rest
}) {
  const ref = useRef(null);

  useEffect(() => {
    const node = ref.current;
    if (!node) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    node.classList.add("reveal-ready");
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const timer = setTimeout(
            () => entry.target.classList.add("is-visible"),
            delay,
          );
          entry.target._revealTimer = timer;
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" },
    );

    observer.observe(node);
    return () => {
      clearTimeout(node._revealTimer);
      observer.disconnect();
    };
  }, [delay]);

  return (
    <Tag ref={ref} className={`reveal ${className}`} {...rest}>
      {children}
    </Tag>
  );
}

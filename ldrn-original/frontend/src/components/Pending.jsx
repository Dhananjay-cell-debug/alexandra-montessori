/**
 * Public-facing stand-in for a value the client has not confirmed. Says
 * plainly that the detail is coming rather than inventing one.
 */
export default function Pending({
  children = "Details to follow",
  className = "",
}) {
  return (
    <p className={`font-body text-sm italic text-ink/65 ${className}`}>
      {children}
    </p>
  );
}

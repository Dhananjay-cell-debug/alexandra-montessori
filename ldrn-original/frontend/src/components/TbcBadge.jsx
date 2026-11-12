import { useReviewMode } from "../lib/reviewMode";

/**
 * Amber marker naming the outstanding review question for an unconfirmed
 * value. Renders only in review mode, so the public page never shows it.
 */
export default function TbcBadge({ value, className = "" }) {
  const reviewMode = useReviewMode();
  if (!value?.tbc || !reviewMode) return null;

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-50/90 px-2 py-0.5 font-body text-[0.65rem] font-semibold uppercase tracking-wide text-amber-700 ${className}`}
      title={value.note || undefined}
    >
      {value.question}
    </span>
  );
}

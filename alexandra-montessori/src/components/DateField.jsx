import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from "react";
import { CalendarDays } from "lucide-react";

// A date field parents can actually type into.
//
// The native <input type="date"> only accepts segment-by-segment keystrokes and
// renders in the browser's locale (US visitors were shown mm/dd/yyyy on a UK
// nursery site). This keeps a plain text box the visitor can type DD/MM/YYYY
// into, and keeps the calendar button, which opens the real native picker.
//
// The value handed to the parent stays ISO YYYY-MM-DD, which is what the
// WordPress ingestion endpoint validates with a strict `!Y-m-d` format.

const DISPLAY_HINT = "DD/MM/YYYY";

function isoToDisplay(iso) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(iso || "")) return "";
  const [year, month, day] = iso.split("-");
  return `${day}/${month}/${year}`;
}

// Strict parse: rejects 31/02/2026 and other calendar-impossible dates.
function displayToISO(text) {
  const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(text).trim());
  if (!match) return "";
  const [, dd, mm, yyyy] = match;
  const day = Number(dd);
  const month = Number(mm);
  const year = Number(yyyy);
  const probe = new Date(year, month - 1, day);
  if (
    probe.getFullYear() !== year ||
    probe.getMonth() !== month - 1 ||
    probe.getDate() !== day
  ) {
    return "";
  }
  return `${yyyy}-${mm}-${dd}`;
}

const DateField = forwardRef(function DateField(
  {
    id,
    name,
    label,
    required = false,
    value = "",
    min = "",
    onChange,
    labelClassName = "mb-1.5 block text-sm font-medium text-sage-800",
    fieldClassName = "w-full rounded border border-sage-300 bg-white py-3 pl-4 pr-12 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600",
    className = "block",
  },
  ref,
) {
  const [text, setText] = useState(() => isoToDisplay(value));
  const [touched, setTouched] = useState(false);
  const textRef = useRef(null);
  const pickerRef = useRef(null);

  // Keep the typed text in step when the parent changes the value itself
  // (the end date is cleared when it falls before a newly chosen start date).
  useEffect(() => {
    setText((current) =>
      displayToISO(current) === value ? current : isoToDisplay(value),
    );
  }, [value]);

  const trimmed = text.trim();
  const parsed = displayToISO(trimmed);
  const isEmpty = trimmed === "";

  let errorMessage = "";
  if (required && isEmpty) {
    errorMessage = "Please add a date.";
  } else if (!isEmpty && !parsed) {
    errorMessage = `Please use ${DISPLAY_HINT}, for example 05/09/2026.`;
  } else if (parsed && min && parsed < min) {
    errorMessage = `Please choose ${isoToDisplay(min)} or later.`;
  }
  const showError = touched && !!errorMessage;

  const commit = (nextText) => {
    setText(nextText);
    const iso = displayToISO(nextText);
    const rejected = iso && min && iso < min;
    if (onChange) onChange(rejected ? "" : iso);
  };

  const handleChange = (event) => {
    const raw = event.target.value;
    let digits = raw.replace(/\D/g, "").slice(0, 8);
    // Backspacing onto a separator should eat the digit in front of it too,
    // otherwise re-formatting instantly puts the "/" back and the caret sticks.
    if (text.endsWith("/") && raw === text.slice(0, -1)) {
      digits = digits.slice(0, -1);
    }
    const parts = [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)];
    commit(parts.filter(Boolean).join("/"));
  };

  // The calendar button opens the browser's own picker. showPicker is available
  // in every current browser; if it refuses, typing is still the primary path.
  const openPicker = () => {
    const picker = pickerRef.current;
    if (picker && typeof picker.showPicker === "function") {
      try {
        picker.showPicker();
        return;
      } catch {
        /* fall through to the text box */
      }
    }
    if (textRef.current) textRef.current.focus();
  };

  useImperativeHandle(ref, () => ({
    validate() {
      setTouched(true);
      return !errorMessage;
    },
    reset() {
      setText("");
      setTouched(false);
      if (onChange) onChange("");
    },
    focus() {
      if (textRef.current) textRef.current.focus();
    },
  }));

  return (
    <div className={className}>
      <label htmlFor={id} className={labelClassName}>
        {label} {required ? "*" : ""}
      </label>
      <div className="relative">
        <input
          id={id}
          ref={textRef}
          type="text"
          inputMode="numeric"
          autoComplete="off"
          placeholder={DISPLAY_HINT}
          value={text}
          onChange={handleChange}
          onBlur={() => setTouched(true)}
          aria-invalid={showError || undefined}
          aria-describedby={showError ? `${id}-error` : undefined}
          className={fieldClassName}
        />
        {/* Rendered but invisible: showPicker() requires a rendered element,
            so this cannot be display:none. */}
        <input
          ref={pickerRef}
          type="date"
          tabIndex={-1}
          aria-hidden="true"
          value={parsed || ""}
          min={min || undefined}
          onChange={(event) => {
            setTouched(true);
            commit(isoToDisplay(event.target.value));
          }}
          className="pointer-events-none absolute right-2 top-1/2 h-0 w-0 -translate-y-1/2 opacity-0"
        />
        <button
          type="button"
          onClick={openPicker}
          aria-label={`Choose ${String(label).toLowerCase()} from a calendar`}
          className="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded text-sage-700 transition-colors hover:bg-sage-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-500"
        >
          <CalendarDays className="h-5 w-5" strokeWidth={1.8} />
        </button>
      </div>
      {/* Submitted value stays ISO for the WordPress endpoint. */}
      {name && <input type="hidden" name={name} value={parsed} />}
      {showError && (
        <p
          id={`${id}-error`}
          role="alert"
          className="mt-1.5 text-xs font-medium text-red-700"
        >
          {errorMessage}
        </p>
      )}
    </div>
  );
});

export default DateField;

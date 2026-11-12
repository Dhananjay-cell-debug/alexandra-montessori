import {
  forwardRef,
  useEffect,
  useImperativeHandle,
  useRef,
  useState,
} from "react";
import { CalendarDays } from "lucide-react";

const DISPLAY_FORMAT = "DD/MM/YYYY";

/** ISO (YYYY-MM-DD) to the UK display format. */
function toDisplay(iso) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(iso || "")) return "";
  const [year, month, day] = iso.split("-");
  return `${day}/${month}/${year}`;
}

/**
 * UK display format to ISO, rejecting dates that do not exist (31/02/2026
 * would otherwise roll forward into March).
 */
function toIso(display) {
  const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(display).trim());
  if (!match) return "";
  const [, day, month, year] = match;
  const date = new Date(Number(year), Number(month) - 1, Number(day));
  if (
    date.getFullYear() !== Number(year) ||
    date.getMonth() !== Number(month) - 1 ||
    date.getDate() !== Number(day)
  ) {
    return "";
  }
  return `${year}-${month}-${day}`;
}

/**
 * Date entry that types like a UK date and still submits ISO.
 *
 * A bare `<input type="date">` renders in the browser's locale, which
 * confuses UK parents entering a start date. So the visible control is a
 * masked text field, with a hidden native date input driving the calendar
 * picker button and a hidden field carrying the ISO value to the server.
 *
 * The parent form calls `validate()` through the ref before submitting.
 */
const DateField = forwardRef(function DateField(
  { id, name, label, required = false, value = "", min = "", max = "", onChange },
  ref,
) {
  const [display, setDisplay] = useState(() => toDisplay(value));
  const [touched, setTouched] = useState(false);
  const textRef = useRef(null);
  const pickerRef = useRef(null);

  // Accept an external value change without clobbering what the visitor is
  // part-way through typing.
  useEffect(() => {
    setDisplay((current) => (toIso(current) === value ? current : toDisplay(value)));
  }, [value]);

  const trimmed = display.trim();
  const iso = toIso(trimmed);
  const empty = trimmed === "";

  let error = "";
  if (required && empty) {
    error = "Please add a date.";
  } else if (!empty && !iso) {
    error = `Please use ${DISPLAY_FORMAT}.`;
  } else if (iso && min && iso < min) {
    error = `Please choose ${toDisplay(min)} or later.`;
  } else if (iso && max && iso > max) {
    error = "Please choose an earlier date.";
  }

  const showError = touched && !!error;

  const update = (nextDisplay) => {
    setDisplay(nextDisplay);
    const nextIso = toIso(nextDisplay);
    // Report an out-of-range date as empty so the parent never submits one.
    const outOfRange =
      nextIso && ((min && nextIso < min) || (max && nextIso > max));
    onChange?.(outOfRange ? "" : nextIso);
  };

  useImperativeHandle(ref, () => ({
    validate() {
      setTouched(true);
      return !error;
    },
    focus() {
      textRef.current?.focus();
    },
    reset() {
      setDisplay("");
      setTouched(false);
      onChange?.("");
    },
  }));

  return (
    <div>
      <label htmlFor={id} className="form-label">
        {label} {required && <span aria-hidden="true">*</span>}
      </label>

      <div className="relative">
        <input
          id={id}
          ref={textRef}
          type="text"
          inputMode="numeric"
          autoComplete="off"
          placeholder={DISPLAY_FORMAT}
          value={display}
          onChange={(event) => {
            let digits = event.target.value.replace(/\D/g, "").slice(0, 8);
            // Deleting the auto-inserted slash should remove the digit before
            // it, otherwise the separator reappears immediately.
            if (display.endsWith("/") && event.target.value === display.slice(0, -1)) {
              digits = digits.slice(0, -1);
            }
            update(
              [digits.slice(0, 2), digits.slice(2, 4), digits.slice(4, 8)]
                .filter(Boolean)
                .join("/"),
            );
          }}
          onBlur={() => setTouched(true)}
          aria-invalid={showError || undefined}
          aria-describedby={showError ? `${id}-error` : undefined}
          className="form-control pr-12"
        />

        {/* Native picker, kept out of the tab order and visually hidden; it
            exists purely so the calendar button has something to open. */}
        <input
          ref={pickerRef}
          type="date"
          tabIndex={-1}
          aria-hidden="true"
          value={iso || ""}
          min={min || undefined}
          max={max || undefined}
          onChange={(event) => {
            setTouched(true);
            update(toDisplay(event.target.value));
          }}
          className="pointer-events-none absolute right-2 top-1/2 h-0 w-0 -translate-y-1/2 opacity-0"
        />

        <button
          type="button"
          onClick={() => {
            try {
              pickerRef.current?.showPicker();
            } catch {
              // showPicker is unsupported or blocked; typing still works.
              textRef.current?.focus();
            }
          }}
          aria-label={`Choose ${String(label).toLowerCase()} from a calendar`}
          className="absolute right-1 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded text-sky-700 transition-colors hover:bg-sky-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
        >
          <CalendarDays className="h-5 w-5" strokeWidth={1.8} aria-hidden />
        </button>
      </div>

      {name && <input type="hidden" name={name} value={iso} />}

      {showError && (
        <p id={`${id}-error`} role="alert" className="form-error">
          {error}
        </p>
      )}
    </div>
  );
});

export default DateField;

/**
 * Labelled form control with hint and error text wired up for screen readers.
 *
 * Every form on the site uses this so that server-side validation errors
 * returned by WordPress can be rendered identically to client-side ones.
 */
export default function FormField({
  id,
  name = id,
  label,
  required = false,
  error = "",
  hint = "",
  as = "input",
  options = [],
  className = "",
  ...rest
}) {
  const describedBy = [hint ? `${id}-hint` : "", error ? `${id}-error` : ""]
    .filter(Boolean)
    .join(" ");

  const controlProps = {
    id,
    name,
    required,
    "aria-invalid": !!error || undefined,
    "aria-describedby": describedBy || undefined,
    className: `form-control ${className}`,
    ...rest,
  };

  return (
    <div>
      <label htmlFor={id} className="form-label">
        {label}{" "}
        {required && <span aria-hidden="true">*</span>}
      </label>

      {as === "textarea" ? (
        <textarea {...controlProps} />
      ) : as === "select" ? (
        <select {...controlProps}>
          <option value="">Select an option</option>
          {options.map((option) => {
            const value = typeof option === "string" ? option : option.value;
            const optionLabel =
              typeof option === "string" ? option : option.label;
            return (
              <option key={value} value={value}>
                {optionLabel}
              </option>
            );
          })}
        </select>
      ) : (
        <input {...controlProps} />
      )}

      {hint && (
        <p id={`${id}-hint`} className="form-hint">
          {hint}
        </p>
      )}
      {error && (
        <p id={`${id}-error`} role="alert" className="form-error">
          {error}
        </p>
      )}
    </div>
  );
}

import { forwardRef, useImperativeHandle, useMemo, useState } from "react";
import {
  AsYouType,
  getCountries,
  getCountryCallingCode,
  validatePhoneNumberLength,
} from "libphonenumber-js";
import { PHONE_ERROR } from "../lib/validation";

const regionNames = new Intl.DisplayNames(["en"], { type: "region" });

// Use the library's full numbering metadata rather than a hand-kept
// shortlist. The UK is pinned first for the nursery's main audience; every
// other territory follows alphabetically by its readable English name.
const COUNTRY_OPTIONS = getCountries()
  .map((iso) => ({
    iso,
    name: regionNames.of(iso) || iso,
    code: getCountryCallingCode(iso),
  }))
  .sort((a, b) => {
    if (a.iso === "GB") return -1;
    if (b.iso === "GB") return 1;
    return a.name.localeCompare(b.name, "en");
  });

const onlyDigits = (value) => String(value ?? "").replace(/[^\d]/g, "");

/** Formats as the visitor types and derives the E.164 value and validity. */
function analyse(country, typed) {
  const formatter = new AsYouType(country);
  const display = formatter.input(typed);
  const number = formatter.getNumber();
  const callingCode = getCountryCallingCode(country);
  return {
    display,
    e164: number
      ? number.number
      : typed
        ? `+${callingCode}${onlyDigits(typed)}`
        : "",
    valid: number ? number.isPossible() && number.isValid() : false,
  };
}

/**
 * Phone entry with a country selector.
 *
 * The visible field shows the number formatted for the chosen country; the
 * hidden field submits full international E.164 so the nursery team can dial
 * it straight from the admin inbox. The parent form calls `validate()`
 * through the ref before submitting.
 */
const PhoneField = forwardRef(function PhoneField(
  {
    id = "mobilePhone",
    name = "mobilePhone",
    label = "Mobile number",
    required = true,
    onValidityChange,
  },
  ref,
) {
  const [country, setCountry] = useState("GB");
  const [typed, setTyped] = useState("");
  const [touched, setTouched] = useState(false);

  const { display, e164, valid } = useMemo(
    () => analyse(country, typed),
    [country, typed],
  );

  const isEmpty = onlyDigits(typed).length === 0;
  const showError = touched && ((required && isEmpty) || (!isEmpty && !valid));

  const emitValidity = (nextCountry, nextTyped) => {
    if (!onValidityChange) return;
    onValidityChange(
      onlyDigits(nextTyped).length === 0
        ? !required
        : analyse(nextCountry, nextTyped).valid,
    );
  };

  useImperativeHandle(ref, () => ({
    validate() {
      setTouched(true);
      return required ? !isEmpty && valid : isEmpty || valid;
    },
    focus() {
      document.getElementById(id)?.focus();
    },
    reset() {
      setCountry("GB");
      setTyped("");
      setTouched(false);
    },
    value: e164,
  }));

  return (
    <div>
      <label htmlFor={id} className="form-label">
        {label} {required && <span aria-hidden="true">*</span>}
      </label>

      <div className="ldrn-phone-controls">
        <select
          aria-label={`${label} country dialling code`}
          value={country}
          onChange={(event) => {
            const next = event.target.value;
            setCountry(next);
            if (touched) emitValidity(next, typed);
          }}
          className="form-control"
        >
          {COUNTRY_OPTIONS.map((option) => (
            <option key={option.iso} value={option.iso}>
              {option.name} (+{option.code})
            </option>
          ))}
        </select>

        <input
          id={id}
          type="tel"
          inputMode="tel"
          autoComplete="tel"
          value={display}
          onChange={(event) => {
            const next = onlyDigits(event.target.value);
            // Reject a digit that would push the number past the maximum
            // length for the selected country.
            if (
              next.length > onlyDigits(typed).length &&
              validatePhoneNumberLength(next, country) === "TOO_LONG"
            ) {
              return;
            }
            setTyped(next);
            if (touched) emitValidity(country, next);
          }}
          onBlur={() => {
            setTouched(true);
            emitValidity(country, typed);
          }}
          aria-invalid={showError || undefined}
          aria-describedby={showError ? `${id}-error` : undefined}
          placeholder="Phone number"
          className="form-control"
        />
      </div>

      {/* Submitted value: full international E.164. */}
      <input type="hidden" name={name} value={e164} />

      {showError && (
        <p id={`${id}-error`} role="alert" className="form-error">
          {PHONE_ERROR}
        </p>
      )}
    </div>
  );
});

export default PhoneField;

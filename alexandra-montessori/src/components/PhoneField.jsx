import {
  forwardRef,
  useImperativeHandle,
  useMemo,
  useState,
} from "react";
import {
  AsYouType,
  getCountries,
  getCountryCallingCode,
  validatePhoneNumberLength,
} from "libphonenumber-js";
import { PHONE_ERROR } from "../lib/validation";

const regionNames = new Intl.DisplayNames(["en"], { type: "region" });

// Use the library's complete numbering metadata rather than a hand-maintained
// shortlist. UK stays first for the nursery's main audience; every other
// supported country/territory is alphabetical by its readable English name.
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

const SUPPORTED_COUNTRIES = new Set(COUNTRY_OPTIONS.map(({ iso }) => iso));

const onlyDigits = (value) => String(value ?? "").replace(/[^\d]/g, "");

// Build the E.164 value + validity for a given country + typed national number.
function analyse(country, typed) {
  const formatter = new AsYouType(country);
  const display = formatter.input(typed);
  const number = formatter.getNumber();
  const callingCode = getCountryCallingCode(country);
  return {
    display,
    e164: number ? number.number : typed ? `+${callingCode}${onlyDigits(typed)}` : "",
    valid: number ? number.isPossible() && number.isValid() : false,
  };
}

const PhoneField = forwardRef(function PhoneField(
  {
    name = "phone",
    id = "phone",
    label = "Phone",
    required = true,
    defaultCountry = "GB",
    className = "",
    fieldClassName = "w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600",
    labelClassName = "mb-1.5 block font-body text-sm font-medium text-sage-800",
    onValidityChange,
  },
  ref,
) {
  const initialCountry = SUPPORTED_COUNTRIES.has(defaultCountry)
    ? defaultCountry
    : "GB";
  const [country, setCountry] = useState(initialCountry);
  const [typed, setTyped] = useState(""); // raw national digits as typed
  const [touched, setTouched] = useState(false);

  const { display, e164, valid } = useMemo(
    () => analyse(country, typed),
    [country, typed],
  );

  const isEmpty = onlyDigits(typed).length === 0;
  const errorMessage =
    (required && isEmpty) || (!isEmpty && !valid) ? PHONE_ERROR : "";
  const showError = touched && !!errorMessage;

  const emitValidity = (nextValid) => {
    if (onValidityChange) onValidityChange(nextValid);
  };

  useImperativeHandle(ref, () => ({
    // Called by the parent form on submit. Forces the error to show and
    // returns whether the current value is acceptable.
    validate() {
      setTouched(true);
      return required ? !isEmpty && valid : isEmpty || valid;
    },
    reset() {
      setCountry(initialCountry);
      setTyped("");
      setTouched(false);
    },
    value: e164,
  }));

  const handleChange = (event) => {
    const raw = event.target.value;
    const nextDigits = onlyDigits(raw);
    // Prevent more digits than the selected country permits.
    if (
      nextDigits.length > onlyDigits(typed).length &&
      validatePhoneNumberLength(nextDigits, country) === "TOO_LONG"
    ) {
      return; // reject the extra digit
    }
    setTyped(nextDigits);
    if (touched) emitValidity(nextDigits ? analyse(country, nextDigits).valid : !required);
  };

  const handleCountry = (event) => {
    const nextCountry = event.target.value;
    setCountry(nextCountry);
    if (touched) {
      emitValidity(isEmpty ? !required : analyse(nextCountry, typed).valid);
    }
  };

  return (
    <div className={`am-phone-field ${className}`}>
      <label htmlFor={id} className={labelClassName}>
        {label} {required ? "*" : ""}
      </label>
      <div className="am-phone-controls">
        <select
          aria-label="Country dialling code"
          value={country}
          onChange={handleCountry}
          className="w-full min-w-0 rounded border border-sage-300 bg-white px-2 py-3 text-sm text-ink outline-none transition-colors focus:border-sage-600"
        >
          {COUNTRY_OPTIONS.map((c) => (
            <option key={c.iso} value={c.iso}>
              {c.name} (+{c.code})
            </option>
          ))}
        </select>
        <input
          id={id}
          type="tel"
          inputMode="tel"
          autoComplete="tel"
          value={display}
          onChange={handleChange}
          onBlur={() => {
            setTouched(true);
            emitValidity(isEmpty ? !required : valid);
          }}
          aria-invalid={showError || undefined}
          placeholder="Phone number"
          className={fieldClassName}
        />
      </div>
      {/* Submitted value: full international E.164 so the team can dial it. */}
      <input type="hidden" name={name} value={e164} />
      {showError && (
        <p role="alert" className="mt-1.5 text-xs font-medium text-red-700">
          {errorMessage}
        </p>
      )}
    </div>
  );
});

export default PhoneField;

<?php
/**
 * Shared server-side validation for public website forms.
 */

if (!defined('ABSPATH')) exit;

/**
 * Validate and normalize a phone number already submitted in E.164 format.
 *
 * Browsers send the value produced by libphonenumber-js. The server still
 * enforces the transport contract so a direct REST request cannot store junk.
 * E.164 permits a leading plus followed by 7-15 digits; the first digit of the
 * country calling code cannot be zero.
 *
 * @return string|WP_Error Normalized E.164 value, an empty optional value, or error.
 */
function am_validate_e164_phone($value, $required = false) {
  $phone = trim((string) $value);

  if ($phone === '') {
    return $required
      ? new WP_Error('missing_phone', 'A phone number with country code is required.', ['status' => 400])
      : '';
  }

  // Accept harmless display separators but always persist one canonical value.
  $phone = preg_replace('/[\s().-]+/', '', $phone);
  if (str_starts_with($phone, '00')) {
    $phone = '+' . substr($phone, 2);
  }

  if (!preg_match('/^\+[1-9][0-9]{6,14}$/', $phone)) {
    return new WP_Error(
      'invalid_phone',
      'Please enter a valid phone number and select its country code.',
      ['status' => 400]
    );
  }

  return $phone;
}

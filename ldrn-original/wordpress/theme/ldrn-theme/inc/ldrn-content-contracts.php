<?php
/**
 * Shared publish-readiness rules for wp-admin and frontend CMS getters.
 */

if (!defined('ABSPATH')) exit;

function ldrn_frontend_title($post) {
  return html_entity_decode(
    (string) get_the_title($post),
    ENT_QUOTES | ENT_HTML5,
    get_bloginfo('charset') ?: 'UTF-8'
  );
}

function ldrn_missing_text_field($value) {
  return trim((string) wp_strip_all_tags($value)) === '';
}

function ldrn_event_readiness_issues($post_id) {
  $post = get_post($post_id);
  if (!$post) return ['event record'];

  $issues = [];
  if (ldrn_missing_text_field($post->post_title)) $issues[] = 'title';
  if (ldrn_missing_text_field($post->post_excerpt)) $issues[] = 'card excerpt';
  if (ldrn_missing_text_field($post->post_content)) $issues[] = 'full description';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_event_date', true))) $issues[] = 'event date';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_event_time', true))) $issues[] = 'event time';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_event_location', true))) $issues[] = 'location';
  if (!has_post_thumbnail($post_id)) $issues[] = 'featured image';
  return $issues;
}

function ldrn_event_is_frontend_ready($post_id) {
  return ldrn_event_readiness_issues($post_id) === [];
}

function ldrn_blog_readiness_issues($post_id) {
  $post = get_post($post_id);
  if (!$post) return ['article record'];

  $issues = [];
  if (ldrn_missing_text_field($post->post_title)) $issues[] = 'title';
  if (ldrn_missing_text_field($post->post_content)) $issues[] = 'article body';
  return $issues;
}

function ldrn_blog_is_frontend_ready($post_id) {
  return ldrn_blog_readiness_issues($post_id) === [];
}

function ldrn_testimonial_readiness_issues($post_id) {
  $issues = [];
  if (ldrn_missing_text_field(get_the_title($post_id))) $issues[] = 'internal title';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_testimonial_quote', true))) $issues[] = 'quote';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_testimonial_name', true))) $issues[] = 'parent descriptor';
  return $issues;
}

function ldrn_testimonial_is_frontend_ready($post_id) {
  return ldrn_testimonial_readiness_issues($post_id) === [];
}

function ldrn_job_readiness_issues($post_id) {
  $issues = [];
  if (ldrn_missing_text_field(get_the_title($post_id))) $issues[] = 'role title';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_job_location', true))) $issues[] = 'nursery';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_job_type', true))) $issues[] = 'employment type';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_job_short', true))) $issues[] = 'summary';
  if (ldrn_missing_text_field(get_post_meta($post_id, '_ldrn_job_full', true))) $issues[] = 'full description';
  return $issues;
}

function ldrn_job_is_frontend_ready($post_id) {
  return ldrn_job_readiness_issues($post_id) === [];
}

function ldrn_setting_readiness_issues($post_id) {
  $post = get_post($post_id);
  if (!$post) return ['nursery record'];

  $fields = [
    '_ldrn_setting_address' => 'address',
    '_ldrn_setting_phone' => 'phone',
    '_ldrn_setting_email' => 'email',
    '_ldrn_setting_hours' => 'opening hours',
    '_ldrn_setting_age_range' => 'age range',
    '_ldrn_setting_short' => 'short description',
    '_ldrn_setting_welcome' => 'welcome text',
    '_ldrn_setting_hero_image' => 'hero image',
  ];
  $issues = [];
  if (ldrn_missing_text_field($post->post_title)) $issues[] = 'title';
  foreach ($fields as $key => $label) {
    if (ldrn_missing_text_field(get_post_meta($post_id, $key, true))) $issues[] = $label;
  }
  return $issues;
}

function ldrn_setting_is_frontend_ready($post_id) {
  return ldrn_setting_readiness_issues($post_id) === [];
}

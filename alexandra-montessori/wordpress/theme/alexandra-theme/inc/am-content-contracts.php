<?php
/**
 * Shared publish-readiness rules for wp-admin and frontend CMS getters.
 */

if (!defined('ABSPATH')) exit;

function am_frontend_title($post) {
  return html_entity_decode(
    (string) get_the_title($post),
    ENT_QUOTES | ENT_HTML5,
    get_bloginfo('charset') ?: 'UTF-8'
  );
}

function am_missing_text_field($value) {
  return trim((string) wp_strip_all_tags($value)) === '';
}

function am_event_readiness_issues($post_id) {
  $post = get_post($post_id);
  if (!$post) return ['event record'];

  $issues = [];
  if (am_missing_text_field($post->post_title)) $issues[] = 'title';
  if (am_missing_text_field($post->post_excerpt)) $issues[] = 'card excerpt';
  if (am_missing_text_field($post->post_content)) $issues[] = 'full description';
  if (am_missing_text_field(get_post_meta($post_id, '_am_event_date', true))) $issues[] = 'event date';
  if (am_missing_text_field(get_post_meta($post_id, '_am_event_time', true))) $issues[] = 'event time';
  if (am_missing_text_field(get_post_meta($post_id, '_am_event_location', true))) $issues[] = 'location';
  if (!has_post_thumbnail($post_id)) $issues[] = 'featured image';
  return $issues;
}

function am_event_is_frontend_ready($post_id) {
  return am_event_readiness_issues($post_id) === [];
}

function am_blog_readiness_issues($post_id) {
  $post = get_post($post_id);
  if (!$post) return ['article record'];

  $issues = [];
  if (am_missing_text_field($post->post_title)) $issues[] = 'title';
  if (am_missing_text_field($post->post_content)) $issues[] = 'article body';
  return $issues;
}

function am_blog_is_frontend_ready($post_id) {
  return am_blog_readiness_issues($post_id) === [];
}

function am_testimonial_readiness_issues($post_id) {
  $issues = [];
  if (am_missing_text_field(get_the_title($post_id))) $issues[] = 'internal title';
  if (am_missing_text_field(get_post_meta($post_id, '_am_testimonial_quote', true))) $issues[] = 'quote';
  if (am_missing_text_field(get_post_meta($post_id, '_am_testimonial_name', true))) $issues[] = 'parent descriptor';
  if (am_missing_text_field(get_post_meta($post_id, '_am_testimonial_location', true))) $issues[] = 'nursery location';
  return $issues;
}

function am_testimonial_is_frontend_ready($post_id) {
  return am_testimonial_readiness_issues($post_id) === [];
}

function am_job_readiness_issues($post_id) {
  $issues = [];
  if (am_missing_text_field(get_the_title($post_id))) $issues[] = 'role title';
  if (am_missing_text_field(get_post_meta($post_id, '_am_job_location', true))) $issues[] = 'nursery';
  if (am_missing_text_field(get_post_meta($post_id, '_am_job_type', true))) $issues[] = 'employment type';
  if (am_missing_text_field(get_post_meta($post_id, '_am_job_short', true))) $issues[] = 'summary';
  if (am_missing_text_field(get_post_meta($post_id, '_am_job_full', true))) $issues[] = 'full description';
  return $issues;
}

function am_job_is_frontend_ready($post_id) {
  return am_job_readiness_issues($post_id) === [];
}

function am_nursery_readiness_issues($post_id) {
  $post = get_post($post_id);
  if (!$post) return ['nursery record'];

  $fields = [
    '_am_nursery_address' => 'address',
    '_am_nursery_phone' => 'phone',
    '_am_nursery_email' => 'email',
    '_am_nursery_hours' => 'opening hours',
    '_am_nursery_age_range' => 'age range',
    '_am_nursery_short' => 'short description',
    '_am_nursery_welcome' => 'welcome text',
    '_am_nursery_hero_image' => 'hero image',
  ];
  $issues = [];
  if (am_missing_text_field($post->post_title)) $issues[] = 'title';
  foreach ($fields as $key => $label) {
    if (am_missing_text_field(get_post_meta($post_id, $key, true))) $issues[] = $label;
  }
  return $issues;
}

function am_nursery_is_frontend_ready($post_id) {
  return am_nursery_readiness_issues($post_id) === [];
}

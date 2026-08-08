<?php
/**
 * SPA shell for Alexandra Montessori.
 *
 * Outputs the #root mount node only; the React/Vite bundle (enqueued in
 * functions.php) renders the entire site and handles client-side routing.
 * This template is served for the front page and, as the 404 fallback, for
 * every client-side route as well.
 */
$is_home_route = function_exists('alexandra_montessori_is_home_request')
  && alexandra_montessori_is_home_request();
$hero_poster = $is_home_route && function_exists('alexandra_montessori_hero_poster')
  ? alexandra_montessori_hero_poster()
  : array('src' => '', 'mobile' => '');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#3a5446">
  <link rel="icon" type="image/svg+xml" href="<?php echo esc_url(get_template_directory_uri() . '/dist/favicon.svg'); ?>">
  <link rel="icon" type="image/png" href="<?php echo esc_url(get_template_directory_uri() . '/dist/favicon.png'); ?>">
  <link rel="apple-touch-icon" href="<?php echo esc_url(get_template_directory_uri() . '/dist/favicon.png'); ?>">
  <?php if ($is_home_route) : ?>
    <style id="alexandra-initial-shell-css">
      body{margin:0}.initial-shell{min-height:100vh;background:#edf5ec}.initial-nav{height:4.75rem;background:#a3bc9a}.initial-hero{position:relative;height:380px;overflow:hidden}.initial-hero picture,.initial-hero img{display:block;width:100%;height:100%}.initial-hero img{object-fit:cover;filter:brightness(1.1)}@media(min-width:640px){.initial-hero{height:460px}}@media(min-width:1024px){.initial-nav{height:5rem}.initial-hero{height:540px}}
    </style>
  <?php endif; ?>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>
  <div id="root">
    <?php if ($is_home_route) : ?>
      <div class="initial-shell" aria-hidden="true">
        <div class="initial-nav"></div>
        <section class="initial-hero">
          <picture>
            <?php if ($hero_poster['mobile'] !== $hero_poster['src']) : ?>
              <source media="(max-width: 1023px)" srcset="<?php echo esc_url($hero_poster['mobile']); ?>">
            <?php endif; ?>
            <img src="<?php echo esc_url($hero_poster['src']); ?>" alt="" fetchpriority="high" decoding="async">
          </picture>
        </section>
      </div>
    <?php endif; ?>
  </div>
  <?php wp_footer(); ?>
</body>
</html>

<?php
// Run through wp-local.ps1 eval-file. Existing records are never overwritten.
if (!defined('ABSPATH')) exit;
if (parse_url(home_url('/'), PHP_URL_HOST) !== 'lrdn.local') throw new RuntimeException('This seed is restricted to lrdn.local.');
$baseline = json_decode(file_get_contents(__DIR__.'/baseline.json'), true, 512, JSON_THROW_ON_ERROR);
$media_base = get_template_directory_uri().'/dist';
foreach ($baseline['settings'] as $setting) {
    if (get_page_by_path($setting['slug'], OBJECT, 'ldrn_setting')) continue;
    $id = wp_insert_post(array('post_type'=>'ldrn_setting','post_status'=>'publish','post_title'=>$setting['name'],'post_name'=>$setting['slug']));
    if (!$id || is_wp_error($id)) throw new RuntimeException('Nursery record could not be created.');
    $gallery = array();
    foreach (array('child-at-story-and-circle-time.jpg','children-building-wooden-train-track.jpg','child-drawing-with-crayons.jpg') as $index=>$file) $gallery[$index+1] = $media_base.'/assets/photography/'.$file;
    $fields = array(
        'address'=>implode(', ', $setting['address']), 'postcode'=>'TW3 1SR',
        'phone'=>$setting['phone'], 'email'=>$setting['email'], 'hours'=>$setting['hours'],
        'age_range'=>$setting['ages'], 'short'=>$setting['strapline'], 'welcome'=>$baseline['welcome'],
        'hero_tagline'=>$setting['strapline'], 'hero_image'=>$media_base.$setting['image'],
        'welcome_image'=>$media_base.$setting['image'], 'gallery'=>$gallery,
        'ofsted_rating'=>$setting['ofsted']['grade'] ?? '', 'ofsted_label'=>$setting['ofsted']['date'] ?? '',
    );
    foreach ($fields as $key=>$value) update_post_meta($id,'_ldrn_setting_'.$key,$value);
}
if (!get_posts(array('post_type'=>'ldrn_testimonial','post_status'=>array('publish','draft'),'posts_per_page'=>1))) {
    foreach ($baseline['testimonials'] as $index=>$story) {
        $id = wp_insert_post(array('post_type'=>'ldrn_testimonial','post_status'=>'publish','post_title'=>$story['attribution'],'menu_order'=>$index));
        update_post_meta($id,'_ldrn_testimonial_quote',implode("\n\n",$story['body']));
        update_post_meta($id,'_ldrn_testimonial_name',$story['attribution']);
        update_post_meta($id,'_ldrn_testimonial_location',$story['location'] ?? '');
    }
}
$default = get_page_by_path('hello-world', OBJECT, 'post');
if ($default && $default->post_title === 'Hello world!' && str_contains($default->post_content, 'Welcome to WordPress')) wp_update_post(array('ID'=>$default->ID, 'post_status'=>'draft'));
update_option('blogname','Little Rainbows Day Nursery');
update_option('blogdescription','Learning Through Play');
update_option('permalink_structure','/%postname%/');
update_option('blog_public',0);
flush_rewrite_rules();
ldrn_ws_seed_pages();
if (!get_posts(array('post_type'=>'ldrn_ws_session','post_status'=>'private','posts_per_page'=>1))) ldrn_ws_capture_session('Local starting point');
echo 'Local nursery records, parent stories and starting session prepared. Existing content preserved.';

<?php
defined('ABSPATH') || exit;
function am_ws_templates() {
    $templates = get_option('am_ws_templates',array());
    return is_array($templates) ? array_values($templates) : array();
}
add_action('wp_ajax_am_ws_template_save', function() {
    am_ws_studio_guard();
    $raw = json_decode(wp_unslash($_POST['template'] ?? ''),true);
    if (!is_array($raw) || empty($raw['tree']['id'])) wp_send_json_error(array('message'=>'Choose a section to save.'),400);
    $id = $raw['tree']['id'];
    $doc = am_ws_studio_clean_doc(array('nodes'=>$raw['nodes'] ?? array(),'blocks'=>array($id=>array('at'=>'m:/','pos'=>'append','tree'=>$raw['tree']))));
    if (empty($doc['blocks'][$id]['tree'])) wp_send_json_error(array('message'=>'This section cannot be saved.'),400);
    $name = mb_substr(sanitize_text_field(wp_unslash($_POST['name'] ?? 'My section')),0,80);
    $template = array('id'=>strtolower(wp_generate_password(12,false,false)),'name'=>$name,'tree'=>$doc['blocks'][$id]['tree'],'nodes'=>$doc['nodes']);
    $templates = am_ws_templates();
    if (count($templates) >= 100) wp_send_json_error(array('message'=>'The library already holds 100 saved sections.'),400);
    $templates[]=$template;
    update_option('am_ws_templates',$templates,false);
    am_ws_audit('section_template_saved',$name);
    wp_send_json_success(array('template'=>$template));
});

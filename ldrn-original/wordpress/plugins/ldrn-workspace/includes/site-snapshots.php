<?php
defined('ABSPATH') || exit;

/** Visitor design snapshots deliberately exclude users, submissions and credentials. */
function ldrn_ws_snapshot_types() { return array('post','ldrn_setting','ldrn_event','ldrn_testimonial','ldrn_job'); }
function ldrn_ws_snapshot_options() {
    $site = (array)get_option('ldrn_site_settings', array());
    return array(
        'ldrn_site_settings'=>array_intersect_key($site,array_flip(array('phone','email','hours','address','apply_url','socials','ga4_id','meta_pixel_id','gsc_verification'))),
        'ldrn_about'=>get_option('ldrn_about',array()),
        'blog_public'=>get_option('blog_public',0),
    );
}
function ldrn_ws_snapshot_records() {
    $records=array();
    foreach(get_posts(array('post_type'=>ldrn_ws_snapshot_types(),'post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC')) as $post) {
        $record=array_intersect_key((array)$post,array_flip(array('ID','post_type','post_name','post_title','post_content','post_excerpt','post_date','post_date_gmt','menu_order','post_status')));
        $record['meta']=array();
        foreach(get_post_meta($post->ID) as $key=>$values) {
            if(str_starts_with($key,'_ldrn_') || $key==='_thumbnail_id' || str_starts_with($key,'_yoast_wpseo_')) {
                $record['meta'][$key]=array_map('maybe_unserialize',$values);
            }
        }
        $record['terms']=array();
        foreach(array('category','post_tag') as $tax) {
            if(is_object_in_taxonomy($post->post_type,$tax))$record['terms'][$tax]=wp_get_object_terms($post->ID,$tax,array('fields'=>'slugs'));
        }
        $records[]=$record;
    }
    return $records;
}
function ldrn_ws_snapshot_data() {
    $articles=array();
    foreach(ldrn_ready_blog_posts() as $post) {
        $related=array();
        foreach(ldrn_ready_blog_posts() as $other) {
            if($other->ID===$post->ID)continue;
            $related[]=ldrn_prepare_blog_card($other);
            if(count($related)===3)break;
        }
        $articles[$post->post_name]=array('post'=>ldrn_prepare_blog_article($post),'related'=>$related);
    }
    $quotes=array();
    $first=ldrn_testimonial_query(1,24);
    for($page=1;$page<=$first['pages'];$page++)$quotes=array_merge($quotes,ldrn_testimonial_query($page,24)['items']);
    return array('schemaVersion'=>4,'settings'=>ldrn_get_settings(),'events'=>ldrn_get_events(),'jobs'=>ldrn_get_jobs(),
        'site'=>ldrn_get_site_settings(),'about'=>ldrn_get_about(),'testimonials'=>array_slice($quotes,0,3),
        'allTestimonials'=>$quotes,'blogArchive'=>ldrn_get_blog_archive(),'blogs'=>array_map('ldrn_prepare_blog_card',ldrn_ready_blog_posts()),'articles'=>$articles);
}

/** Immutable, content-addressed public assets. No SQL or session JSON is written here. */
function ldrn_ws_store_build($public_state, $current_code = false) {
    $active=$current_code ? null : get_option('ldrn_ws_active_build',null);
    $root=$active ? wp_upload_dir()['basedir'].'/ldrn-site-builds/'.$active['hash'].'/dist' : get_template_directory().'/dist';
    if(!is_readable($root.'/.vite/manifest.json'))return new WP_Error('build_missing','The website build is missing.');
    $files=array();
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
        if(!$file->isFile() || $file->isLink())continue;
        $rel=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
        if(!preg_match('/\.(?:js|css|json|html|txt|woff2?|png|jpe?g|webp|svg|gif|ico|pdf|docx|mp4|webm|mp3|ogg)$/i',$rel))continue;
        $files['dist/'.$rel]=$file->getPathname();
    }
    $runtime=ldrn_ws_studio_live_runtime();
    $files['runtime.js']=$active ? wp_upload_dir()['basedir'].'/ldrn-site-builds/'.$active['hash'].'/runtime.js' : LDRN_WS_DIR.'assets/'.$runtime;
    $files['asset-map.js']=LDRN_WS_DIR.'assets/snapshot-assets.js';
    $upload=wp_upload_dir();
    $map=array();
    // Copy only media actually referenced by public content/design, never private enquiry attachments.
    preg_match_all('~https?://[^\s"<>\\\\]+~',wp_json_encode($public_state,JSON_UNESCAPED_SLASHES),$matches);
    foreach(array_unique($matches[0]) as $url) {
        $url=rtrim(html_entity_decode($url),',');
        if(!str_starts_with($url,$upload['baseurl'].'/'))continue;
        $rel=rawurldecode(substr($url,strlen($upload['baseurl'])+1));
        if(str_contains($rel,'..') || str_contains($rel,'ldrn-site-builds/'))continue;
        $file=realpath($upload['basedir'].'/'.$rel);
        $allowed=realpath($upload['basedir']);
        if(!$file || !str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$allowed).'/') || !is_readable($file))return new WP_Error('media_missing','A referenced media file is missing. The session was not saved.');
        if(!preg_match('/\.(png|jpe?g|webp|svg|gif|ico|pdf|docx|mp4|webm|mp3|ogg)$/i',$file,$ext))continue;
        $target='media/'.hash_file('sha256',$file).'.'.strtolower($ext[1]);
        $files[$target]=$file; $map[$url]=$target;
    }
    ksort($files); ksort($map);
    $hashes=array();
    foreach($files as $rel=>$file)$hashes[$rel]=hash_file('sha256',$file);
    $hash=hash('sha256',wp_json_encode(array($hashes,$map)));
    $base=$upload['basedir'].'/ldrn-site-builds';
    $dir=$base.'/'.$hash;
    if(is_readable($dir.'/complete.json'))return array('hash'=>$hash,'media'=>$map,'files'=>$hashes);
    $temp=$base.'/.building-'.wp_generate_password(12,false,false);
    if(!wp_mkdir_p($temp))return new WP_Error('archive_write','Cannot create the saved website archive.');
    foreach($files as $rel=>$file) {
        if(!wp_mkdir_p(dirname($temp.'/'.$rel)) || !copy($file,$temp.'/'.$rel) || hash_file('sha256',$temp.'/'.$rel)!==$hashes[$rel]) {
            return new WP_Error('archive_write','Cannot preserve the complete website build. Nothing was saved.');
        }
    }
    file_put_contents($temp.'/complete.json',wp_json_encode(array('hash'=>$hash,'files'=>$hashes)));
    if(!rename($temp,$dir) && !is_readable($dir.'/complete.json'))return new WP_Error('archive_write','Cannot finish the saved website archive.');
    return array('hash'=>$hash,'media'=>$map,'files'=>$hashes);
}
function ldrn_ws_build_valid($build) {
    if(!is_array($build) || !preg_match('/^[a-f0-9]{64}$/',$build['hash'] ?? ''))return false;
    $dir=wp_upload_dir()['basedir'].'/ldrn-site-builds/'.$build['hash'];
    foreach(($build['files'] ?? array()) as $rel=>$hash) {
        if(str_contains($rel,'..') || !is_readable($dir.'/'.$rel) || !hash_equals($hash,hash_file('sha256',$dir.'/'.$rel)))return false;
    }
    return !empty($build['files']['dist/.vite/manifest.json']);
}
function ldrn_ws_build_url($build) { return wp_upload_dir()['baseurl'].'/ldrn-site-builds/'.$build['hash']; }
function ldrn_ws_snapshot_asset_values($value,$build,$key='') {
    if(is_array($value)) {
        foreach($value as $name=>$item)$value[$name]=ldrn_ws_snapshot_asset_values($item,$build,(string)$name);
    }elseif(is_string($value) && in_array($key,array('src','background-image','image','welcomeImage','heroPoster','imageSrcSet','feeSheetPdf'),true)) {
        foreach($build['media'] as $from=>$rel)$value=str_replace($from,ldrn_ws_build_url($build).'/'.$rel,$value);
        $value=preg_replace('~(?:https?://[^/]+)?/wp-content/themes/ldrn-theme/dist/~',ldrn_ws_build_url($build).'/dist/',$value);
        if(str_starts_with($value,'/assets/'))$value=ldrn_ws_build_url($build).'/dist'.$value;
    }
    return $value;
}
function ldrn_ws_snapshot_request() {
    if(isset($_GET['ldrn_session']) && current_user_can('manage_options')) {
        $state=ldrn_ws_session(sanitize_key(wp_unslash($_GET['ldrn_session'])));
        return !empty($state['site']) ? $state : null;
    }
    if(ldrn_ws_studio_is_canvas_request())return get_option('ldrn_ws_pending_site',null);
    return null;
}
function ldrn_ws_request_build() {
    $state=ldrn_ws_snapshot_request();
    $build=$state['site']['build'] ?? get_option('ldrn_ws_active_build',null);
    if(!is_array($build))return null;
    $dir=wp_upload_dir()['basedir'].'/ldrn-site-builds/'.$build['hash'];
    return is_readable($dir.'/complete.json') ? $build : null;
}
add_filter('ldrn_dist_path',function($path) { $b=ldrn_ws_request_build();return $b ? wp_upload_dir()['basedir'].'/ldrn-site-builds/'.$b['hash'].'/dist' : $path; });
add_filter('ldrn_dist_uri',function($url) { $b=ldrn_ws_request_build();return $b ? ldrn_ws_build_url($b).'/dist' : $url; });
add_filter('ldrn_seo_route_meta',function($meta,$path) {
    $state=ldrn_ws_snapshot_request();
    if(!$state)return $meta;
    foreach(array('settings'=>'/our-nursery/','events'=>'/events/','jobs'=>'/careers/vacancies/') as $key=>$prefix) {
        foreach($state['site']['data'][$key] as $record)if($path===$prefix.$record['id']) {
            $meta['found']=true;$meta['index']=false;$meta['post']=null;$meta['entity']='';
            $meta['title']=($record['title'] ?? $record['name']).' | Little Rainbows';
        }
    }
    if(str_starts_with($path,'/blogs/')) {
        $article=$state['site']['data']['articles'][substr($path,7)]['post'] ?? null;
        $meta['found']=(bool)$article;$meta['index']=false;$meta['post']=null;$meta['entity']='';
        if($article)$meta['title']=$article['title'].' | Little Rainbows';
    }
    return $meta;
},20,2);
add_filter('ldrn_cms_data',function($data) {
    $state=ldrn_ws_snapshot_request();
    if($state) {
        $data=array_replace($data,$state['site']['data']);
        $data['submissions']=array('enabled'=>false,'reason'=>'Forms are disabled while reviewing a saved website.');
        $data['analyticsTagId']=$data['ga4Id']=$data['metaPixelId']='';
    }
    $build=ldrn_ws_request_build();
    if($build) {
        $map=array();
        foreach($build['media'] as $from=>$rel)$map[$from]=ldrn_ws_build_url($build).'/'.$rel;
        $data['snapshotAssetBase']=ldrn_ws_build_url($build).'/dist';
        $data['snapshotAssetMap']=$map;
    }
    return $data;
});
// Snapshot browsing keeps the version parameter on every internal navigation and
// REST request; authenticated preview requests cannot leak snapshots to visitors.
add_action('wp_enqueue_scripts',function() {
    if(ldrn_ws_request_build())wp_enqueue_script('ldrn-snapshot-assets',LDRN_WS_URL.'assets/snapshot-assets.js',array('ldrn-app'),filemtime(LDRN_WS_DIR.'assets/snapshot-assets.js'),true);
    if(!ldrn_ws_snapshot_request())return;
    $state=ldrn_ws_snapshot_request();
    wp_add_inline_script('ldrn-app','window.ldrnSnapshot='.wp_json_encode(array('session'=>$state['id'],'nonce'=>wp_create_nonce('wp_rest'),'canvas'=>ldrn_ws_studio_is_canvas_request())).';','before');
    wp_enqueue_script('ldrn-snapshot-navigation',LDRN_WS_URL.'assets/snapshot-navigation.js',array('ldrn-app'),filemtime(LDRN_WS_DIR.'assets/snapshot-navigation.js'),true);
},31);
add_filter('rest_pre_dispatch',function($response,$server,$request) {
    if($response!==null || !$request->get_param('ldrn_session'))return $response;
    if(!current_user_can('manage_options'))return new WP_Error('preview_access','Sign in to review a saved website.',array('status'=>403));
    if($request->get_method()!=='GET')return new WP_Error('preview_readonly','Saved website previews are read only.',array('status'=>403));
    $state=ldrn_ws_session(sanitize_key($request->get_param('ldrn_session')));
    if(empty($state['site']))return $response;
    $data=$state['site']['data']; $route=$request->get_route();
    if($route==='/ldrn/v1/blogs') {
        $featured=$data['blogArchive']['featured'];
        $items=array_values(array_filter($data['blogs'],function($post)use($featured,$request){
            if($featured && $post['slug']===$featured['slug'])return false;
            $year=(int)$request->get_param('year');$month=(int)$request->get_param('month');
            return (!$year || (int)substr($post['date'],0,4)===$year) && (!$month || (int)substr($post['date'],5,2)===$month);
        }));
        $pages=max(1,(int)ceil(count($items)/9));$page=max(1,min($pages,(int)$request->get_param('page')));
        return rest_ensure_response(array('featured'=>$featured,'items'=>array_slice($items,($page-1)*9,9),'total'=>count($items),'page'=>$page,'perPage'=>9,'totalPages'=>$pages,'filters'=>$data['blogArchive']['filters']));
    }
    if(preg_match('~^/ldrn/v1/blogs/([a-z0-9-]+)$~',$route,$m))return isset($data['articles'][$m[1]]) ? rest_ensure_response($data['articles'][$m[1]]) : new WP_Error('missing','Article not published in this save.',array('status'=>404));
    if($route==='/ldrn/v1/testimonials') {
        $items=$data['allTestimonials']; $location=$request->get_param('location');
        if($location)$items=array_values(array_filter($items,function($q)use($location){return in_array($q['location'],array($location,'All settings','General'),true);}));
        $size=max(1,min(24,(int)($request->get_param('per_page') ?: 9)));$pages=max(1,(int)ceil(count($items)/$size));$page=max(1,min($pages,(int)$request->get_param('page')));
        return rest_ensure_response(array('items'=>array_slice($items,($page-1)*$size,$size),'page'=>$page,'pages'=>$pages,'total'=>count($items),'perPage'=>$size));
    }
    return $response;
},10,3);

/** Apply visitor content at publication, retaining later records as drafts. */
function ldrn_ws_apply_pending_site() {
    $state=get_option('ldrn_ws_pending_site',null);
    if(!$state)return true;
    if(!ldrn_ws_build_valid($state['site']['build']))return new WP_Error('archive_missing','The saved website archive is incomplete. Nothing was published.');
    $keep=array();
    foreach($state['site']['records'] as $record) {
        $post=get_post($record['ID']);
        if(!$post || $post->post_type!==$record['post_type'] || $post->post_name!==$record['post_name'])return new WP_Error('record_missing','A saved content record was deleted or changed identity. Restore cannot overwrite another record.');
        $keep[]=$post->ID;
    }
    foreach(get_posts(array('post_type'=>ldrn_ws_snapshot_types(),'post_status'=>'publish','numberposts'=>-1)) as $post) {
        if(!in_array($post->ID,$keep,true))wp_update_post(array('ID'=>$post->ID,'post_status'=>'draft'));
    }
    foreach($state['site']['records'] as $record) {
        $meta=$record['meta'];$terms=$record['terms'];unset($record['meta'],$record['terms']);
        $result=wp_update_post(wp_slash($record),true);
        if(is_wp_error($result))return $result;
        foreach(get_post_meta($record['ID']) as $key=>$values) {
            if(str_starts_with($key,'_ldrn_') || $key==='_thumbnail_id' || str_starts_with($key,'_yoast_wpseo_'))delete_post_meta($record['ID'],$key);
        }
        foreach($meta as $key=>$values)foreach($values as $value)add_post_meta($record['ID'],$key,wp_slash($value));
        foreach($terms as $tax=>$slugs)wp_set_object_terms($record['ID'],$slugs,$tax);
    }
    foreach($state['site']['options'] as $key=>$value) {
        if($key==='ldrn_site_settings')$value=array_replace((array)get_option($key,array()),$value);
        update_option($key,$value,false);
    }
    update_option('ldrn_ws_active_build',$state['site']['build'],false);
    delete_option('ldrn_ws_pending_site');
    return true;
}

add_action('wp_footer',function() {
    if(!isset($_GET['ldrn_session']) || !current_user_can('manage_options'))return;
    $state=ldrn_ws_snapshot_request();
    if(!$state)return;
    $config=array('id'=>$state['id'],'title'=>$state['name'],'when'=>wp_date('j M Y, g:i a',$state['time']),
        'complete'=>!empty($state['site']),'nonce'=>wp_create_nonce('ldrn_ws_sessions'),'rev'=>ldrn_ws_studio_rev(),
        'ajax'=>admin_url('admin-ajax.php'),'back'=>admin_url('admin.php?page=ldrn-sessions'));
    echo '<script>window.__LDRN_SESSION__='.wp_json_encode($config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script>';
    echo '<script src="'.esc_url(LDRN_WS_URL.'assets/session-preview.js?ver='.filemtime(LDRN_WS_DIR.'assets/session-preview.js')).'"></script>';
},100);

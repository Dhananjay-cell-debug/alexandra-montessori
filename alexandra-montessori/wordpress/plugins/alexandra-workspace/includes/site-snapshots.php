<?php
defined('ABSPATH') || exit;

/** Visitor design snapshots deliberately exclude users, submissions and credentials. */
function am_ws_snapshot_types() { return array('post','am_nursery','am_event','am_testimonial','am_job','am_visual_page'); }
function am_ws_snapshot_options() {
    $site = (array)get_option('am_settings', array());
    return array(
        'am_settings'=>array_intersect_key($site,array_flip(array('phone','email','hours','address','apply_url','socials','search_engine_visible','ga4_id','meta_pixel_id','gsc_verification'))),
        'am_about'=>get_option('am_about',array()),
        'am_vb_home_design'=>get_option('am_vb_home_design',array()),
        'blog_public'=>get_option('blog_public',0),
    );
}
function am_ws_snapshot_records() {
    $records=array();
    foreach(get_posts(array('post_type'=>am_ws_snapshot_types(),'post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC')) as $post) {
        $record=array_intersect_key((array)$post,array_flip(array('ID','post_type','post_name','post_title','post_content','post_excerpt','post_date','post_date_gmt','menu_order','post_status')));
        $record['meta']=array();
        foreach(get_post_meta($post->ID) as $key=>$values) {
            if(str_starts_with($key,'_am_') || $key==='_thumbnail_id' || str_starts_with($key,'_yoast_wpseo_')) {
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
function am_ws_snapshot_data() {
    $articles=array();
    foreach(am_ready_blog_posts() as $post) {
        $related=array();
        foreach(am_ready_blog_posts() as $other) {
            if($other->ID===$post->ID)continue;
            $related[]=am_prepare_blog_card($other);
            if(count($related)===3)break;
        }
        $articles[$post->post_name]=array('post'=>am_prepare_blog_article($post),'related'=>$related);
    }
    $quotes=array();
    $first=am_testimonial_query(1,24);
    for($page=1;$page<=$first['pages'];$page++)$quotes=array_merge($quotes,am_testimonial_query($page,24)['items']);
    return array('schemaVersion'=>4,'settings'=>am_get_settings(),'nurseries'=>am_get_nurseries(),'events'=>am_get_events(),'jobs'=>am_get_jobs(),
        'about'=>am_get_about(),'heroPoster'=>alexandra_montessori_hero_poster(),'testimonials'=>array_slice($quotes,0,3),
        'allTestimonials'=>$quotes,'blogArchive'=>am_get_blog_archive(),'blogs'=>array_map('am_prepare_blog_card',am_ready_blog_posts()),'articles'=>$articles);
}

/** Immutable, content-addressed public assets. No SQL or session JSON is written here. */
function am_ws_store_build($public_state, $current_code = false) {
    $active=$current_code ? null : get_option('am_ws_active_build',null);
    $root=$active ? wp_upload_dir()['basedir'].'/am-site-builds/'.$active['hash'].'/dist' : get_template_directory().'/dist';
    if(!is_readable($root.'/.vite/manifest.json'))return new WP_Error('build_missing','The website build is missing.');
    $files=array();
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
        if(!$file->isFile() || $file->isLink())continue;
        $rel=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
        if(!preg_match('/\.(?:js|css|json|html|txt|woff2?|png|jpe?g|webp|svg|gif|ico|pdf|docx|mp4|webm|mp3|ogg)$/i',$rel))continue;
        $files['dist/'.$rel]=$file->getPathname();
    }
    $runtime=am_ws_studio_live_runtime();
    $files['runtime.js']=$active ? wp_upload_dir()['basedir'].'/am-site-builds/'.$active['hash'].'/runtime.js' : AM_WS_DIR.'assets/'.$runtime;
    $files['asset-map.js']=AM_WS_DIR.'assets/snapshot-assets.js';
    $upload=wp_upload_dir();
    $map=array();
    // Copy only media actually referenced by public content/design, never private enquiry attachments.
    preg_match_all('~https?://[^\s"<>\\\\]+~',wp_json_encode($public_state,JSON_UNESCAPED_SLASHES),$matches);
    foreach(array_unique($matches[0]) as $url) {
        $url=rtrim(html_entity_decode($url),',');
        if(!str_starts_with($url,$upload['baseurl'].'/'))continue;
        $rel=rawurldecode(substr($url,strlen($upload['baseurl'])+1));
        if(str_contains($rel,'..') || str_contains($rel,'am-site-builds/'))continue;
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
    $base=$upload['basedir'].'/am-site-builds';
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
function am_ws_build_valid($build) {
    if(!is_array($build) || !preg_match('/^[a-f0-9]{64}$/',$build['hash'] ?? ''))return false;
    $dir=wp_upload_dir()['basedir'].'/am-site-builds/'.$build['hash'];
    foreach(($build['files'] ?? array()) as $rel=>$hash) {
        if(str_contains($rel,'..') || !is_readable($dir.'/'.$rel) || !hash_equals($hash,hash_file('sha256',$dir.'/'.$rel)))return false;
    }
    return !empty($build['files']['dist/.vite/manifest.json']);
}
function am_ws_build_url($build) { return wp_upload_dir()['baseurl'].'/am-site-builds/'.$build['hash']; }
function am_ws_snapshot_asset_values($value,$build,$key='') {
    if(is_array($value)) {
        foreach($value as $name=>$item)$value[$name]=am_ws_snapshot_asset_values($item,$build,(string)$name);
    }elseif(is_string($value) && in_array($key,array('src','background-image','image','welcomeImage','heroPoster','imageSrcSet','feeSheetPdf'),true)) {
        foreach($build['media'] as $from=>$rel)$value=str_replace($from,am_ws_build_url($build).'/'.$rel,$value);
        $value=preg_replace('~(?:https?://[^/]+)?/wp-content/themes/alexandra-theme/dist/~',am_ws_build_url($build).'/dist/',$value);
        if(str_starts_with($value,'/assets/'))$value=am_ws_build_url($build).'/dist'.$value;
    }
    return $value;
}
function am_ws_snapshot_request() {
    if(isset($_GET['am_session']) && current_user_can('manage_options')) {
        $state=am_ws_session(sanitize_key(wp_unslash($_GET['am_session'])));
        return !empty($state['site']) ? $state : null;
    }
    if(am_ws_studio_is_canvas_request())return get_option('am_ws_pending_site',null);
    return null;
}
function am_ws_request_build() {
    $state=am_ws_snapshot_request();
    $build=$state['site']['build'] ?? get_option('am_ws_active_build',null);
    if(!is_array($build))return null;
    $dir=wp_upload_dir()['basedir'].'/am-site-builds/'.$build['hash'];
    return is_readable($dir.'/complete.json') ? $build : null;
}
add_filter('alexandra_dist_path',function($path) { $b=am_ws_request_build();return $b ? wp_upload_dir()['basedir'].'/am-site-builds/'.$b['hash'].'/dist' : $path; });
add_filter('alexandra_dist_uri',function($url) { $b=am_ws_request_build();return $b ? am_ws_build_url($b).'/dist' : $url; });
add_filter('am_seo_route_meta',function($meta,$path) {
    $state=am_ws_snapshot_request();
    if(!$state)return $meta;
    foreach(array('nurseries'=>'/nurseries/','events'=>'/events/','jobs'=>'/careers/vacancies/') as $key=>$prefix) {
        foreach($state['site']['data'][$key] ?? array() as $record)if($path===$prefix.$record['id'] || ($key==='nurseries' && $path==='/contact/'.$record['id'])) {
            $meta['found']=true;$meta['index']=false;$meta['post']=null;$meta['entity']='';
            $meta['title']=($record['title'] ?? $record['name']).' | Alexandra Montessori';
        }
    }
    if(str_starts_with($path,'/blogs/')) {
        $article=$state['site']['data']['articles'][substr($path,7)]['post'] ?? null;
        $meta['found']=(bool)$article;$meta['index']=false;$meta['post']=null;$meta['entity']='';
        if($article)$meta['title']=$article['title'].' | Alexandra Montessori';
    }
    return $meta;
},20,2);
add_filter('am_cms_data',function($data) {
    $state=am_ws_snapshot_request();
    if($state) {
        $data=array_replace($data,$state['site']['data']);
        $data['submissions']=array('enabled'=>false,'reason'=>'Forms are disabled while reviewing a saved website.');
        $data['analyticsTagId']=$data['ga4Id']=$data['metaPixelId']='';
    }
    $build=am_ws_request_build();
    if($build) {
        $map=array();
        foreach($build['media'] as $from=>$rel)$map[$from]=am_ws_build_url($build).'/'.$rel;
        $data['snapshotAssetBase']=am_ws_build_url($build).'/dist';
        $data['snapshotAssetMap']=$map;
    }
    return $data;
});
// Snapshot browsing keeps the version parameter on every internal navigation and
// REST request; authenticated preview requests cannot leak snapshots to visitors.
add_action('wp_enqueue_scripts',function() {
    if(am_ws_request_build())wp_enqueue_script('am-snapshot-assets',AM_WS_URL.'assets/snapshot-assets.js',array('alexandra-app'),filemtime(AM_WS_DIR.'assets/snapshot-assets.js'),true);
    if(!am_ws_snapshot_request())return;
    $state=am_ws_snapshot_request();
    wp_add_inline_script('alexandra-app','window.amSnapshot='.wp_json_encode(array('session'=>$state['id'],'nonce'=>wp_create_nonce('wp_rest'),'canvas'=>am_ws_studio_is_canvas_request())).';','before');
    wp_enqueue_script('am-snapshot-navigation',AM_WS_URL.'assets/snapshot-navigation.js',array('alexandra-app'),filemtime(AM_WS_DIR.'assets/snapshot-navigation.js'),true);
},31);
add_filter('rest_pre_dispatch',function($response,$server,$request) {
    if($response!==null || !$request->get_param('am_session'))return $response;
    if(!current_user_can('manage_options'))return new WP_Error('preview_access','Sign in to review a saved website.',array('status'=>403));
    if($request->get_method()!=='GET')return new WP_Error('preview_readonly','Saved website previews are read only.',array('status'=>403));
    $state=am_ws_session(sanitize_key($request->get_param('am_session')));
    if(empty($state['site']))return $response;
    $data=$state['site']['data']; $route=$request->get_route();
    if($route==='/alexandra/v1/blogs') {
        $featured=$data['blogArchive']['featured'];
        $items=array_values(array_filter($data['blogs'],function($post)use($featured,$request){
            if($featured && $post['slug']===$featured['slug'])return false;
            $year=(int)$request->get_param('year');$month=(int)$request->get_param('month');
            return (!$year || (int)substr($post['date'],0,4)===$year) && (!$month || (int)substr($post['date'],5,2)===$month);
        }));
        $pages=max(1,(int)ceil(count($items)/9));$page=max(1,min($pages,(int)$request->get_param('page')));
        return rest_ensure_response(array('featured'=>$featured,'items'=>array_slice($items,($page-1)*9,9),'total'=>count($items),'page'=>$page,'perPage'=>9,'totalPages'=>$pages,'filters'=>$data['blogArchive']['filters']));
    }
    if(preg_match('~^/alexandra/v1/blogs/([a-z0-9-]+)$~',$route,$m))return isset($data['articles'][$m[1]]) ? rest_ensure_response($data['articles'][$m[1]]) : new WP_Error('missing','Article not published in this save.',array('status'=>404));
    if($route==='/alexandra/v1/testimonials') {
        $items=$data['allTestimonials']; $location=$request->get_param('location');
        if($location)$items=array_values(array_filter($items,function($q)use($location){return in_array($q['location'],array($location,'All Nurseries','General'),true);}));
        $size=max(1,min(24,(int)($request->get_param('per_page') ?: 9)));$pages=max(1,(int)ceil(count($items)/$size));$page=max(1,min($pages,(int)$request->get_param('page')));
        return rest_ensure_response(array('items'=>array_slice($items,($page-1)*$size,$size),'page'=>$page,'pages'=>$pages,'total'=>count($items),'perPage'=>$size));
    }
    return $response;
},10,3);

/** Apply visitor content at publication, retaining later records as drafts. */
function am_ws_apply_pending_site() {
    $state=get_option('am_ws_pending_site',null);
    if(!$state)return true;
    if(!am_ws_build_valid($state['site']['build']))return new WP_Error('archive_missing','The saved website archive is incomplete. Nothing was published.');
    $keep=array();
    foreach($state['site']['records'] as $record) {
        $post=get_post($record['ID']);
        if(!$post || $post->post_type!==$record['post_type'] || $post->post_name!==$record['post_name'])return new WP_Error('record_missing','A saved content record was deleted or changed identity. Restore cannot overwrite another record.');
        $keep[]=$post->ID;
    }
    foreach(get_posts(array('post_type'=>am_ws_snapshot_types(),'post_status'=>'publish','numberposts'=>-1)) as $post) {
        if(!in_array($post->ID,$keep,true))wp_update_post(array('ID'=>$post->ID,'post_status'=>'draft'));
    }
    foreach($state['site']['records'] as $record) {
        $meta=$record['meta'];$terms=$record['terms'];unset($record['meta'],$record['terms']);
        $result=wp_update_post(wp_slash($record),true);
        if(is_wp_error($result))return $result;
        foreach(get_post_meta($record['ID']) as $key=>$values) {
            if(str_starts_with($key,'_am_') || $key==='_thumbnail_id' || str_starts_with($key,'_yoast_wpseo_'))delete_post_meta($record['ID'],$key);
        }
        foreach($meta as $key=>$values)foreach($values as $value)add_post_meta($record['ID'],$key,wp_slash($value));
        foreach($terms as $tax=>$slugs)wp_set_object_terms($record['ID'],$slugs,$tax);
    }
    foreach($state['site']['options'] as $key=>$value) {
        if($key==='am_settings')$value=array_replace((array)get_option($key,array()),$value);
        update_option($key,$value,false);
    }
    update_option('am_ws_active_build',$state['site']['build'],false);
    delete_option('am_ws_pending_site');
    return true;
}

add_action('wp_footer',function() {
    if(!isset($_GET['am_session']) || !current_user_can('manage_options'))return;
    $state=am_ws_snapshot_request();
    // A Visual Builder save is a page layout rather than a whole site, but it can still be restored.
    if(!$state){$state=am_ws_session(sanitize_key(wp_unslash($_GET['am_session'])));if(empty($state['vb']))return;}
    $config=array('id'=>$state['id'],'title'=>$state['name'],'when'=>wp_date('j M Y, g:i a',$state['time']),
        'complete'=>!empty($state['site']),'vb'=>!empty($state['vb']),'nonce'=>wp_create_nonce('am_ws_sessions'),'rev'=>am_ws_studio_rev(),
        'ajax'=>admin_url('admin-ajax.php'),'back'=>admin_url('admin.php?page=am-sessions'));
    echo '<script>window.__AM_SESSION__='.wp_json_encode($config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script>';
    echo '<script src="'.esc_url(AM_WS_URL.'assets/session-preview.js?ver='.filemtime(AM_WS_DIR.'assets/session-preview.js')).'"></script>';
},100);

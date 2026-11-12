<?php
// CLI or the single-use authenticated FTP maintenance launcher only.
if (!defined('ABSPATH') || !function_exists('ldrn_ws_store_build')) throw new RuntimeException('WordPress required.');
$admins=get_users(array('role'=>'administrator','number'=>1));
if (!$admins) throw new RuntimeException('Administrator missing.');
wp_set_current_user($admins[0]->ID);
$live=ldrn_ws_studio_live();$draft=ldrn_ws_studio_draft();$revision=ldrn_ws_studio_rev();
$previous=get_option('ldrn_ws_active_build',null);
$before=ldrn_ws_capture_session('Before independent-element editor fix');
if(!$before) throw new RuntimeException('Recovery session could not be saved.');
$state=ldrn_ws_session($before);
$build=ldrn_ws_store_build($state,true);
if(is_wp_error($build) || !ldrn_ws_build_valid($build)) throw new RuntimeException('New code archive did not validate.');
update_option('ldrn_ws_active_build',$build,false);
try {
    $after=ldrn_ws_capture_session('Independent-element editor fix');
    if(!$after || ldrn_ws_studio_live()!==$live || ldrn_ws_studio_draft()!==$draft || ldrn_ws_studio_rev()!==$revision) throw new RuntimeException('Code activation changed an editor document.');
} catch(Throwable $error) {
    if($previous===null)delete_option('ldrn_ws_active_build');else update_option('ldrn_ws_active_build',$previous,false);
    throw $error;
}
if(defined('DB_ENGINE') && DB_ENGINE==='sqlite' && !ldrn_sqlite_backup()) throw new RuntimeException('Post-update private database backup failed.');
echo wp_json_encode(array('ok'=>true,'build'=>$build['hash'],'before'=>$before,'after'=>$after,'draft_preserved'=>true,'live_preserved'=>true))."\n";

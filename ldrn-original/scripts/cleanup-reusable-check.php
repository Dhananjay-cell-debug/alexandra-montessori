<?php
// Removes only the named artifact created by this local browser verification.
if (parse_url(home_url(),PHP_URL_HOST)!=='lrdn.local')throw new RuntimeException('Local verification cleanup only.');
$before=ldrn_ws_templates();
$after=array_values(array_filter($before,static function($template){return ($template['name']??'')!=='Editor QA reusable button 20261004';}));
update_option('ldrn_ws_templates',$after,false);
echo 'Removed '.(count($before)-count($after))." local verification template; all other templates preserved.\n";

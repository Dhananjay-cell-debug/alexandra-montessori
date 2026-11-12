<?php
$token=trim((string)file_get_contents('/home/u815362143/.api_token'));
$json=json_decode($token,true);
echo 'Token file format: '.(is_array($json) ? 'JSON' : (substr_count($token,'.')===2 ? 'JWT' : 'plain token'))."\n";
echo 'Bearer prefix: '.(str_starts_with(strtolower($token),'bearer ') ? 'yes' : 'no')."\n";
echo 'Environment assignment: '.(preg_match('/^(?:export\s+)?[A-Za-z_][A-Za-z0-9_]*=/',$token) ? 'yes' : 'no')."\n";
echo 'Quoted value: '.(in_array($token[0] ?? '',["'",'"'],true) ? 'yes' : 'no')."\n";
if(is_array($json))echo 'Top-level field names: '.implode(', ',array_keys($json))."\n";
if(substr_count($token,'.')===2) {
    $part=explode('.',$token)[1];$claims=json_decode(base64_decode(strtr($part,'-_','+/')),true);
    echo 'JWT issuer: '.($claims['iss'] ?? 'not specified')."\n";
    echo 'JWT audience: '.json_encode($claims['aud'] ?? 'not specified')."\n";
    echo 'Expired: '.(!empty($claims['exp']) && $claims['exp']<time() ? 'yes' : 'no or unspecified')."\n";
}

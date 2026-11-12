<?php
// Public LDRN gateway. The existing LDRN WordPress remains the data controller.
// No WordPress code, login cookies, credentials or recipient selection live here.
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function fail_request(int $status, string $message): never {
    http_response_code($status);
    echo json_encode(['message'=>$message]);
    exit;
}
$method=$_SERVER['REQUEST_METHOD'] ?? 'GET';
$path=parse_url($_SERVER['REQUEST_URI'] ?? '',PHP_URL_PATH);
$route=preg_replace('~^/ldrn/api/?~','',(string)$path);
$form_routes=['enquiry','availability','quick-application','application'];
$read_route=preg_match('~^(site|testimonials|blogs(?:/[a-z0-9-]+)?)$~',$route)===1;
if(!in_array($method,['GET','POST'],true))fail_request(405,'This request method is not supported.');
if(($method==='POST' && !in_array($route,$form_routes,true)) || ($method==='GET' && !$read_route))fail_request(404,'This page is unavailable.');
$origin=$_SERVER['HTTP_ORIGIN'] ?? '';
if($method==='POST' && $origin!=='' && $origin!=='https://alexandramontessori.co.uk' && $origin!=='https://www.alexandramontessori.co.uk')fail_request(403,'Please submit the form from this website.');
if($method==='POST' && ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')==='cross-site')fail_request(403,'Please submit the form from this website.');
if((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>6*1024*1024)fail_request(413,'Please keep CV files under 5 MB.');
$query=[];
foreach(['page','per_page','year','month','location','search'] as $key) {
    if(isset($_GET[$key]) && is_scalar($_GET[$key]))$query[$key]=substr((string)$_GET[$key],0,200);
}
$url='https://ldrn.krildigital.com/wp-json/ldrn/v1/'.$route.($query ? '?'.http_build_query($query) : '');
$curl=curl_init($url);
if(!$curl)fail_request(503,'The nursery enquiry service is temporarily unavailable.');
$headers=['Accept: application/json'];
// The gateway has already checked the browser's origin. The fixed upstream sees
// its own canonical origin; no client-controlled upstream or credentials exist.
if($method==='POST')$headers[]='Origin: https://ldrn.krildigital.com';
$key=$_SERVER['HTTP_X_LDRN_IDEMPOTENCY_KEY'] ?? '';
if(preg_match('/^[a-zA-Z0-9-]{8,128}$/',$key))$headers[]='X-LDRN-Idempotency-Key: '.$key;
curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>45,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'Alexandra Montessori LDRN public-site gateway']);
if($method==='POST') {
    curl_setopt($curl,CURLOPT_POST,true);
    if(str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''),'multipart/form-data')) {
        $body=[];
        foreach($_POST as $name=>$value)if(is_scalar($value))$body[$name]=(string)$value;
        foreach($_FILES as $name=>$file) {
            if(!in_array($name,['cv','resume'],true) || !is_array($file) || is_array($file['tmp_name'] ?? null))fail_request(400,'Please attach one CV file.');
            if(($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || (int)$file['size']>5*1024*1024)fail_request(400,'Please attach a valid CV under 5 MB.');
            $body[$name]=new CURLFile($file['tmp_name'],(string)($file['type'] ?? 'application/octet-stream'),basename((string)$file['name']));
        }
        curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
    } else {
        if(!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''),'application/json'))fail_request(415,'Please use the website enquiry form.');
        $body=file_get_contents('php://input',false,null,0,65537);
        if($body===false || strlen($body)>65536 || !is_array(json_decode($body,true)))fail_request(400,'Please check the form and try again.');
        $headers[]='Content-Type: application/json';
        curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
    }
}
curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
$response=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
curl_close($curl);
if($response===false || $status<200 || $status>=500 || json_decode($response,true)===null)fail_request(502,'The nursery enquiry service is temporarily unavailable. Please try again or call the nursery.');
http_response_code($status);
echo $response;

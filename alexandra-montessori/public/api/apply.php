<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

const RECIPIENT_EMAIL = 'info@alexandramontessori.co.uk';
const DIRECT_UPLOAD_MAX_BYTES = 5242880; // 5 MB
const TOTAL_REQUEST_MAX_BYTES = 7340032; // 7 MB to allow multipart overhead
const RATE_LIMIT_WINDOW_SECONDS = 900;
const RATE_LIMIT_MAX_SUBMISSIONS = 8;

$allowedExtensions = [
    'pdf' => ['application/pdf'],
    'doc' => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream'],
    'docx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/octet-stream',
    ],
];

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function clean_string(?string $value, int $maxLength = 1000): string
{
    $value = trim((string) $value);
    $value = str_replace(["\r", "\n", "\0"], '', $value);
    $value = preg_replace('/[ \t]+/', ' ', $value) ?? '';
    return function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength, 'UTF-8')
        : substr($value, 0, $maxLength);
}

function clean_multiline(?string $value, int $maxLength = 4000): string
{
    $value = trim((string) $value);
    $value = str_replace("\0", '', $value);
    $value = preg_replace("/\r\n|\r/", "\n", $value) ?? '';
    return function_exists('mb_substr')
        ? mb_substr($value, 0, $maxLength, 'UTF-8')
        : substr($value, 0, $maxLength);
}

function remote_ip(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        $ip = trim(explode(',', (string) $candidate)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    return 'unknown';
}

function check_rate_limit(): void
{
    $ip = remote_ip();
    $key = hash('sha256', $ip);
    $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'am_apply_' . $key . '.json';
    $now = time();
    $attempts = [];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $attempts = array_values(array_filter($decoded, static fn ($time) => is_int($time) && $time > $now - RATE_LIMIT_WINDOW_SECONDS));
        }
    }

    if (count($attempts) >= RATE_LIMIT_MAX_SUBMISSIONS) {
        respond(429, ['success' => false, 'message' => 'Too many submissions. Please try again later.']);
    }

    $attempts[] = $now;
    file_put_contents($file, json_encode($attempts), LOCK_EX);
}

function uploaded_resume(array $allowedExtensions): ?array
{
    if (!isset($_FILES['resume']) || !is_array($_FILES['resume'])) {
        return null;
    }

    $file = $_FILES['resume'];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        respond(400, ['success' => false, 'message' => 'The attachment could not be uploaded.']);
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > DIRECT_UPLOAD_MAX_BYTES) {
        respond(413, ['success' => false, 'message' => 'CV/resume attachments must be 5 MB or smaller.']);
    }

    $originalName = clean_string((string) ($file['name'] ?? 'attachment'), 180);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!isset($allowedExtensions[$extension])) {
        respond(400, ['success' => false, 'message' => 'Please upload CV/resume as a PDF or Word document only.']);
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmpName)) {
        respond(400, ['success' => false, 'message' => 'Invalid attachment upload.']);
    }

    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }
    } elseif (!empty($file['type'])) {
        $mime = clean_string((string) $file['type'], 120);
    }

    if (!in_array($mime, $allowedExtensions[$extension], true)) {
        respond(400, ['success' => false, 'message' => 'The attachment content does not match a PDF or Word document.']);
    }

    return [
        'name' => $originalName,
        'tmp_name' => $tmpName,
        'mime' => $mime,
        'size' => $size,
    ];
}

function attachment_part(array $file, string $boundary): string
{
    $content = chunk_split(base64_encode((string) file_get_contents($file['tmp_name'])));
    $safeName = str_replace(['"', "\n"], '', $file['name']);

    return "--{$boundary}\r\n"
        . "Content-Type: {$file['mime']}; name=\"{$safeName}\"\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n\r\n"
        . $content . "\r\n";
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Allow: OPTIONS, POST');
    respond(200, ['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: OPTIONS, POST');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > TOTAL_REQUEST_MAX_BYTES) {
    respond(413, ['success' => false, 'message' => 'This submission is too large. CV/resume attachments must be 5 MB or smaller.']);
}

check_rate_limit();

$website = clean_string($_POST['website'] ?? '', 200);
if ($website !== '') {
    respond(200, ['success' => true]);
}

$loadedAt = (int) ($_POST['formLoadedAt'] ?? 0);
if ($loadedAt > 0 && ((int) floor(microtime(true) * 1000) - $loadedAt) < 2500) {
    respond(200, ['success' => true]);
}

$firstName = clean_string($_POST['firstName'] ?? '', 80);
$lastName = clean_string($_POST['lastName'] ?? '', 80);
$email = filter_var(clean_string($_POST['email'] ?? '', 180), FILTER_VALIDATE_EMAIL);
$phone = clean_string($_POST['phone'] ?? '', 80);
$qualification = clean_string($_POST['qualification'] ?? '', 160);
$position = clean_string($_POST['position'] ?? '', 180);
$about = clean_multiline($_POST['about'] ?? '', 4000);
$jobSlug = clean_string($_POST['jobSlug'] ?? 'general', 120);
$jobTitle = clean_string($_POST['jobTitle'] ?? '', 180);
$consent = isset($_POST['consent']);

if ($firstName === '' || $lastName === '' || !$email || $phone === '' || $qualification === '' || $position === '') {
    respond(400, ['success' => false, 'message' => 'Please complete all required fields.']);
}

if (!$consent) {
    respond(400, ['success' => false, 'message' => 'Consent is required before submitting an application.']);
}

$resume = uploaded_resume($allowedExtensions);
if ($resume === null) {
    respond(400, ['success' => false, 'message' => 'Please attach your CV/resume as a PDF or Word document.']);
}

$submittedAt = gmdate('Y-m-d H:i:s') . ' UTC';
$subject = 'New Alexandra Montessori application - ' . $firstName . ' ' . $lastName;

$lines = [
    'New careers application from alexandramontessori.co.uk',
    '',
    'Submitted: ' . $submittedAt,
    'Name: ' . $firstName . ' ' . $lastName,
    'Email: ' . $email,
    'Phone: ' . $phone,
    'Qualification: ' . $qualification,
    'Position: ' . $position,
    'Job slug: ' . $jobSlug,
];

if ($jobTitle !== '') {
    $lines[] = 'Job title: ' . $jobTitle;
}

$lines[] = 'Direct attachment: ' . $resume['name'] . ' (' . number_format($resume['size']) . ' bytes)';

$lines[] = '';
$lines[] = 'Applicant message:';
$lines[] = $about !== '' ? $about : '(none provided)';
$lines[] = '';
$lines[] = 'Security note: CV/resume uploads are restricted to PDF and Word documents.';

$body = implode("\r\n", $lines);
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$from = 'Alexandra Montessori Website <no-reply@alexandramontessori.co.uk>';

$boundary = 'am_' . bin2hex(random_bytes(16));
$headers = [
    'From: ' . $from,
    'Reply-To: ' . $firstName . ' ' . $lastName . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
];

$message = "--{$boundary}\r\n"
    . "Content-Type: text/plain; charset=\"UTF-8\"\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $body . "\r\n\r\n"
    . attachment_part($resume, $boundary)
    . "--{$boundary}--\r\n";

$sent = mail(RECIPIENT_EMAIL, $encodedSubject, $message, implode("\r\n", $headers));
if (!$sent) {
    respond(500, ['success' => false, 'message' => 'The application could not be emailed.']);
}

respond(200, ['success' => true]);

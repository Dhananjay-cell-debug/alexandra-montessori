<?php
/** Private SMTP configuration for the LDRN website; no browser credentials. */
if (!defined('ABSPATH')) exit;
$ldrn_mail_path = dirname(rtrim(ABSPATH, '/\\'), 2) . '/ldrn-private/mail-config.json';
$ldrn_mail = is_readable($ldrn_mail_path) ? json_decode(file_get_contents($ldrn_mail_path), true) : null;
// "hosting" = send through the web host's mail server (like Alexandra); no mailbox password needed.
$ldrn_mail_hosting = is_array($ldrn_mail) && ($ldrn_mail['transport'] ?? '') === 'hosting' && !empty($ldrn_mail['from_email']);
if (!is_array($ldrn_mail) || (!$ldrn_mail_hosting && (empty($ldrn_mail['user']) || empty($ldrn_mail['password'])))) return;
if (!defined('LDRN_OPS_NOTIFY_EMAIL')) define('LDRN_OPS_NOTIFY_EMAIL', $ldrn_mail['recipient']);
if (!defined('LDRN_OPS_MAIL_FROM')) define('LDRN_OPS_MAIL_FROM', $ldrn_mail['from_email']);
add_action('phpmailer_init', static function ($mailer) use ($ldrn_mail, $ldrn_mail_hosting) {
    if ($ldrn_mail_hosting) {
        $mailer->isMail();
        $mailer->setFrom($ldrn_mail['from_email'], $ldrn_mail['from_name'] ?? '', false);
        $mailer->Sender = $ldrn_mail['envelope_sender'] ?? $ldrn_mail['from_email'];
        return;
    }
    $mailer->isSMTP();
    $mailer->Host = $ldrn_mail['host'];
    $mailer->Port = (int)$ldrn_mail['port'];
    $mailer->SMTPSecure = $ldrn_mail['encryption'];
    $mailer->SMTPAuth = true;
    $mailer->Username = $ldrn_mail['user'];
    $mailer->Password = $ldrn_mail['password'];
    $mailer->SMTPDebug = 0;
    $mailer->Timeout = 20;
    $mailer->setFrom($ldrn_mail['from_email'], $ldrn_mail['from_name'], false);
    $mailer->Sender = $ldrn_mail['from_email'];
}, PHP_INT_MAX);
unset($ldrn_mail, $ldrn_mail_path, $ldrn_mail_hosting);

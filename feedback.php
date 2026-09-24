<?php
/**
 * feedback.php — приём отзыва/сообщения об ошибке из плавающего виджета.
 * POST: message (обяз.), email (опц.), page (опц.), company (honeypot — должен быть пуст).
 * Шлёт письмо на info@prodom-expert.ru через mail() (почта домена на Timeweb, SPF настроен).
 * Возвращает JSON {ok:bool, error?:string}. База не нужна.
 */
require_once __DIR__ . '/includes/seo-config.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method']);
  exit;
}

// Honeypot: боты заполняют скрытое поле. Молча «успех», ничего не шлём.
if (trim((string) ($_POST['company'] ?? '')) !== '') {
  echo json_encode(['ok' => true]);
  exit;
}

$message = trim((string) ($_POST['message'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$page    = trim((string) ($_POST['page'] ?? ''));

// Валидация сообщения
if (mb_strlen($message) < 5) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'error' => 'short']);
  exit;
}
if (mb_strlen($message) > 5000) {
  $message = mb_substr($message, 0, 5000);
}

// Email опционален; если задан — проверяем и защищаемся от инъекции заголовков
$replyTo = '';
if ($email !== '') {
  if (filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/[\r\n]/', $email)) {
    $replyTo = $email;
  } else {
    $email = '(указан невалидный email)';
  }
}

// Чистим page от переводов строк (уходит в тему письма)
$page = preg_replace('/[\r\n]+/', ' ', mb_substr($page, 0, 300));
if ($page === '') { $page = '(страница не указана)'; }

$to      = defined('DOMEXPERT_CONTACT_EMAIL') ? DOMEXPERT_CONTACT_EMAIL : 'info@prodom-expert.ru';
$subject = '=?UTF-8?B?' . base64_encode('Отзыв с сайта: ' . mb_substr($page, 0, 120)) . '?=';

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '—';
$ip = preg_replace('/[\r\n]/', '', substr((string) $ip, 0, 60));
$ua = preg_replace('/[\r\n]/', '', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? '—'), 0, 300));

$body  = "Новое сообщение с плавающего виджета ДомЭксперт\n";
$body .= str_repeat('—', 40) . "\n";
$body .= "Страница: $page\n";
$body .= 'Email для ответа: ' . ($email !== '' ? $email : 'не указан') . "\n";
$body .= "Дата: " . date('Y-m-d H:i:s') . "\n";
$body .= "IP: $ip\n";
$body .= "UA: $ua\n";
$body .= str_repeat('—', 40) . "\n\n";
$body .= $message . "\n";

$headers  = 'From: =?UTF-8?B?' . base64_encode('ДомЭксперт') . "?= <$to>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
if ($replyTo !== '') {
  $headers .= "Reply-To: $replyTo\r\n";
}

$sent = @mail($to, $subject, $body, $headers);

// Подстраховка: пишем в локальный лог (на случай, если mail() не доставит).
// scripts/ закрыт от веба через .htaccess, лог не публичный.
@file_put_contents(
  __DIR__ . '/scripts/feedback.log',
  date('c') . "\t" . ($sent ? 'sent' : 'MAILFAIL') . "\t$page\t$email\t" .
    str_replace(["\n", "\t"], [' ', ' '], $message) . "\n",
  FILE_APPEND | LOCK_EX
);

echo json_encode(['ok' => (bool) $sent, 'error' => $sent ? null : 'mail']);

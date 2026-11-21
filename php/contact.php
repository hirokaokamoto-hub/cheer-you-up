<?php
/**
 * お問い合わせフォーム送信処理（ロリポップ用）
 *
 * ▼ 設定はこのブロックだけ変更してください ▼
 */
// お問い合わせを受け取るメールアドレス（複数ならカンマ区切り）
const MAIL_TO   = 'info@cheer-you-up.co.jp';
// 送信元アドレス。必ずロリポップで作成した「独自ドメインのメールアドレス」にしてください
// （Gmail等を指定すると迷惑メール扱い・不達になります）
const MAIL_FROM = 'info@cheer-you-up.co.jp';
const FROM_NAME = '合同会社 Cheer you up';
// 送信者への自動返信メールを送るか
const SEND_AUTO_REPLY = true;
/** ▲ 設定ここまで ▲ */

date_default_timezone_set('Asia/Tokyo');
mb_language('Japanese');
mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=UTF-8');

function respond(bool $ok, string $message, int $status = 200): void {
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, '不正なアクセスです。', 405);
}

// スパム対策：人には見えない項目に入力があればボットとみなす
if (!empty($_POST['website'])) {
    respond(true, 'お問い合わせを受け付けました。');
}

// 改行・制御文字を除去（ヘッダーインジェクション対策）
function clean_line(string $v): string {
    return trim(preg_replace('/[\r\n\t\x00-\x1F\x7F]/u', ' ', $v));
}

$company = clean_line($_POST['companyName'] ?? '');
$name    = clean_line($_POST['fullName'] ?? '');
$email   = clean_line($_POST['email'] ?? '');
$tel     = clean_line($_POST['tel'] ?? '');
$type    = clean_line($_POST['type'] ?? '');
$message = trim(str_replace("\0", '', $_POST['message'] ?? ''));
$privacy = $_POST['privacy'] ?? '';

$types = [
    'cosmetics'     => '化粧品ブランドについて',
    'human-support' => '外国人材・企業支援について',
    'partnership'   => '連携・パートナーシップについて',
    'other'         => 'その他',
];

if ($name === '' || $message === '' || !isset($types[$type]) || $privacy === '') {
    respond(false, '必須項目をご確認のうえ、もう一度お試しください。', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'メールアドレスの形式をご確認ください。', 400);
}
if (mb_strlen($message) > 5000 || mb_strlen($name) > 100 || mb_strlen($company) > 200) {
    respond(false, '入力内容が長すぎます。', 400);
}

$typeLabel = $types[$type];
$date = date('Y年n月j日 H:i');
$body = <<<TXT
ウェブサイトのお問い合わせフォームから送信がありました。

■ 受付日時　　：{$date}
■ 会社名　　　：{$company}
■ お名前　　　：{$name}
■ メールアドレス：{$email}
■ 電話番号　　：{$tel}
■ お問い合わせ種別：{$typeLabel}

■ お問い合わせ内容
{$message}
TXT;

$fromHeader = 'From: ' . mb_encode_mimeheader(FROM_NAME) . ' <' . MAIL_FROM . '>';
$adminHeaders = $fromHeader . "\r\n" . 'Reply-To: ' . $email;

$sent = mb_send_mail(MAIL_TO, '【お問い合わせ】' . $typeLabel . '（' . $name . '様）', $body, $adminHeaders, '-f' . MAIL_FROM);

if (!$sent) {
    respond(false, '送信に失敗しました。時間をおいて再度お試しください。', 500);
}

if (SEND_AUTO_REPLY) {
    $reply = <<<TXT
{$name} 様

このたびは合同会社 Cheer you up へお問い合わせいただき、
誠にありがとうございます。
以下の内容でお問い合わせを受け付けました。
内容を確認のうえ、担当者よりご連絡いたします。

――――――――――――――――――――
■ お問い合わせ種別：{$typeLabel}
■ お問い合わせ内容
{$message}
――――――――――――――――――――

※お心当たりのない場合は、お手数ですが本メールを破棄してください。

合同会社 Cheer you up
TXT;
    mb_send_mail($email, '【Cheer you up】お問い合わせを受け付けました', $reply, $fromHeader, '-f' . MAIL_FROM);
}

respond(true, 'お問い合わせを受け付けました。担当者よりご連絡いたします。');

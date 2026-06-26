<?php
date_default_timezone_set("Europe/Istanbul"); // Use your country timezone
/**********************
 * SETTINGS
 **********************/
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$client_id = ''; // PARASUT API CLIENT ID
$client_secret = ''; // PARASUT API CLIENT SECRET
$company_id = ''; // PARASUT COMPANY ID Ex: https://uygulama.parasut.com/{here}/
$redirect_uri = ''; // PARASUT API REDIRECT URI
$first_auth_code = ''; // PARASUT API FIRST AUTH CODE

define('MAIL_HOST', 'mail.leventemre.com'); // MAIL SERVER HOSTNAME
define('MAIL_USER', 'iletisim@leventemre.com'); // MAIL SERVER USERNAME
define('MAIL_PASS', 'test'); // MAIL SERVER PASSWORD
define('MAIL_PORT', 587); // MAIL SERVER PORT
define('MAIL_FROM', 'iletisim@leventemre.com'); // MAIL FROM
define('MAIL_FROM_NAME', 'Levent Emre Paçal'); // MAIL FROM NAME

define('TG_TOKEN', ''); // TELEGRAM API TOKEN
define('TG_CHAT_ID', ''); // TELEGRAM CHAT ID

$token_file = __DIR__ . '/token.json'; // DEFAULT PARASUT API TOKEN SAVED FILE
$save_dir = __DIR__ . '/invoices/'; // INVOICES SAVED DIR
$api_base = 'https://api.parasut.com/v4'; // PARASUT API BASEMENT
$token_url = 'https://api.parasut.com/oauth/token'; // PARASUT API TOKEN BASEMENT

if (!is_dir($save_dir))
    mkdir($save_dir, 0777, true);

function sendTelegram($message, $subject = '')
{
    $msg = "📧 <b>{$subject}</b>\n{$message}";
    $url = "https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage";

    $data = [
        'chat_id' => TG_CHAT_ID,
        'text' => $msg,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    curl_close($ch);
}

function curlPost($url, $data)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function curlGet($url, $token)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function saveToken($data)
{
    global $token_file;
    if (empty($data['access_token']))
        die(print_r($data));
    $data['expires_at'] = time() + $data['expires_in'] - 60;
    file_put_contents($token_file, json_encode($data, JSON_PRETTY_PRINT));
}

function getAccessToken()
{
    global $token_file;
    if (!file_exists($token_file))
        return false;
    $token = json_decode(file_get_contents($token_file), true);
    if ($token['expires_at'] > time())
        return $token['access_token'];
    if (!empty($token['refresh_token']))
        return refreshToken($token['refresh_token']);
    return false;
}

function refreshToken($refresh_token)
{
    global $client_id, $client_secret, $token_url;
    $data = http_build_query([
        'grant_type' => 'refresh_token',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'refresh_token' => $refresh_token
    ]);
    $response = curlPost($token_url, $data);
    saveToken($response);
    return $response['access_token'];
}

function exchangeAuthCode($code)
{
    global $client_id, $client_secret, $redirect_uri, $token_url;
    $data = http_build_query([
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'code' => $code
    ]);
    $response = curlPost($token_url, $data);
    saveToken($response);
    return $response['access_token'];
}

$access_token = getAccessToken();

if (!$access_token) {
    echo "İlk token alınıyor...\n";
    $access_token = exchangeAuthCode($first_auth_code);
    echo "Access token alındı ve token.json kaydedildi.\n";
}

/**********************
 * EDITABLE PARASUT API GETTING INVOICES Ex: issue date, page size
 **********************/
$today = date('Y-m-d'); // Editable date Ex: 2026-06-26
$invoice_url = $api_base . "/$company_id/sales_invoices?" . http_build_query([
    'filter[issue_date]' => $today,
    'page[size]' => 25,
    'sort' => '-issue_date',
    'include' => 'active_e_document',
]);

$response = curlGet($invoice_url, $access_token);
$invoices = $response['data'] ?? [];

if (!$invoices)
    die("Bugün için fatura bulunamadı.");

function normalizeFirma($str)
{
    $str = mb_strtoupper($str, 'UTF-8');

    $tr = ['Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü', 'I'];
    $en = ['C', 'G', 'I', 'O', 'S', 'U', 'I'];

    return str_replace($tr, $en, $str);
}
function find_mail_excel($company_name)
{
    $dosya = __DIR__ . '/company_mail.xlsx'; // COMPANY-MAIL LIST EXCEL
    $arananFirma = $company_name;
    $mailler = [];

    $zip = new ZipArchive;
    if ($zip->open($dosya) === true) {

        $sharedStrings = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xmlObj = simplexml_load_string($sharedXml);
            foreach ($xmlObj->si as $i) {
                if (isset($i->t)) {
                    $sharedStrings[] = (string) $i->t;
                } elseif (isset($i->r)) {
                    $val = '';
                    foreach ($i->r as $r) {
                        $val .= (string) $r->t;
                    }
                    $sharedStrings[] = $val;
                }
            }
        }

        $rels = [];
        if (($relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels')) !== false) {
            $relsObj = simplexml_load_string($relsXml);
            foreach ($relsObj->Relationship as $rel) {
                $id = (string) $rel['Id'];
                $target = (string) $rel['Target'];
                $rels[$id] = 'xl/' . $target;
            }
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if (!$workbookXml) {
            return false;
        }
        $wbObj = simplexml_load_string($workbookXml);
        $wbNs = $wbObj->getNamespaces(true);

        foreach ($wbObj->sheets->sheet as $sheet) {
            $sheetName = (string) $sheet['name'];
            $rId = (string) $sheet->attributes($wbNs['r'])['id'] ?? (string) $sheet['r:id'];
            if (!isset($rels[$rId]))
                continue;
            $sheetPath = $rels[$rId];

            $sheetXml = $zip->getFromName($sheetPath);
            if (!$sheetXml)
                continue;

            $sheetObj = simplexml_load_string($sheetXml);
            $rows = $sheetObj->sheetData->row;

            foreach ($rows as $row) {
                $cells = $row->c;

                $firma = '';
                if (isset($cells[0])) {
                    $cell = $cells[0];
                    $t = (string) $cell['t'] ?? '';
                    $v = (string) $cell->v ?? '';
                    if ($t === 's') {
                        $firma = $sharedStrings[(int) $v] ?? '';
                    } elseif ($t === 'inlineStr') {
                        $firma = (string) $cell->is->t;
                    } else {
                        $firma = $v;
                    }
                }

                $mail = '';
                if (isset($cells[1])) {
                    $cell = $cells[1];
                    $t = (string) $cell['t'] ?? '';
                    $v = (string) $cell->v ?? '';
                    if ($t === 's') {
                        $mail = $sharedStrings[(int) $v] ?? '';
                    } elseif ($t === 'inlineStr') {
                        $mail = (string) $cell->is->t;
                    } else {
                        $mail = $v;
                    }
                }

                $firmaN = normalizeFirma($firma);
                $arananN = normalizeFirma($arananFirma);

                if ($firmaN && stripos($firmaN, $arananN) !== false) {
                    $mailler[] = $mail;
                }
            }
        }

        $zip->close();

    } else {
        return false;
    }

    if (!empty($mailler)) {
        return $mailler[0];
    } else {
        return false;
    }
}

foreach ($invoices as $invoice) {

    $attr = $invoice['attributes'];
    $title = ($attr['description'] ?? '') . ' ' . ($attr['invoice_no'] ?? '');

    $invoice_id = $invoice['relationships']["active_e_document"]["data"]["id"];
    $file_name = ($attr['invoice_no'] ?? $invoice_id) . '.pdf';

    $pdf_json = $api_base . "/$company_id/e_invoices/$invoice_id/pdf";

    $ch = curl_init($pdf_json);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $access_token",
            "Accept: application/json"
        ],
    ]);
    $pdf_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && $pdf_response) {
        $pdf_data = json_decode($pdf_response, true);
        $pdf_url = $pdf_data['data']['attributes']['url'] ?? null;

        if ($pdf_url) {
            $ch2 = curl_init($pdf_url);
            curl_setopt_array($ch2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $pdf_content = curl_exec($ch2);
            $pdf_http = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($pdf_http == 200 && $pdf_content) {
                file_put_contents($save_dir . $file_name, $pdf_content);

                $subject = $attr['description'];
                
                $mail_bul = find_mail_excel($subject);

                if ($mail_bul != false) {
                    $to = $mail_bul;
					
                    $attachmentPath = $save_dir . '/' . $file_name;

                    $bodyHtml = '
<p>Merhaba,</p>
<p>' . date("d.m.Y", strtotime($attr["issue_date"])) . ' tarihinde firmanıza fatura iletilmiştir. Fatura detayları ektedir, <strong>lütfen bizlere fatura işlem süreci hakkında dönüş sağlar mısınız?</strong>
<br>
Herhangi bir soru/hata için bu mail üzerinden bizlerle iletişime geçebilirsiniz.
</p>
';

                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = MAIL_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = MAIL_USER;
                        $mail->Password = MAIL_PASS;
                        //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
						$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = MAIL_PORT;
						$mail->Timeout = 15;

                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ];

                        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                        $mail->addAddress($to);
                        //$mail->addCC('iletisim@leventemre.com'); // maybe add your e-mail on cc for saving sending mails

                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Encoding = 'base64';
                        $mail->Subject = $subject;
                        $mail->Body = $bodyHtml;

                        if (file_exists($attachmentPath)) {
                            $mail->addAttachment($attachmentPath);
                        }

                        $mail->send();
                        sendTelegram("Mail gönderildi: {$mail_bul}", $subject);

                    } catch (Exception $e) {
                        sendTelegram("Mail gönderilemedi: {$mail_bul}\nHata: " . $mail->ErrorInfo, $subject);
                    }

                } else {
                    echo $subject . " mail adresi bulunamadı.";
                }

            } else {
                echo "PDF alınamadı (S3): $file_name (HTTP $pdf_http)<br>";
            }
        } else {
            echo "PDF URL bulunamadı: $file_name<br>";
        }
    } else {
        echo "PDF JSON alınamadı: $file_name (HTTP $http_code $pdf_json)<br>";
    }



}

echo "<br>İşlem tamamlandı.";
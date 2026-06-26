# Paraşüt Automatic Invoice Mail Sender

Automatically download daily invoices from **Paraşüt**, match customer email addresses using an Excel file, send invoice PDFs via email, and receive instant Telegram notifications.

---

## Türkçe

### 📌 Proje Hakkında

Bu proje, **Paraşüt** ön muhasebe programından günlük oluşturulan faturaları otomatik olarak çeker, ilgili firmaların e-posta adreslerini bir Excel dosyasından eşleştirir ve faturaları PDF ekiyle birlikte otomatik olarak gönderir.

Ayrıca tüm işlem sonuçlarını ve oluşabilecek hataları Telegram üzerinden bildirir.

---

## ✨ Özellikler

* 📄 Günlük faturaları Paraşüt API üzerinden otomatik olarak indirir.
* 📧 Firma adını Excel dosyasıyla eşleştirerek doğru e-posta adresini bulur.
* 📎 PDF faturayı PHPMailer ile otomatik olarak gönderir.
* 🤖 Gönderim başarılarını ve hataları Telegram üzerinden bildirir.
* 🔄 Paraşüt API erişim tokenlarını otomatik olarak yeniler ve `token.json` dosyasında saklar.

---

## 📋 Gereksinimler

* PHP 7.4 veya üzeri
* Composer
* PHP eklentileri:

  * cURL
  * ZIP
  * mbstring

---

## ⚙️ Kurulum

Projeyi bilgisayarınıza veya sunucunuza indirin.

Ardından proje dizininde aşağıdaki komutu çalıştırın:

```bash
composer install
```

Daha sonra aşağıdaki dosyayı oluşturun:

```
company_mail.xlsx
```

Excel dosya formatı:

| A Sütunu  | B Sütunu       |
| --------- | -------------- |
| Firma Adı | E-posta Adresi |

---

# 🔧 Yapılandırma

`sender.php` dosyasının üst kısmındaki ayarları kendi bilgilerinizle doldurun.

## 1. Paraşüt API Ayarları

```php
$client_id = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$company_id = 'YOUR_COMPANY_ID';
$redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
$first_auth_code = 'FIRST_AUTH_CODE';
```

---

## 2. SMTP Ayarları

```php
define('MAIL_HOST', 'mail.example.com');
define('MAIL_USER', 'info@example.com');
define('MAIL_PASS', 'YOUR_PASSWORD');
define('MAIL_PORT', 587);

define('MAIL_FROM', 'info@example.com');
define('MAIL_FROM_NAME', 'Company Name');
```

---

## 3. Telegram Ayarları

```php
define('TG_TOKEN', 'BOT_TOKEN');
define('TG_CHAT_ID', 'CHAT_ID');
```

Bot oluşturmak için **@BotFather**, Chat ID öğrenmek için ise **@userinfobot** kullanılabilir.

---

# ▶️ Kullanım

Manuel olarak çalıştırmak için:

```bash
php sender.php
```

---

# ⏰ Cronjob

Her gün saat **18:00**'de çalıştırmak için:

```cron
0 18 * * * /usr/bin/php /path/to/project/sender.php >/dev/null 2>&1
```

---

# 📂 Proje Yapısı

```
.
├── faturalar/
├── company_mail.xlsx
├── sender.php
├── token.json
├── composer.json
└── vendor/
```

---

## 📁 Dosyalar

### faturalar/

İndirilen PDF faturalar burada saklanır.

> Klasör yoksa otomatik oluşturulur.

---

### token.json

Paraşüt API erişim tokenları burada tutulur.

Silinirse, bir sonraki çalıştırmada `first_auth_code` kullanılarak yeniden oluşturulur.

---

# English

## 📌 About

This project automatically downloads daily invoices from **Paraşüt**, matches customer email addresses using an Excel file, sends invoice PDFs via email, and reports the results through Telegram.

---

## ✨ Features

* Automatically downloads daily invoices from the Paraşüt API.
* Matches company names with email addresses from an Excel file.
* Sends invoice PDFs using PHPMailer.
* Sends Telegram notifications for successful deliveries and errors.
* Automatically refreshes and stores Paraşüt API access tokens.

---

## 📋 Requirements

* PHP 7.4+
* Composer
* PHP Extensions:

  * cURL
  * ZIP
  * mbstring

---

## ⚙️ Installation

Clone or download the project.

Install dependencies:

```bash
composer install
```

Create the following file:

```
company_mail.xlsx
```

| Column A     | Column B      |
| ------------ | ------------- |
| Company Name | Email Address |

---

## 🔧 Configuration

Edit the configuration section at the top of `sender.php`.

### Paraşüt API

```php
$client_id = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$company_id = 'YOUR_COMPANY_ID';
$redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
$first_auth_code = 'FIRST_AUTH_CODE';
```

### SMTP

```php
define('MAIL_HOST', 'mail.example.com');
define('MAIL_USER', 'info@example.com');
define('MAIL_PASS', 'YOUR_PASSWORD');
define('MAIL_PORT', 587);

define('MAIL_FROM', 'info@example.com');
define('MAIL_FROM_NAME', 'Company Name');
```

### Telegram

```php
define('TG_TOKEN', 'BOT_TOKEN');
define('TG_CHAT_ID', 'CHAT_ID');
```

---

## ▶️ Usage

```bash
php sender.php
```

---

## ⏰ Cron Job

```cron
0 18 * * * /usr/bin/php /path/to/project/sender.php >/dev/null 2>&1
```

---

## 📂 Project Structure

```
.
├── faturalar/
├── company_mail.xlsx
├── sender.php
├── token.json
├── composer.json
└── vendor/
```

---

## 📄 License

This project is released under the MIT License.

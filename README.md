# Laravel PDF Generator

HTML অথবা Blade view থেকে PDF বানান (Dompdf ব্যবহার)। **বাংলা ফন্ট সাপোর্ট** আছে – বাংলা টেক্সট PDF এ সঠিকভাবে দেখাবে।

## Installation

```bash
composer require sabuj073/laravel-pdf-generator
```

```bash
php artisan vendor:publish --tag=pdf-generator-config
```

## Bangla ফন্ট সেটআপ (বাংলা + ইংরেজি দুটোই সাপোর্ট)

**Noto Sans Bengali** একটি ফন্ট দিয়েই বাংলা ও ইংরেজি দুটো ঠিকভাবে দেখাবে।

### অটো ইনস্টল (সুপারিশকৃত)

```bash
php artisan pdf-generator:install-bangla-font
```

এটা `storage/fonts/NotoSansBengali-Regular.ttf` এ ফন্ট ডাউনলোড করবে। `.env` এ পাথ দিতে পারেন: `PDF_BANGLA_FONT_PATH=` অথবা ফাঁকা রাখলেও হবে – প্যাকেজ নিজে `storage/fonts/` চেক করবে।

### ম্যানুয়াল ইনস্টল

যদি কমান্ড কাজ না করে:

1. https://fonts.google.com/noto/specimen/Noto+Sans+Bengali এ যান
2. Download করুন, ZIP এক্সট্রাক করে `NotoSansBengali-Regular.ttf` বের করুন
3. `storage/fonts/` এ রাখুন অথবা প্যাকেজের `vendor/sabuj073/laravel-pdf-generator/resources/fonts/` এ

### প্যাকেজের ভেতরে ফন্ট রাখা

আপনি চাইলে `.ttf` ফাইল সরাসরি প্যাকেজের `resources/fonts/` ফোল্ডারে রেখে পাবলিশ করতে পারেন – তখন ইউজারদের আলাদা ডাউনলোড করতে হবে না। (Noto Sans Bengali OFL লাইসেন্সের অধীন, পুনর্বিতরণ অনুমোদিত।)

## Configuration

`.env`:

```
PDF_PAPER=A4
PDF_ORIENTATION=portrait
PDF_FONT="DejaVu Sans"

PDF_BANGLA_FONT_ENABLED=true
PDF_BANGLA_FONT_PATH=  (অথবা পুরো পাথ দিন)
PDF_BANGLA_FONT_FAMILY="Noto Sans Bengali"
```

## Usage

**Blade view থেকে ডাউনলোড (বাংলা থাকলে `true` দিন):**

```php
use Sabuj073\PdfGenerator\PdfGenerator;

$pdf = app(PdfGenerator::class);
return $pdf->download('invoices.show', ['invoice' => $invoice], 'invoice-001.pdf', true);
```

**ব্রাউজারে ইনলাইন দেখানোর জন্য:**

```php
return $pdf->stream('reports.monthly', ['data' => $data], 'report.pdf', true);
```

**ফাইলে সেভ:**

```php
$pdf->save('invoices.show', storage_path('app/invoices/inv-001.pdf'), ['invoice' => $invoice], true);
```

**HTML স্ট্রিং থেকে (বাংলা সহ):**

```php
$html = '<h1>নমস্কার</h1><p>বাংলা টেক্সট PDF এ আসবে।</p>';
$rawPdf = $pdf->fromHtml($html, true);
// or
$pdf->saveHtml($html, storage_path('app/temp.pdf'), true);
```

কনফিগে `PDF_BANGLA_FONT_ENABLED=true` এবং ফন্ট পাথ সেট থাকলে `loadView`/`fromHtml` ডিফল্টই Bangla ফন্ট ব্যবহার করে। আলাদা করে চাইলে চতুর্থ/তৃতীয় আর্গুমেন্টে `true` দিন।

**রাও আউটপুট (কাস্টম রেসপন্স):**

```php
$output = $pdf->fromView('reports.summary', ['items' => $items]);
return response($output, 200, ['Content-Type' => 'application/pdf']);
```

## Blade template

সাধারণ HTML + CSS ব্যবহার করুন। ইনলাইন CSS ভালো কাজ করে।

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $invoice->id }}</h1>
    ...
</body>
</html>
```

# Laravel PDF Generator

HTML অথবা Blade view থেকে PDF বানান (Dompdf ব্যবহার)।

## Installation

```bash
composer require sabuj073/laravel-pdf-generator
```

```bash
php artisan vendor:publish --tag=pdf-generator-config
```

## Configuration

`.env`:

```
PDF_PAPER=A4
PDF_ORIENTATION=portrait
PDF_FONT="DejaVu Sans"
```

## Usage

**Blade view থেকে ডাউনলোড:**

```php
use Sabuj073\PdfGenerator\PdfGenerator;

$pdf = app(PdfGenerator::class);
return $pdf->download('invoices.show', ['invoice' => $invoice], 'invoice-001.pdf');
```

**ব্রাউজারে ইনলাইন দেখানোর জন্য:**

```php
return $pdf->stream('reports.monthly', ['data' => $data], 'report.pdf');
```

**ফাইলে সেভ:**

```php
$pdf->save('invoices.show', storage_path('app/invoices/inv-001.pdf'), ['invoice' => $invoice]);
```

**HTML স্ট্রিং থেকে:**

```php
$html = '<h1>Hello</h1><p>World</p>';
$rawPdf = $pdf->fromHtml($html);
// or
$pdf->saveHtml($html, storage_path('app/temp.pdf'));
```

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

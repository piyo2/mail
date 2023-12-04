# piyo2/mail

A PHP mail composer.

## Installation

```bash
composer require piyo2/mail
```

## Usage

```php
<?php
$mail = new \piyo2\mail\Mail();

// Set sender
$mail->from('sender@example.com', 'John Doe');

// Set subject
$mail->subject('Hello World');

// Set body
$mail->message('Hello World');

// Set HTML body
$mail->htmlMessage('<h1>Hello World</h1>');

// Add header
$mail->header('X-My-Header', 'My Header');

// Add attachment
$attachment = \piyo2\mail\Attachment::fromFile('/path/to/file', 'text/plain', 'file.txt');
$mail->attach($attachment);

$other = \piyo2\mail\Attachment::fromContent('Hello World', 'text/plain', 'hello.txt');
$mail->attach($other);

// Send mail
$mail->send('recipient@example.com');
```

<?php
// File: helpers/MailHelper.php

require_once __DIR__ . '/../vendor/autoload.php'; // Ini tetep butuh buat PHPMailer-nya ya

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper {

    public static function sendOTP($toEmail, $toName, $otpCode) {
        // 🔥 BACA FILE .env SECARA NATIVE (Tanpa Composer tambahan) 🔥
        $env = parse_ini_file(__DIR__ . '/../.env');

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $env['SMTP_HOST']; // Ambil dari $env
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['SMTP_USER']; 
            $mail->Password   = $env['SMTP_PASS']; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $env['SMTP_PORT'];

            // Pengirim & Penerima
            // Pengirim & Penerima (Email pengirim WAJIB SAMA dengan akun SMTP_USER)
            $mail->setFrom($env['SMTP_USER'], 'SSO BKK Jateng');
            $mail->addAddress($toEmail, $toName);

            // Konten Email
            $mail->isHTML(true);
            $mail->Subject = 'Kode OTP Reset Password - BKK Jateng';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h3 style='color: #333;'>Halo, {$toName}</h3>
                    <p>Anda meminta untuk mereset password. Berikut adalah kode OTP Anda:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <h2 style='color: #0056b3; letter-spacing: 5px; font-size: 32px; background: #f4f4f4; padding: 15px; border-radius: 8px; display: inline-block;'>{$otpCode}</h2>
                    </div>
                    <p>Kode ini hanya berlaku selama <strong>5 menit</strong>. Jangan berikan kode ini kepada siapapun termasuk pihak BKK!</p>
                </div>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            throw new Exception("Gagal mengirim email OTP. Error: {$mail->ErrorInfo}");
        }
    }
}
<?php
/**
 * Email sending utilities
 */

/**
 * Send email verification link to new sponsor
 */
function send_verification_email(string $email, string $first_name, string $token): bool {
    $verify_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'millcreek-ar-learning.com')
        . app_url('verify-email.php') . '?token=' . urlencode($token);

    $subject = 'Verify Your Email - Mill Creek-AR Learning Center';
    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button { display: inline-block; padding: 12px 24px; background-color: #133d34; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; }
            .footer { font-size: 12px; color: #666; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Welcome, $first_name!</h2>
            <p>Thank you for creating a sponsor account with the Mill Creek-AR Learning Center. To activate your account, please verify your email address.</p>
            <p><a href="$verify_url" class="button">Verify Email Address</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p style="word-break: break-all; font-size: 12px; color: #666;">$verify_url</p>
            <p>This link expires in 24 hours.</p>
            <div class="footer">
                <p>If you did not create this account, please ignore this email.</p>
                <p>&copy; Mill Creek-AR Learning Center. A learning initiative in Accra, Ghana.</p>
            </div>
        </div>
    </body>
    </html>
    HTML;

    return send_mail($email, $subject, $html);
}

/**
 * Send donation receipt/confirmation
 */
function send_donation_receipt(string $email, string $first_name, float $amount, string $frequency, string $student_name): bool {
    $subject = 'Donation Confirmation - Mill Creek-AR Learning Center';
    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .amount { font-size: 24px; font-weight: 700; color: #133d34; }
            .footer { font-size: 12px; color: #666; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Thank you for your support, $first_name!</h2>
            <p>Your donation has been confirmed:</p>
            <p>
                <strong>Student:</strong> $student_name<br>
                <strong>Amount:</strong> <span class="amount">\$$amount</span><br>
                <strong>Frequency:</strong> $frequency
            </p>
            <p>You will receive updates about your student's learning journey in your sponsor portal.</p>
            <div class="footer">
                <p>&copy; Mill Creek-AR Learning Center. A learning initiative in Accra, Ghana.</p>
            </div>
        </div>
    </body>
    </html>
    HTML;

    return send_mail($email, $subject, $html);
}

/**
 * Send password reset link
 */
function send_password_reset_email(string $email, string $first_name, string $token): bool {
    $reset_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'millcreek-ar-learning.com')
        . app_url('reset_password.php') . '?token=' . urlencode($token);

    $subject = 'Reset Your Password - Mill Creek-AR Learning Center';
    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button { display: inline-block; padding: 12px 24px; background-color: #133d34; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; }
            .footer { font-size: 12px; color: #666; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Password Reset Request</h2>
            <p>Hi $first_name,</p>
            <p>We received a request to reset the password for your sponsor account. If this was not you, please ignore this email.</p>
            <p><a href="$reset_url" class="button">Reset Password</a></p>
            <p>Or copy and paste this link:</p>
            <p style="word-break: break-all; font-size: 12px; color: #666;">$reset_url</p>
            <p>This link expires in 24 hours.</p>
            <div class="footer">
                <p>&copy; Mill Creek-AR Learning Center. A learning initiative in Accra, Ghana.</p>
            </div>
        </div>
    </body>
    </html>
    HTML;

    return send_mail($email, $subject, $html);
}

/**
 * Generic mail function using PHP's mail() or configured SMTP
 * Can be replaced with Mailer/PHPMailer for production
 */
function send_mail(string $to, string $subject, string $html): bool {
    $from = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'millcreek-ar-learning.com');
    $headers = "MIME-Version: 1.0\r\n"
        . "Content-type: text/html; charset=UTF-8\r\n"
        . "From: Mill Creek-AR Learning Center <$from>\r\n"
        . "X-Mailer: PHP/" . phpversion();

    return (bool)mail($to, $subject, $html, $headers);
}

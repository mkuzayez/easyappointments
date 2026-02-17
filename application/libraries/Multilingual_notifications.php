<?php defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Multilingual notifications library.
 *
 * Sends branded HTML emails to customers in their preferred language (AR/EN).
 *
 * @package Libraries
 */
class Multilingual_notifications
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('branches_model');
        $this->CI->load->model('services_model');
    }

    /**
     * Send appointment confirmation in user's preferred language.
     *
     * @param array $appointment Appointment data (DB format).
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param string|null $language Language code ('ar' or 'en'). Falls back to customer's preferred_language, then 'en'.
     */
    public function send_appointment_confirmation(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        ?string $language = null,
    ): void {
        $language = $language ?? ($customer['preferred_language'] ?? 'en');

        // Resolve branch info if the service has a branch.
        $branch_name = 'Expert Medical Center';
        $branch_name_ar = 'مركز الخبراء الطبي';
        $branch_phone = '';

        if (!empty($service['id_branches'])) {
            try {
                $branch = $this->CI->branches_model->find((int) $service['id_branches']);
                $branch_name = $branch['name'] ?? $branch_name;
                $branch_name_ar = $branch['name_ar'] ?? $branch_name_ar;
                $branch_phone = $branch['phone'] ?? '';
            } catch (Throwable $e) {
                log_message('error', 'Multilingual_notifications: Could not load branch: ' . $e->getMessage());
            }
        }

        $data = [
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'provider_name' => 'Dr. ' . trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'appointment_time' => date('h:i A', strtotime($appointment['start_datetime'])),
            'service_name' => $service['name'] ?? '',
            'payment_amount' => $appointment['payment_amount'] ?? null,
            'payment_currency' => $appointment['payment_currency'] ?? 'AED',
            'branch_name' => $language === 'ar' ? $branch_name_ar : $branch_name,
            'branch_phone' => $branch_phone,
            'reschedule_link' => site_url('booking/reschedule/' . ($appointment['hash'] ?? '')),
            'cancel_link' => site_url('booking/cancel/' . ($appointment['hash'] ?? '')),
        ];

        // Load the template for the requested language, falling back to English.
        $template = 'emails/' . $language . '/appointment_confirmation';

        if (!file_exists(VIEWPATH . $template . '.php')) {
            $template = 'emails/en/appointment_confirmation';
        }

        $html = $this->CI->load->view($template, $data, true);

        $subject =
            $language === 'ar'
                ? 'تأكيد الموعد - مركز الخبراء الطبي'
                : 'Appointment Confirmation - Expert Medical Center';

        $this->send_email($customer['email'], $subject, $html);
    }

    /**
     * Send email via PHPMailer (follows the same SMTP configuration as Email_messages).
     *
     * @param string $to Recipient email address.
     * @param string $subject Email subject.
     * @param string $html HTML message body.
     */
    private function send_email(string $to, string $subject, string $html): void
    {
        try {
            $php_mailer = new PHPMailer(true);

            $php_mailer->CharSet = 'UTF-8';
            $php_mailer->SMTPDebug = config('smtp_debug') ? SMTP::DEBUG_SERVER : null;

            if (config('protocol') === 'smtp') {
                $php_mailer->isSMTP();
                $php_mailer->Host = config('smtp_host');
                $php_mailer->SMTPAuth = config('smtp_auth');
                $php_mailer->Username = config('smtp_user');
                $php_mailer->Password = config('smtp_pass');
                $php_mailer->SMTPSecure = config('smtp_crypto');
                $php_mailer->Port = config('smtp_port');
            }

            $from_name = config('from_name') ?: setting('company_name');
            $from_address = config('from_address') ?: setting('company_email');
            $reply_to_address = config('reply_to') ?: setting('company_email');

            $php_mailer->setFrom($from_address, $from_name);
            $php_mailer->addReplyTo($reply_to_address);
            $php_mailer->addAddress($to);
            $php_mailer->Subject = $subject;

            if (config('mailtype') === 'html') {
                $php_mailer->isHTML();
                $php_mailer->Body = $html;
                $php_mailer->AltBody = strip_tags($html);
            } else {
                $php_mailer->Body = strip_tags($html);
            }

            $php_mailer->send();
        } catch (Throwable $e) {
            log_message(
                'error',
                'Multilingual_notifications: Email failed to send to ' . $to . ': ' . $e->getMessage(),
            );
        }
    }
}

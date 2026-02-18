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

        [$branch_name, $branch_phone] = $this->resolve_branch($service, $language);

        $data = [
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'provider_name' => 'Dr. ' . trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'appointment_time' => date('h:i A', strtotime($appointment['start_datetime'])),
            'service_name' => $service['name'] ?? '',
            'payment_amount' => $appointment['payment_amount'] ?? null,
            'payment_currency' => $appointment['payment_currency'] ?? 'AED',
            'branch_name' => $branch_name,
            'branch_phone' => $branch_phone,
            'reschedule_link' => $this->frontend_url('booking/reschedule/' . ($appointment['hash'] ?? '')),
            'cancel_link' => $this->frontend_url('booking/cancel/' . ($appointment['hash'] ?? '')),
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
     * Send appointment cancellation email in user's preferred language.
     *
     * @param array $appointment Appointment data (DB format).
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param string|null $language Language code ('ar' or 'en').
     */
    public function send_appointment_cancelled(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        ?string $language = null,
    ): void {
        $language = $language ?? ($customer['preferred_language'] ?? 'en');

        [$branch_name, $branch_phone] = $this->resolve_branch($service, $language);

        $data = [
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'provider_name' => 'Dr. ' . trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'appointment_time' => date('h:i A', strtotime($appointment['start_datetime'])),
            'service_name' => $service['name'] ?? '',
            'branch_name' => $branch_name,
            'branch_phone' => $branch_phone,
            'refund_amount' => $appointment['refund_amount'] ?? null,
            'refund_currency' => $appointment['payment_currency'] ?? 'AED',
        ];

        $template = 'emails/' . $language . '/appointment_cancelled';

        if (!file_exists(VIEWPATH . $template . '.php')) {
            $template = 'emails/en/appointment_cancelled';
        }

        $html = $this->CI->load->view($template, $data, true);

        $subject =
            $language === 'ar'
                ? 'إلغاء الموعد - مركز الخبراء الطبي'
                : 'Appointment Cancelled - Expert Medical Center';

        $this->send_email($customer['email'], $subject, $html);
    }

    /**
     * Send appointment rescheduled email in user's preferred language.
     *
     * @param array $appointment Appointment data (DB format) — the updated appointment with new date/time.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param string|null $language Language code ('ar' or 'en').
     */
    public function send_appointment_rescheduled(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        ?string $language = null,
    ): void {
        $language = $language ?? ($customer['preferred_language'] ?? 'en');

        [$branch_name, $branch_phone] = $this->resolve_branch($service, $language);

        $data = [
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'provider_name' => 'Dr. ' . trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'new_appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'new_appointment_time' => date('h:i A', strtotime($appointment['start_datetime'])),
            'service_name' => $service['name'] ?? '',
            'branch_name' => $branch_name,
            'branch_phone' => $branch_phone,
            'reschedule_link' => $this->frontend_url('booking/reschedule/' . ($appointment['hash'] ?? '')),
            'cancel_link' => $this->frontend_url('booking/cancel/' . ($appointment['hash'] ?? '')),
        ];

        $template = 'emails/' . $language . '/appointment_rescheduled';

        if (!file_exists(VIEWPATH . $template . '.php')) {
            $template = 'emails/en/appointment_rescheduled';
        }

        $html = $this->CI->load->view($template, $data, true);

        $subject =
            $language === 'ar'
                ? 'إعادة جدولة الموعد - مركز الخبراء الطبي'
                : 'Appointment Rescheduled - Expert Medical Center';

        $this->send_email($customer['email'], $subject, $html);
    }

    /**
     * Send payment pending email in user's preferred language.
     *
     * @param array $appointment Appointment data (DB format).
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param string $payment_link URL for the customer to complete payment.
     * @param string|null $language Language code ('ar' or 'en').
     */
    public function send_payment_pending(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        string $payment_link,
        ?string $language = null,
    ): void {
        $language = $language ?? ($customer['preferred_language'] ?? 'en');

        [$branch_name, $branch_phone] = $this->resolve_branch($service, $language);

        $data = [
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'provider_name' => 'Dr. ' . trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'appointment_time' => date('h:i A', strtotime($appointment['start_datetime'])),
            'service_name' => $service['name'] ?? '',
            'branch_name' => $branch_name,
            'branch_phone' => $branch_phone,
            'payment_amount' => $appointment['payment_amount'] ?? ($service['price'] ?? ''),
            'payment_currency' => $appointment['payment_currency'] ?? 'AED',
            'payment_link' => $payment_link,
        ];

        $template = 'emails/' . $language . '/payment_pending';

        if (!file_exists(VIEWPATH . $template . '.php')) {
            $template = 'emails/en/payment_pending';
        }

        $html = $this->CI->load->view($template, $data, true);

        $subject =
            $language === 'ar'
                ? 'الدفع معلق - مركز الخبراء الطبي'
                : 'Payment Pending - Expert Medical Center';

        $this->send_email($customer['email'], $subject, $html);
    }

    /**
     * Send feedback request email in user's preferred language.
     *
     * @param array $appointment Appointment data (DB format).
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param string $feedback_link URL for the customer to submit feedback.
     * @param string|null $language Language code ('ar' or 'en').
     */
    public function send_feedback_request(
        array $appointment,
        array $provider,
        array $customer,
        string $feedback_link,
        ?string $language = null,
    ): void {
        $language = $language ?? ($customer['preferred_language'] ?? 'en');

        $service_name = '';

        if (!empty($appointment['id_services'])) {
            try {
                $service = $this->CI->services_model->find((int) $appointment['id_services']);
                $service_name = $service['name'] ?? '';
            } catch (Throwable $e) {
                log_message('error', 'Multilingual_notifications: Could not load service: ' . $e->getMessage());
            }
        }

        $data = [
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'provider_name' => 'Dr. ' . trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
            'appointment_date' => date('d M Y', strtotime($appointment['start_datetime'])),
            'service_name' => $service_name,
            'feedback_link' => $feedback_link,
        ];

        $template = 'emails/' . $language . '/feedback_request';

        if (!file_exists(VIEWPATH . $template . '.php')) {
            $template = 'emails/en/feedback_request';
        }

        $html = $this->CI->load->view($template, $data, true);

        $subject =
            $language === 'ar'
                ? 'شاركنا رأيك - مركز الخبراء الطبي'
                : 'Share Your Feedback - Expert Medical Center';

        $this->send_email($customer['email'], $subject, $html);
    }

    /**
     * Build a frontend URL for a given path.
     *
     * @param string $path URL path (e.g. 'booking/payment/abc123').
     *
     * @return string Full frontend URL.
     */
    private function frontend_url(string $path): string
    {
        return rtrim(config('frontend_url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Resolve branch name and phone from the service record.
     *
     * @param array $service Service data.
     * @param string $language Language code.
     *
     * @return array [branch_name, branch_phone]
     */
    private function resolve_branch(array $service, string $language): array
    {
        $branch_name = $language === 'ar' ? 'مركز الخبراء الطبي' : 'Expert Medical Center';
        $branch_phone = '';

        if (!empty($service['id_branches'])) {
            try {
                $branch = $this->CI->branches_model->find((int) $service['id_branches']);
                $branch_name = $language === 'ar'
                    ? ($branch['name_ar'] ?? $branch_name)
                    : ($branch['name'] ?? $branch_name);
                $branch_phone = $branch['phone'] ?? '';
            } catch (Throwable $e) {
                log_message('error', 'Multilingual_notifications: Could not load branch: ' . $e->getMessage());
            }
        }

        return [$branch_name, $branch_phone];
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

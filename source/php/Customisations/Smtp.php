<?php

declare(strict_types=1);

namespace EslovCustomisation\Customisations;

/**
 * Route outgoing mail through SMTP instead of the server's local mail transport.
 *
 * Ported from the eslov-se mu-plugin (web/app/mu-plugins/smtp.php). Credentials
 * are still supplied as SMTP_* constants (typically defined from env vars in
 * config/application.php); the filter is a no-op until all of them are set.
 */
class Smtp
{
    public function __construct()
    {
        add_action('phpmailer_init', [$this, 'configure'], PHP_INT_MAX);
    }

    /**
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
     */
    public function configure($phpmailer): void
    {
        if (
            !defined('SMTP_HOST') ||
            !defined('SMTP_PORT') ||
            !defined('SMTP_USERNAME') ||
            !defined('SMTP_PASSWORD') ||
            !defined('SMTP_FROM') ||
            !defined('SMTP_FROM_NAME')
        ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = constant('SMTP_HOST');
        $phpmailer->Port = constant('SMTP_PORT');
        $phpmailer->Username = constant('SMTP_USERNAME');
        $phpmailer->Password = constant('SMTP_PASSWORD');
        $phpmailer->SMTPAuth = true;
        $phpmailer->SMTPSecure = 'tls';

        $phpmailer->From = constant('SMTP_FROM');
        $phpmailer->FromName = constant('SMTP_FROM_NAME');
    }
}

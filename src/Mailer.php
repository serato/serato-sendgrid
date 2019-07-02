<?php
declare(strict_types=1);

namespace Serato\SendGrid;

use SendGrid;
use SendGrid\Mail\Mail;
use DateTime;

class Mailer extends SendGrid
{
    const FROM_EMAIL = 'no-reply@serato.com';
    const FROM_NAME = 'Serato Web Mailer';

    /**
     * @var boolean
     */
    private $disableEmailDelivery = false;

    /**
     * Overriding the constructor
     *
     * @param string $apiKey  Your Twilio SendGrid API Key.
     * @param array  $options An array of options, currently only "host", "curl" and
     *                        "impersonateSubuser" are implemented.
     * @param boolean $disableEmailDelivery
     */
    public function __construct($apiKey, $options = array(), $disableEmailDelivery = false)
    {
        $this->disableEmailDelivery = $disableEmailDelivery;
        parent::__construct($apiKey, $options);
    }

    /**
     * Overriding the send method to stop email delivery in load testing
     *
     * @param \SendGrid\Mail\Mail $email A Mail object, containing the request object
     *
     * @return \SendGrid\Response | null
     */
    public function send(Mail $email)
    {
        if ($this->disableEmailDelivery === true) {
            return null;
        }
        return parent::send($email);
    }

    /**
     * Returns a configured Mail object
     * @return Mail
     */
    private function getMail()
    {
        $mail = new Mail();
        $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
        return $mail;
    }
}

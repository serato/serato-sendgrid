<?php
declare(strict_types=1);

namespace Serato\SendGrid;

use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Category;
use SendGrid\Mail\Cc;
use SendGrid\Mail\Bcc;
use SendGrid\Response;
use Exception;

class Mailer extends SendGrid
{
    const EMAIL_FROM = 'no-reply@serato.com';
    const EMAIL_FROM_NAME = 'Serato Web Mailer';
    const EMAIL_CONFIG_FILE_PATH = '/spec/email_config.json';

    /**
     * @var boolean
     */
    private $disableEmailDelivery = false;

    /**
     * @var array
     */
    private $emailConfig;

    /**
     * @var Mail
     */
    private $mail;

    /**
     * Overriding the constructor
     *
     * @param string $apiKey  Your Twilio SendGrid API Key.
     * @param boolean $disableEmailDelivery  Optional flag for disabling the delivery
     * @param string $emailConfigFilePath   Optional path for email configuration (mainly for testing purpose)
     * @param array  $options An array of options, currently only "host", "curl" and
     *                        "impersonateSubuser" are implemented.
     */
    public function __construct(
        string $apiKey,
        bool $disableEmailDelivery = false,
        string $emailConfigFilePath = '',
        array $options = array()
    ) {
        $this->disableEmailDelivery = $disableEmailDelivery;
        if ($emailConfigFilePath !== '') {
            $configurationJson = file_get_contents($emailConfigFilePath);
        } else {
            $configurationJson = file_get_contents(__DIR__ . self::EMAIL_CONFIG_FILE_PATH);
        }
        if (is_string($configurationJson)) {
            $this->emailConfig = json_decode($configurationJson, true);
        }
        if ($this->emailConfig === null || !is_array($this->emailConfig) || empty($this->emailConfig)) {
            throw new Exception('SendGrid Mailer Exception - Invalid email configuration.');
        }
        parent::__construct($apiKey, $options);
    }

    /**
     * Returns email configuration for a specific template name
     *
     * @param string $templateName
     * @return array
     */

    public function getEmailConfigByName(String $templateName): array
    {
        if (array_key_exists($templateName, $this->getEmailConfig())) {
            return $this->getEmailConfig()[$templateName];
        }
        throw new Exception('SendGrid Mailer Exception - Invalid email template name.');
    }

    /**
     * Check template parameters are valid
     *
     * @param array $templateParams
     * @param array $emailOptTemplateParams
     * @return boolean
     */
    public function validateTemplateParams(Array $templateParams, Array $emailOptTemplateParams): bool
    {
        foreach ($templateParams as $key => $value) {
            if (!in_array($key, $emailOptTemplateParams)) {
                throw new Exception('SendGrid Mailer Exception - Invalid parameter: ' . $key);
            }
            if (!is_string($key) || (!is_string($value) && !is_bool($value))) {
                throw new Exception('SendGrid Mailer Exception - Parameter should be string: "' . $key . '"');
            }
        }

        foreach ($emailOptTemplateParams as $param) {
            if (!array_key_exists($param, $templateParams)) {
                throw new Exception('SendGrid Mailer Exception - Parameter "' . $param . '" is missing.');
            }
        }

        return true;
    }

    /**
     * Send email
     *
     * @param string $templateName
     * @return \SendGrid\Response | null
     */
    public function sendEmail(
        string $templateName,
        string $recipientName,
        string $recipientEmail,
        string $language,
        array $templateParams
    ):? Response {
        $emailOptions = $this->getEmailConfigByName($templateName);
        $this->validateTemplateParams($templateParams, $emailOptions['template_params']);

        $emailLanguage = isset($emailOptions['languages'][$language]) ? $language : 'en';
        $templateId = $emailOptions['languages'][$emailLanguage]['template_id'];
        $categories = array_unique(array_merge(
            $emailOptions['languages'][$emailLanguage]['categories'],
            $emailOptions['categories']
        ));

        $this->mail = new Mail();

        $emailFromEmail = self::EMAIL_FROM;
        $emailFromEmailName = self::EMAIL_FROM_NAME;
        if (isset($emailOptions['email_from']) && isset($emailOptions['email_from']['from'])) {
            $emailFromEmail = $emailOptions['email_from']['from']['email'];
            $emailFromEmailName = $emailOptions['email_from']['from']['name'];
        };
        $this->mail->setFrom($emailFromEmail, $emailFromEmailName);

        if (isset($emailOptions['email_from']) && isset($emailOptions['email_from']['reply_to'])) {
            $replyToEmail = $emailOptions['email_from']['reply_to']['email'];
            $replyToEmailName = $emailOptions['email_from']['reply_to']['name'];
            $this->mail->setReplyTo($replyToEmail, $replyToEmailName);
        };

        foreach ($templateParams as $key => $value) {
              $this->mail->addSubstitution($key, $value);
        }
        foreach ($categories as $category) {
            $this->mail->addCategory(new Category($category));
        }
        $this->mail->addTo($recipientEmail, $recipientName);
        $this->mail->setTemplateId($templateId);
        if (isset($emailOptions['recipients']) && isset($emailOptions['recipients']['bcc'])) {
            foreach ($emailOptions['recipients']['bcc'] as $bcc) {
                $this->mail->addBcc(new Bcc($bcc));
            }
        }
        if (isset($emailOptions['recipients']) && isset($emailOptions['recipients']['cc'])) {
            foreach ($emailOptions['recipients']['cc'] as $cc) {
                $this->mail->addCc(new Cc($cc));
            }
        }

        if ($this->disableEmailDelivery === true) {
            return null;
        }
        return parent::send($this->mail);
    }

    /**
     * Returns the email configuration
     * @return array
     */
    public function getEmailConfig()
    {
        return $this->emailConfig;
    }

    /**
     * Returns the SendGrid Mail object
     * @return Mail
     */
    public function getMail()
    {
        return $this->mail;
    }
}

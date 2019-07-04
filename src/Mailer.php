<?php
declare(strict_types=1);

namespace Serato\SendGrid;

use phpDocumentor\Reflection\Types\Boolean;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Category;
use SendGrid\Mail\Cc;
use SendGrid\Mail\Bcc;
use SendGrid\Response;
use Exception;

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
    public function __construct($apiKey, $disableEmailDelivery = false, $options = array())
    {
        $this->disableEmailDelivery = $disableEmailDelivery;
        parent::__construct($apiKey, $options);
    }

    /**
     * Fetch email information from email_config.json file by template name
     *
     * @param string $templateName
     * @return array
     */

    public function fetchEmailOptionsByTemplateName(String $templateName): array
    {
        // Read JSON file
        $templatesJson = file_get_contents(__DIR__ . '/spec/email_config.json');
        //Decode JSON
        $templatesJsonData = '';
        if (is_string($templatesJson)) {
            $templatesJsonData = json_decode($templatesJson, true);
        }
        if ($templatesJsonData === null) {
            // $templatesJsonData is null because the json cannot be decoded
            throw new Exception('Invalid JSON file.');
        }
            //Traverse array and get the data for students aged less than 20
        foreach ($templatesJsonData as $key => $value) {
            if ($key == $templateName) {
                return $value;
            }
        }
        throw new Exception('Invalid template name');
    }

    /**
     * Check template params are valid
     *
     * @param array $templateParams
     * @param array $emailOptTemplateParams
     * @return array
     */
    public function validateTemplateParams(Array $templateParams, Array $emailOptTemplateParams): array
    {
        $validTemplateParams = array();
        foreach ($templateParams as $key => $value) {
            if (in_array($key, $emailOptTemplateParams)) {
                $validTemplateParams[$key] = $value;
            }
        }
        return $validTemplateParams;
    }

    /**
     * Check template language is valid
     *
     * @param string $language
     * @param array $emailLanguageOptions
     * @return bool
     */
    public function validateEmailLanguage(String $language, Array $emailLanguageOptions): bool
    {
        if (isset($emailLanguageOptions[$language]) && isset($emailLanguageOptions[$language]['template_id'])) {
            return true;
        } else {
            return false;
        }
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
        $emailOptions = $this->fetchEmailOptionsByTemplateName($templateName);
        $validTemplateParams = $this->validateTemplateParams($templateParams, $emailOptions['template_params']);

        $emailLanguage = $this->validateEmailLanguage($language, $emailOptions['languages']) ? $language : 'en';
        $templateId = $emailOptions['languages'][$emailLanguage]['template_id'];
        $categories = array_merge(
            $emailOptions['languages'][$emailLanguage]['categories'],
            $emailOptions['categories']
        );

        // get a configured Mail object
        $mail = new Mail();
        $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
        foreach ($validTemplateParams as $key => $value) {
              $mail->addSubstitution($key, $value);
        }
        foreach ($categories as $category) {
            $mail->addCategory(new Category($category));
        }
        $mail->addTo($recipientEmail, $recipientName);
        $mail->setTemplateId($templateId);
        if (isset($emailOptions['recipients']) && isset($emailOptions['recipients']['bcc'])) {
            foreach ($emailOptions['recipients']['bcc'] as $bcc) {
                $mail->addBcc(new Bcc($bcc));
            }
        }
        if (isset($emailOptions['recipients']) && isset($emailOptions['recipients']['cc'])) {
            foreach ($emailOptions['recipients']['cc'] as $cc) {
                $mail->addCc(new Cc($cc));
            }
        }

        try {
            // send mail
            if ($this->disableEmailDelivery === true) {
                return null;
            }
            return parent::send($mail);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), '\n';
        }
    }
}

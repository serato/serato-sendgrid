<?php
declare(strict_types=1);

namespace Serato\SendGrid\Test;

use PHPUnit\Framework\TestCase;
use Serato\SendGrid\Mailer;

class MailerTest extends TestCase
{
    const NOT_DISABLE_EMAIL_DELIVERY = false;
    const DISABLE_EMAIL_DELIVERY = true;
    const TEMPLATE_NAME = 'studio-sub-voluntary-cancel';
    const FAKE_API_KEY = 'fake-api-key';


    /**
     * Test email options by a valid template name
     * @group mail
     */
    public function testFetchEmailOptionsByTemplateNameWithValidTemplateName()
    {
        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);
        $emailOption = $mail->fetchEmailOptionsByTemplateName(self::TEMPLATE_NAME);

        $this->assertNotNull($emailOption);
    }

    /**
     * Test email options by an invalid template name
     * @group mail
     *
     * @expectedException \Exception
     */
    public function testFetchEmailOptionsByTemplateNameWithInvalidTemplateName()
    {
        $templateName = 'invalid-template-name';

        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);
        $emailOption = $mail->fetchEmailOptionsByTemplateName($templateName);
    }

    /**
     * Test template params are valid with valid params
     * @group mail
     */
    public function testValidateTemplateParamsWithValidParams()
    {
        $templateParams = ['subscription_end_date'=>'2017-12-12', 'plan_name'=>'DJ'];

        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);
        $mailOptions = $mail->fetchEmailOptionsByTemplateName(self::TEMPLATE_NAME);

        $validTemplateParams = $mail->validateTemplateParams($templateParams, $mailOptions['template_params']);

        $this->assertEquals($validTemplateParams, $templateParams);
    }

    /**
     * Test template params are valid with invalid params
     * @group mail
     */
    public function testValidateTemplateParamsWithInvalidParams()
    {
        $templateParams = ['invalid_param'=>'2017-12-12', 'plan_name'=>'DJ'];

        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);
        $mailOptions = $mail->fetchEmailOptionsByTemplateName(self::TEMPLATE_NAME);

        $validTemplateParams = $mail->validateTemplateParams($templateParams, $mailOptions['template_params']);

        $this->assertEquals($validTemplateParams, ['plan_name'=>'DJ']);
    }

    /**
     * Test template language are valid with valid language
     * @group mail
     */
    public function testValidateEmailLanguageWithValidlanguage()
    {
        $language = 'en';

        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);
        $mailOptions = $mail->fetchEmailOptionsByTemplateName(self::TEMPLATE_NAME);

        $this->assertTrue($mail->validateEmailLanguage($language, $mailOptions['languages']));
    }

    /**
     * Test template language are valid with invalid language
     * @group mail
     */
    public function testValidateEmailLanguageWithInvalidlanguage()
    {
        $language = 'zh';

        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);
        $mailOptions = $mail->fetchEmailOptionsByTemplateName(self::TEMPLATE_NAME);

        $this->assertNotTrue($mail->validateEmailLanguage($language, $mailOptions['languages']));
    }

    /**
     * Test that email sent unsuccessfully with disabled email delievery
     * @group mail
     */
    public function testSendMailUnsuccessfully()
    {
        $mail = new Mailer(self:: FAKE_API_KEY, self::DISABLE_EMAIL_DELIVERY);

        $sendgridResponse = $mail->sendEmail(
            self::TEMPLATE_NAME,
            'Jing Xu',
            'jing.xu@serato.com',
            'en',
            [
                'subscription_end_date' => '2017-07-12',
                'plan_name' => 'DJ'
            ]
        );
        $this->assertNull($sendgridResponse);
    }
}

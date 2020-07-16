<?php
declare(strict_types=1);

namespace Serato\SendGrid\Test;

use PHPUnit\Framework\TestCase;
use Serato\SendGrid\Mailer;
use Exception;
use SendGrid\Mail\Attachment;

class MailerTest extends TestCase
{
    const FAKE_API_KEY = 'fake-api-key';

    /**
     * Test default configuration file is valid.
     * Change this test when you add a new template to configuration file.
     *
     * @group config
     */
    public function testDefaultEmailConfiguration()
    {
        $mailer = new Mailer(self::FAKE_API_KEY, true);
        $emailOptions = $mailer->getEmailConfig();

        # Check number of email templates
        # Change this number when a new template is added
        $this->assertEquals(52, count($emailOptions));
        $templateIds = [];

        foreach ($emailOptions as $templateName => $config) {
            # Check that all templates have at least "en" for language
            $this->assertTrue(array_key_exists('en', $config['languages']));
            foreach ($config['languages'] as $lang => $langOptions) {
                # Check that there is a template id for all languages
                $this->assertTrue(
                    !is_null($langOptions['template_id']) && !empty($langOptions['template_id']),
                    "Template ID is missing for '$lang' in '$templateName'"
                );

                # Check that all templates are unique
                $this->assertFalse(
                    in_array($langOptions['template_id'], $templateIds),
                    "The TemplateId in {$templateName} is repeated: {$langOptions['template_id']}"
                );
                $templateIds[] = $langOptions['template_id'];
            }
        }
    }

    /**
     * Test an invalid configuration file
     *
     * @group config
     * @expectedException Exception
     */
    public function testInvalidEmailConfigurationFile()
    {
        $mailer = new Mailer(self::FAKE_API_KEY, true, __DIR__ . '/resources/invalid_email_config.json');
    }

    /**
     * Test sending an email with valid configuration
     *
     * @group mail
     */
    public function testValidEmailConfigurationFile()
    {
        $mailer = new Mailer(self::FAKE_API_KEY, true, __DIR__ . '/resources/valid_email_config.json');

        # check that an invalid template name throws an exception
        try {
            $mailer->sendEmail(
                'invalid-template-name',
                'test name',
                'test@test.com',
                'en',
                ['param1' => 'value1']
            );
        } catch (Exception $e) {
            $this->assertContains('Invalid email template name', $e->getMessage());
        }

        # check that invalid parameters, throws an exception
        try {
            $mailer->sendEmail(
                'studio-sub-voluntary-cancel',
                'test name',
                'test@test.com',
                'en',
                ['param1' => 'value1']
            );
        } catch (Exception $e) {
            $this->assertContains('Invalid parameter', $e->getMessage());
        }

        # check that invalid parameters, throws an exception
        try {
            $mailer->sendEmail(
                'studio-sub-voluntary-cancel',
                'test name',
                'test@test.com',
                'en',
                ['subscription_end_date' => 'value1']
            );
        } catch (Exception $e) {
            $this->assertContains('missing', $e->getMessage());
        }

        # check that invalid parameters, throws an exception
        try {
            $mailer->sendEmail(
                'studio-sub-voluntary-cancel',
                'test name',
                'test@test.com',
                'en',
                ['subscription_end_date' => 'value1', 'plan_name' => 123]
            );
        } catch (Exception $e) {
            $this->assertContains('plan_name', $e->getMessage());
        }

        # Checking that a valid template, set the correct attributes
        $mailer->sendEmail(
            'studio-sub-voluntary-cancel',
            'test name',
            'test@test.com',
            'fr',
            ['subscription_end_date' => '2016-12-05', 'plan_name' => 'name']
        );
        $mail = $mailer->getMail();
        // Asserting that the right categories are set
        $expctedCategories = ['French', 'studio-sub-voluntary-cancel'];
        foreach ($mail->getCategories() as $category) {
            $this->assertContains($category->getCategory(), $expctedCategories);
        }
        // Asserting that `from` value is set to `sales@serato.com`, which is defined in configuration file
        $this->assertEquals('sales@serato.com', $mail->getFrom()->getEmail());
        $this->assertEquals('Sales team email', $mail->getFrom()->getName());
        // Asserting that `reply-to` value is set to `no-reply@serato.com`, which is defined in configuration file
        $this->assertEquals('no-reply@serato.com', $mail->getReplyTo()->getEmail());
        $this->assertEquals('Serato', $mail->getReplyTo()->getName());
        // Asserting that the template id is correct
        $this->assertEquals('template-id-for-french', $mail->getTemplateId()->getTemplateId());

        # Checking that the undefined language results in English by default, add `attachments` parameter
        $attachments = new Attachment('template contents');
        $mailer->sendEmail(
            'studio-sub-voluntary-cancel',
            'test name',
            'test@test.com',
            '', // No language is provided
            ['subscription_end_date' => '2016-12-05', 'plan_name' => 'name'],
            [$attachments]
        );
        $mail = $mailer->getMail();
        $this->assertEquals('template-id-for-english', $mail->getTemplateId()->getTemplateId());
    }
}

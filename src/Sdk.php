<?php
declare(strict_types=1);

namespace Serato\SendGrid;

use SendGrid;
use SendGrid\Mail\Mail;
use DateTime;

class Sdk extends SendGrid
{
    const FROM_EMAIL = 'no-reply@serato.com';
    const FROM_NAME = 'Serato Web Mailer';

    /**
     * @var boolean
     */
    private $disableEmailDelivery = false;

    # Email Template Ids (these value are retrieved from Send Grid)
    const STUDIO_SUB_VOLUNTARILY_CANCEL = 'd-8146e370bca14acf955bf1df39d8d93f';
    const DJ_SUB_VOLUNTARILY_CANCEL = 'd-67a2956be8a140b7adb302f91cfc3d6a';
    const STUDIO_SUB_PENDING_CHANGE = 'd-17210a9b3710425e8ac41282b29da6bf';
    const DJ_SUB_PENDING_CHANGE = 'd-16e85338f4a84a74822114b8362a861b';
    const DJ_SUB_IMMEDIATE_CHANGE = 'd-95bf3a84a1f6446f864c9593c5f50565';
    const STUDIO_SUB_WENT_ACTIVE = 'd-37885105de8a4afcbd9c999bd12050d5';
    const DJ_PRO_SUB_WENT_ACTIVE = 'd-0e273edeae574f4f86ccf88004116d44';
    const DJ_ESSENTIALS_SUB_WENT_ACTIVE = 'd-5d0c967bdd16492c8f189421745e942d';
    const DJ_SUITE_SUB_WENT_ACTIVE = 'd-e56b5f5b6cf84df4a2d0842d5b3c77e0';
    const DJ_EXPANSION_PACKS_SUB_WENT_ACTIVE = 'd-3e62b9906edb4fc596f732fcd1177923';
    const DJ_SUB_EXPLICIT_CANCEL_IN_PAST_DUE = 'd-3639eb1bf91f46e883c966f876481063';
    const STUDIO_SUB_EXPLICIT_CANCEL_IN_PAST_DUE = ''; // TODO: create template in send grid
    const DJ_SUB_FIRST_PAYMENT_DECLINED = 'd-203663fedc0944da80250520deae2cd7';
    const STUDIO_SUB_FIRST_PAYMENT_DECLINED = 'd-21879c0f45e049fdb37a7b609a5d541c';
    const DJ_SUB_SECOND_PAYMENT_DECLINED = 'd-a1ec3a08d96e46c0a8e373ce47b67f7e';
    const STUDIO_SUB_SECOND_PAYMENT_DECLINED = 'd-6ea59e567530426a8ee57382a36a5f54';
    const DJ_SUB_THIRD_PAYMENT_DECLINED = 'd-e9da2bf3d86946c4b1c38094c1918c95';
    const STUDIO_SUB_THIRD_PAYMENT_DECLINED = 'd-42feab22f62647099b65b0ac1557b580';

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
     * Sends email for a voluntary subscription cancellation.
     * The subscription will be active until the end of billing cycle.
     *
     * IMPORTANT: Use this for subscriptions that are still active
     *
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $subscriptionGroup
     * @param DateTime $subscriptionEndDate
     * @param string $subscriptionPlanName
     */
    public function sendEmailForVoluntarySubscriptionCancel(
        string $recipientEmail,
        string $recipientName,
        string $subscriptionGroup,
        DateTime $subscriptionEndDate,
        string $subscriptionPlanName
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'subscription_end_date' => $subscriptionEndDate->format('j F Y'),
            'plan_name' => $subscriptionPlanName
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                $mail->setTemplateId(self::STUDIO_SUB_VOLUNTARILY_CANCEL);
                return $this->send($mail);
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_VOLUNTARILY_CANCEL);
                return $this->send($mail);
        }
    }

    /**
     * Sends email for a subscription change that takes affect immediately
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $subscriptionGroup
     * @param string $targetPlanName
     * @param string $currentPlanName
     * @param int $targetPlanBillingPeriod
     * @param float $chargedAmount
     * @param float $nextBillingAmount
     * @param DateTime $nextBillingDate
     */
    public function sendEmailForImmediateSubscriptionChange(
        string $recipientEmail,
        string $recipientName,
        string $subscriptionGroup,
        string $targetPlanName,
        string $currentPlanName,
        int $targetPlanBillingPeriod,
        float $chargedAmount,
        float $nextBillingAmount,
        DateTime $nextBillingDate
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'target_plan_name' => $targetPlanName,
            'current_plan_name' => $currentPlanName,
            'charged_amount' => (string)$chargedAmount,
            'next_billing_amount' => (string)$nextBillingAmount,
            'next_billing_date' => $nextBillingDate->format('j F Y'),
            'target_plan_billing_period' => $this->getBillingPeriodText($targetPlanBillingPeriod)
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_IMMEDIATE_CHANGE);
                return $this->send($mail);
        }
    }

    /**
     * Sends email for a subscription change that takes affect current billing cycle ends
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $subscriptionGroup
     * @param string $targetPlanName
     * @param string $currentPlanName
     * @param int $targetPlanBillingPeriod
     * @param float $nextBillingAmount
     * @param DateTime $nextBillingDate
     */
    public function sendEmailForPendingSubscriptionChange(
        string $recipientEmail,
        string $recipientName,
        string $subscriptionGroup,
        string $targetPlanName,
        string $currentPlanName,
        int $targetPlanBillingPeriod,
        float $nextBillingAmount,
        DateTime $nextBillingDate
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'target_plan_name' => $targetPlanName,
            'current_plan_name' => $currentPlanName,
            'next_billing_amount' => (string)$nextBillingAmount,
            'next_billing_date' => $nextBillingDate->format('j F Y'),
            'target_plan_billing_period' => $this->getBillingPeriodText($targetPlanBillingPeriod)
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                $mail->setTemplateId(self::STUDIO_SUB_PENDING_CHANGE);
                return $this->send($mail);
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_PENDING_CHANGE);
                return $this->send($mail);
        }
    }

    /**
     * Sends email when a subscription goes active
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $planName
     * @param string $planId
     * @param int $billingPeriod
     * @param DateTime $billingStartDate
     * @param string $subscriptionGroup
     */
    public function sendEmailForSubscriptionWentActive(
        string $recipientEmail,
        string $recipientName,
        string $planName,
        string $planId,
        int $billingPeriod,
        DateTime $billingStartDate,
        string $subscriptionGroup
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'plan_name' => $planName,
            'billing_start_date' => $billingStartDate->format('j F Y'),
            'billing_period' => $this->getBillingPeriodText($billingPeriod)
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                $mail->setTemplateId(self::STUDIO_SUB_WENT_ACTIVE);
                return $this->send($mail);
            case 'dj':
                switch ($planId) {
                    case 'dj-pro-sub':
                        $mail->setTemplateId(self::DJ_PRO_SUB_WENT_ACTIVE);
                        return $this->send($mail);
                        break;
                    case 'expansion-packs-sub':
                        $mail->setTemplateId(self::DJ_EXPANSION_PACKS_SUB_WENT_ACTIVE);
                        return $this->send($mail);
                        break;
                    case 'dj-essentials-sub':
                        $mail->setTemplateId(self::DJ_ESSENTIALS_SUB_WENT_ACTIVE);
                        return $this->send($mail);
                        break;
                    case 'dj-suite-sub':
                        $mail->setTemplateId(self::DJ_SUITE_SUB_WENT_ACTIVE);
                        return $this->send($mail);
                        break;
                }
                break;
        }
    }

    /**
     * Sends email when a past-due subscription is cancelled
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $planName
     * @param string $subscriptionGroup
     */
    public function sendEmailForExplicitCancelInPastDue(
        string $recipientEmail,
        string $recipientName,
        string $planName,
        string $subscriptionGroup
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'plan_name' => $planName,
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                # TODO STUDIO_SUB_EXPLICIT_CANCEL_IN_PAST_DUE
                break;
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_EXPLICIT_CANCEL_IN_PAST_DUE);
                return $this->send($mail);
                break;
        }
    }

    /**
     * Sends email when a recurring payment fails for the first time
     * @param string $recipientEmail
     * @param string $recipientName
     * @param DateTime $nextBillingDate
     * @param string $subscriptionGroup
     */
    public function sendEmailForFirstPaymentDeclined(
        string $recipientEmail,
        string $recipientName,
        DateTime $nextBillingDate,
        string $subscriptionGroup
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'next_billing_date' => $nextBillingDate->format('j F Y')
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                $mail->setTemplateId(self::STUDIO_SUB_FIRST_PAYMENT_DECLINED);
                return $this->send($mail);
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_FIRST_PAYMENT_DECLINED);
                return $this->send($mail);
        }
    }

    /**
     * Sends email when a recurring payment fails for the second time
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $subscriptionGroup
     */
    public function sendEmailForSecondPaymentDeclined(
        string $recipientEmail,
        string $recipientName,
        string $subscriptionGroup
    ) {
        $mail = $this->getMail();
        $emailParams = [];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                $mail->setTemplateId(self::STUDIO_SUB_SECOND_PAYMENT_DECLINED);
                return $this->send($mail);
                break;
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_SECOND_PAYMENT_DECLINED);
                return $this->send($mail);
        }
    }

    /**
     * Sends email when a recurring payment fails for the third time
     * @param string $recipientEmail
     * @param string $recipientName
     * @param string $planName
     * @param int $productId
     * @param string $subscriptionGroup
     */
    public function sendEmailForThirdPaymentDeclined(
        string $recipientEmail,
        string $recipientName,
        string $planName,
        int $productId,
        string $subscriptionGroup
    ) {
        $mail = $this->getMail();
        $emailParams = [
            'plan_name' => $planName,
            'product_id' => $productId
        ];
        $mail->addTo($recipientEmail, $recipientName, $emailParams);
        switch ($subscriptionGroup) {
            case 'serato_studio':
                $mail->setTemplateId(self::STUDIO_SUB_THIRD_PAYMENT_DECLINED);
                return $this->send($mail);
            case 'dj':
                $mail->setTemplateId(self::DJ_SUB_THIRD_PAYMENT_DECLINED);
                return $this->send($mail);
        }
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

    /**
     * Returns text for billing period
     * @param int $billingPeriod
     * @return string
     */
    private function getBillingPeriodText(int $billingPeriod)
    {
        switch ($billingPeriod) {
            case 1:
                return 'monthly';
            case 12:
                return 'annually';
            default:
                return 'every ' . $billingPeriod . ' months';
        }
    }
}

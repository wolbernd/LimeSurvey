<?php

namespace LimeSurvey\Models\Services;

use CException;
use LimeSurvey\Controllers\UserManagementController as Controller;
use LimeSurvey\Core\LimeMailer;
use PHPMailer\PHPMailer\Exception;
use PluginEvent as PluginEvent;
use User as User;

/**
 * This class contains all functions for the process of password reset and creating new administration users
 * and sending email to those with a link to set the password.
 *
 * All this functions were implemented in UserManagementController before.
 */
class PasswordManagement
{
    // NB: PHP 7.0 does not support class constant visibility
    const MIN_PASSWORD_LENGTH = 8;
    const EMAIL_TYPE_REGISTRATION = 'registration';
    const EMAIL_TYPE_RESET_PW = 'resetPassword';

    /** @var $user User */
    private User $user;

    /** @var Controller $controller */
    private Controller $controller;

    /**
     * @var LimeMailer $mailer
     */
    private LimeMailer $mailer;

    /**
     * PasswordManagement constructor.
     * @param User $user
     * @param Controller $controller
     * @param LimeMailer|null $mailer
     */
    public function __construct(User $user, Controller $controller, LimeMailer $mailer = null)
    {
        $this->user = $user;
        $this->controller = $controller;

        if ($mailer !== null) {
            $this->mailer = $mailer;
        }
    }

    /**
     * This function prepare the email template to send to the new created user
     *
     * @param string $siteName
     * @param string $adminEmail
     * @param string $emailSubject
     * @param string $emailTemplate
     * @return array $adminCreationEmail array with subject and email body
     */
    public function generateAdminCreationEmail(string $siteName, string $adminEmail, string $emailSubject, string $emailTemplate): array
    {
        $adminCreationEmail = [];
        $url = 'admin/authentication/sa/newPassword/param/' . $this->user->validation_key;

        $loginUrl = $this->controller->createAbsoluteUrl($url);

        //Replace placeholder in Email subject
        $emailSubject = str_replace("{SITENAME}", $siteName, $emailSubject);
        $emailSubject = str_replace("{SITEADMINEMAIL}", $adminEmail, $emailSubject);

        //Replace placeholder in Email body
        $emailTemplate = str_replace("{SITENAME}", $siteName, $emailTemplate);
        $emailTemplate = str_replace("{SITEADMINEMAIL}", $adminEmail, $emailTemplate);
        $emailTemplate = str_replace("{FULLNAME}", $this->user->full_name, $emailTemplate);
        $emailTemplate = str_replace("{USERNAME}", $this->user->users_name, $emailTemplate);
        $emailTemplate = str_replace("{LOGINURL}", $loginUrl, $emailTemplate);

        $adminCreationEmail['subject'] = $emailSubject;
        $adminCreationEmail['body']    = $emailTemplate;

        return $adminCreationEmail;
    }

    /**
     * Sets the validationKey and the validationKey expiration and
     * sends email to the user, containing the link to set/reset password.
     *
     * @param string $emailType           this could be 'registration' or 'resetPassword' (see const in this class)
     * @param User   $currentLoggedInUser Current Logged In User
     * @return array message if sending email to user was successful
     *
     * @throws Exception
     * @throws CException
     */
    public function sendPasswordLinkViaEmail(string $emailType, User $currentLoggedInUser): array
    {
        $success = true;
        $this->user->setValidationKey();
        $this->user->setValidationExpiration();

        $mailer = $this->sendAdminMail($currentLoggedInUser, $emailType);

        if ($mailer->getError()) {
            $sReturnMessage = \CHtml::tag("h4", array(), gT("Error"));
            $sReturnMessage .= \CHtml::tag("p", array(), sprintf(
                gT("Email to %s (%s) failed."),
                "<strong>" . $this->user->users_name . "</strong>",
                $this->user->email
            ));
            $sReturnMessage .= \CHtml::tag("p", array(), $mailer->getError());
            $success = false;
        } else {
            // has to be sent again or no other way
            $sReturnMessage = \CHtml::tag("h4", array(), gT("Success"));
            $sReturnMessage .= \CHtml::tag("p", array(), sprintf(
                gT("Username : %s - Email : %s."),
                $this->user->users_name,
                $this->user->email
            ));
            $sReturnMessage .= \CHtml::tag("p", array(), gT("An email with a generated link was sent to the user."));
        }

        return [
            'success' => $success,
            'sReturnMessage' => $sReturnMessage
        ];
    }

    /**
     * Send a link to email of the user to set a new password (forgot password functionality)
     *
     * @param User $userToSend
     * @return string message for user
     * @throws Exception
     * @throws \Exception
     */
    public function sendForgotPasswordEmailLink(User $userToSend): string
    {
        $mailer = $this->mailer;
        $mailer->emailType = 'passwordreminderadminuser';
        $mailer->addAddress($userToSend->email, $userToSend->full_name);
        $mailer->Subject = gT('User data');

        /* Body construct */
        $userToSend->setValidationKey();
        $userToSend->setValidationExpiration();
        $username = sprintf(gT('Username: %s'), $userToSend->users_name);

        $linkToResetPage = $this->controller->createAbsoluteUrl('admin/authentication/sa/newPassword/param/' . $userToSend->validation_key);
        $linkText = gT("Click here to set your password: ") . $linkToResetPage;

        $body   = array();
        $body[] = sprintf(gT('Your link to reset password %s'), \Yii::app()->getConfig('sitename'));
        $body[] = $username;
        $body[] = $linkText;
        $body   = implode("\n", $body);

        $mailer->Body = $body;
        /* Go to send email and set password*/
        $hasSend = $mailer->sendMessage();
        if ($hasSend) {
            // For security reasons, we don't show a successful message
            $sMessage = gT('If the username and email address is valid and you are allowed to use the internal database authentication a new password has been sent to you.');
        } else {
            $sMessage = gT('Email failed');
        }

        return $sMessage;
    }

    /**
     * Creates a random password through the core plugin
     *
     * @todo it's fine to use static functions, until it is used only in controllers ...
     *
     * @param int $length Length of the password
     * @return string
     */
    public static function getRandomPassword(int $length = self::MIN_PASSWORD_LENGTH): string
    {
        $oGetPasswordEvent = new PluginEvent('createRandomPassword');
        $oGetPasswordEvent->set('targetSize', $length);
        \Yii::app()->getPluginManager()->dispatchEvent($oGetPasswordEvent);

        return $oGetPasswordEvent->get('password');
    }

    /**
     * Send the registration email to a new survey administrator
     *
     * @param User $currentLoggedInUser Current Logged In User which sends the email.
     * @param string $type two types are available 'resetPassword' or 'registration', default is 'registration'
     * @return LimeMailer if send is successful
     *
     * @throws Exception \CException
     * @throws CException
     */
    private function sendAdminMail(User $currentLoggedInUser, string $type = self::EMAIL_TYPE_REGISTRATION): LimeMailer
    {
        $absolutUrl = $this->controller->createAbsoluteUrl("/admin");

        switch ($type) {
            case self::EMAIL_TYPE_RESET_PW:
                $passwordResetUrl = $this->controller->createAbsoluteUrl('admin/authentication/sa/newPassword/param/' . $this->user->validation_key);
                $renderArray = [
                    'surveyapplicationname' => \Yii::app()->getConfig("sitename"),
                    'emailMessage' => sprintf(gT("Hello %s,"), $this->user->full_name) . "<br />"
                        . sprintf(gT("This is an automated email to notify you that your login credentials for '%s' have been reset."), \Yii::app()->getConfig("sitename")),
                    'credentialsText' => gT("Here are your new credentials."),
                    'siteadminemail' => \Yii::app()->getConfig("siteadminemail"),
                    'linkToAdminpanel' => $absolutUrl,
                    'username' => $this->user->users_name,
                    'password' => $passwordResetUrl,
                    'mainLogoFile' => LOGO_URL,
                    'showPasswordSection' => \Yii::app()->getConfig("auth_webserver") === false && \Permission::model()->hasGlobalPermission('auth_db', 'read', $this->user->uid),
                    'showPassword' => (\Yii::app()->getConfig("display_user_password_in_email") === true),
                ];
                $subject = "[" . \Yii::app()->getConfig("sitename") . "] " . gT("Your login credentials have been reset");
                $body = $this->controller->renderPartial('partial/usernotificationemail', $renderArray, true);
                break;
            case self::EMAIL_TYPE_REGISTRATION:
            default:
                //Get email template from globalSettings
                $siteName      = \Yii::app()->getConfig("sitename");
                $adminEmail    = \Yii::app()->getConfig("siteadminemail");
                $emailSubject  = \Yii::app()->getConfig("admincreationemailsubject");
                $emailTemplate = \Yii::app()->getConfig("admincreationemailtemplate");

                $aAdminEmail = $this->generateAdminCreationEmail($siteName, $adminEmail, $emailSubject, $emailTemplate);

                $subject = $aAdminEmail["subject"];
                $body    = $aAdminEmail["body"];
                break;
        }

        $emailType = "addadminuser";

        $mailer = $this->mailer;
        $toUser = $this->user;
        $mailer->addAddress($toUser->email, $toUser->full_name);
        $mailer->Subject = $subject;
        $mailer->setFrom($currentLoggedInUser->email, $currentLoggedInUser->users_name);
        $mailer->Body = $body;
        $mailer->isHtml(true);
        $mailer->emailType = $emailType;
        $mailer->sendMessage();
        return $mailer;
    }

    /**
     * Setter for Mailer
     */
    public function setMailer(LimeMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Getter for Mailer
     * @return LimeMailer
     */
    public function getMailer(): LimeMailer
    {
        return $this->mailer;
    }
}

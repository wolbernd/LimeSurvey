<?php

namespace ls\tests\services;

use LimeSurvey\Core\LimeMailer as Mailer;
use ls\tests\TestBaseClass;
use LimeSurvey\Models\Services\PasswordManagement as Service;
use LimeSurvey\Controllers\UserManagementController as Controller;
use PHPMailer\PHPMailer\Exception;
use User as User;

/**
 * Test the password management service class
 */
class PasswordManagement extends TestBaseClass
{
    /** @var Service $service */
    private Service $service;

    /**
     * Set Up.
     */
    public function setUp(): void
    {
        parent::setUpBeforeClass();

        // Needs to be set - otherwise tests do not work correctly.
        $_SERVER['SERVER_NAME'] = 'PasswordManagementTestServer';

        $controller = new Controller(1, null);
        $user = new User();
        $user->setValidationKey();
        $user->setEmail('test@test.com');

        $this->service = new Service($user, $controller);
    }

    /**
     * Tear Down.
     */
    public function tearDown(): void
    {
        $this->service->__destruct();

        parent::tearDownAfterClass();
    }

    /**
     * Checks if the admin creation email will
     * be generated successfully.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::generateAdminCreationEmail
     */
    public function generateAdminCreationEmailSuccess()
    {
        $expectedSiteName   = 'This is an automated email notification that a user has been created for you on the website {SITENAME}';
        $expectedAdminEmail = 'If you have any questions regarding this email, please do not hesitate to contact the site administrator at {SITEADMINEMAIL}';
        $expectedEmailSubject  = '';
        $expectedEmailTemplate = '';

        $expected = [
            'subject' => '',
            'body' => ''
        ];
        $actual   = $this->service->generateAdminCreationEmail(
            $expectedSiteName,
            $expectedAdminEmail,
            $expectedEmailSubject,
            $expectedEmailTemplate
        );

        $this->assertEquals($expected, $actual);
        $this->markTestIncomplete('This test is incomplete.');
    }

    /**
     * Checks if the admin creation email will be failing.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::generateAdminCreationEmail
     */
    public function generateAdminCreationEmailFailure()
    {
        $this->markTestIncomplete('This test is incomplete.');
    }

    /**
     * Checks if the send forgot email password link
     * will be successful.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::sendForgotPasswordEmailLink
     * @throws Exception
     */
    public function sendForgotPasswordLinkSuccess()
    {
        $user = new User();
        $user->users_name = 'User Forgot Password 01';
        $user->full_name = 'User 01';
        $user->setEmail('sendforgotpassword@example.com');
        $expected = 'If the username and email address is valid and you are allowed to use the internal database authentication a new password has been sent to you.';

        // Mock the Mailer Class.
        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->once())->method('sendMessage')->with($this->equalTo(true));

        $this->service->setMailer($mailer);

        $actual = $this->service->sendForgotPasswordEmailLink($user);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Checks if the send forgot email password link
     * will be failing.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::sendForgotPasswordEmailLink
     * @throws Exception
     */
    public function sendForgotPasswordLinkFailure()
    {
        $user = new User();
        $user->users_name = 'User Forgot Password 02';
        $user->full_name = 'User 01';
        $user->setEmail('sendforgotpassword@example.com');
        $expected = 'Email failed';
        $actual = $this->service->sendForgotPasswordEmailLink($user);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Checks if the returned random password works correctly.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::getRandomPassword
     */
    public function getRandomPasswordSuccess()
    {
        $expected = 8;
        $actual = $this->service::getRandomPassword();
        $actual  = strlen($actual);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Checks if the returned random password is not equal.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::getRandomPassword
     */
    public function getRandomPasswordFailure()
    {
        $expectedMinimumSize = 4;
        $expected = '';
        $actual = $this->service::getRandomPassword($expectedMinimumSize);

        $this->assertNotEquals($expected, $actual);
    }

    /**
     * Checks if the password link will be send via email successful.
     * @test
     * @covers \LimeSurvey\Models\Services\PasswordManagement::sendPasswordLinkViaEmail
     */
    public function sendPasswordLinkViaEmailSuccess()
    {
        // Registration or resetPassword are allowed as emailTypes.
        $emailType = $this->service::EMAIL_TYPE_REGISTRATION;
        $currentLoggedInUser = new User();
        $currentLoggedInUser->setEmail('currentLoggedInUser@example.com');
        $expected = ['success' => true, 'sReturnMessage' => "<h4>Success</h4><p>Username: - Email: test@test.com</p><p>An email with a generated link was sent to the user.</p>"];
        $actual = $this->service->sendPasswordLinkViaEmail($emailType, $currentLoggedInUser);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Checks if the sendPasswordLinkViaEmail Method
     * will fail.
     *
     * @covers \LimeSurvey\Models\Services\PasswordManagement::sendPasswordLinkViaEmail
     * @test
     */
    public function sendPasswordLinkViaEmailFailure()
    {
        // Registration or resetPassword are allowed as emailTypes.
        $emailType = $this->service::EMAIL_TYPE_REGISTRATION;
        $currentLoggedInUser = new User();
        $currentLoggedInUser->setEmail('currentLoggedInUser@example.com');

        $expected = ['success' => false, 'sReturnMessage' => '<h4>Error</h4><p>Email to <strong></strong> (test@test.com) failed.</p><p>Could not instantiate mail function.</p>'];
        $actual = $this->service->sendPasswordLinkViaEmail($emailType, $currentLoggedInUser);

        $this->assertEquals($expected, $actual);
    }
}

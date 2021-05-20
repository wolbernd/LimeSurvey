<?php

namespace ls\tests\services;

use ls\tests\TestBaseClass;
use LimeSurvey\Models\Services\PasswordManagement as Service;
use \User as User;

/**
 * Test the password management service class
 */
class PasswordManagementTestCase extends TestBaseClass
{

    public function setUp()
    {
        parent::setUpBeforeClass();

        $this->user = new User();
        $this->user->setValidationKey();
        #$this->user->validation_key = '2021-05-21 13:00:00';

        //$this->user->validation_key_expires = "2022-05-21 13:00:00";

        $this->service = new Service($this->user);
    }

    public function tearDown()
    {
        $this->user = null;
        $this->service = null;

        parent::tearDownAfterClass();
    }

    /**
     * Checks if the admin creation email will 
     * be generated successfully.
     * @test
     */
    public function generateAdminCreationEmailSuccess()
    {
        print "User: " . $this->user;

        $expected = [];
        
        $actual = $this->service->generateAdminCreationEmail();
        $this->assertEqual($expected, $actual);
    }
}
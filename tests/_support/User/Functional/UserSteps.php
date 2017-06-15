<?php

namespace User\Functional;

use FunctionalTester;

class UserSteps
{
    protected $tester;

    public function __construct(FunctionalTester $I)
    {
        $this->tester = $I;
    }

    public function loginAsAdmin()
    {
        $this->tester->haveInSession('auth', [
            'id' => '0',
            'user_id' => 'admin',
            'title' => 'Admin',
            'name' => 'Admin',
            'type' => 'Admin',
            'view' => 'Admin'
        ]);
    }

    public function loginAsStudent()
    {
        $this->tester->haveInSession('auth', [
            'id' => '478',
            'user_id' => 'test_student',
            'title' => 'Undefined',
            'name' => 'is student',
            'type' => 'Student',
        ]);
    }
}
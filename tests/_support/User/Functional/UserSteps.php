<?php

namespace User\Functional;

use FunctionalTester;

class UserSteps
{
    protected $tester;
    protected $advisor_id;
    protected $admin_id;
    protected $student_id;

    public function __construct(FunctionalTester $I)
    {
        $this->tester = $I;
    }

    public function getAdvisorId()
    {
        return $this->advisor_id;
    }

    public function createTestAdvisor()
    {
        //create advisor
        $id = $this->tester->haveRecord('User', [
            'user_id' => 'test_advisor',
            'email' => 'test@test.com',
            'title' => 'Test',
            'name' => 'test_advisor',
            'tel' => 'xxxxxxxx',
            'type' => 'Advisor',
            'advisor_group' => 0,
            'active' => 1,
            'create_date' => date('Y-m-d H:i:s')
        ]);

        //create quota
        $this->tester->haveRecord('Quota', [
            'advisor_id' => $id,
            'quota_pp' => 1
        ]);

        $this->advisor_id = $id;
    }

    public function loginAsAdvisor()
    {
        if (empty($this->advisor_id))
            $this->createTestAdvisor();

        $this->tester->haveInSession('auth', [
            'id' => $this->advisor_id,
            'user_id' => 'test_advisor',
            'title' => 'Test',
            'name' => 'test_advisor',
            'type' => 'Advisor',
            'view' => null
        ]);
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

    public function createTestStudent()
    {
        //create advisor
        $id = $this->tester->haveRecord('User', [
            'user_id' => 'test_student',
            'email' => 'test_student@test.com',
            'title' => 'Test',
            'name' => 'test_student',
            'tel' => 'xxxxxxxx',
            'facebook' => 'test',
            'type' => 'Student',
            'advisor_group' => 0,
            'active' => 1,
            'interesting' => '-',
            'create_date' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s')
        ]);


        $this->student_id = $id;
    }

    public function loginAsStudent()
    {
        if (empty($this->student_id))
            $this->createTestStudent();

        $user = $this->tester->grabRecord('User', ['id' => $this->student_id]);

        //workaround unknown bug
        $user->active = 1;
        $user->save();

        $this->tester->haveInSession('auth', [
            'id' => $this->student_id,
            'user_id' => 'test_student',
            'title' => 'Undefined',
            'facebook' => 'test',
            'name' => 'is student',
            'type' => 'Student',
        ]);
    }
}
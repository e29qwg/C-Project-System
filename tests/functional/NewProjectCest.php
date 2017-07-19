<?php

use User\Functional\UserSteps;


class NewProjectCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
        $enroll = $I->grabRecord('Enroll', ['student_id' => 'test_student']);
        if ($enroll)
            $enroll->delete();
        $detail = $I->grabRecord('Detail', ['username' => 'test_student']);
        if ($detail)
            $detail->delete();
    }

    public function createProjectFailQuotaLimit(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();
        $userSteps->createTestAdvisor();

        //set env
        $setting = $I->grabRecord('Settings', ['name' => 'current_semester']);

        //enroll
        $I->haveRecord('Enroll', [
            'student_id' => 'test_student',
            'project_level_id' => '2',
            'semester_id' => $setting->value
        ]);

        $advisor_id = $userSteps->getAdvisorId();

        //load page
        $I->amOnPage('/projects/newProject');
        $I->see('Create Project');
        $I->selectOption(['name' => 'advisor_id'], $advisor_id);
        $I->fillField(['name' => 'project_name'], 'test_project');
        $I->selectOption(['name' => 'project_type'], 'SW-HW');
        $I->fillField(['name' => 'description'], 'test');


        //set quota to 0
        $quota = $I->grabRecord('Quota', [
           'advisor_id' => $advisor_id
        ]);

        $quota->quota_pp = 0;
        $quota->save();

        $I->click('Create Project');

        $I->dontSee('New project success');
        $I->see('Advisor quota limit exceed');

        $I->cantSeeRecord('Project', [
            'project_name' => 'test_project',
            'project_type' => 'SW-HW',
        ]);
    }

    public function createProjectPrepareSuccess(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();
        $userSteps->createTestAdvisor();

        //create env
        $setting = $I->grabRecord('Settings', ['name' => 'current_semester']);

        $I->haveRecord('Enroll', [
            'student_id' => 'test_student',
            'project_level_id' => '1',
            'semester_id' => $setting->value
        ]);
        $I->haveRecord('Detail', [
            'username' => 'test_student',
            'name' => 'test',
            'email' => 'test@test.com',
            'placement_test' => '9.99',
            'total_time' => 450,
            'update_time' => date('Y-m-d H:i:s')
        ]);

        //test
        $I->amOnPage('/projects/newProject');
        $I->see('Create Project');
        $I->selectOption(['name' => 'advisor_id'], $userSteps->getAdvisorId());
        $I->fillField(['name' => 'project_name'], 'test_project');
        $I->selectOption(['name' => 'project_type'], 'SW-HW');
        $I->fillField(['name' => 'description'], 'test');
        $I->click('Create Project');

        $I->see('New project success');
        $I->seeRecord('Project', [
            'project_name' => 'test_project',
            'project_type' => 'SW-HW',
        ]);
    }

    public function createProjectInvalidEnroll(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();

        $I->haveRecord('Detail', [
            'username' => 'test_student',
            'name' => 'test',
            'email' => 'test@test.com',
            'placement_test' => '9.99',
            'total_time' => 450,
            'update_time' => date('Y-m-d H:i:s')
        ]);


        $I->amOnPage('/projects/newProject');
        $I->cantSee('Create Project');
    }

    public function createProject1InvalidTMM(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();

        //create env
        $setting = $I->grabRecord('Settings', ['name' => 'current_semester']);

        $I->haveRecord('Enroll', [
            'student_id' => 'test_student',
            'project_level_id' => '1',
            'semester_id' => $setting->value
        ]);

        //test
        $I->amOnPage('/projects/newProject');
        $I->cantSee('Create Project');
    }


    public function notFoundEnroll(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();
        $I->amOnPage('/projects/newProject');
        $I->see('ไม่มีข้อมูลการลงทะเบียน');
    }
}

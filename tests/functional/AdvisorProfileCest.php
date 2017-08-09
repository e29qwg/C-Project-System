<?php

use User\Functional\UserSteps;


class AdvisorProfileCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function viewProjectAdvising(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();

        $I->amOnPage('/advisor/list');
        $advisors = $this->getAdvisor();
        $advisor = $advisors[0];
        $I->click($advisor->title.$advisor->name);
        $I->click('Project Advising');
        $I->see('Semester', ['class' => 'control-label']);
        $I->see('Project List', ['class' => 'control-label']);
    }

/*    public function viewAdvisorProfile(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();
        $I->amOnPage('/advisor/list');

        $advisors = $this->getAdvisor();
        $advisor = $advisors[0];

        $I->click($advisor->title.$advisor->name);
        $I->seeLink('Profile', '#profile');
        $I->seeLink('Project Advising', '#project');
        $I->see('Name', ['class' => 'control-label']);
        $I->see($advisor->title.$advisor->name, ['class' => 'form-control-static']);
        $I->see('Tel', ['class' => 'control-label']);
        $I->see($advisor->tel, ['class' => 'form-control-static']);
        $I->see('Facebook', ['class' => 'control-label']);
        $I->see($advisor->email, ['class' => 'form-control-static']);
        $I->see('Interesting', ['class' => 'control-label']);
    }

    public function showListPage(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();
        $I->amOnPage('/advisor/list');

        $advisors = $this->getAdvisor();

        foreach ($advisors as $advisor)
        {
            $text = $advisor->title.$advisor->name;
            $I->see($text);
            $I->seeLink($text, '/advisor/profile/'.$advisor->id);
        }
    }*/

    private function getAdvisor()
    {
        $advisors = User::find("type='Advisor'");
        return $advisors;
    }
}

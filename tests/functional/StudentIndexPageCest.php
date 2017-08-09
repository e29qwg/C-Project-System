<?php

use User\Functional\UserSteps;


class StudentIndexPageCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function showIndexPage(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();

        $I->amOnPage('/');
        $I->seeLink('', '/session/logout');
        $I->seeElement('a', ['href' => '/advisor/list', 'class'=>'btn btn-primary btn-lg btn-block']);
        $I->seeElement('a', ['href' => '/projects/newProject', 'class'=>'btn btn-primary btn-lg btn-block']);
        $I->seeElement('a', ['href' => '/projects/me', 'class'=>'btn btn-primary btn-lg btn-block']);
        $I->seeElement('a', ['href' => '/exam/showExam', 'class'=>'btn btn-primary btn-lg btn-block']);
    }

    public function linkToAdvisorProfile(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsStudent();
        $I->amOnPage('/');
        $I->click('a[name=advisor_list]');
    }
}

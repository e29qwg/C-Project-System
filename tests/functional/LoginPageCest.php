<?php


class LoginPageCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function showNormalUserLoginPage(FunctionalTester $I)
    {
        putenv('APPLICATION_ENV=testing');
        $I->amOnPage('/');
        $I->seeInCurrentUrl('/session');
        $I->see('Admin Login');
    }

    public function showAdminLoginPage(FunctionalTester $I)
    {
        $I->amOnPage('/session/adminLogin');
        $I->see('Normal User Login');
    }

    public function linkToAdminLogin(FunctionalTester $I)
    {
        $I->amOnPage('/session/index');
        $I->click('Admin Login');
        $I->see('Normal User Login');
    }
}

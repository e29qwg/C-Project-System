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
        $I->amOnPage('/');
        $I->seeInCurrentUrl('/session');
        $I->see('Login with PSU PASSPORT');
        $I->see('Admin Login');
    }

    public function showAdminLoginPage(FunctionalTester $I)
    {
        $I->amOnPage('/session/adminLogin');
        $I->see('Normal User Login');
        $I->seeElement('input', ['name' => 'username']);
        $I->seeElement('input', ['name' => 'password']);
    }

    public function linkToAdminLogin(FunctionalTester $I)
    {
        $I->amOnPage('/session/index');
        $I->click('Admin Login');
        $I->see('Normal User Login');
        $this->showAdminLoginPage($I);
    }
}

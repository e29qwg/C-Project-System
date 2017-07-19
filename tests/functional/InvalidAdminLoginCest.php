<?php


class InvalidAdminLoginCest
{
    public function _before(FunctionalTester $I)
    {

    }

    public function _after(FunctionalTester $I)
    {
    }

    public function InvalidPasswordAdminLogin(FunctionalTester $I)
    {
        $this->goToLoginPage($I);

        $I->fillField('username', 'admin');
        $I->fillField('password', 'invalid');
        $I->click('#login');
        $I->see('Login failure');
    }


    public function EmptyAdminLogin(FunctionalTester $I)
    {
        $this->goToLoginPage($I);
        $I->click('#login');
        $I->see('Login failure');
    }

    public function EmptyAdminUsername(FunctionalTester $I)
    {
        $this->goToLoginPage($I);
        $I->fillField('password', 'invalid');
        $I->click('#login');
        $I->see('Login failure');
    }

    public function EmptyAdminPassword(FunctionalTester $I)
    {
        $this->goToLoginPage($I);
        $I->fillField('username', 'invalid');
        $I->click('#login');
        $I->see('Login failure');
    }

    private function goToLoginPage(FunctionalTester $I)
    {
        $I->amOnPage('/');
        $I->click('Admin Login');
        $I->seeInCurrentUrl('/session/adminLogin');
    }
}

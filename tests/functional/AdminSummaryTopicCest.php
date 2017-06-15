<?php

use User\Functional\UserSteps;


class AdminSummaryTopicCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function showPage(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsAdmin();

        $I->amOnPage('/admin/summaryTopic');
        $I->seeLink('Save Excel', '/admin/summaryTopicExport');
        $I->see('filter');
    }
}

<?php

use User\Functional\UserSteps;


class AdminIndexPageCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    public function showIndexPage(FunctionalTester $I, UserSteps $userSteps)
    {
        $userSteps->loginAsAdmin();

        $I->amOnPage('/admin');

        $this->adminMenuTest($I);
    }

    private function adminMenuTest(FunctionalTester $I)
    {
        $I->seeLink('สรุปหัวข้อโครงงาน','/admin/summaryTopic');
        $I->seeLink('จัดการตารางสอบ', '/exam/manage');
        $I->seeLink('จัดการคะแนน', '/score/manageScore');
        $I->seeLink('จัดการผู้เรียน', '/enroll');
        $I->seeLink('จัดการอาจารย์ที่ปรึกษา', '/advisor/manageAdvisor');
        $I->seeLink('จัดการอาจารย์ที่ปรึกษาร่วม', '/admin/manageCoadvisor');
        $I->seeLink('จัดการโควต้า', '/advisor/quota');
        $I->seeLink('เปลี่ยนสิทธิ์การใช้งาน','/admin/advisorProfile');
        $I->seeLink('จัดการข่าว', '/news/manageNews');
        $I->seeLink('เปลี่ยนมุมมอง', '/admin/setView');
        $I->seeLink('ตั้งค่าระบบ', '/settings');
    }
}

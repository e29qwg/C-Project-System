<?php

use Phalcon\Validation;

class Detail extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=10, nullable=false)
     */
    public $id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $username;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $name;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $email;

    /**
     *
     * @var integer
     * @Column(type="integer", length=10, nullable=false)
     */
    public $placement_test;

    /**
     *
     * @var integer
     * @Column(type="integer", length=10, nullable=false)
     */
    public $progress_test;

    public $archivement_test_1;
    public $archivement_test_2;

    /**
     *
     * @var integer
     * @Column(type="integer", length=10, nullable=false)
     */
    public $total_time;
    public $update_time;

    public function initialize()
    {
        $this->setConnectionService('dbTMM');
    }
}

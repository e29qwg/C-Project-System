<?php
$auth = $this->session->get('auth');
$user_id = $auth['id'];

$params = $this->dispatcher->getParams();
$action = $this->dispatcher->getActionName();
$controller = $this->dispatcher->getControllerName();

if (!isset($params[0]))
{
    return $this->flash->error('Invalid request');
}

if ($auth['type'] == 'Student')
{
include __DIR__ . '/../projects/me.phtml';
?>

<div class="col-sm-9">
    <?php
    }
    else
        echo '<div>';
    ?>
    <ul class="nav nav-tabs" id="pTab">
        <?= ($action == 'manage') ? '<li class="active">' : '<li>'; ?>
        <a href="<?= $url . 'projects/manage/' . $params[0] ?>">Project Info</a></li>

        <?= ($action == 'member' || $action == 'addmember') ? '<li class="active">' : '<li>'; ?>
        <a href="<?= $url . 'projects/member/' . $params[0] ?>">Member</a></li>



        <?php
        if ($auth['type'] != 'Student')
        {
            ?>
            <?= ($controller == 'progress') ? '<li class="active">' : '<li>'; ?>
            <a href="<?= $url . 'progress/evaluate/' . $params[0] ?>">Progress</a></li>

            <?php

            if ($selectProject->project_level_id == 3):

                ?>

                <?= ($controller == 'report') ? '<li class="active">' : '<li>'; ?>
                <a href="<?= $url . 'report/evaluate/' . $params[0]; ?>">Final Report</a></li>

                <?php
            endif;
        }
        else
        {
            ?>
            <?= ($controller == 'progress') ? '<li class="active">' : '<li>'; ?>
            <a href="<?= $url . 'progress/index/' . $params[0] ?>">Progress</a></li>

            <?php

            if ($selectProject->project_level_id == 3):
                ?>

                <?= ($controller == 'report') ? '<li class="active">' : '<li>'; ?>
                <a href="<?= $url . 'report/index/' . $params[0]; ?>">Final Report</a></li>
                <?php
            endif;
        }
        ?>


        <?= ($controller == 'store') ? '<li class="active">':'<li>'; ?>
        <a href="<?= $url. 'store/index/'.$params[0] ?>">Store</a>


    </ul>

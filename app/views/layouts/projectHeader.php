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

    <h4><?= $selectProject->project_name; ?> ( <?= $selectProject->Semester->semester_term; ?>/<?= $selectProject->Semester->semester_year; ?> )</h4>

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



            <?= ($controller == 'projects' && ($action == 'status')) ? '<li class="active">' : '<li>'; ?>
            <a href="<?= $url . 'projects/status/' . $params[0] ?>">Status</a></li>
            <?php
        }
        else
        {
            ?>
            <?= ($controller == 'progress') ? '<li class="active">' : '<li>'; ?>
            <a href="<?= $url . 'progress/index/' . $params[0] ?>">Progress</a></li>
            <?php
        }
        ?>


        <?= ($controller == 'store') ? '<li class="active">' : '<li>'; ?>
        <a href="<?= $url . 'store/index/' . $params[0] ?>">Store</a>


    </ul>

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

$project = Project::findFirst("project_id='$params[0]'");

if ($auth['type'] == 'Student')
{
?>
<div class="col-sm-3">
    <div class="list-group">
        <a href="#" class="list-group-item active">Project</a>
        <?php

        $projectMaps = ProjectMap::find("user_id='$user_id'");

        foreach ($projectMaps as $projectMap)
        {
            $project = Project::findFirst("project_id='$projectMap->project_id'");
            echo '<a href="' . $this->url->get('projects/manage/') . $project->project_id . '" class="list-group-item">';
            echo $project->project_name;
            echo '</a>';
        }

        ?>
        </ul>
    </div>
</div>
<div class="col-sm-9">
    <?php
    }
    else
        echo '<div>';
    ?>
    <ul class="nav nav-tabs" id="pTab">
        <?= ($action == 'manage') ? '<li class="active">' : '<li>'; ?>
        <a href="<?= $this->url->get('projects/manage/'); ?><?= $params[0] ?>">Project Info</a></li>
        <?= ($controller == 'progress') ? '<li class="active">' : '<li>'; ?>
        <?php
        if ($auth['type'] != 'Student')
        {
            ?>
            <a href="<?= $this->url->get('progress/evaluate/'); ?><?= $params[0] ?>">Progress</a></li>
        <?php
        }
        else
        {
            ?>
            <a href="<?= $this->url->get('progress/index/'); ?><?= $params[0] ?>">Progress</a></li>
        <?php
        }
        ?>
        <?= ($action == 'member' || $action == 'addmember') ? '<li class="active">' : '<li>'; ?>
        <a href="<?= $this->url->get('projects/member/'); ?><?= $params[0] ?>">Member</a></li>
    </ul>

<?php

if (!defined('DOKU_INC')) { die(); }

class action_plugin_gitlabapi extends DokuWiki_Action_Plugin {
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array());
    }

    function insert_button(&$event, $param) {
        $event->data[] = array(
            'type' => 'format',
            'title' => $this->getLang('button'),
            'icon' => '../../plugins/gitlabapi/images/gitlab.png',
            'open' => '<gitlab-api project-path="<NAMESPACE>/<PROJECT_NAME>" milestones="1" issues="3" commits="5" pipelines="1"',
            'close' => ' />',
        );
    }
}



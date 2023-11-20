<?php
/**
 * GitLab Api Plugin
 *
 * @license : BSD-3 Clause
 * @author  : Mehmet Ozgan <mozgan@gmail.com>
 */

if (!defined('DOKU_INC')) { die(); }

require_once __DIR__.'/gitlab/gitlabapi.php';

class syntax_plugin_gitlabapi extends DokuWiki_Syntax_Plugin {
    /**
     * Syntax Plugins
     *
     * The class needs to implement at least the following functions:
     *  -) getType()
     *  -) getSort()
     *  -) connectTo($mode)
     *  -) handle($match, $state, $pos, Doku_Handler $handler)
     *  -) render($mode, Doku_Renderer $renderer, $data)
     *
     *  More information: https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */

    public function getType() { return 'substition'; }

    public function getPType() { return 'normal'; }

    public function getSort() { return 196; }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<gitlab-api[^>]*/>', $mode, 'plugin_gitlabapi');
    }

    function getServerFromJson($server) {
        $json_file = file_get_contents(__DIR__.'/server.json');
        $json_data = json_decode($json_file, true);
        if (isset($json_data[$server])) {
            return $json_data[$server];
        } else {
            return null;
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler) {
        switch ($state) {
            case DOKU_LEXER_SPECIAL:
                $data = array(
                    'state' => $state
                );

                // match @server and @token
                preg_match("/server *= *(['\"])(.*?)\\1/", $match, $server);
                //if (count($server) != 0) {
                if (!empty($server)) {
                    $server_data = $this->getServerFromJson($server[2]);
                    if (!is_null($server_data)) {
                        $data['server'] = $server_data['url'];
                        $data['token'] = $server_data['api_token'];
                    }
                }
                if (!isset($data['server'])) {
                    $data['server'] = $this->getConf('server.default');
                } 

                if (!isset($data['token'])) {
                    $data['token'] = $this->getConf('token.default');
                }

                // match @project-path
                preg_match("/project-path *= *(['\"])(.*?)\\1/", $match, $project_path);
                if (!empty($project_path)) {
                    $data['project-path'] = $project_path[2];
                }

                // match @milestones
                preg_match("/milestones *= *(['\"])(.*?)\\1/", $match, $milestones);
                if(!empty($milestone)) {
                    $data['milestones'] = $milestones[2];
                }

                // match @commits
                preg_match("/commits *= *(['\"])(.*?)\\1/", $match, $commits);
                if(!empty($commits)) {
                    $data['commits'] = $commits[2];
                }

                // match @issues
                preg_match("/issues *= *(['\"])(.*?)\\1/", $match, $issues);
                if(!empty($issues)) {
                    $data['issues'] = $issues[2];
                }

                // match @pipelines
                preg_match("/pipelines *= *(['\"])(.*?)\\1/", $match, $pipelines);
                if(!empty($pipelines)) {
                    $data['pipelines'] = $pipelines[2];
                }

                return $data;

            case DOKU_LEXER_UNMATCHED:
                return array('state' => $state, 'text' => $match);

            default:
                return array('state' => $state, 'bytepos_end' => $pos + strlen($match));
        }
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode !== 'xhtml') { return false; }
        if ($mode != 'xhtml') { return false; }

        if (isset($data['error'])) {
            $renderer->doc .= $data['text'];
            return true;
        }

        $renderer->info['cache'] = false;
        switch ($data['state']) {
            case DOKU_LEXER_SPECIAL:
                $this->renderGitLab($renderer, $data);
                break;

            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_EXIT:
            case DOKU_LEXER_UNMATCHED:
                $renderer->doc .= $renderer->_xmlEntities($data['text']);
                break;
        }

        return true;
    }

    function renderGitLab($renderer, $data) {
        // create GitLabApi object
        $gitlab = new GitLabApi($data);

        // get project
        $project = $gitlab->getProject();
        //dbg($project);

        if (empty($project)) {
            $this->renderProjectError($renderer, $data);
            return array('state' => $state, 'bytepos_end' => $pos + strlen($match));
        }

        $project_id = $project['id'];

        $project_url = $project['web_url'];
        $project_name = $project['name'];
        $date_time = $this->getDateTime($project['last_activity_at']);
        $namespace = $project['namespace']['full_path'];

        $img_url = DOKU_URL . 'lib/plugins/gitlabapi/images/gitlab.png';

        $renderer->doc .= '<div class="gitlab">';
        $renderer->doc .= '<span><img src="'.$img_url.'" class="gitlab"></span>';
        $renderer->doc .= '<b class="gitlab">'.$this->getLang('gitlab.project').'</b><br>';
        $renderer->doc .= '<hr class="gitlab">';
        $renderer->doc .= '<a href="'.$project_url.'" class="gitlab">'.$project_name.'</a>';
        $renderer->doc .= ' - <b>Namespace:</b> <a href="'.$data['server'].'/'.$namespace.'"> '.$namespace.'</a>';
        $renderer->doc .= '<p><b>'.$this->getLang('gitlab.activity').':</b> '.$date_time['date'].' - '.$date_time['time'].'</p>';

        if (!empty($data['milestones'])) {
            $this->renderProjectMilestones($renderer, $gitlab, $project_id, $data['milestones']);
        }
        if (!empty($data['commits'])) {
            $this->renderProjectCommits($renderer, $gitlab, $project_id, $data['commits']);
        }
        if (!empty($data['issues'])) {
            $this->renderProjectIssues($renderer, $gitlab, $project_id, $data['issues']);
        }
        if (!empty($data['pipelines'])) {
            $this->renderProjectPipelines($renderer, $gitlab, $project_id, $data['pipelines']);
        }

        $renderer->doc .= '</p>';
        $renderer->doc .= '</div>';

        $gitlab->closeClient();
    }

    function renderProjectCommits($renderer, $gitlab, $project_id, $number) {
        $commits = $gitlab->getCommits($project_id);

        $renderer->doc .= '<b class="gitlab">'.$this->getLang('gitlab.commits').'</b><br>';
        $renderer->doc .= '<table border="1">
            <thread>
                <tr>
                    <th>Committer Name</th>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>URL</th>
                <tr>
            </thread>
            <tbody>';

        $total = count($commits) < $number ? count($commits) : $number;
        for ($i = 0; $i < $total; $i++) {
            $renderer->doc .= '<tr>';
            $renderer->doc .= '<td>'.$commits[$i]['committer_name'].'</td>';
            $renderer->doc .= '<td>'.$commits[$i]['title'].'</td>';
            $renderer->doc .= '<td>'.$commits[$i]['message'].'</td>';
            $renderer->doc .= '<td>'.$commits[$i]['committed_date'].'</td>';
            $renderer->doc .= '<td><a href='.$commits[$i]['web_url'].'>Commit link</a></td>';
            $renderer->doc .= '</tr>';
        }
        $renderer->doc .= '</tbody></table>';
    }

    function renderProjectIssues($renderer, $gitlab, $project_id, $number) {
        $issues = $gitlab->getIssues($project_id);

        $renderer->doc .= '<b class="gitlab">'.$this->getLang('gitlab.issues').'</b><br>';
        $renderer->doc .= '<table border="1">
            <thread>
                <tr>
                    <th>Author</th>
                    <th>Title</th>
                    <th>Labels</th>
                    <th>Assignees</th>
                    <th>State</th>
                    <th>URL</th>
                <tr>
            </thread>
            <tbody>';

        $total = count($issues) < $number ? count($issues) : $number;
        for ($i = 0; $i < $total; $i++) {
            if ($issues[$i]['state'] == 'closed') {
                continue;
            }
            $renderer->doc .= '<tr>';
            $renderer->doc .= '<td>'.$issues[$i]['author']['name'].'</td>';
            $renderer->doc .= '<td>'.$issues[$i]['title'].'</td>';
            $labels = $issues[$i]['labels'];
            $l_list = '';
            foreach ($labels as $l) {
                $l_list .= $l.', ';
            }
            $renderer->doc .= '<td>'.rtrim($l_list,', ').'</td>';
            $assignees = $issues[$i]['assignees'];
            $a_list = '';
            foreach ($assignees as $a) {
                $a_list .= $a['name'].', ';
            }
            $renderer->doc .= '<td>'.rtrim($a_list, ', ').'</td>';
            $renderer->doc .= '<td>'.$issues[$i]['state'].'</td>';
            $renderer->doc .= '<td><a href='.$issues[$i]['web_url'].'>Issue link</a></td>';
            $renderer->doc .= '</tr>';
        }
        $renderer->doc .= '</tbody></table>';

    }

    function renderProjectMilestones($renderer, $gitlab, $project_id, $number) {
        $milestones = $gitlab->getMilestones($project_id);

        $renderer->doc .= '<b class="gitlab">'.$this->getLang('gitlab.milestones').'</b><br>';
        $renderer->doc .= '<table border="1">
            <thread>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>State</th>
                    <th>Created</th>
                    <th>URL</th>
                <tr>
            </thread>
            <tbody>';

        $total = count($milestones) < $number ? count($milestones) : $number;
        for ($i = 0; $i < $total; $i++) {
            $renderer->doc .= '<tr>';
            $renderer->doc .= '<td>'.$milestones[$i]['title'].'</td>';
            $renderer->doc .= '<td>'.$milestones[$i]['description'].'</td>';
            $renderer->doc .= '<td>'.$milestones[$i]['state'].'</td>';
            $renderer->doc .= '<td>'.$milestones[$i]['created_at'].'</td>';
            $renderer->doc .= '<td><a href='.$milestones[$i]['web_url'].'>Milestone link</a></td>';
            $renderer->doc .= '</tr>';
        }

        $renderer->doc .= '</tbody></table>';
    }

    function renderProjectPipelines($renderer, $gitlab, $project_id, $number) {
        $pipelines = $gitlab->getPipelines($project_id);

        $renderer->doc .= '<b class="gitlab">'.$this->getLang('gitlab.pipelines').'</b><br>';
        $renderer->doc .= '<table border="1">
            <thread>
                <tr>
                    <th>Ref</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>URL</th>
                <tr>
            </thread>
            <tbody>';

        $total = count($pipelines) < $number ? count($pipelines) : $number;
        for ($i = 0; $i < $total; $i++) {
            $renderer->doc .= '<tr>';
            $renderer->doc .= '<td>'.$pipelines[$i]['ref'].'</td>';
            $renderer->doc .= '<td>'.$pipelines[$i]['status'].'</td>';
            $renderer->doc .= '<td>'.$pipelines[$i]['source'].'</td>';
            $renderer->doc .= '<td><a href='.$pipelines[$i]['web_url'].'>Pipeline link</a></td>';
            $renderer->doc .= '</tr>';
        }

        $renderer->doc .= '</tbody></table>';

    }

    function renderProjectError($renderer, $data) {
        $img_url = DOKU_URL . 'lib/plugins/gitlabapi/images/gitlab.png';

        $renderer->doc .= '<div class="gitlab">';
        $renderer->doc .= '<span><img src="'.$img_url.'" class="gitlab"></span>';
        $renderer->doc .= '<b class="gitlab">'.$this->getLang('gitlab.project').'</b><br>';
        $renderer->doc .= '<hr class="gitlab">';
        $renderer->doc .= '<p>'.$this->getLang('gitlab.error').'</p>';
        $renderer->doc .= '</div>';
    }

    function getDateTime($activity_time) {
        $date_exploded = explode('T', $activity_time);
        $time_exploded = explode('Z', $date_exploded[1]);

        return array('date' => $date_exploded[0], 'time' => substr($time_exploded[0], 0, -4));
    }

}




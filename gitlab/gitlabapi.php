<?php

class GitLabApi {
    const version = '0.0.1';

    public $client;
    public $data;

    function __construct($dw_data) {
        $this->dw_data = $dw_data;
        $this->client = curl_init();
    }

    function getAPIUrl() { return $this->dw_data['server'] . '/api/v4/'; }

    function closeClient() { curl_close($this->client); }

    function gitlabRequest($url) {
        curl_setopt($this->client, CURLOPT_URL, $url);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, array(
            'PRIVATE-TOKEN: '.$this->dw_data['token']
        ));
        curl_setopt($this->client, CURLOPT_SSL_VERIFYHOST, '1');
        curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, '0');
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);

        $answer = curl_exec($this->client);
        $answer_decoded = json_decode($answer, true);

        return $answer_decoded;
    }

    function getProject() {
        $project_name = basename($this->dw_data['project-path']);
        $url_request = $this->getAPIUrl().'search?scope=projects&search='.$project_name;
        $project = $this->gitlabRequest($url_request);

        foreach ($project as $p) {
            if ($p['path_with_namespace'] == $this->dw_data['project-path'])
                return $p;
        }
    }

    function getCommits($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/repository/commits';
        $commits = $this->gitlabRequest($url_request);
        return $commits;
    }

    function getIssues($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/issues';
        $issues = $this->gitlabRequest($url_request);
        return $issues;
    }

    function getMilestones($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/milestones';
        $milestones = $this->gitlabRequest($url_request);
        return $milestones;
    }

    function getPipelines($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/pipelines';
        $pipelines = $this->gitlabRequest($url_request);
        return $pipelines;
    }
}



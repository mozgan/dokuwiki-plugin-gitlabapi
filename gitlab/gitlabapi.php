<?php

class GitLabApi {
    const version = '0.1.0';

    public $client;
    public $data;

    function __construct($dw_data) {
        if ($dw_data['server'] == null) {
            echo "GitLabApi: ERROR - NO SERVER DEFINED!";
            die();
        }
        if($dw_data['token'] == null) {
            echo "GitLabApi: ERROR - NO TOKEN GIVEN!";
            die();
        }
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
        return json_decode($answer, true);
    }

    function getProject() {
        $project_name = basename($this->dw_data['project-path']);
        $url_request = $this->getAPIUrl().'search?scope=projects&search='.$project_name;
        $project = $this->gitlabRequest($url_request);

        if (array_key_exists('message', $project)) {
            echo "ERROR: ", $project['message'];
            return;
        }

        foreach ($project as $p) {
            if ($p['path_with_namespace'] == $this->dw_data['project-path']) {
                return $p;
            }
        }
    }

    function getCommits($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/repository/commits';
        return $this->gitlabRequest($url_request);
    }

    function getIssues($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/issues';
        return $this->gitlabRequest($url_request);
    }

    function getMilestones($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/milestones';
        return $this->gitlabRequest($url_request);
    }

    function getPipelines($id) {
        $url_request = $this->getAPIUrl().'projects/'.$id.'/pipelines';
        return $this->gitlabRequest($url_request);
    }
}



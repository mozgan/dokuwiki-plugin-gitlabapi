# DokuWiki Plugin GitLab-Api
Dokuwiki plugin to display some information from a GitLab project.

## Requirements

The curl library (``php-curl``) for PHP is required.

## Install

Download the plugin GitLab-Api into the `${dokuwiki_root}/lib/plugins` folder and restart DokuWiki or use the Extension Manager.

## Configuration

The following values must be configured in the Configuration Manager:

- **server.default**: Set your default GitLab url without slash ending. You can override this setting in `server.json` file.
- **token.default**: Fill your admin token. You can override this setting in `server.json` file.

## Syntax

### Default Syntax

```php
<gitlab-api project-path="<NAMESPACE>/<SUB_DIRS>/<PROJECT_NAME>" />
```

- **NAMESPACE** is the namespace of your project.
- **SUB_DIRS** is the sub-directories of your project if exists.
- **PROJECT_NAME** is the name of your project.

For instance, if your project is available at `http://gitlab.domain.com/ns/dir1/dir2/project`, then the syntax should be:

```php
<gitlab-api project-path="ns/dir1/dir2/project" />
```

### Display Information

The plugin displays following information if you add corresponded parameter:

- **milestones="n"** lists the latest $n$ milestones.
- **commits="n"** lists the latest $n$ commits.
- **issues="n"** lists the latest $n$ issues.
- **pipelines="n"** lists the latest $n$ pipelines.

For instance, if you want to see the latest 3 milestones, 10 commits, 5 issues, and 10 pipelines, then the syntax will be:

```php
<gitlab-api project-path="ns/dir/project" commits="10" issues="5" milestones="3" pipelines="10" />
```

### Override Server and Token

There a JSON file `server.json` inside the root of plugin. You can add or change the servers and their tokens.

For instance; you have a GitLab server namely `gitlab.home` and following JSON file:

```json
{
  "gitlab.home": {
    "url": "http://192.168.0.10",
    "token": "aabbccddeeffgghh"
  }
}
```

Then you can add `server` parameter as follows:

```php
<gitlab-api server="gitlab.home" project-path="ns/project" />
```

## Thanks

This plugis is an extended version of [Gitlab-Project](https://github.com/algorys/gitlabproject/). Thanks to [@algorys](https://github.com/algorys). 

[![Build Status](https://scrutinizer-ci.com/g/DTForce/slim-hook/badges/build.png?b=master)](https://scrutinizer-ci.com/g/DTForce/slim-hook/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DTForce/slim-hook/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DTForce/slim-hook/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/DTForce/slim-hook/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/DTForce/slim-hook/?branch=master)
# Gitlab webhook in PHP
This is a very simple webhook for gitlab, allowing to start bash scripts as a reaction to BUILD, PUSTH and TAG events.
## Installation
Install with composer:

```
composer create-project dtforce/slim-hook
```

Or by cloning this repo.
## Configuration
Create a file `local.yaml` in the `config` folder containing something like this:
```yaml
settings:
  secret: 3219874514564 - this should match your webhook secret token
scripts:
  gitlab-org/gitlab-test: - name of the project
    deploy: - what event do we react to
      staging: bash /path/to/app/test.bash deploy - on deploy, what enviroment do we consider
    push:
      refs/heads/master: - on push, what branch do we consider
        cwd: /path/to/app - optional you can set working directory
        - bash /path/to/app/test.bash push - this is going to be executed throug shell_exec
    tag:
        - bash /path/to/app - on tag no subcategories
        - bash do-smothin-else - you can execute multiple commands with one hook
  gitlab-org/gitlab-something-else: - more projects
```
## Variables
When is your script being executed, there are few variables, given to the environment.

These are examples, it should be quite clear:
### Deploy
```php
[
    'HOOK_PROJECT_PATH' => 'gitlab-org/gitlab-test',
    'HOOK_BUILD_ID' => 379,
    'HOOK_BUILD_REF' => 'bcbb5ec396a2c0f828686f14fac9b80b780504f2',
    'HOOK_ENV_NAME' => 'staging'
]
```
### Push
```php
[
    'HOOK_PROJECT_PATH' => 'gitlab-org/gitlab-test',
    'HOOK_REF' => 'refs/heads/master',
    'HOOK_BRANCH' => 'master',
    'HOOK_BUILD_REF' => 'da1560886d4f094c3e6c9ef40349f7d38b5d27d7'
]
```
### Tag
```php
[
    'HOOK_PROJECT_PATH' => 'jsmith/example',
    'HOOK_REF' => 'refs/tags/v1.0.0',
    'HOOK_TAG' => 'v1.0.0',
    'HOOK_BUILD_REF' => '82b3d5ae55f7080f1e6022629cdb57bfae7cccc7'
]
```
## Launching
You can configure Apache in the usual way, or you can launch using PHP embedded server like this:
```
/usr/bin/php7.0 -S localhost:8080 -t /path/to/app/slim-hook/public
```
## BashREST
For convenience this application can also serve simple REST requests. This can be handy, when you want a result of the script executed
on the target platform when deploying in gitlab CI. By making request to this server in the following form:

`POST to /groupName/projectName/action`

you will launch a script described in the config like this:
```yaml
bashREST:
  groupName/projectName:
    action: launch some bash command here
    action2:
      cwd: dir
      0: test1
      1: test2
```

The response will be json of the following format:
```json
{
    "result" : "Whatever the script you launched returned to stdout"
}
```

If you sent some data (in the form of JSON) in the POST body, the script
will receive them in its enviroment variables in a flattened form.

Example:
```json
{
    "test" : "asd",
    "nested" : {
        "a" : "b",
        "asd" : "c"
    },
    "array" : ["asd", "zxc", "xcvxcv"]
}
```

sent as POST to `/bash-rest/test-app/my-action`

Will result in these environment variables set:
```bash
HOOK_PROJECT_PATH=bash-rest/test-app
HOOK_ACTION=my-action
HOOK_test=asd
HOOK_nested_a=b
HOOK_nested_asc=c
HOOK_array_0=asd
HOOK_array_1=zxc
HOOK_array_2=xcvxcv
```

If you set-up your secret in the config script, request to the __BashREST__
server will need to authorize themselves with this secret. Secret is stored
in header field `X-Secret`. Notice it is different to the one used by Gitlab 
Webhooks.

Example of a BashREST call:

```bash
curl -X POST http://localhost:4000/gitlab-org/gitlab-test/status -H "X-Secret: $BASH_REST_SECRET" -H 'Content-Type: application/json' -d '{"ENV":"production"}'
```

## That's all
Hope you like it!

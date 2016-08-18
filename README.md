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
        command: bash /path/to/app/test.bash push - this is going to be executed throug shell_exec
    tag: bash /path/to/app - on tag no subcategories
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
## That's all
Hope you like it!
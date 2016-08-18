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
        cwd: /path/to/app
        command: bash /path/to/app/test.bash push v
    tag: bash /path/to/app - on tag no subcategories
  gitlab-org/gitlab-something-else: - more projects
```
## Launching
You can configure Apache in the usual way, or you can launch using PHP embedded server like this:
```
/usr/bin/php7.0 -S localhost:8080 -t /path/to/app/slim-hook/public
```
## That's all
Hope you like it!
# Static analysis and formatting

Use PHP 8.3–8.5 with the extensions required by `composer.json` and Composer.
Install the locked dependencies with `composer install --no-plugins --no-scripts`.
Fetch the target branch so its remote-tracking ref is current, then, for a pull
request targeting `master`, run:

```sh
bash scripts/lint.sh "$(git merge-base origin/master HEAD)"
```

For another target branch, substitute its remote-tracking ref. This runs CI's PHPStan
configuration (level 6 on `lib/`, including its baseline) and the same PHPCS
PSR-2 error check on changed tracked PHP files under `lib/`. The comparison
includes working-tree changes, but not untracked files; add new files to the
index before checking them. Warnings and files outside `lib/` are not CI's
PHPCS gate. Review formatting in those files using the surrounding conventions.

Set `PHP_RUNNER` to a PHP executable when needed. AI sessions must use the
Docker-backed `$PHP_BIN` described in `.ai-cli/README.md`. To format a selected
file on a standard PHP installation, run:

```sh
php vendor/bin/phpcbf --standard=PSR2 -- path/to/file.php
```

Review the resulting diff and rerun the checks and relevant tests. PHPCBF can
modify existing work and may leave errors it cannot fix automatically; select
files carefully and inspect its output and exit status.

# Local Deploy

This repository currently has no built-in GitHub Actions or standalone deployment pipeline.
For local development, deploy the plugin directly into the Moodle checkout:

```bash
bash bin/deploy-local.sh
```

Default target:

```text
/Users/moskaliuk/demo/site/moodle/public/local/customerportal
```

Override the target if needed:

```bash
bash bin/deploy-local.sh --target /path/to/moodle/public/local/customerportal
```

Notes:

- Deployment uses host-side `rsync -a --delete`
- If `demo-webserver-1` exists, the script auto-detects the `/var/www/site` bind mount
- `.git`, editor metadata, `node_modules`, and `vendor` are excluded
- If Moodle still shows old output after deploy, purge Moodle caches in the running environment

## Integration tests

### Requirements

- Shopware installed - default setup without any extra configurations
    - Language `en_US`
    - Currency `EUR`
    - SW min version can be checked in [composer.json](../composer.json)
- Current plugin installed & active
    - `bin/console plugin:install --activate SgateShopgatePluginSW6`
- Demo Data plugin installed & active
    - `APP_ENV=prod php bin/console store:download -p SwagPlatformDemoData`
    - `bin/console plugin:install --activate SwagPlatformDemoData`
- Newman - runs tests in a CLI or via App
    - CLI can be installed via `npm install -g newman@^5.3.0`
        - Run `newman run ./collection.json -g ./globals.json -e ./environment.json --env-var "host=http://local.sw6"`
        - You can check how we run the collection via CLI in [gitlab.ci](../.gitlab-ci.yml) file
    - You could also use Postman App:
        - Import collection data - collection.json, environment.json, global.json
        - Right click on collection folder & `Run collection`
- Mockoon - this is a mock server for Order & Cron tests. It mimics Shopgate server & responses coming from it.
  Likewise, you could use the CLI or the App here.
    - CLI installation `npm install -g @mockoon/cli`
        - Run `mockoon-cli start --data ../tests/Mockoon/environment.json --all --hostname $APP_URL`
        - It defaults to localhost:3000
        - Note that in Postman/Newman globals.json there is a URL pointing to mockoon `mock_server_merchant_uri`. Just
          make sure they match in case you have to change it from default.
    - App - just search for Mockoon & download UI App
        - Import [environment.json](Mockoon/environment.json) file
        - Run

### Useful

We found this command helpful for re-installing the environment between tests. This already assumes that you installed
both plugins mentioned earlier.

```shell
# removes cache folders
# re-installs DB
# installs & activates plugins
docker exec [CONTAINER_ID] sh -c "rm -rf var/cache/dev_*;rm -rf var/cache/prod_*;bin/console system:install --drop-database --basic-setup --force;bin/console plugin:refresh --quiet;bin/console plugin:install --activate SgateShopgatePluginSW6;bin/console plugin:install --activate SwagPlatformDemoData;bin/console bundle:dump --quiet;bin/console assets:install --quiet;"
```

Another useful way to re-install is placing the following script in the `[root]/composer.json` **scripts** section:
```json
{
    "reinstall": [
        "@init:db",
        "@php bin/console theme:compile",
        "@php bin/console theme:change --all Storefront",
        "@php bin/console plugin:refresh",
        "@php bin/console plugin:install -a --skip-asset-build SwagPlatformDemoData",
        "@php bin/console plugin:install -a --skip-asset-build SgateShopgatePluginSW6",
        "@php bin/console assets:install"
    ]
}
```

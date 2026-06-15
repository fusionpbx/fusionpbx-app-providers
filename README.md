# fusionpbx-app-providers
SMS/MMS Providers App

## Install
```
cd /var/www/fusionpbx/app
git clone https://github.com/fusionpbx/fusionpbx-app-messages.git providers
chown -R www-data:www-data /var/www/fusionpbx
php /var/www/fusionpbx/core/upgrade/upgrade.php
```

Requires the install of [https://github.com/fusionpbx/fusionpbx-app-messages](https://github.com/fusionpbx/fusionpbx-app-messages)

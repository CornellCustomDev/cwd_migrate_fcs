<?php

// PUT THIS FILE IN web/sites/default/
// ADD "include" TO web/sites/default/settings.php (refer to pantheon docs link below, and cwd_migrate_fcs/README.md)

/**
 * @file
 * Configuration file with source Drupal 7 site database credentials.
 * DB creds can be updated with pantheon-systems/terminus-secrets-plugin, on
 * whichever env you're migrating **INTO.** A secrets.json file has been added
 * to dev, test, and m-runtest.
 * Based on https://pantheon.io/blog/running-drupal-8-data-migrations-pantheon-through-drush
 */

$secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
if (file_exists($secretsFile)) {
  $secrets = json_decode(file_get_contents($secretsFile), 1);
}
if (!empty($secrets['migrate_fcs_db_port']) && !empty($secrets['migrate_fcs_db_host']) && !empty($secrets['migrate_fcs_db_password'])) {
  $databases['migrate_fcs_db']['default'] = array(
    'database' => 'pantheon',
    'username' => 'pantheon',
    'password' => $secrets['migrate_fcs_db_password'],
    'host' => $secrets['migrate_fcs_db_host'],
    'port' => $secrets['migrate_fcs_db_port'],
    'driver' => 'mysql',
    'prefix' => '',
    'collation' => 'utf8mb4_general_ci',
  );
}

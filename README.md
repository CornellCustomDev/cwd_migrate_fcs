# cwd_migrate_fcs
A raw copy of the custom migration used for the FCS Drupal 7 to Drupal 8 project; by Alison McCauley and Eric Woods.

## Links
Just, dumping some links in here until if/when we do additional documentation/organization!
* Great blog series! (didn't exist when we were building the FCS migration)<br />
  https://understanddrupal.com/migrations
* Media entities:
  * We used this tutorial/how-to:<br />
  https://thinktandem.io/blog/2019/04/04/migrating-a-drupal-7-file-to-a-drupal-8-media-entity/
  * In the months since the FCS migration, there's a new contrib module to help with migrating into the D8 media system:<br />
  https://www.drupal.org/project/migrate_media_handler
    > Provides migration process plugins to facilitate conversion of Drupal 7 file/image fields and inline file embeds in rich text, into full-fledged Drupal 8 media entities.

## Misc
...thoughts or whatever...
* Key contrib modules: migrate_upgrade, migrate_tools, migrate_plus
  * Explanation of "what's what" (from @heddn on Drupal slack, 2019-12-23):
    > upgrade is generating config entities from the core migrate templates.<br />
    > tools is the toolbox to execute migrations<br />
    > plus is all the process and source plugins that didn't make it into core, including config entity support
* The URL alias handling in this migration is SUPER COOL. "Also," as of Drupal 8.8.x, aliases are different, so, FYI (from @mikelutz on Drupal slack, 2019-12-23):
  > Prior to 8.8 there was a custom destination for url alias, but starting with 8.8.0, aliases are entities, so you can migrate them in like any entity.
* From John Locke (@freelock) on Drupal slack (2020-02-15), talking about how they do migrations without a custom migrate module, in response to my question about an online resource for doing migrations in the UI and/or without a custom migrate module (since my migration experience is 6-8 months old):
  > what I do is use drush migrate-update to create the migrate configs<br />
  > e.g. drush migrate-upgrade --configure-only --legacy-root=/home/john/git/drupal6<br />
  > then drush migrate-import --tag=Configuration<br />
  > and then when you drush cex you've got a ton of migrate configs to work with<br />
  > oh but you do want to enable all the modules that might be included in your migration before starting -- e.g. media migrations, flag, addressfield, etc -- many of these have migrations that will auto configure themselves with that first command<br />
  > and you also need to set up a database connection to the old db. Haven't looked for a good online resource recently -- we've done 20 - 30 of these, so I'm mostly copying from existing settings files, and my shell history 😉

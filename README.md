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

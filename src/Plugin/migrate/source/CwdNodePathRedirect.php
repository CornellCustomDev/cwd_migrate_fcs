<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\source;

use Drupal\path\Plugin\migrate\source\d7\UrlAlias;
use Drupal\migrate\Row;

/**
 * Customize Drupal 7 URL aliases source from database for creating redirects
 * from old node aliases to the migated nodes on the destination site.
 * Source: https://deninet.com/blog/2018/04/22/migrating-path-aliases-drupal-8-redirects-part-2
 * (Source for related YML code: https://deninet.com/blog/2018/04/03/migrating-path-aliases-drupal-8-redirects-part-1)
 *
 * @MigrateSource(
 *   id = "cwd_node_path_redirect",
 *   source_module = "path"
 * )
 */
class CwdNodePathRedirect extends UrlAlias {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Only include node aliases.
    $query->condition('ua.source', 'node/%', 'LIKE');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $aliasSource = $row->getSourceProperty('source');
    if (preg_match('/node\/[0-9]+/', $aliasSource)) {
      // Extract the node ID from source string.
      $nid = substr($aliasSource, 5);
      // Provide it to the migration as the "nid_from_path" field.
      $row->setSourceProperty('nid_from_path', $nid);
    }
    return parent::prepareRow($row);
  }

}

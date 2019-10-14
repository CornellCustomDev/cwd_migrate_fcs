<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;

/**
 * Drupal 7 node with workbench_access source data from database.
 *
 * @MigrateSource(
 *   id = "d7_node_wb_access",
 *   source_provider = "node"
 * )
 *
 * @code
 * field_term:
 *   plugin: migration_lookup
 *   source: wba_termids
 *   migration: cwd_term_secgroup
 * @endcode
 */
class NodeWorkbenchAccess extends Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $node_id = $row->getSourceProperty('nid');
    // Source data is queried from 'workbench_access_node' table.
    $query = $this->select('workbench_access_node', 'wban')
      ->condition('wban.nid', $node_id);

    // Store the value I need in a variable.
    $waids = $query->fields('wban', ['access_id'])
      ->execute()
      ->fetchAll();

    $wba_termids = array();
    foreach ($waids as $index => $waid) {
      $wba_termids[] = $waids[$index]['access_id'];
    }

    $row->setSourceProperty('wba_termids', $wba_termids);

    return parent::prepareRow($row);
  }

}

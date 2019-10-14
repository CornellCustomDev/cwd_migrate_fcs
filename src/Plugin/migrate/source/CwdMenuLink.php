<?php

// Modify query to only include links from specific menu(s).

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\source;

use Drupal\menu_link_content\Plugin\migrate\source\MenuLink;
use Drupal\migrate\Row;

/**
 * Drupal menu link source from database.
 *
 * @MigrateSource(
 *   id = "cwd_menu_link",
 *   source_module = "menu"
 * )
 */
class CwdMenuLink extends MenuLink {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // Only migrate menu items from breadcrumbs and main menus.
    $query->condition('ml.menu_name', ['menu-main-menu-breadcrumbs'], 'IN');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    return parent::prepareRow($row);
  }

}

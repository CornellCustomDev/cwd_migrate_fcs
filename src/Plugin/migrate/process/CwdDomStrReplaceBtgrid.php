<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\process;

use Drupal\cwd_migrate_fcs\Utility;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\DomProcessBase;

/**
 * Replace bootstrap grid on a source dom.
 *
 * Replace bootstrap grid markup with that of our own framework.
 * Handles 2- and 3-col grids (that's all that was in use on source site).
 * Meant to be used after dom process plugin (like migrate_plus DomStrReplace).
 *
 * Example:
 *
 * @code
 * process:
 *   'body/value':
 *     -
 *       plugin: dom
 *       method: import
 *       source: 'body/0/value'
 *     -
 *       plugin: cwd_dom_str_replace_btgrid
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "cwd_dom_str_replace_btgrid"
 * )
 */
class CwdDomStrReplaceBtgrid extends DomProcessBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->init($value, $destination_property);
    $this->xpath->registerNamespace("php", "http://php.net/xpath");
    $this->xpath->registerPHPFunctions("preg_match");

    
    $btgrid_nodes = $this->xpath->query('//div[@class="btgrid"]');
    /** @var \DOMNode $html_node */
    foreach ($btgrid_nodes as $html_node) {
      Utility::removeMatchedEnclosingNode($this->xpath, 'div[php:function("preg_match", "/^row row-\d+$/", string(@class))]', $html_node);
      Utility::removeMatchedEnclosingNode($this->xpath, '//div/div[@class="content"]', $html_node);
    }
    foreach ($this->xpath->query('//div[(@class="btgrid") and div[@class="col col-md-6"]]') as $div) {
      $div->setAttribute('class', 'padded two-col');
    }
    foreach ($this->xpath->query('//div[(@class="btgrid") and div[@class="col col-md-4"]]') as $div) {
      $div->setAttribute('class', 'padded three-col');
    }

    // remove underlines!  all underlines!
    Utility::removeMatchedEnclosingNode($this->xpath, '//u');
    // also remove <strong> elems inside of headings, ew
    Utility::removeMatchedEnclosingNode($this->xpath, '//h3/strong');
    Utility::removeMatchedEnclosingNode($this->xpath, '//h4/strong');

    return $this->document;
  }
}

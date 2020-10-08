<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\process;

use Drupal\cwd_migrate_fcs\Utility;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\DomProcessBase;

/**
 * Remove enclosing tag, given xpath to match.
 *
 * Context is optional (entire field value by default), xpath is required
 * and can support removing multiple nodes in the same process run.
 *
 * NOTE: I *think* we didn't end up using this plugin, I think we ended up
 * putting everything into ./CwdRichTextDomProcess.php, and there's a
 * (simpler) function in ../../../Utility.php: removeMatchedEnclosingNode()
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
 *       plugin: cwd_dom_remove_enclosing_tag
 *       context: '//div[@class="btgrid"]'
 *       xpath:
 *         - '//div/div[@class="content"]'
 *     -
 *       plugin: cwd_dom_remove_enclosing_tag
 *       xpath:
 *         - '//u'
 *         - '//h3/strong'
 *         - '//h4/strong'
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "cwd_dom_remove_enclosing_tag"
 * )
 */
class CwdDomRemoveEnclosingTag extends DomProcessBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->init($value, $destination_property);
    $this->xpath->registerNamespace("php", "http://php.net/xpath");
    $this->xpath->registerPHPFunctions("preg_match");

    $match_expressions = $this->configuration['xpath'];
    if (!is_array($match_expressions)) {
      $match_expressions = [$match_expressions];
    }

    foreach ($match_expressions as $match_expression) {
      if (isset($this->configuration['context'])) {
        foreach ($this->xpath->query($this->configuration['context']) as $html_node) {
          Utility::removeMatchedEnclosingNode($this->xpath, $match_expression, $html_node);
        }
      }
      else {
        Utility::removeMatchedEnclosingNode($this->xpath, $match_expression);
      }
    }

    return $this->document;
  }

}

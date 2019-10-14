<?php


namespace Drupal\cwd_migrate_fcs;

/**
 * Utility methods for Custom Development migration
 *
 * @package Drupal\cwd_migrate_fcs
 */
class Utility {

  /**
   * Remove the enclosing node matched by the expression, retaining node content.
   *
   * @param \DOMXPath $xpath
   * @param string $match_expression
   * @param \DOMNode|null $html_node
   */
  public static function removeMatchedEnclosingNode(\DOMXPath $xpath, string $match_expression, \DOMNode $html_node = NULL): void {
    $nodesToRemove = [];
    $matched_node = $xpath->query($match_expression, $html_node);
    /** @var \DOMNode $node */
    foreach ($matched_node as $node) {
      $nodesToRemove[] = $node;
      while ($node->hasChildNodes()) {
        $child = $node->removeChild($node->firstChild);
        $node->parentNode->insertBefore($child, $node);
      }
    }
    /** @var \DOMNode $removeNode */
    foreach ($nodesToRemove as $removeNode) {
      $removeNode->parentNode->removeChild($removeNode);
    }
  }

}

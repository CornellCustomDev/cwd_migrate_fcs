<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\process;

use Drupal\Core\Config\ConfigFactory;
use Drupal\cwd_migrate_fcs\Utility;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\Dom;
use Drupal\migrate_plus\Plugin\migrate\process\DomApplyStyles;
use Drupal\migrate_plus\Plugin\migrate\process\StrReplace;

/**
 * Content transformations used on most/all rich text fields.
 *
 * Example:
 *
 * @code
 * process:
 *   body:
 *     plugin: cwd_rich_text_dom_process
 *     source: body
 *   _source_field_list:
 *     plugin: cwd_rich_text_dom_process
 *     source: field_list/0/value
 *     method: string
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "cwd_rich_text_dom_process"
 * )
 */
class CwdRichTextDomProcess extends DomApplyStyles {

  /**
   * A locally instantiated DOM to import and export the content.
   *
   * @var \Drupal\migrate_plus\Plugin\migrate\process\Dom
   */
  private $dom;

  /**
   * A locally instantiated StrReplace for preprocessing before parsing the DOM.
   *
   * @var \Drupal\migrate_plus\Plugin\migrate\process\StrReplace
   */
  private $strReplacePre;

  /**
   * A locally instantiated StrReplace for post-processing the DOM.
   *
   * @var \Drupal\migrate_plus\Plugin\migrate\process\StrReplace
   */
  private $strReplacePost;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory) {
    $this->dom = $this->getDom();
    $this->strReplacePre = $this->getStrReplaceConfigurationPre();
    $this->strReplacePost = $this->getStrReplaceConfigurationPost();
    $configuration += ['method' => 'field'];

    $configuration = $this->getDomApplyStylesConfiguration($configuration);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory);
  }


  /**
   * Get a Dom processor.
   *
   * @return \Drupal\migrate_plus\Plugin\migrate\process\Dom
   */
  protected function getDom(): Dom {
    // Defaults to 'import" method; other available method is 'export'.
    return new Dom(['method' => 'import'], 'dom', []);
  }

  /**
   * Get a StrReplace processor with config'd search/replace.
   *
   * @return \Drupal\migrate_plus\Plugin\migrate\process\StrReplace
   */
  protected function getStrReplaceConfigurationPre() {
    $configuration['regex'] = true;
    $empty_lines = '[\n\r]{1}[ \t]*';
    $configuration['search'] = [
      "/${empty_lines}<p>&nbsp;<\/p>/",
      "/${empty_lines}<div.*>&nbsp;<\/div>/",
      "/${empty_lines}<h2>&nbsp;<\/h2>/",
      "/${empty_lines}<h3>&nbsp;<\/h3>/",
      "/${empty_lines}<h4>&nbsp;<\/h4>/",
      "/${empty_lines}<li>&nbsp;<\/li>/",
    ];
    $configuration['replace'] = ['', '', '', '', '', ''];
    return new StrReplace($configuration,'str_replace', []);
  }

  /**
   * Get config for a DomApplyStyles processor.
   *
   * @param array $configuration
   *
   * @return array
   */
  protected function getDomApplyStylesConfiguration(array $configuration): array {
    $configuration['format'] = 'migration_html';
    $configuration['rules'] = [
      [
        'xpath' => '//div[contains(@style,"background:#eeeeee;border:1px solid #cccccc;padding:5px 10px;min-height:350px")]',
        'style' => 'Container: Gray',
      ],
      [
        'xpath' => '//span[@style="font-weight:bold"]',
        'style' => 'Bold',
      ],
      [
        'xpath' => '//ul[contains(@class,"nav")]',
        'style' => 'UL: Custom chevron bullets',
      ],
      [
        'xpath' => '//ul[contains(@id,"list-menu")]/li/ul',
        'style' => 'UL: Custom chevron bullets',
      ],
      [
        'xpath' => '//ul[contains(@class,"list-unstyled")]/li',
        'style' => 'Paragraph',
      ],
      [
        'xpath' => '//ul[contains(@class,"list-unstyled")]',
        'style' => 'Paragraph',
      ],
    ];
    return $configuration;
  }

  /**
   * Get a StrReplace processor with config'd search/replace.
   *
   * @return \Drupal\migrate_plus\Plugin\migrate\process\StrReplace
   */
  protected function getStrReplaceConfigurationPost() {
    $configuration['regex'] = true;
    $configuration['search'] = [
      '/&#13;/',
      '/(<p>)<p>/',
      '/(<\/ul>)<\/p>([\n\r])<\/p>/',
      '/([\n\r]){2,}/',
      '/(([\n\r])+([ \t])+([\n\r]+))/',
      '/(<div) class="col col-md-4"(>)/',
      '/(<div) class="col col-md-6"(>)/',
      '/( href=")https*:\/\/fcs\.cornell\.edu(\/)/'
    ];
    $configuration['replace'] = ['', '$1', '$1$2', '$1', '$2', '$1$2', '$1$2', '$1$2'];
    return new StrReplace($configuration,'str_replace', []);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      throw new MigrateSkipProcessException('No content for rich text dom process.');
    }
    if ($this->configuration['method'] == 'field') {
      if (!isset($value['value'])) {
        throw new MigrateSkipProcessException('No field value for rich text dom process.');
      }
      $value_field = $value['value'];
    }
    if ($this->configuration['method'] == 'string') {
      $value_field = $value;
    }
    $format = !empty($value['format']) ? $value['format'] : 'filtered_html';

    // Apply strReplacePre, to clean up before parsing DOM.
    $pre_import_value = $this->strReplacePre->transform($value_field, $migrate_executable, $row, $destination_property);

    // Import the DOM for processing.
    $dom_value = $this->dom->import($pre_import_value, $migrate_executable, $row, $destination_property);

    // Xpath initialization.
    $this->init($dom_value, $destination_property);
    $this->xpath->registerNamespace("php", "http://php.net/xpath");
    $this->xpath->registerPHPFunctions("preg_match");

    // Apply style rules.
    foreach ($this->configuration['rules'] as $rule) {
      $this->apply($rule);
    }

    // Remove btgrid.
    $btgrid_nodes = $this->xpath->query('//div[@class="btgrid"]');
    /** @var \DOMNode $node */
    foreach ($btgrid_nodes as $node) {
      Utility::removeMatchedEnclosingNode($this->xpath, 'div[php:function("preg_match", "/^row row-\d+$/", string(@class))]', $node);
      Utility::removeMatchedEnclosingNode($this->xpath, '//div/div[@class="content"]', $node);
    }
    foreach ($this->xpath->query('//div[(@class="btgrid") and div[@class="col col-md-6"]]') as $div) {
      $div->setAttribute('class', 'padded two-col');
    }
    foreach ($this->xpath->query('//div[(@class="btgrid") and div[@class="col col-md-4"]]') as $div) {
      $div->setAttribute('class', 'padded three-col');
    }

    // Remove additional superfluous nodes.
    Utility::removeMatchedEnclosingNode($this->xpath, '//u');
    Utility::removeMatchedEnclosingNode($this->xpath, '//h3/strong');
    Utility::removeMatchedEnclosingNode($this->xpath, '//h4/strong');

    // Export the DOM back to a string.
    $post_dom_value = $this->dom->export($this->document, $migrate_executable, $row , $destination_property);

    // Apply strReplacePost, to clean up after parsing DOM.
    $transformed_value = $this->strReplacePost->transform($post_dom_value, $migrate_executable, $row, $destination_property);

    if ($this->configuration['method'] == 'string') {
      return $transformed_value;
    }

    return ['value' => $transformed_value, 'format' => $format];
  }

}

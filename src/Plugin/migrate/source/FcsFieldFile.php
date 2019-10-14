<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 field_data_* and file_managed source from database.
 *
 * @MigrateSource(
 *   id = "fcs_field_file",
 *   source_module = "file"
 * )
 */
class FcsFieldFile extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $field_name = $this->configuration['field'];

    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->orderBy('f.fid');

    $query->innerJoin("field_data_{$field_name}", 'd', "d.{$field_name}_fid = f.fid");

    if (isset($this->configuration['bundle'])) {
      $query->condition('d.bundle', $this->configuration['bundle']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'uid' => $this->t('The {users}.uid who added the file. If set to 0, this file was added by an anonymous user.'),
      'filename' => $this->t('File name'),
      'uri' => $this->t('The URI to access the file'),
      'filemime' => $this->t('File MIME Type'),
      'status' => $this->t('The published status of a file.'),
      'timestamp' => $this->t('The time that the file was added.'),
      'type' => $this->t('The type of this file.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }
}

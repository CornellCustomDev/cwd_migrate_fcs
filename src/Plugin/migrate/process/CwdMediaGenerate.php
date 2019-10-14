<?php

namespace Drupal\cwd_migrate_fcs\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;

/**
 * Generates a media entity from a file and returns the media id.
 *
 * Credit: https://thinktandem.io/blog/2019/04/04/migrating-a-drupal-7-file-to-a-drupal-8-media-entity/
 *
 * @MigrateProcessPlugin(
 *   id = "cwd_media_generate"
 * )
 *
 * Generate the entity in a subprocess:
 *
 * @code
 *  field_name:
 *    -
 *      plugin: sub_process
 *      source: field_name
 *      process:
 *        target_id:
 *          -
 *            plugin: migration_lookup
 *            source: fid
 *            migration: cwd_file
 *          -
 *            plugin: cwd_media_generate
 *            destination_bundle: image
 *            destination_field: field_media_image
 * @endcode
 */
class CwdMediaGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['destination_field'])) {
      throw new MigrateException('Destination field must be set.');
    }
    if (!isset($this->configuration['destination_bundle'])) {
      throw new MigrateException('Destination bundle must be set.');
    }

    $field = $this->configuration['destination_field'];
    $bundle = $this->configuration['destination_bundle'];

    /* @var /Drupal/file/entity/File $file */
    $file = File::load($value);
    if ($file === NULL) {
      throw new MigrateException('Referenced file does not exist');
    }

    // Grab our alt tag.
    $alt = $row->getSourceProperty('alt') ?: '';
    if (empty($alt)) {
      $alt = ''; // "Media Name: " . $file->label();
    }

    $media_properties = [
      'bundle' => $bundle,
      'uid' => $file->getOwner() ? $file->getOwner()->id() : 1,
      'status' => '1',
      'created' => $file->getCreatedTime(),
      'changed' => $file->getChangedTime(),
      'name' => $file->label(),
      $field => [
        'target_id' => $file->id(),
        'alt' => $alt,
      ],
    ];
    $media = Media::create($media_properties);
    $media->save();

    // @todo uncomment this on the final migration: file_delete($file->id());

    return $media->id();
  }
}

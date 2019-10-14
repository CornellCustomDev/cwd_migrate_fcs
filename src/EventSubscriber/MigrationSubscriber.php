<?php


namespace Drupal\cwd_migrate_fcs\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateRollbackEvent;

/**
 * Class MigrationSubscriber.
 *
 * Handles various migrations tasks outside of normal flow.
 *
 * Credit: https://thinktandem.io/blog/2019/04/04/migrating-a-drupal-7-file-to-a-drupal-8-media-entity/
 *
 * @package Drupal\cwd_migrate_fcs
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Constructs a new MigrationSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManager $entity_field_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROLLBACK][] = ['onMigratePreRollback'];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onMigratePostRowSave'];
    return $events;
  }

  /**
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onMigratePostRowSave(MigratePostRowSaveEvent $event) {
    $row = $event->getRow();
    $entity_type_id = $this->getDestinationEntityTypeId($event->getMigration());

    $this->resetWebformNextSerial($entity_type_id, $row);
  }

  /**
   * React to rollback start.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The map event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onMigratePreRollback(MigrateRollbackEvent $event) {
    $migration = $event->getMigration();
    $entity_type_id = $this->getDestinationEntityTypeId($migration);
    $bundle = $this->getDestinationDefaultBundle($migration);

    $this->checkFieldsForMediaEntities($entity_type_id, $bundle);
  }

  /**
   * Resets webform.next_serial based on the webform data.
   *
   * @param $entity_type_id
   * @param \Drupal\migrate\Row $row
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function resetWebformNextSerial($entity_type_id, Row $row) {
    if ($entity_type_id != 'webform') {
      return;
    }

    $webform_data = $row->getDestination();
    /** @var \Drupal\webform\WebformEntityStorage $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    $webform = $webform_storage->load($webform_data['id']);
    $webform_storage->setNextSerial($webform, $webform_data['next_serial']);
  }

  /**
   * Checks the nodes fields for media entities.
   *
   * @param string $entity_type_id
   * @param string $bundle
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function checkFieldsForMediaEntities($entity_type_id, $bundle) {
    // Only run for entities.
    if (empty($entity_type_id)) {
      return;
    }

    // Don't run for webforms.
    if ($entity_type_id == 'webform') {
      return;
    }

    // Grab all our fields for this entity type.
    $fields = $this->entityFieldManager
      ->getFieldDefinitions($entity_type_id, $bundle);

    foreach ($fields as $field_name => $field_definition) {
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      if ($field_definition->getTargetBundle() !== NULL) {
        if ($field_definition->getType() === 'entity_reference'
            && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'media') {
          $this->removeMediaEntities($entity_type_id, $bundle, $field_name);
        }
      }
    }
  }

  /**
   * Remove the media entities for that field and type.
   *
   * @param string $entity_type_id
   * @param string $bundle
   * @param string $field_name
   *   The field name we are checking.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function removeMediaEntities($entity_type_id, $bundle, $field_name) {
    // Grab all our nodes to get the media ids.
    $entities = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->loadByProperties([
        'type' => [$bundle],
      ]);

    // Go through and load up the target entity ids.
    foreach ($entities as $entity) {
      $media = [];
      $ids = $entity->get($field_name)->getValue();
      foreach ($ids as $id) {
        if (isset($id['target_id'])) {
          $media_check = $this->entityTypeManager
            ->getStorage('media')->load($id['target_id']);
          if ($media_check !== NULL) {
            $media[] = $media_check;
          }
        }
      }
      // Remove the media entities associated with that type.
      if (!empty($media)) {
        $this->entityTypeManager->getStorage('media')->delete($media);
      }
    }
  }

  /**
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *
   * @return string
   */
  private function getDestinationEntityTypeId(MigrationInterface $migration): string {
    // Grab our type from the destination configuration
    // Note: This is brittle to the default_bundle being specified
    $dest = $migration->getDestinationConfiguration();
    if (!isset($dest['plugin'])) {
      return '';
    }
    $destinationEntityTypeId = ltrim(strstr($dest['plugin'], ':'), ':');

    return $destinationEntityTypeId;
  }

  /**
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *
   * @return string
   */
  private function getDestinationDefaultBundle(MigrationInterface $migration): string {
    $dest = $migration->getDestinationConfiguration();
    if (!isset($dest['default_bundle'])) {
      return '';
    }
    return $dest['default_bundle'];
  }

}

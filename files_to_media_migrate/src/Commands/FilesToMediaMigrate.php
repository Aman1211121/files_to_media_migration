<?php

namespace Drupal\files_to_media_migrate\Commands;

use Drush\Commands\DrushCommands;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A drush command file.
 *
 * @package Drupal\files_to_media_migrate\Commands
 */
class FilesToMediaMigrate extends DrushCommands {
  use StringTranslationTrait;

  /**
   * The Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * FilesToMediaMigrate constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The Entity Field Manager Interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Entity TypeManager Interface.
   * @param Drupal\Core\Database\Connection $database
   *   The $database.
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Media_field :bundle,type,target_media_bundle,entity_type.
   *
   * @param string $bundle
   *   Argument bundle (content type).
   * @param string $type
   *   Argument image type.
   * @param string $target_media_bundle
   *   Argument media type.
   * @param string $entity_type
   *   Argument entity.
   *
   * @command create-media-field:createMediaField.
   * @aliases create-media-field
   */
  public function createMediaField(
  $bundle,
  $type,
  $target_media_bundle,
  $entity_type
  ) {
    $all_bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // Gather a list of all target fields.
    $map = $this->entityFieldManager->getFieldMapByFieldType($type);

    $source_fields = [];

    if ($type !== $target_media_bundle) {
      $this->output()
        ->writeln("Media field doesn't created, Please check your field_type and target_media_bundle.");
      return;
    }

    if (empty($map)) {
      $this->output()
        ->writeln("Media field doesn't created, Please check your field_type and target_media_bundle.");
      return;
    }

    foreach ($map[$entity_type] as $name => $data) {
      foreach ($data['bundles'] as $bundle_name) {
        if ($bundle_name == $bundle) {
          $target_field = substr($name, 0, FieldStorageConfig::NAME_MAX_LENGTH - 6) . '_media';
          $source_fields[$target_field] = $all_bundle_fields[$name];
        }
      }
    }

    // Create missing fields.
    $missing_fields = array_diff_key($source_fields);
    // \Drupal::logger("result_set")->notice('<pre>'.print_r($missing_fields,true).'<pre>');
    foreach ($missing_fields as $new_field_name => $field) {
      try {
        $new_field = $this->createMedia(
          $entity_type,
          $bundle,
          $field,
          $new_field_name,
          $type,
          $target_media_bundle
        );
      }

      catch (\Exception $ex) {
        $this->output()
          ->writeln("Created media field: {$new_field_name}.");
      }      
    }

    $map = $this->entityFieldManager->getFieldMapByFieldType($type);
    $array = [];

    foreach ($map[$entity_type] as $name => $data) {
      foreach ($data['bundles'] as $bundle_name) {
        $array[] = $bundle_name;
      }
    }
    if (empty(in_array($bundle, $array))) {
      $this->output()
        ->writeln("please check the bundle type.");
    }
  }

  /**
   * Create a new entity media reference field.
   *
   * @param string $entity_type
   *   Argument entity.
   * @param string $bundle
   *   Argument bundle.
   * @param \Drupal\field\Entity\FieldConfig $existing_field
   *   Argument existing field.
   * @param string $new_field_name
   *   Argument new field.
   * @param string $type
   *   Argument type of field.
   * @param string $target_media_bundle
   *   Argument target media bundle.
   */
  private function createMedia(
    $entity_type,
    $bundle,
    FieldConfig $existing_field,
    $new_field_name,
    $type,
    $target_media_bundle
  ) {
    $field = FieldConfig::loadByName($entity_type, $bundle, $new_field_name);
    if (empty($field)) {

      // Load existing field storage.
      $field_storage = FieldStorageConfig::loadByName($entity_type, $new_field_name);

      // Create a field storage if none found.
      if ($type == $target_media_bundle) {
        if (empty($field_storage)) {
          $field_storage = FieldStorageConfig::create(
          [
            'field_name' => $new_field_name,
            'entity_type' => $entity_type,
            'cardinality' => $existing_field->getFieldStorageDefinition()
              ->getCardinality(),
            'type' => 'entity_reference',
            'settings' => ['target_type' => 'media'],
          ]
          );
          $field_storage->save();
        }

        $field = $this->entityTypeManager->getStorage('field_config')->create([
          'field_storage' => $field_storage,
          'bundle' => $bundle,
          'label' => $existing_field->getLabel() . ' Media',
          'settings' => [
            'handler' => 'default:media',
            'handler_settings' => ['target_bundles' => [$target_media_bundle => $target_media_bundle]],
          ],
        ]);
        $field->save();

        // Update Form Widget.
        $type = $entity_type . '.' . $bundle . '.default';
        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $definition */
        $definition = $this->entityTypeManager->getStorage('entity_form_display')
          ->load($type);
        $definition->setComponent(
        $new_field_name,
        [
          'type' => 'media_library',
        ]
        )->save();
      }
      return $field;
    }

  }

  /**
   * Migrating :field_name :type :entity_type.
   *
   * @param string $field_name
   *   Argument field to be migrated.
   * @param string $type
   *   Argument type.
   * @param string $entity_type
   *   Argument entity.
   *
   * @command files-to-media:filesToMedia
   * @aliases files-to-media
   */
  public function filesToMedia(
  $field_name,
  $type,
  $entity_type
  ) {

    // Target database table for fetch entity_its of specific field (tablename)
    $tablename = 'node__' . $field_name;
    if ($entity_type == 'block_content') {
      $tablename = 'block_content__' . $field_name;
    }
    if ($entity_type == 'paragraph') {
      $tablename = 'paragraph__' . $field_name;
    }
    if ($entity_type == 'user') {
      $tablename = 'user__' . $field_name;
    }

    // Fetch entity id's.
    $query = $this->database->select($tablename, 'fd');
    $query->fields('fd', ['entity_id']);
    $entity_ids = $query->execute()->fetchAll();

    // Breakdown your process into small batches(operations).
    // migrate 200 nodes per batch.
    $operations = [];
    foreach (array_chunk($entity_ids, 200) as $entity_id) {
      $operations[] = ['files_to_media_migrate_migrating_files_to_media',
      [$entity_id, $field_name, $type, $entity_type],
      ];
    }

    // Setup and define batch informations.
    $batch = [
      'title' => $this->t('Migrating Image to Media with batch processing...'),
      'operations' => $operations,
    ];

    batch_set($batch);
    drush_backend_batch_process();
  }

}

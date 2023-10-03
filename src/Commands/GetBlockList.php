<?php

namespace Drupal\list_inline_block\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Drush Command to get the list of Inline Block.
 */
class GetBlockList extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new \Drupal\list_inline_block\Commands\GetBlockList object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * A drush command to get the specified block usage.
   *
   * @param string $blockType
   *   Type of Inline Block.
   *
   * @command inline-block:list
   * @aliases inline-block-list
   *
   * @usage inline-block:list machine-name
   *   Get list of nodes where Inline Blocks of specified machine-name used.
   */
  public function listBlock($blockType) {
    try {
      $database = $this->database;
      $query = $database->select('inline_block_usage', 'iu')
        ->fields('iu', [
          'block_content_id',
          'layout_entity_type',
          'layout_entity_id',
        ]);
      $result = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      $this->output()->writeln($e);
    }
    $count = 0;
    $bundles = [];
    foreach ($result as $record) {
      $block = $this->entityTypeManager->getStorage('block_content')->load($record->block_content_id);
      $bundle = $block->bundle();
      array_push($bundles, $bundle);
      if ($blockType == $bundle) {
        $link = '/' . $record->layout_entity_type . '/' . $record->layout_entity_id;
        $this->output()->writeln($link);
        $count = $count + 1;
      }
    }
    if ($count == 0) {
      $this->output()->writeln('<error>Please provide the correct machine-name which is in use. Please refer the available used blocks:</error>');
      $this->output()->writeln('<error></error>');
      $unique_bundles = array_unique($bundles);
      // $sorted_array = sort($unique_bundles);
      foreach ($unique_bundles as $bundle) {
        $this->output()->writeln('<error>' . $bundle . '</error>');
      }
    }
  }

}

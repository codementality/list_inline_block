<?php

namespace Drupal\list_inline_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Utility\TableSort;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to get the table of Inline Block used in Layout Builder.
 */
class ListBlock extends ControllerBase {

  /**
   * The Pager Manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

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
   * Constructs a new \Drupal\list_inline_block\Controller\ListBlok object.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager to introduce pager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    PagerManagerInterface $pagerManager,
    Connection $database,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->pagerManager = $pagerManager;
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pager.manager'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Functions returns a table of Inline Block.
   */
  public function getBlock(Request $request) {
    $database = $this->database;
    $query = $database->select('inline_block_usage', 'iu')
      ->fields('iu', [
        'block_content_id',
        'layout_entity_type',
        'layout_entity_id',
      ]);
    $result = $query->execute()->fetchAll();

    $header = [
      [
        'data' => $this->t('Block Type'),
        'field' => 'blockType',
        'sort' => 'asc',
      ],
      'Title' => $this->t('Title'),
      'url' => $this->t('Link'),
    ];
    $output = [];

    // Use the PagerManager to handle pagination.
    $pager = $this->pagerManager->createPager(count($result), 25);
    $pager->getCurrentPage();
    $chunks = array_chunk($result, 25);
    $current_page_items = $chunks[$pager->getCurrentPage()];
    $k = 0;
    foreach ($current_page_items as $record) {
      $block = $this->entityTypeManager->getStorage('block_content')->load($record->block_content_id);
      $blockType = $block->type->entity->label();
      $node = $this->entityTypeManager->getStorage('node')->load($record->layout_entity_id);
      $link = $this->t('<a href=/@type/@id/layout>Edit</a> (@title)', [
        '@type' => $record->layout_entity_type,
        '@id' => $record->layout_entity_id,
        '@title' => $node->label(),
      ]);
      $title = $this->t('<a href=/@type/@id>@title</a>', [
        '@type' => $record->layout_entity_type,
        '@id' => $record->layout_entity_id,
        '@title' => $block->info->value,
      ]);
      $output[$k] = [
        'blockType' => $blockType,
        'Title' => $title,
        'url' => $link,
      ];
      $k = $k + 1;
    }
    $output = $this->sortList($output, $header, $request);

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $output,
      '#empty' => $this->t('No blocks found'),
    ];

    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * Sort List based on ASC or DESC order of Block Type.
   *
   * @param array $rows
   *   The array of data with inline blocks.
   * @param array $header
   *   The array of data with table definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int|null $flag
   *   The flag to sort.
   *
   * @return array
   *   Returns sorted array.
   */
  public function sortList(array $rows, array $header, Request $request, $flag = SORT_STRING | SORT_FLAG_CASE) {
    $sort = TableSort::getSort($header, $request);
    foreach ($rows as $row) {
      $temp_array[] = $row['blockType'];
    }
    if ($sort == 'asc') {
      asort($temp_array, $flag);
    }
    else {
      arsort($temp_array, $flag);
    }
    foreach ($temp_array as $index => $data) {
      $new_rows[] = $rows[$index];
    }
    return $new_rows;
  }

}

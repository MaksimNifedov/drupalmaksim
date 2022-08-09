<?php

declare(strict_types=1);

namespace Drupal\media_library_block\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Provides block plugin definitions for all media bundles.
 *
 * @see \Drupal\media_library_block\Plugin\Block\MediaLibraryBlock
 */
class MediaLibraryBlockDeriver implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Entity Type Bundle Info.
   *
   * @var Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a MediaBlockDeriver.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(string $base_plugin_id, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition): array {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }

    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('media');

    foreach ($bundles as $id => $info) {
      $this->derivatives[$id] = [
        'admin_label' => $info['label'],
      ];
      $this->derivatives[$id] += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}

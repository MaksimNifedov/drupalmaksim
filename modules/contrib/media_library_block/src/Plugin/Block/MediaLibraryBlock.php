<?php

declare(strict_types=1);

namespace Drupal\media_library_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a media browser block.
 *
 * @Block(
 *   id = "media_library_block",
 *   admin_label = @Translation("Media Library Block"),
 *   category = @Translation("Media"),
 *   deriver = "Drupal\media_library_block\Plugin\Derivative\MediaLibraryBlockDeriver"
 * )
 */
class MediaLibraryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Media bundle allowed for selecting.
   *
   * @var string
   */
  protected $allowedBundle;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * MediaBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   Entity display repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->allowedBundle = $this->getDerivativeId();
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media' => '',
      'view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['media'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => [$this->allowedBundle],
      '#title' => $this->t('Media'),
      '#default_value' => $this->configuration['media'] ?? NULL,
      '#required' => TRUE,
      '#cardinality' => 1,
      '#description' => $this->t('Add or select media.'),
    ];

    $options = $this->entityDisplayRepository->getViewModeOptionsByBundle('media', $this->allowedBundle);

    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $options,
      '#default_value' => $this->configuration['view_mode'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    // The media_library form returns the selected media as e.g. '3,6,12'.
    // The form element allows selecting multiple media, even when setting
    // cardinality to 1. So only take the first.
    $value = $form_state->getValue('media') ?? '';
    $media = explode(',', $value)[0] ?? '';
    $this->configuration['media'] = $media;

    // View mode.
    $view_mode = $form_state->getValue('view_mode') ?? 'default';
    $this->configuration['view_mode'] = $view_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $media = $this->loadMedia();

    if (!$media) {
      return [];
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $view_mode = $this->getViewmode();
    return $view_builder->view($media, $view_mode);
  }

  /**
   * Load and return the referenced media.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The loaded media, if available.
   */
  public function loadMedia(): ?MediaInterface {
    $target_id = $this->configuration['media'] ?? '';
    return $this->entityTypeManager->getStorage('media')->load($target_id);
  }

  /**
   * Return the selected viewmode for the media.
   *
   * @return string
   *   The viewmode.
   */
  public function getViewmode(): string {
    return $this->configuration['view_mode'] ?? 'full';
  }

}

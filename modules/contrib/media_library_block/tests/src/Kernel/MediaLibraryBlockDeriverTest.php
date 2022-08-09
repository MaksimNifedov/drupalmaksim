<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library_block\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests deriving of MediaLibraryBlock.
 *
 * @group media_library_block
 */
class MediaLibraryBlockDeriverTest extends EntityKernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * Block Manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
    'media',
    'image',
    'media_library_block',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
    $this->createMediaType('image', ['id' => 'document', 'label' => 'Document']);
    $this->createMediaType('image', ['id' => 'teaser', 'label' => 'Teaser']);
    $this->blockManager = $this->container->get('plugin.manager.block');
  }

  /**
   * Tests the build method.
   */
  public function testDerivative(): void {
    $definitions = $this->blockManager->getDefinitions();

    $this->assertArrayHasKey('media_library_block:document', $definitions);
    $this->assertArrayHasKey('media_library_block:image', $definitions);
    $this->assertArrayHasKey('media_library_block:teaser', $definitions);
    $this->assertEqual($definitions['media_library_block:document']['admin_label'], 'Document');
  }

}

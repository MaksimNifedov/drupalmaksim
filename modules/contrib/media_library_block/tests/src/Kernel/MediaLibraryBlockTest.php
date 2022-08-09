<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library_block\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests rendering of MediaLibraryBlock.
 *
 * @coversDefaultClass \Drupal\media_library_block\Plugin\Block\MediaLibraryBlock
 * @group media_library_block
 */
class MediaLibraryBlockTest extends EntityKernelTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

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
    'media',
    'image',
    'file',
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

    $this->blockManager = $this->container->get('plugin.manager.block');
  }

  /**
   * Tests the build method.
   */
  public function testBuild(): void {
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
    File::create(['uri' => $this->getTestFiles('image')[0]->uri])->save();
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Example image',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $media->save();

    /** @var \Drupal\media_library_block\Plugin\Block\MediaLibraryBlock $plugin_block */
    $plugin_block = $this->blockManager->createInstance('media_library_block:image', [
      'media' => $media->id(),
    ]);
    $build = $plugin_block->build();
    $this->assertInstanceOf(Media::class, $build['#media']);

    $plugin_block = $this->blockManager->createInstance('media_library_block:image');
    $build = $plugin_block->build();
    $this->assertEmpty($build);
  }

}

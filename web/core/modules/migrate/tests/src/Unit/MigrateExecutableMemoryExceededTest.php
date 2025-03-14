<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Prophecy\Argument;

/**
 * Tests the \Drupal\migrate\MigrateExecutable::memoryExceeded() method.
 *
 * @group migrate
 */
class MigrateExecutableMemoryExceededTest extends MigrateTestCase {

  /**
   * The mocked migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migration;

  /**
   * The mocked migrate message.
   *
   * @var \Drupal\migrate\MigrateMessageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $message;

  /**
   * The tested migrate executable.
   *
   * @var \Drupal\Tests\migrate\Unit\TestMigrateExecutable
   */
  protected $executable;

  /**
   * The migration configuration, initialized to set the ID to test.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * The php.ini memory_limit value.
   *
   * @var int
   */
  protected $memoryLimit = 10000000;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migration = $this->getMigration();
    $this->message = $this->prophesize('Drupal\migrate\MigrateMessageInterface');

    $this->executable = new TestMigrateExecutable($this->migration, $this->message->reveal());
    $this->executable->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Runs the actual test.
   *
   * @param string $message
   *   The second message to assert.
   * @param bool $memory_exceeded
   *   Whether to test the memory exceeded case.
   * @param int|null $memory_usage_first
   *   (optional) The first memory usage value. Defaults to NULL.
   * @param int|null $memory_usage_second
   *   (optional) The fake amount of memory usage reported after memory reclaim.
   *   Defaults to NULL.
   * @param int|null $memory_limit
   *   (optional) The memory limit. Defaults to NULL.
   */
  protected function runMemoryExceededTest($message, $memory_exceeded, $memory_usage_first = NULL, $memory_usage_second = NULL, $memory_limit = NULL): void {
    $this->executable->setMemoryLimit($memory_limit ?: $this->memoryLimit);
    $this->executable->setMemoryUsage($memory_usage_first ?: $this->memoryLimit, $memory_usage_second ?: $this->memoryLimit);
    $this->executable->setMemoryThreshold(0.85);
    if ($message) {
      $this->message->display(Argument::that(fn(string $subject) => str_contains($subject, 'reclaiming memory')), 'warning')
        ->shouldBeCalledOnce();
      $this->message->display(Argument::that(fn(string $subject) => str_contains($subject, $message)), 'warning')
        ->shouldBeCalledOnce();
    }
    else {
      $this->message->display(Argument::cetera())
        ->shouldNotBeCalled();
    }
    $result = $this->executable->memoryExceeded();
    $this->assertEquals($memory_exceeded, $result);
  }

  /**
   * Tests memoryExceeded method when a new batch is needed.
   */
  public function testMemoryExceededNewBatch(): void {
    // First case try reset and then start new batch.
    $this->runMemoryExceededTest('starting new batch', TRUE);
  }

  /**
   * Tests memoryExceeded method when enough is cleared.
   */
  public function testMemoryExceededClearedEnough(): void {
    $this->runMemoryExceededTest('reclaimed enough', FALSE, $this->memoryLimit, $this->memoryLimit * 0.75);
  }

  /**
   * Tests memoryExceeded when memory usage is not exceeded.
   */
  public function testMemoryNotExceeded(): void {
    $this->runMemoryExceededTest('', FALSE, floor($this->memoryLimit * 0.85) - 1);
  }

}

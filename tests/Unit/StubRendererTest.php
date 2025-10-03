<?php

declare(strict_types=1);

namespace Zenigata\Helpers\Test\Unit;

use function file_get_contents;

use RuntimeException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zenigata\Helpers\StubRenderer;

/**
 * Unit test for {@see StubRenderer} utility.
 *
 * Verifies the behavior of the stub generation utility.
 * 
 * Covered cases:
 *
 * - Replace placeholders within a stub file.
 * - Create intermediate directories when generating files.
 * - Handle error cases such as missing stubs, unreadable directories,
 *   unreadable stub files, and write failures.
 *
 * Uses {@see vfsStream} to simulate file system operations in-memory.
 */
#[CoversClass(StubRenderer::class)]
final class StubRendererTest extends TestCase
{
    /**
     * Virtual file system root directory.
     *
     * @var vfsStreamDirectory
     */
    private vfsStreamDirectory $root;

    /**
     * This method is called automatically before every test and is used
     * to initialize the objects and state required for the test execution.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
    }

    public function testGenerateStubReplacingPlaceholders(): void
    {
        $stub = vfsStream::newFile('stub.txt')
            ->at($this->root)
            ->setContent('Hello, {{name}}!');

        $destination = vfsStream::url('root/output/generated.txt');

        StubRenderer::render(
            stub:         $stub->url(),
            destination:  $destination,
            placeholders: ['{{name}}' => 'Zenigata']
        );

        $this->assertTrue($this->root->hasChild('output/generated.txt'));
        $this->assertSame('Hello, Zenigata!', file_get_contents($destination));
    }

    public function testCreateIntermediateDirectories(): void
    {
        vfsStream::newFile('stub.txt')
            ->at($this->root)
            ->setContent('stub content');

        $destination = vfsStream::url('root/a/b/c/output.txt');

        StubRenderer::render(
            stub:        vfsStream::url('root/stub.txt'),
            destination: $destination
        );

        $this->assertTrue($this->root->hasChild('a/b/c/output.txt'));
        $this->assertSame('stub content', file_get_contents($destination));
    }

    public function testThrowIfDirectoryIsNotReadable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to create directory');

        $stub = vfsStream::newFile('stub.txt')->at($this->root)->setContent('Hello');
        $unreadable = vfsStream::newDirectory('unreadable', 0000)->at($this->root);

        StubRenderer::render(
            stub:        $stub->url(),
            destination: $unreadable->url() . '/file.txt'
        );
    }

    public function testThrowIfStubDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("does not exist or is not a readable");

        StubRenderer::render(
            stub:         vfsStream::url('root/missing.txt'),
            destination:  vfsStream::url('root/output.txt'),
            placeholders: []
        );
    }

    public function testThrowIfStubCannotBeRead(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("does not exist or is not a readable");

        $stub = vfsStream::newFile('stub.txt', 0000)->at($this->root);

        StubRenderer::render(
            stub:        $stub->url(),
            destination: vfsStream::url('root/output.txt')
        );
    }

    public function testThrowIfWriteFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write to file');

        vfsStream::newFile('stub.txt')->at($this->root)->setContent('text');
        $unwritable = vfsStream::newDirectory('unwritable', 0444)->at($this->root);

        StubRenderer::render(
            stub:        vfsStream::url('root/stub.txt'),
            destination: $unwritable->url() . '/output.txt'
        );
    }
}
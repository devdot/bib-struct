<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct;
use ThomasSchaller\BibStruct\Helpers\Repository;

final class RepositoryTest extends TestCase {
    public function testCreatePath() {
        $repo = new Repository('phpunit-test', false);
        $this->assertEquals(realpath(__DIR__.'/../../data').'/phpunit-test.php', $repo->getPath());
        $repo = new Repository('phpunit.test', false);
        $this->assertEquals(realpath(__DIR__.'/../../data').'/phpunit/test.php', $repo->getPath());
    }

    public function testWriteGet() {
        $repo = new Repository('test', false);
        $repo->write([
            'test' => 2,
            'yolo' => [
                'asdf' => '123',
                '2' => true,
            ],
            'arr' => ['a', 'b'],
        ]);

        $this->assertEquals(2, $repo->get('test'));
        $this->assertEquals(null, $repo->get('none'));
        $this->assertEquals(true, $repo->get('none', true));
        $this->assertEquals('123', $repo->get('yolo.asdf'));
        $this->assertEquals(true, $repo->get('yolo.2'));
        $this->assertEquals(true, $repo->get('yolo.2', false));
        $this->assertEquals(['a', 'b'], $repo->get('arr', false));
    }

    public function testCreateFail() {
        $this->expectException(\Exception::class);

        new Repository('unit.test');
    }
}

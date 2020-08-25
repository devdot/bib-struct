<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Reference;
use ThomasSchaller\BibStruct\ReferenceList;
use ThomasSchaller\BibStruct\Factory;

final class ReferenceListTest extends TestCase {

    public function testCountable() {
        // check if this is implementing countable
        $list = new ReferenceList();

        $this->assertEquals(0, count($list));
    }

    public function testInterface() {
        $list = new ReferenceList();

        $this->assertIsArray($list->get());
        $this->assertNull($list->pop());
        $this->assertNull($list->shift());

        $list->push(Factory::reference('Gen', 1, 28));
        $this->assertEquals(1, $list->count());
        $this->assertSame($list->get(0), $list->get()[0]);
        $this->assertEquals([$list->get(0)], $list->get());
        $list->push(Factory::reference('Mt', 12, 3));
        $list->push(Factory::reference('Mk', 3));

        $this->assertEquals(1, $list->get(0)->getBookId());
        $this->assertEquals('Mt', $list->get(1)->book());
        $this->assertEquals('Mk', $list->get(2)->book());
        $this->assertEquals('1:28', $list->get(0)->chapterVerse());
        $this->assertEquals('3', $list->get(2)->chapterVerse());

        // shift and pop
        $ret = $list->pop();
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Mk', $ret->book());
        $this->assertEquals('Gen', $list->get(0)->book());
        $this->assertEquals('Mt', $list->get(1)->book());

        $ret2 = $list->shift();
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Mt', $list->get(0)->book());
        $this->assertEquals(1, $ret2->getBookId());

        // start adding again
        $list->push($ret2);
        $list->push($ret2);
        $this->assertEquals(3, $list->count());
        $this->assertSame($ret2, $list->get(1));
        $this->assertSame($ret2, $list->get()[2]);
        $this->assertNotSame($ret2, $list->get(0));

        // now shift another one from bottom
        $list->unshift($ret);
        $this->assertEquals(4, $list->count());
        $this->assertEquals('3', $list->get(0)->chapterVerse());
        $this->assertEquals('12:3', $list->get(1)->chapterVerse());
        $this->assertEquals('1:28', $list->get(2)->chapterVerse());
        $this->assertEquals('1:28', $list->get(3)->chapterVerse());

        // test set
        $list->set($ret, 3);
        $this->assertEquals('3', $list->get(0)->chapterVerse());
        $this->assertEquals('12:3', $list->get(1)->chapterVerse());
        $this->assertEquals('1:28', $list->get(2)->chapterVerse());
        $this->assertEquals('3', $list->get(3)->chapterVerse());
        $this->assertEquals(4, count($list));

        $list->set($ret2);
        $this->assertEquals(1, $list->count());
        $this->assertSame($ret2, $list->get()[0]);

        $list->set([Factory::reference('1 Cor', 13, 5), Factory::range('Mk', 1, 3, 2, 4)]);
        $this->assertEquals(2, $list->count());
        $this->assertEquals('13:5', $list->get(0)->chapterVerse());
        $this->assertEquals('1:2-3:4', $list->get(1)->chapterVerse());
    }
}

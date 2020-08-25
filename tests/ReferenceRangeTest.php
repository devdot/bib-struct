<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Reference;
use ThomasSchaller\BibStruct\ReferenceRange;
use ThomasSchaller\BibStruct\Factory;

final class ReferenceRangeTest extends TestCase {
    public function testConstructor() {
        $refA = Factory::reference(1, 1, 20);
        $refB = Factory::reference(1, 1, 21);
        $range = $refA->toRange($refB);

        $this->assertInstanceOf(ReferenceRange::class, $range);
        $this->assertInstanceOf(Reference::class, $range);
        $this->assertSame($refA, $range->getFrom());
        $this->assertSame($refB, $range->getTo());
        $this->assertSame($refA->trans(), $range->trans());
        $this->assertSame($refA->book(), $range->book());
        $this->assertSame($refB->book(), $range->book());
        $this->assertSame($refA->bookLong(), $range->bookLong());

        // to and from will be switched around if they are in the wrong order
        $refA = Factory::reference(50, 3, 1);
        $refB = Factory::reference(50, 1, 21);
        $range = new ReferenceRange($refA, $refB);

        $this->assertSame($refA, $range->getTo());
        $this->assertSame($refB, $range->getFrom());
    }

    public function testConstructorFail() {
        $refA = Factory::reference(1, 1, 20);
        $refB = Factory::reference(2, 1, 21);

        $this->expectException(ThomasSchaller\BibStruct\Exceptions\MismatchBooksException::class);
        $refA->toRange($refB);
    }

    public function testCompare() {
        $ref = Factory::reference(30, 20, 20);
        
        $this->assertFalse($ref->toRange($ref)->compare($ref));

        // test range against reference
        $range = Factory::range(20, 2, 5, 3, 10);
        $this->assertTrue($range->compare(Factory::reference(20, 2, 2)));
        $this->assertFalse($range->compare(Factory::reference(20, 2, 3)));
        $this->assertFalse($range->compare(Factory::reference(20, 2, 4)));
        $this->assertTrue($range->compare(Factory::reference(19, 3, 4)));
        $this->assertFalse($range->compare(Factory::reference(21)));
        $range = Factory::range(50, 4, 8);
        $this->assertTrue($range->compare(Factory::reference(50, 3, 9)));
        $this->assertFalse($range->compare(Factory::reference(50, 4, 1)));
        $this->assertFalse($range->compare(Factory::reference(50, 4)));
        $this->assertTrue($range->compare(Factory::reference(50)));

        // test reference against range
        $ref = Factory::reference(31, 4, 6);
        $this->assertTrue($ref->compare(Factory::range(31, 1, 5)));
        $this->assertTrue($ref->compare(Factory::range(31, 1, 5, 4, 6)));
        $this->assertTrue($ref->compare(Factory::range(31, 4, 5)));
        $this->assertTrue($ref->compare(Factory::range(31, 4, 5, 5, 5)));
        $this->assertFalse($ref->compare(Factory::range(31, 4, 5, 6, 51)));
        $ref = Factory::reference(31, 2);
        $this->assertTrue($ref->compare(Factory::range(30, 4, 5, 6, 7)));
        $this->assertTrue($ref->compare(Factory::range(31, 1, 2)));
        $this->assertFalse($ref->compare(Factory::range(31, 2, 3)));
        $this->assertFalse($ref->compare(Factory::range(31, 2, 3, 1, 2)));
        $this->assertFalse($ref->compare(Factory::range(31, 2, 2, 1, 2)));

        // test range against range
        $range = Factory::range(3, 3, 4, 10, 15);
        $this->assertTrue($range->compare(Factory::range(2, 10, 10, 1, 2)));
        $this->assertFalse($range->compare(Factory::range(3, 10, 10, 1, 2)));
        $this->assertFalse($range->compare($range));
        $this->assertTrue($range->compare(Factory::range(3, 2, 4, 10, 15)));
        $this->assertTrue($range->compare(Factory::range(3, 3, 4, 9, 15)));
        $this->assertTrue($range->compare(Factory::range(3, 3, 4, 10, 14)));
        $this->assertFalse($range->compare(Factory::range(3, 3, 4, 10, 15)));
        $this->assertFalse($range->compare(Factory::range(3, 3, 4, 10, 16)));
        $this->assertFalse($range->compare(Factory::range(3, 3, 4, 10)));
        $range = Factory::range(4, 2, 4);
        $this->assertTrue($range->compare(Factory::range(4, 1, 3, 1, 3)));
        $this->assertTrue($range->compare(Factory::range(4, 1, 5, 1, 3)));
        $this->assertFalse($range->compare(Factory::range(4, 2, 5, 1, 3)));
        $this->assertTrue($range->compare(Factory::range(4, 2, 3, null, 3)));
        $this->assertFalse($range->compare(Factory::range(4, 2, 5, null, 1)));
        $this->assertFalse($range->compare($range));
    }

    public function testToStr() {
        $this->assertEquals('9:1-3', Factory::range(1, 9, 9, 1, 3)->chapterVerse());
        $this->assertEquals('9:1-10:3', Factory::range(1, 9, 10, 1, 3)->chapterVerse());
        $this->assertEquals('9-10', Factory::range(1, 9, 10)->chapterVerse());
        $this->assertEquals('9-10:1', Factory::range(1, 9, 10, null, 1)->chapterVerse());
        $this->assertEquals('9:12-10', Factory::range(1, 9, 10, 12)->chapterVerse()); // this one is correct but semantically useless
        $this->assertEquals('12:3a-4b', Factory::range(1, 12, 12, 3, 4, 'a', 'b')->chapterVerse());
        $this->assertEquals('12:3a-b', Factory::range(1, 12, 12, 3, 3, 'a', 'b')->chapterVerse());
        $this->assertEquals('12:3a-14:3b', Factory::range(1, 12, 14, 3, 3, 'a', 'b')->chapterVerse());
        $this->assertEquals('a-b', Factory::range(1, null, null, null, null, 'a', 'b')->chapterVerse());
        $this->assertEquals('', Factory::range(1, null, null, null, null)->chapterVerse());
        $this->assertEquals('-b', Factory::range(1, null, null, null, null, '', 'b')->chapterVerse()); // not nice but expected

        Factory::lang('en');
        $this->assertEquals('Ex 8-9', Factory::range(2, 8, 9)->toStr());
        $this->assertEquals('Exodus 8-9 EN-GEN', Factory::range(2, 8, 9)->toStr(true, true));
        $this->assertEquals('Ex 8-9 EN-GEN', Factory::range(2, 8, 9)->toStr(true, false));
        $this->assertEquals('Exodus 8-9', Factory::range(2, 8, 9)->toStr(false, true));
        $this->assertEquals('Ex 8-9', Factory::range(2, 8, 9)->toStr(false, false));

        $this->assertEquals('Gen 8:2b-9:3c', Factory::range(1, 8, 9, 2, 3, 'b', 'c')->toStr());
        $this->assertEquals('Gen 8:2-9:3c', Factory::range(1, 8, 9, 2, 3, '', 'c')->toStr());
        $this->assertEquals('Gen 10:2a-3', Factory::range(1, 10, 10, 2, 3, 'a')->toStr());
    }

    public function testCoalesce() {
        $range = Factory::range(22, 4, 6, 5, 15);

        // single
        $this->assertNotNull($range->coalesce(Factory::reference(22, 4, 10)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 4, 4)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 4, 5)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 6, 15)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 6, 16)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 4)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 5)));
        $this->assertNotNull($range->coalesce(Factory::reference(22, 6)));
        $this->assertEquals('4:5-6:15', $range->coalesce(Factory::reference(22, 4, 10))->chapterVerse());
        $this->assertEquals('4:4-6:15', $range->coalesce(Factory::reference(22, 4, 4))->chapterVerse());
        $this->assertEquals('4:5-6:15', $range->coalesce(Factory::reference(22, 4, 5))->chapterVerse());
        $this->assertEquals('4:5-6:15', $range->coalesce(Factory::reference(22, 6, 15))->chapterVerse());
        $this->assertEquals('4:5-6:16', $range->coalesce(Factory::reference(22, 6, 16))->chapterVerse());
        $this->assertEquals('4-6:15', $range->coalesce(Factory::reference(22, 4))->chapterVerse()); // semantical weird but correct
        $this->assertEquals('4:5-6:15', $range->coalesce(Factory::reference(22, 5))->chapterVerse());
        $this->assertEquals('4:5-6', $range->coalesce(Factory::reference(22, 6))->chapterVerse()); // semantically weird but correct

        $this->assertNull($range->coalesce(Factory::reference(21, 5, 2)));
        $this->assertNull($range->coalesce(Factory::reference(23, 5, 2)));
        $this->assertNull($range->coalesce(Factory::reference(22, 3, 1)));
        $this->assertNull($range->coalesce(Factory::reference(22, 4, 3)));
        $this->assertNull($range->coalesce(Factory::reference(22, 6, 17)));
        $this->assertNull($range->coalesce(Factory::reference(22)));

        // single full chapter
        $range = Factory::range(11, 5, 7);
        $this->assertNotNull($range->coalesce(Factory::reference(11, 5, 1)));
        $this->assertNotNull($range->coalesce(Factory::reference(11, 7, 10)));
        $this->assertNotNull($range->coalesce(Factory::reference(11, 4)));
        $this->assertNotNull($range->coalesce(Factory::reference(11, 5)));
        $this->assertNotNull($range->coalesce(Factory::reference(11, 7)));
        $this->assertNotNull($range->coalesce(Factory::reference(11, 8)));
        $this->assertEquals('5-7', $range->coalesce(Factory::reference(11, 5, 1))->chapterVerse());
        $this->assertEquals('5-7', $range->coalesce(Factory::reference(11, 7, 10))->chapterVerse());
        $this->assertEquals('4-7', $range->coalesce(Factory::reference(11, 4))->chapterVerse());
        $this->assertEquals('5-7', $range->coalesce(Factory::reference(11, 5))->chapterVerse());
        $this->assertEquals('5-7', $range->coalesce(Factory::reference(11, 7))->chapterVerse());
        $this->assertEquals('5-8', $range->coalesce(Factory::reference(11, 8))->chapterVerse());

        $this->assertNull($range->coalesce(Factory::reference(11, 8, 1)));
        $this->assertNull($range->coalesce(Factory::reference(11, 3)));
        $this->assertNull($range->coalesce(Factory::reference(11, 9)));

        // check class for range with from and to equal
        $this->assertInstanceOf(ReferenceRange::class, Factory::range(11, 22, 22)->coalesce(Factory::range(11, 22, 22)));

        // range on range
        $range = Factory::range(1, 3, 4, 5, 6);
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 4, 5, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 3, 5, 9)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 4, 4, 1, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 4, 9, 1)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 4, 5, 7)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 4, 4, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 2, 4, 5, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 5, 5, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 2, 4, 1, 9)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 1, 3, 1, 4)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 1, 3, 1, 5)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 4, 5, 6, 1)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 4, 5, 7, 1)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 3, 4)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 2, 3)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 4, 5)));
        $this->assertNotNull($range->coalesce(Factory::range(1, 1, 6)));
        $this->assertEquals('3:5-4:6', $range->coalesce(Factory::range(1, 3, 4, 5, 6))->chapterVerse());
        $this->assertEquals('3:5-4:6', $range->coalesce(Factory::range(1, 3, 3, 5, 9))->chapterVerse());
        $this->assertEquals('3:5-4:6', $range->coalesce(Factory::range(1, 4, 4, 1, 6))->chapterVerse());
        $this->assertEquals('3:5-4:6', $range->coalesce(Factory::range(1, 3, 4, 9, 1))->chapterVerse());
        $this->assertEquals('3:5-4:7', $range->coalesce(Factory::range(1, 3, 4, 5, 7))->chapterVerse());
        $this->assertEquals('3:4-4:6', $range->coalesce(Factory::range(1, 3, 4, 4, 6))->chapterVerse());
        $this->assertEquals('2:5-4:6', $range->coalesce(Factory::range(1, 2, 4, 5, 6))->chapterVerse());
        $this->assertEquals('3:5-5:6', $range->coalesce(Factory::range(1, 3, 5, 5, 6))->chapterVerse());
        $this->assertEquals('2:1-4:9', $range->coalesce(Factory::range(1, 2, 4, 1, 9))->chapterVerse());
        $this->assertEquals('1:1-4:6', $range->coalesce(Factory::range(1, 1, 3, 1, 4))->chapterVerse());
        $this->assertEquals('1:1-4:6', $range->coalesce(Factory::range(1, 1, 3, 1, 5))->chapterVerse());
        $this->assertEquals('3:5-5:1', $range->coalesce(Factory::range(1, 4, 5, 6, 1))->chapterVerse());
        $this->assertEquals('3:5-5:1', $range->coalesce(Factory::range(1, 4, 5, 7, 1))->chapterVerse());
        $this->assertEquals('3-4', $range->coalesce(Factory::range(1, 3, 4))->chapterVerse());
        $this->assertEquals('2-4:6', $range->coalesce(Factory::range(1, 2, 3))->chapterVerse()); // semantically weird but correct
        $this->assertEquals('3:5-5', $range->coalesce(Factory::range(1, 4, 5))->chapterVerse()); // semantically weird but correct
        $this->assertEquals('1-6', $range->coalesce(Factory::range(1, 1, 6))->chapterVerse());

        $this->assertNull($range->coalesce(Factory::range(1, 1, 3, 1, 3)));
        $this->assertNull($range->coalesce(Factory::range(1, 3, 3, 1, 3)));
        $this->assertNull($range->coalesce(Factory::range(1, 4, 5, 8, 1)));
        $this->assertNull($range->coalesce(Factory::range(1, 5, 5, 1, 2)));
        $this->assertNull($range->coalesce(Factory::range(2, 3, 4, 5, 6)));
        $this->assertNull($range->coalesce(Factory::range(2, 3, 4, 5, 6)));

        $range = Factory::range(2, 4, 5);
        $this->assertNotNull($range->coalesce(Factory::range(2, 4, 5)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 4, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 3, 3)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 3, 5)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 3, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 6, 6)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 1, 3)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 6, 7)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 4, 5, 1, 9)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 3, 5, 9, 9)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 4, 6, 9, 9)));
        $this->assertNotNull($range->coalesce(Factory::range(2, 3, 6, 9, 9)));
        $this->assertEquals('4-5', $range->coalesce(Factory::range(2, 4, 5))->chapterVerse());
        $this->assertEquals('4-6', $range->coalesce(Factory::range(2, 4, 6))->chapterVerse());
        $this->assertEquals('3-5', $range->coalesce(Factory::range(2, 3, 3))->chapterVerse());
        $this->assertEquals('3-5', $range->coalesce(Factory::range(2, 3, 5))->chapterVerse());
        $this->assertEquals('3-6', $range->coalesce(Factory::range(2, 3, 6))->chapterVerse());
        $this->assertEquals('4-6', $range->coalesce(Factory::range(2, 6, 6))->chapterVerse());
        $this->assertEquals('1-5', $range->coalesce(Factory::range(2, 1, 3))->chapterVerse());
        $this->assertEquals('4-7', $range->coalesce(Factory::range(2, 6, 7))->chapterVerse());
        $this->assertEquals('4-5', $range->coalesce(Factory::range(2, 4, 5, 1, 9))->chapterVerse());
        $this->assertEquals('3:9-5', $range->coalesce(Factory::range(2, 3, 5, 9, 9))->chapterVerse()); // semantically strange but correct
        $this->assertEquals('4-6:9', $range->coalesce(Factory::range(2, 4, 6, 9, 9))->chapterVerse()); // semantically strange but correct
        $this->assertEquals('3:9-6:9', $range->coalesce(Factory::range(2, 3, 6, 9, 9))->chapterVerse());

        $this->assertNull($range->coalesce(Factory::range(1, 4, 5)));
        $this->assertNull($range->coalesce(Factory::range(1, 5, 5, 1, 3)));
        $this->assertNull($range->coalesce(Factory::range(2, 1, 2)));
        $this->assertNull($range->coalesce(Factory::range(2, 7, 8)));
        $this->assertNull($range->coalesce(Factory::range(2)));

        // last one
        $range = Factory::range(12);
        $this->assertNull($range->coalesce($range));
        $this->assertNull($range->coalesce(Factory::range(12, 1, 2)));
        $this->assertNull($range->coalesce(Factory::range(12, 1, 2, 3, 4)));
        $this->assertNull($range->coalesce(Factory::reference(12)));
        $this->assertNull($range->coalesce(Factory::reference(13)));
        $this->assertNull($range->coalesce(Factory::reference(12, 1)));
        $this->assertNull($range->coalesce(Factory::reference(12, 1, 2)));
    }
}

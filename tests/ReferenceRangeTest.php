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
}

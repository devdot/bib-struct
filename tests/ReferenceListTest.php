<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Reference;
use ThomasSchaller\BibStruct\ReferenceList;
use ThomasSchaller\BibStruct\Factory;
use ThomasSchaller\BibStruct\ReferenceGroup;

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

    public function testCopy() {
        $refA = Factory::reference('Mt', 1, 1);
        $refB = Factory::reference('Gen', 3);
        $list = new ReferenceList([$refA, $refB]);

        $this->assertSame($refA, $list->get(0));
        $this->assertSame($refB, $list->get(1));

        $copy = $list->copy();
        $this->assertNotSame($list, $copy);
        $this->assertSame($list->get(0), $copy->get(0));
        $this->assertSame($list->get(1), $copy->get(1));
        $this->assertEquals(2, $list->count());
        $this->assertEquals(2, $copy->count());
        
        $list->pop();
        $this->assertEquals(1, $list->count());
        $this->assertEquals(2, $copy->count());
    }

    public function testToGroups() {
        $list = new ReferenceList([
            Factory::reference('Ex', 12), 
            Factory::reference('Ex', 32, 2),
            Factory::range('Ex', 45, 45, 1, 3),
        ]);
        $this->assertEquals(3, $list->count());
        $this->assertEquals('Ex 12; Ex 32:2; Ex 45:1-3', $list->toStr());
        
        $groups = $list->generateGroups();
        $nList = new ReferenceList($groups);
        $this->assertEquals(1, $nList->count());
        $this->assertInstanceOf(ReferenceGroup::class, $nList->get(0));
        $this->assertEquals('Ex 12; 32:2; 45:1-3', $nList->toStr());

        // add multiple groups
        $list->push(Reference::parseStr('Gen 1:28'));
        $list->push(Factory::reference('Gen', 33));
        $list->push(new ReferenceGroup(new ReferenceList([Factory::reference('Ex', 1), Factory::reference('Ex', 2)])));
        $list->push(Factory::reference('Ex', 4));
        $this->assertEquals(7, count($list));

        // create copy and to group it
        $new = $list->copy()->toGroups();
        $this->assertNotSame($list, $new);
        $this->assertEquals(3, $new->count());
        $this->assertEquals('Ex 12; 32:2; 45:1-3; Gen 1:28; 33; Ex 1; 2; 4', $new->toStr()); // order is kept

        // order now (prohibit breaking)
        $new->sort(true, false);
        $this->assertEquals(3, $new->count());
        $this->assertEquals('Gen 1:28; 33; Ex 1; 2; 4; Ex 12; 32:2; 45:1-3', $new->toStr());
        // to groups again
        $new->toGroups();
        $this->assertEquals('Gen 1:28; 33; Ex 1; 2; 4; 12; 32:2; 45:1-3', $new->toStr()); 

        // go back to list and sort it deep
        $next = $list->copy();
        $next->sort();
        $next->toGroups();
        $this->assertEquals(2, $next->count());
        $this->assertEquals('Gen 1:28; 33; Ex 1; 2; 4; 12; 32:2; 45:1-3', $next->toStr());
    }

    public function testBreakInnerGroups() {
        $list = new ReferenceList([
            Factory::reference('Ex', 12), 
            Factory::reference('Gen', 32, 2),
            Factory::range('Ex', 45, 45, 1, 3),
        ]);
        $this->assertEquals(3, $list->count());
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3', $list->toStr());
        $this->assertIsArray($list->getReferencesBroken());
        $this->assertEquals(3, count($list->getReferencesBroken()));

        $list->breakInnerGroups();
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3', $list->toStr()); // nothing changed yet

        $list->push(Factory::reference('Ex', 4, 1));
        $this->assertCount(4, $list);
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3; Ex 4:1', $list->toStr());
        $list->toGroups();
        $this->assertCount(3, $list);
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3; 4:1', $list->toStr());
        $list->breakInnerGroups();
        $this->assertCount(4, $list);
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3; Ex 4:1', $list->toStr());

        // now do it deeper
        $list->push(new ReferenceGroup(new ReferenceList([Factory::reference('Num', 1), Factory::reference('Num', 2)])));
        $this->assertCount(5, $list);
        $this->assertCount(6, $list->getReferencesBroken());
        $this->assertCount(5, $list); // but list is unchanged
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3; Ex 4:1; Num 1; 2', $list->toStr());

        // add a recursive group
        $list->push(new ReferenceGroup(new ReferenceList([
            Factory::reference('Num', 5),
            new ReferenceGroup(new ReferenceList([Factory::reference('Num', 10), Factory::range('Num', 12, 13)])),
        ])));
        $this->assertCount(6, $list); // just pushed once
        $this->assertEquals('Ex 12; Gen 32:2; Ex 45:1-3; Ex 4:1; Num 1; 2; Num 5; 10; 12-13', $list->toStr());
        
        // check that recursive break
        $this->assertCount(9, $list->copy()->breakInnerGroups());

        // compare normal toGroups with breaking toGroups
        $breaking = $list->copy()->toGroups(true);
        $list->toGroups();
        $this->assertInstanceOf(ReferenceGroup::class, $list->get(3));
        $this->assertCount(2, $list->get(3)->getList()); // Num books should be 5, but only finds 2 here, rest is groups
        $this->assertInstanceOf(ReferenceGroup::class, $breaking->get(3));
        $this->assertCount(5, $breaking->get(3)->getList()); // Num books now found as 5
    
        // finally do the breaking sort and then regroup
        $list->sort();
        $this->assertEquals('Gen 32:2; Ex 4:1; Ex 12; Ex 45:1-3; Num 1; Num 2; Num 5; Num 10; Num 12-13', $list->toStr());
        $list->toGroups();
        $this->assertEquals('Gen 32:2; Ex 4:1; 12; 45:1-3; Num 1; 2; 5; 10; 12-13', $list->toStr());

        // generate recursive list
        $last = new ReferenceGroup(new ReferenceList([Factory::reference(1, 1, 3)]));
        $group = $last;
        for($i = 2; $i < 10; $i++) {
            $g = new ReferenceGroup(new ReferenceList([Factory::reference(1, $i, 3)]));
            $last->getList()->push($g);
            $this->assertCount(2, $last->getList());
            $last = $g;
        }
        $this->assertEquals('1:3; 2:3; 3:3; 4:3; 5:3; 6:3; 7:3; 8:3; 9:3', $group->chapterVerse());
        $this->assertCount(2, $group->getList()); // because they are all stacked recursively
        $save_list = $group->getList()->copy();
        
        $group->getList()->breakInnerGroups();
        $this->assertEquals('1:3; 2:3; 3:3; 4:3; 5:3; 6:3; 7:3; 8:3; 9:3', $group->chapterVerse());
        $this->assertCount(9, $group->getList());
    }

    public function testImplode() {
        $list = new ReferenceList();
        $list->push(Factory::reference('Joh', 15));
        $list->push(Factory::range('Joh', 14, 16));
        $list->push(Factory::reference('Mk', 3, 3));
        $list->push(Factory::reference('Mk', 3, 4));
        $list->push(Factory::reference('Mk', 3, 5));
        $list->push(Factory::range('Mk', 3, 3, 5, 7));
        $list->push(Factory::range('Mk', 3, 3, 8, 10));
        $list->push(Factory::reference('Gen', 1, 28));
        $list->push(Factory::reference('Mk', 3, 5));
        $list->push(Factory::reference('Joh', 3, 16));

        $this->assertEquals('Joh 15; Joh 14-16; Mk 3:3; Mk 3:4; Mk 3:5; Mk 3:5-7; Mk 3:8-10; Gen 1:28; Mk 3:5; Joh 3:16', $list->toStr());
        $list->implode();
        $this->assertEquals('Joh 14-16; Mk 3:3-10; Gen 1:28; Mk 3:5; Joh 3:16', $list->toStr());
        $list->toGroups();
        $this->assertCount(5, $list);
        $list->push(Factory::reference('Joh', 15));
        $this->assertCount(6, $list);
        $this->assertEquals('Joh 14-16; Mk 3:3-10; Gen 1:28; Mk 3:5; Joh 3:16; Joh 15', $list->toStr());
        $list->implode();
        $this->assertCount(5, $list);
        $this->assertEquals('Joh 14-16; Mk 3:3-10; Gen 1:28; Mk 3:5; Joh 3:16; 15', $list->toStr());
        // finally cleanup just to check
        $list->cleanup();
        $this->assertCount(3, $list);
        $this->assertEquals('Gen 1:28; Mk 3:3-10; Joh 3:16; 14-16', $list->toStr());

    }

    public function testSort() {
        $list = new ReferenceList();
        $list->push(Factory::reference('Joh', 15));
        $list->push(Factory::range('Joh', 14, 16));
        $list->push(Factory::reference('Mk', 3, 3));
        $list->push(Factory::reference('Mk', 4, 3));
        $list->push(Factory::reference('Gen', 1, 28));
        $list->push(Factory::reference('Joh', 3, 16));
        $list->push(Factory::range('Gen', 1, 11));
        $list->push(Factory::range('Joh', 15, 15, 1, 6));
        $list->push(Factory::reference('Joh', 15, 1));

        $this->assertEquals('Joh 15; Joh 14-16; Mk 3:3; Mk 4:3; Gen 1:28; Joh 3:16; Gen 1-11; Joh 15:1-6; Joh 15:1', $list->toStr());
        $list->sort();
        $this->assertEquals('Gen 1-11; Gen 1:28; Mk 3:3; Mk 4:3; Joh 3:16; Joh 14-16; Joh 15; Joh 15:1-6; Joh 15:1', $list->toStr());
        $list->sort(false);
        $this->assertEquals('Joh 15:1-6; Joh 15:1; Joh 15; Joh 14-16; Joh 3:16; Mk 4:3; Mk 3:3; Gen 1:28; Gen 1-11', $list->toStr());
        
        $list->cleanup(false);
        $this->assertEquals('Gen 1-11; Mk 3:3; Mk 4:3; Joh 3:16; Joh 14-16', $list->toStr());
        $list->toGroups();
        $this->assertEquals('Gen 1-11; Mk 3:3; 4:3; Joh 3:16; 14-16', $list->toStr());
    }
}

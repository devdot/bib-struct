<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Reference;
use ThomasSchaller\BibStruct\ReferenceGroup;
use ThomasSchaller\BibStruct\ReferenceList;
use ThomasSchaller\BibStruct\Factory;

final class ReferenceGroupTest extends TestCase {

    public function testConstructor() {
        Factory::lang('en');
        $list = new ReferenceList([
            Factory::reference('Ex', 12), 
            Factory::reference('Ex', 32, 2),
            Factory::range('Ex', 45, 45, 1, 3),
        ]);

        $group = new ReferenceGroup($list);

        $this->assertSame($list, $group->getList());
        $this->assertEquals('Ex 12; 32:2; 45:1-3', $group->toStr());
        $this->assertEquals('Exodus 12; 32:2; 45:1-3', $group->toStr(false, true));
        $this->assertEquals('Exodus 12; 32:2; 45:1-3 EN-GEN', $group->toStr(true, true));

        // list is referenced by object, can still modify the list
        $list->pop();
        $this->assertEquals('Ex 12; 32:2', $group->toStr());
        
        // should not sort
        $list->push(Factory::reference('Ex', 1, 1));
        $this->assertEquals('Ex 12; 32:2; 1:1', $group->toStr());
    }

    public function testConstructorFail() {
        $list = new ReferenceList([
            Factory::range(1, 12, 12, 3, 10),
        ]);

        try {
            $catch = false;
            // empty list
            $group = new ReferenceGroup(new ReferenceList());
        }
        catch(\ThomasSchaller\BibStruct\Exceptions\EmptyListException $e) {
            $catch = true;
        }
        $this->assertFalse(isset($group));
        $this->assertTrue($catch);

        $group = new ReferenceGroup($list);
        try {
            $catch = false;
            // empty list after construct
            $group->setList(new ReferenceList());
        }
        catch(\ThomasSchaller\BibStruct\Exceptions\EmptyListException $e) {
            $catch = true;
        }
        $this->assertSame($list, $group->getList());
        $this->assertTrue($catch);

        try {
            $catch = false;
            // inconsistent books
            $list->push(Factory::reference('Num', 1, 1));
            $group->setList($list);
        }
        catch(\ThomasSchaller\BibStruct\Exceptions\MismatchBooksException $e) {
            $catch = true;
        }
        $this->assertTrue($catch);
    }

    public function testCoalesce() {
        // prepare a simple testing list
        $list = new ReferenceList();
        $list->push(Factory::reference(12, 1, 28));
        $list->push(Factory::reference(12, 1, 29));
        $list->push(Factory::range(12, 1, 1, 9, 11));
        $list->push(Factory::reference(12, 1, 11));
        $list->push(Factory::reference(12, 3));
        $list->push(Factory::reference(12, 10, 12));
        $list->push(Factory::range(12, 3, 5));
        $list->push(Factory::range(12, 12, 13, 3, 10));
        $group = new ReferenceGroup($list);
        $this->assertCount(8, $group->getList());
        $this->assertEquals('1:28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:3-13:10', $group->chapterVerse());
        
        // something that cannot be merged
        $this->assertNull($group->coalesce(Factory::reference(11, 1, 1)));
        $this->assertNull($group->coalesce(Factory::reference(12)));
        $this->assertNull($group->coalesce(Factory::range(12)));
        
        // add references
        $this->assertNotNull($group->coalesce(Factory::reference(12, 25)));
        $this->assertCount(9, $group->getList()); // this object will keep it's list
        $this->assertEquals('1:28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:3-13:10; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::reference(12, 1, 28)));
        $this->assertEquals('1:28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:3-13:10; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::reference(12, 1, 27)));
        $this->assertEquals('1:27-28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:3-13:10; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::reference(12, 3, 12)));
        $this->assertEquals('1:27-28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:3-13:10; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::reference(12, 12, 2)));
        $this->assertEquals('1:27-28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:2-13:10; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::reference(12, 13, 11)));
        $this->assertEquals('1:27-28; 1:29; 1:9-11; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::reference(12, 1, 12)));
        $this->assertEquals('1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25', $group->chapterVerse());
        
        // this should sort itself of self coalesce
        $save_list = $list->copy();
        $this->assertNotNull($group->coalesce($group));
        $this->assertEquals('1:9-12; 1:27-29; 3-5; 10:12; 12:2-13:11; 25', $group->chapterVerse());

        // get back old list and continue there with ranges
        $group->setList($save_list);
        $this->assertEquals('1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::range(12, 1, 1, 29, 31)));
        $this->assertEquals('1:27-31; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::range(12, 3, 4)));
        $this->assertEquals('1:27-31; 1:29; 1:9-12; 1:11; 3-4; 10:12; 3-5; 12:2-13:11; 25', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::range(12, 26, 26)));
        $this->assertEquals('1:27-31; 1:29; 1:9-12; 1:11; 3-4; 10:12; 3-5; 12:2-13:11; 25-26', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::range(12, 6, 6)));
        $this->assertEquals('1:27-31; 1:29; 1:9-12; 1:11; 3-4; 10:12; 3-6; 12:2-13:11; 25-26', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(Factory::range(12, 24, 27, 22, 1)));
        $this->assertEquals('1:27-31; 1:29; 1:9-12; 1:11; 3-4; 10:12; 3-6; 12:2-13:11; 24:22-27:1', $group->chapterVerse());
        
        // and now just implode the list
        $save_list = $group->getList()->copy();
        $group->getList()->implode();
        $this->assertEquals('1:27-31; 1:9-12; 3-4; 10:12; 3-6; 12:2-13:11; 24:22-27:1', $group->chapterVerse());
        $this->assertCount(7, $group->getList());
        $group->cleanup();
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1', $group->chapterVerse());
        $this->assertCount(6, $group->getList());
        
        // check with cleanup straigh away
        $group->setList($save_list);
        $this->assertEquals('1:27-31; 1:29; 1:9-12; 1:11; 3-4; 10:12; 3-6; 12:2-13:11; 24:22-27:1', $group->chapterVerse());
        $group->cleanup();
        $this->assertCount(6, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1', $group->chapterVerse());
        
        // add group to group
        // recursive
        $save_list = $group->getList()->copy();
        $group2 = new ReferenceGroup($list);
        $this->assertEquals('1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25', $group2->chapterVerse());
        $this->assertNotNull($group2->coalesce(Factory::reference(12, 50)));
        $this->assertEquals('1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25; 50', $group2->chapterVerse());
        // set just to see it added on
        $group->getList()->push($group2);
        $this->assertCount(7, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1; 1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25; 50', $group->chapterVerse());
        // coalesce normally (will merge with normal Reference in list)
        $this->assertNotNull($group->coalesce(Factory::reference(12, 7)));
        $this->assertCount(7, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-7; 10:12; 12:2-13:11; 24:22-27:1; 1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25; 50', $group->chapterVerse());
        // coalesce with recursed group
        $this->assertNotNull($group->coalesce(Factory::reference(12, 51)));
        $this->assertCount(7, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-7; 10:12; 12:2-13:11; 24:22-27:1; 1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25; 50-51', $group->chapterVerse());
        // cleanup just to check
        $group->cleanup();
        $this->assertCount(7, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-7; 10:12; 12:2-13:11; 24:22-27:1; 50-51', $group->chapterVerse());
        
        // restore main list merge
        $group->setList($save_list);
        $this->assertCount(6, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1', $group->chapterVerse());
        $this->assertCount(10, $group2->getList());
        $this->assertEquals('1:27-28; 1:29; 1:9-12; 1:11; 3; 10:12; 3-5; 12:2-13:11; 25; 50-51', $group2->chapterVerse());
        $this->assertNotNull($group->coalesce($group2));
        $this->assertCount(7, $group->getList());
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1; 50-51', $group->chapterVerse());
        
        // last a failing group merge
        $this->assertNull($group->coalesce(Factory::reference(12)));
        $this->assertNull($group->coalesce(Factory::reference(11, 1, 1)));
        $this->assertNull($group->coalesce(Factory::range(12)));
        $this->assertNull($group->coalesce(Factory::range(13, 1, 1)));
        $this->assertNull($group->coalesce(new ReferenceGroup(new ReferenceList([Factory::reference(11, 1, 1)]))));
        
        // merge with wrong entries, but they are not affecting the group
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1; 50-51', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(new ReferenceGroup(new ReferenceList([Factory::reference(12)]))));
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1; 50-51', $group->chapterVerse());
        $this->assertNotNull($group->coalesce(new ReferenceGroup(new ReferenceList([Factory::range(12)]))));
        $this->assertEquals('1:9-12; 1:27-31; 3-6; 10:12; 12:2-13:11; 24:22-27:1; 50-51', $group->chapterVerse());
    }

    public function testParse() {
        // prepare a simple testing list
        $list = new ReferenceList();
        $list->push(Factory::reference(12, 1, 28));
        $list->push(Factory::reference(12, 1, 29, 'b'));
        $list->push(Factory::range(12, 1, 1, 9, 11));
        $list->push(Factory::reference(12, 1, 11));
        $list->push(Factory::reference(12, 3));
        $list->push(Factory::reference(12, 10, 12));
        $list->push(Factory::range(12, 3, 5));
        $list->push(Factory::range(12, 12, 13, 3, 10, 'b', 'a'));
        $group = new ReferenceGroup($list);
        $this->assertCount(8, $group->getList());
        $this->assertEquals('1:28; 1:29b; 1:9-11; 1:11; 3; 10:12; 3-5; 12:3b-13:10a', $group->chapterVerse());

        $this->assertInstanceOf(ReferenceGroup::class, ReferenceGroup::parseStr($group->toStr()));
        $this->assertEquals($group->toStr(), ReferenceGroup::parseStr($group->toStr())->toStr());
        
        $this->assertEquals('Mk 12; 17:1; 23:1-2b', ReferenceGroup::parseStr('Mk 12; 17:1; 23:1-2b')->toStr());
        $this->assertEquals('Mk 12; 17:1; 5', ReferenceGroup::parseStr('Mk 12; 17:1; 5')->toStr());
        $this->assertEquals('Mk 12; 17:1; 5; 1b', ReferenceGroup::parseStr('Mk 12; 17:1; 5; 1b')->toStr());
        $this->assertEquals('Mk 12-13', ReferenceGroup::parseStr('Mk 12-13')->toStr());
        $this->assertInstanceOf(ReferenceGroup::class, ReferenceGroup::parseStr('Mk 12-13'));
        $this->assertEquals('Mk 12-13; 22', ReferenceGroup::parseStr('Mk 12-13; 22; Lk 1')->toStr());
        $this->assertInstanceOf(ReferenceGroup::class, ReferenceGroup::parseStr('Mk 12-13; 22; Lk 1'));
    }

}

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

}

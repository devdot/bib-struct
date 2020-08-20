<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Reference;
use ThomasSchaller\BibStruct\Factory;

final class ReferenceTest extends TestCase {
    public function testConstruct() {
        $ref = new Reference(Factory::translation('DE-GEN'), 1, 1, 28, 'a');
        $this->assertEquals('Gen 1:28a', $ref->toStr());
        $this->assertEquals('Gen 1:28a DE-GEN', $ref->toStr(true));
        $this->assertEquals('Genesis 1:28a', $ref->toStr(false, true));
        $this->assertEquals('Genesis 1:28a DE-GEN', $ref->toStr(true, true));
    }

    public function testGetters() {
        $this->assertEquals('3:9', Factory::reference(1, 3, 9)->chapterVerse());
        $this->assertEquals('Gen', Factory::reference(1)->book());
        $this->assertEquals('Exodus', Factory::reference('Ex')->bookLong());
        $this->assertEquals('3:9', Factory::reference(1, 3, 9, 'a')->chapterVerse(true));
        $this->assertEquals('3:9a', Factory::reference(1, 3, 9, 'a')->chapterVerse());
        $this->assertEquals('3', Factory::reference(1, 3)->chapterVerse());
        $this->assertEquals('Gen 3', Factory::reference(1, 3)->toStr());
        $this->assertEquals('EN-GEN', Factory::getInstance()->createReference(Factory::translation('EN-GEN'), 1, 3)->trans());
        $this->assertEquals('DE-GEN', Factory::getInstance()->createReference(Factory::translation('DE-GEN'), 1, 3)->trans());
    }

}

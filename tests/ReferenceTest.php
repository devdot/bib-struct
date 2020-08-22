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

    public function testParse() {
        $this->assertEquals('Gen 3:1', Reference::parseStr('Gen 3:1')->toStr());
        $this->assertEquals('Gen 4:1', Reference::parseStr(' Gen 4:1')->toStr());
        $this->assertEquals('Gen 5:1', Reference::parseStr('Gen  5:1')->toStr());
        $this->assertEquals('Gen 6:1', Reference::parseStr('Gen  6 : 1')->toStr());
        $this->assertEquals('Mk', Reference::parseStr('Mk')->toStr());
        $this->assertEquals('Mk', Reference::parseStr('Mk ')->toStr());
        $this->assertEquals('Lk 8', Reference::parseStr('Lk 8:')->toStr());
        $this->assertEquals('Lk 9', Reference::parseStr('Lk 9')->toStr());
        $this->assertEquals('Lk 2a', Reference::parseStr('Lk 2a')->toStr());
        $this->assertEquals('Lk 3:1b', Reference::parseStr('Lk 3:1b')->toStr());
        $this->assertEquals('Lk 22:13c', Reference::parseStr('Lk 22:13c')->toStr());
        $this->assertEquals('Lk 23:13c', Reference::parseStr('Lk 23 : 13 c')->toStr());
        
        // use inherent
        $this->assertEquals('Joh 3:16', Reference::parseStr('3:16', Factory::reference('Joh'))->toStr());
        $this->assertEquals('Joshua 3:16', Reference::parseStr('3:16', Factory::reference('Jos'))->toStr(false, true));
        $this->assertEquals('Gen 3:15a', Reference::parseStr('3:15 a', Factory::reference('Gen'))->toStr());

        // errors that get ignored
        $this->assertEquals('1 Cor 8', Reference::parseStr('1 Cor 8:')->toStr());
        $this->assertEquals('1 Cor', Reference::parseStr('1 Cor a')->toStr());

    }

    public function testParseException() {
        // $this->expectException(\ThomasSchaller\BibStruct\Exceptions\ParseException::class);

        // first to the 'could not identify book' error
        $strs = [
            '8:1',
            '',
            '1T 8:1',
            (string) rand(),
        ];
        foreach($strs as $str) {
            try {
                Reference::parseStr($str);
                $this->assertEquals('should have failed on', $str);
            }
            catch(\ThomasSchaller\BibStruct\Exceptions\ParseException $e) {
                $this->assertEquals('Could not identify book for '.$str, $e->getMessage());
            }
        }

        // now the 'could not match' error
        $strs = [
            '_asdf',
            'KK' => 'KK 8:1',
            'NOBOOK' => 'NOBOOK 1:13a',
            'test' => 'test 321',
            '@dgadf',
            '_df',
            'T132:2',
            'Mk12:1',
            'Luke1',
        ];
        foreach($strs as $book => $str) {
            if(is_numeric($book))
                $book = $str;
            try {
                Reference::parseStr($str);
                $this->assertEquals('should have failed on', $str);
            }
            catch(\ThomasSchaller\BibStruct\Exceptions\ParseException $e) {
                $this->assertEquals('Could not match book '.$book.' in '.$str, $e->getMessage());
            }
        }
    }

    public function testCompare() {
        $ref = Factory::reference(40, 4, 30);
        $this->assertTrue($ref->compare(Factory::reference(39)));
        $this->assertTrue($ref->compare(Factory::reference(40)));
        $this->assertFalse($ref->compare(Factory::reference(41)));

        // for a Reference, no verse is beaten by a set verse
        $this->assertTrue($ref->compare(Factory::reference(40, 4)));
        $this->assertFalse($ref->compare($ref));
        $this->assertTrue($ref->compare(Factory::reference(40, 3, 100)));
        $this->assertTrue($ref->compare(Factory::reference(40, 4, 1)));
        $this->assertTrue($ref->compare(Factory::reference(40, 4, 29)));
        $this->assertFalse($ref->compare(Factory::reference(40, 4, 30)));
        $this->assertFalse($ref->compare(Factory::reference(40, 4, 31)));
        $this->assertFalse($ref->compare(Factory::reference(40, 5, 1)));
        $this->assertFalse($ref->compare(Factory::reference(40, 5, 100)));
    }
}

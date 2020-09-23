<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Parser;
use ThomasSchaller\BibStruct\Factory;
use ThomasSchaller\BibStruct\Reference;
use ThomasSchaller\BibStruct\ReferenceRange;
use ThomasSchaller\BibStruct\ReferenceGroup;
use ThomasSchaller\BibStruct\ReferenceList;

final class ParseTest extends TestCase {

    public function testModes() {
        $str = 'Gen 3:19-27; 12:3; Ex 33';

        $this->assertInstanceOf(Reference::class, Parser::parse($str, null, null, Parser::$MODE_SINGLE));
        $this->assertEquals('Gen 3:19', Parser::parse($str, null, null, Parser::$MODE_SINGLE)->toStr());
        $this->assertInstanceOf(ReferenceRange::class, Parser::parse($str, null, null, Parser::$MODE_RANGE));
        $this->assertEquals('Gen 3:19-27', Parser::parse($str, null, null, Parser::$MODE_RANGE)->toStr());
        $this->assertInstanceOf(ReferenceGroup::class, Parser::parse($str, null, null, Parser::$MODE_GROUP));
        $this->assertEquals('Gen 3:19-27; 12:3', Parser::parse($str, null, null, Parser::$MODE_GROUP)->toStr());
        $this->assertInstanceOf(ReferenceList::class, Parser::parse($str, null, null, Parser::$MODE_ALL));
        $this->assertEquals('Gen 3:19-27; Gen 12:3; Ex 33', Parser::parse($str, null, null, Parser::$MODE_ALL)->toStr());
        $this->assertEquals('Gen 3:19-27; 12:3; Ex 33', Parser::parse($str, null, null, Parser::$MODE_ALL)->toGroups()->toStr());
    }

    public function testInherit() {
        $this->assertEquals('Gen 1:28', Parser::parse('1:28', Factory::reference(1, 3, 3))->toStr());
        
        try {
            $this->assertEquals('Gen 1:28', Parser::parse('1:28')->toStr());
            $this->assertEquals('should have failed', 'here');
        }
        catch(\Exception $e) {
            $this->assertInstanceOf(\ThomasSchaller\BibStruct\Exceptions\ParseException::class, $e);
        }
        
        $this->assertEquals('Gen 1', Parser::parse('1', Factory::reference(1, 3, 3))->toStr());
        $this->assertEquals('Gen', Parser::parse('', Factory::reference(1, 3, 3))->toStr());
        // check if it properly inherits language too
        $this->assertEquals('Gen EN-GEN', Parser::parse('', Factory::reference(1, 3, 3))->toStr(true));
        $this->assertEquals('Gen DE-GEN', Parser::parse('', Factory::getInstance()->createReference(Factory::translation('DE-GEN'), 1, 3, 3))->toStr(true));
    }

    public function testObject() {
        // test using the parser as actual object
        $parser = new Parser();

        $parser->setString('Lam 9:11');
        $parser->setMode(Parser::$MODE_SINGLE);
        $ref = $parser->run();

        $this->assertInstanceOf(Reference::class, $ref);
        $this->assertEquals('Lam 9:11', $ref->toStr());

        $parser->setString('Gen  1');
        $ref = $parser->run();
        $this->assertEquals('Gen 1', $ref->toStr());
        $this->assertEquals('Ex 2:3', $parser->run(' Ex   2: 3 DE-GEN')->toStr());
        $this->assertEquals('Ex 2:3 DE-GEN', $parser->run(' Ex   2: 3  DE-GEN')->toStr(true));
    }

    public function testTranslation() {
        // test the translation rendering
        $parser = new Parser();
        $parser->setMode(Parser::$MODE_RANGE);
        $this->assertEquals('Gen 1-11 EN-GEN', $parser->run('Gen  1- 11 EN-GEN')->toStr(true));
        $this->assertEquals('Gen 1:11 DE-GEN', $parser->run('Gen  1: 11 DE-GEN')->toStr(true));
        $this->assertEquals('Gen 1-11 DE-GEN', $parser->run('Gen  1- 11 DE-GEN')->toStr(true));

        $parser->setMode(Parser::$MODE_ALL);
        $this->assertEquals('Gen 1-11 DE-GEN; Ex 33 EN-GEN', $parser->run('Gen  1- 11 DE-GEN; Ex 33')->toStr(true));
    }

    public function testAnnoyingCases() {
        $this->assertEquals('Daniel 15 EN-GEN', Parser::parse('Daniel 15 EN-GEN')->toStr(true, true));
        $this->assertEquals('Daniel 15 DE-GEN', Parser::parse('Daniel   15  DE-GEN')->toStr(true, true));
        $this->assertEquals('Daniel 15DE-GEN EN-GEN', Parser::parse('Daniel   15DE-GEN')->toStr(true, true)); // yes this is how it's supposed to work
        $this->assertEquals('Daniel 15', Parser::parse('Daniel 15 EN-GEN')->toStr(false, true));

        $this->assertEquals('Song of Songs 5:4-9:19', Parser::parse('Song 5:4-9:19')->toStr(false, true));
        $this->assertEquals('Song of Songs 5:4-9:19', Parser::parse('Song of Songs 5:4-9:19')->toStr(false, true));
    }
}

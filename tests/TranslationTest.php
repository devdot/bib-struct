<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Translation;

final class TranslationTest extends TestCase {
    public function testConstruct() {
        $trans = new Translation('ABC', 'Anfang', 'de', [], [], []);
        $this->assertEquals('ABC', $trans->getShort());
        $this->assertEquals('Anfang', $trans->getName());
        $this->assertEquals('de', $trans->getLanguage());

    }
}

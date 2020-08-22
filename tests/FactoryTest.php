<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use ThomasSchaller\BibStruct\Factory;
use ThomasSchaller\BibStruct\Helpers\Repository;

final class FactoryTest extends TestCase {
    public static int $BOOK_COUNT = 66;

    public function testConstruct() {
        // test book inverse lists
        $this->assertEquals('Ex', Factory::bookShort(2));
        $id = Factory::bookId('Col');
        $this->assertEquals('Col', Factory::bookShort($id));
    }

    public function testRepositoryBooks() {
        $repo = new Repository('books');

        // make sure no id string is used twice
        $this->assertEquals(self::$BOOK_COUNT, count($repo->get('ids')), 'Number of books not correct!');

        // check that the ID numbers are unique
        $ids = $repo->get('ids');
        $inverse = [];
        foreach($ids as $str => $id) {
            $this->assertFalse(isset($inverse[$id]), 'ID '.$id.' used for '.$str.' is not unique!');
            $inverse[$id] = $str;
        }
    }

    public function testSingleton() {
        $this->assertSame(Factory::getInstance(), Factory::getInstance(), 'Get instance creates new objects!');

        $this->assertEquals(Factory::getInstance()->getLanguage(), Factory::lang());
        $this->assertEquals('en', Factory::lang('en'));
    }

    public function testCreateReference() {
        Factory::lang('en');
        $ref = Factory::reference('Rom', 8);
        $this->assertEquals('Rom 8', $ref->toStr());
        $this->assertEquals('Rom 8 EN-GEN', $ref->toStr(true));
        $this->assertEquals('Romans 8', $ref->toStr(false, true));
        $this->assertSame(Factory::translation(), $ref->getTranslation(), 'reference received a new translation but should not');
        $ref->setTranslation(Factory::translation('DE-GEN'));
        $this->assertNotSame(Factory::translation(), $ref->getTranslation(), 'translations should now be different');
        $this->assertEquals('Römer 8', $ref->toStr(false, true));

        $this->assertEquals('Joshua', Factory::reference(6)->toStr(false, true));
        $this->assertEquals('1 Cor 5:12', Factory::reference('1C', 5, 12)->toStr());
    }

    public function testCreateTranslation() {
        $trans = Factory::translation();
        $this->assertInstanceOf(\ThomasSchaller\BibStruct\Translation::class, $trans);
        $this->assertEquals(Factory::lang(), $trans->getLanguage(), 'default translation does not have default langauge');

        // check the cache respects force unique
        $trans1 = Factory::translation();
        $trans2 = Factory::translation(null, true);
        $trans3 = Factory::translation();
        $this->assertNotSame($trans2, $trans3, 'Factory did not respect translation unique force');
        $this->assertSame($trans1, $trans3, 'Factory did not cache translation correctly');
    }

    public function testCreateReferenceRange() {
        $range = Factory::range(2, 5, 6);
        $this->assertEquals(2, $range->getBookId());
        $this->assertEquals(2, $range->getFrom()->getBookId());
        $this->assertEquals(2, $range->getTo()->getBookId());
        $this->assertEquals(Factory::translation()->getShort(), $range->trans());
        $this->assertEquals(Factory::translation()->getShort(), $range->getTo()->trans());
        $this->assertEquals(Factory::translation()->getShort(), $range->getFrom()->trans());
        $this->assertEquals('5', $range->getFrom()->chapterVerse());
        $this->assertEquals('6', $range->getTo()->chapterVerse());

        // try inversed range
        $range = Factory::range(20, 5, 1, 2, 15);
        $this->assertEquals(20, $range->getBookId());
        $this->assertEquals('1:15', $range->getFrom()->chapterVerse());
        $this->assertEquals('5:2', $range->getTo()->chapterVerse());
    }

    public function testTranslationLists() {
        Factory::lang('de');
        $trans = Factory::translation();

        $this->assertEquals('Dtn', $trans->bookShort(5));
        $this->assertNull($trans->bookShort('asdf'));
        $this->assertNull($trans->bookShort('Apg')); // german translation should still not have effect here
        $this->assertNotNull($trans->bookShort('Ac'));
        $this->assertEquals('Richter', $trans->bookLong('Judg'));
        $this->assertEquals($trans->bookLong('Judg'), $trans->bookLong($trans->matchToId('Ri')));

        $this->assertEquals('Numeri', $trans->matchLong('4 Mos'));
        $this->assertEquals('Num', $trans->matchShort('4 Mos'));
        $this->assertEquals('Ezechiel', $trans->matchLong('Ez'));

        Factory::lang('en');
    }

    public function testRepositoryTranslations() {
        $books = new Repository('books');
        $translations = new Repository('translations.translations');

        $this->assertCount(self::$BOOK_COUNT, $books->get('ids'), 'Number of books in ID list incorrect');

        $this->assertIsArray($translations->get('shorts'), 'translations is missing short array');
        foreach($translations->get('shorts') as $short => $name) {
            $this->assertTrue(Repository::exists('translations.'.$name), 'translation repository '.$name.' not found');
            // load and see if shorts match up
            $this->assertEquals($short, (new Repository('translations.'.$name))->get('short'), 'short does not match with translations file for '.$name);

            // check full integrity now
            $this->checkTranslationIntegrity($name, $books);

            // see if we can create this translation
            $trans = Factory::translation($short);
            $this->assertEquals($short, $trans->getShort());
        }

        // check the defaults exist
        foreach($translations->get('default') as $lang => $short) {
            $this->assertNotNull($translations->get('shorts.'.$short), 'default repository '.$short.' for '.$lang.' not found');
        }
    }

    private function checkTranslationIntegrity(string $name, Repository $books) {
        $repo = new Repository('translations.'.$name);
        $this->assertNotNull($repo->get('books.short'), 'short list missing for translation '.$name);
        $this->assertNotNull($repo->get('books.long'), 'long list missing for translation '.$name);
        $this->assertNotNull($repo->get('books.matchlist'), 'match list missing for translation '.$name);

        // check if all is in there
        $this->assertCount(self::$BOOK_COUNT, $repo->get('books.short'), 'short list missing for translation '.$name);
        $this->assertCount(self::$BOOK_COUNT, $repo->get('books.long'), 'long list missing for translation '.$name);

        $trans = Factory::translation($repo->get('short'));
        foreach($books->get('ids') as $key => $id) {
            $this->assertNotNull($repo->get('books.short.'.$key), $key.' missing in short list of '.$name);
            $this->assertNotNull($repo->get('books.long.'.$key), $key.' missing in long list of '.$name);

            // try to match oneself
            // $this->assertEquals($repo->get('books.short.'.$key), $trans->matchShort($key), 'translation '.$name.' could not match '.$key.' to itself');
            $this->assertEquals($id, $trans->matchToId($repo->get('books.short.'.$key)), 'translation '.$name.' could not match short '.$repo->get('books.short.'.$key).' to id '.$id);
            $this->assertEquals($id, $trans->matchToId($repo->get('books.long.'.$key)), 'translation '.$name.' could not match long '.$repo->get('books.long.'.$key).' to id '.$id);
        }
    }

}

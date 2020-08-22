<?php

namespace ThomasSchaller\BibStruct;
use ThomasSchaller\BibStruct\Helpers\Repository;

class Factory {
    private static Factory $instance;

    public static function getInstance() {
        if(!isset(self::$instance)) {
            self::$instance = new Factory();
        }
        return self::$instance;
    }

    private string $defaultLanguage = 'en';
    private Repository $books;
    private Repository $translations;
    private array $idToShort = [];
    private array $shortToId = [];
    
    private array $cacheTranslations = [];


    public function __construct(){
        $this->books = new Repository('books');
        $this->translations = new Repository('translations.translations');

        // generate the quick lists
        foreach($this->books->get('ids') as $short => $id) {
            $this->idToShort[$id] = $short;
            $this->shortToId[$short] = $id;
        }
    }

    public static function bookId(string $short) {
        return self::getInstance()->getBookId($short);
    }

    public function getBookId(string $short) {
        return $this->shortToId[$short] ?? null;
    }

    public static function bookShort(int $id) {
        return self::getInstance()->getBookShort($id);
    }

    public function getBookShort(int $id) {
        return $this->idToShort[$id] ?? null;
    }

    public function setLanguage(string $lang) {
        $this->defaultLanguage = $lang;
        return $this->defaultLanguage;
    }

    public function getLanguage() {
        return $this->defaultLanguage;
    }

    public static function lang(string $lang = null) {
        if($lang == null)
            return self::getInstance()->getLanguage();
        else
            return self::getInstance()->setLanguage($lang);
    }

    public static function translation(string $short = null, bool $forceUnique = false) {
        return self::getInstance()->createTranslation($short, $forceUnique);
    }

    public function createTranslation(string $short = null, bool $forceUnique = false) {
        // get the default name just in case
        $defaultShort = $this->translations->get('default.'.$this->defaultLanguage);
        if($short == null)
            $short = $defaultShort;

        if(!$forceUnique && isset($this->cacheTranslations[$short])) {
            return $this->cacheTranslations[$short];
        }

        // find the matching translations file name for the current short
        $name = $this->translations->get('shorts.'.$short);
        if($name == null) {
            $name = $this->translations->get('shorts.'.$defaultShort);
        }

        // we have either the real one or the default, so we can load right away
        $repo = new Repository('translations.'.$name);

        // generate lists correctly
        $_bookShort     = $repo->get('books.short');
        $_bookLong      = $repo->get('books.long');
        $_bookMatchlist = $repo->get('books.matchlist');
        $bookShort     = [];
        $bookLong      = [];
        $bookMatchlist = [];
        foreach($this->idToShort as $id => $short) {
            $bookShort    [$id] = $_bookShort    [$short];
            $bookLong     [$id] = $_bookLong     [$short];

            // automatically expand matchlist
            $bookMatchlist[$bookLong[$id]] = $id;
            $bookMatchlist[$bookShort[$id]] = $id;
        }
        foreach($_bookMatchlist as $match => $short) {
            $bookMatchlist[$match] = $this->shortToId[$short];
        }


        // build new object
        $translation = new Translation(
            $repo->get('short'), 
            $repo->get('name'), 
            $repo->get('language'),
            $bookShort,
            $bookLong,
            $bookMatchlist
        );

        // store in cache
        if(!$forceUnique)
            $this->cacheTranslations[$translation->getShort()] = $translation;

        return $translation;
    }

    public static function reference($book, int $chapter = null, int $verse = null, string $add = '') {
        return self::getInstance()->createReference(Factory::translation(), $book, $chapter, $verse, $add);
    }

    public function createReference(Translation $translation, $book, int $chapter = null, int $verse = null, string $add = '') {
        // check the variables for mixed types
        if(!is_numeric($book)) {
            $book = $translation->matchtoId($book);
        }
        
        $ref = new Reference($translation, $book, $chapter, $verse, $add);

        return $ref;
    }

    public static function range($book, $chapterFrom = null, $chapterTo = null, $verseFrom = null, $verseTo = null, $addFrom = '', $addTo = '') {
        return self::getInstance()->createReferenceRange(Factory::translation(), $book, $chapterFrom, $chapterTo, $verseFrom, $verseTo, $addFrom, $addTo);
    }

    public function createReferenceRange(Translation $translation, $book, $chapterFrom = null, $chapterTo = null, $verseFrom = null, $verseTo = null, $addFrom = '', $addTo = '') {
        $refFrom = $this->createReference($translation, $book, $chapterFrom, $verseFrom, $addFrom);
        $refTo = $this->createReference($translation, $book, $chapterTo, $verseTo, $addTo);
        return new ReferenceRange($refFrom, $refTo);
    }
}

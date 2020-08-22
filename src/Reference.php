<?php

namespace ThomasSchaller\BibStruct;

class Reference {
    protected Translation $translation;
    protected int $bookId;
    protected $chapter;
    protected $verse;
    protected $add;

    protected int $sortNum;

    public function __construct(Translation $translation, int $bookId, $chapter = null, $verse = null, $add = null) {
        $this->bookId = $bookId;
        $this->chapter = $chapter;
        $this->verse = $verse;
        $this->translation = $translation;
        $this->add = $add;
        $this->calcSortNum();
    }

    protected function calcSortNum() {
        $this->sortNum = ($this->chapter ?? 0) * 1000 + ($this->verse ?? 0);
    }

    /**
     * Compare this Reference to another Reference and return true if this is greater.
     * Also compares book ID!
     * @param Reference $b The other Reference
     * @return bool True if this is greater, otherwise false
     */
    public function compare(Reference $b) {
        if($this->bookId != $b->bookId) {
            return $this->bookId > $b->bookId;
        }
        return $this->sortNum > $b->sortNum;
    }

    public function toStr(bool $transShort = false, bool $long = false) {
        if($long)
            $str = $this->bookLong();
        else
            $str = $this->book();
        
        if($this->chapter)
            $str .= ' '.$this->chapterVerse();

        if($transShort)
            $str .= ' '.$this->trans();

        return $str;
    }

    public function book() {
        return $this->translation->bookShort($this->bookId);
    }

    public function getBookId() {
        return $this->bookId;
    }

    public function bookLong() {
        return $this->translation->bookLong($this->bookId);
    }

    public function chapterVerse(bool $hideAdd = false) {
        $str = $this->chapter ?? '';

        if($this->verse)
            $str .= ':'.$this->verse;
        if(!$hideAdd)
            $str .= $this->add;
        return $str;
    }

    public function trans() {
        return $this->translation->getShort();
    }

    public function getTranslation() {
        return $this->translation;
    }

    public function setTranslation(Translation $translation) {
        $this->translation = $translation;
        return $this->translation;
    }

    public static function parseStr(string $str, Reference $inherit = null, Translation $translation = null) {
        // get a translation from somewhere
        if($translation == null) {
            if($inherit) {
                $translation = $inherit->getTranslation();
            }
            else {
                $translation = Factory::translation();
            }
        }

        // see if we can find the book
        $str = trim($str);
        $ex = explode(' ', $str, 3);
        $book = null;
        if(count($ex) > 1) {
            // check if this is a 2 part book like 1 Cor
            if(is_numeric($ex[0]) && !is_numeric($ex[1])) {
                $book = $ex[0].' '.$ex[1];
            }
            elseif(!is_numeric(substr($ex[0], 0, 1))) {
                $book = $ex[0];
            }
        }
        else {
            if(is_numeric(substr($str, 0, 1))) {
                // starts with number, but no space, so lets inherit
            }
            else {
                // just see if this is a book
                $book = $str;
            }
        }

        // check if book string was found, if not just get from inherit
        if($book == null) {
            if($inherit == null)
                    throw new \Exception('Could not identify book for '.$str);

            $bookId = $inherit->bookId;
        }
        else {
            // validate book against list
            $bookId = $translation->matchToId(trim($book));
            
            if($bookId == null)
                throw new \Exception('Could not match book '.$book.' in '.$str);

            $str = substr($str, strlen($book));
        }

        // find chapter and verse
        $chapter = null;
        $verse = null;
        $add = null;
            
        // get the chapter/verse part
        $chapter = (int) $str;
        $ex = explode($chapter, $str, 2);
        $str = count($ex) == 2 ? $ex[1] : $ex[0];

        // see if there is a verse part
        $pos = strpos($str, ':');
        if($pos !== false) {
            $str = substr($str, $pos + 1);
            
            $verse = (int) $str;
            
            $ex = explode($verse, $str, 2);
            $str = count($ex) == 2 ? $ex[1] : $ex[0];
        }
        $add = trim($str);

        return new Reference($translation, $bookId, $chapter, $verse, $add);
    }

    /**
     * Convert this Reference to a ReferenceRange
     * @param Reference $to Second Reference with the endpoint of the range, null by default
     * @return RefernceRange the range with this Reference as starting point
     */
    public function toRange(Reference $to) {
        return new ReferenceRange($this, $to);
    }
}

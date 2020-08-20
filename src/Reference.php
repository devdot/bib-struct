<?php

namespace ThomasSchaller\BibStruct;

class Reference {
    protected Translation $translation;
    protected int $bookId;
    protected $chapter;
    protected $verse;
    protected string $add;

    public function __construct(Translation $translation, int $bookId, $chapter = null, $verse = null, string $add = null) {
        $this->bookId = $bookId;
        $this->chapter = $chapter;
        $this->verse = $verse;
        $this->translation = $translation;
        $this->add = $add;
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
}

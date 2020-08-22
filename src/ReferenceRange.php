<?php

namespace ThomasSchaller\BibStruct;

class ReferenceRange extends Reference {
    protected Reference $from;
    protected Reference $to;

    protected $sortNumFrom;
    protected $sortNumTo;

    public function __construct(Reference $from, Reference $to) {
        // make sure the reference goes the right way
        if($from->compare($to)) {
            // from is not equal and greater to to, switch them
            $this->from = $to;
            $this->to = $from;
        }
        else {
            $this->from = $from;
            $this->to = $to;
        }


        // default values to from
        $this->translation = $this->from->translation;
        $this->bookId = $this->from->bookId;
        $this->chapter = $this->from->chapter ?? $this->to->chapter;
        $this->verse = $this->from->verse;
        $this->add = $this->from->add;

        if($this->to->bookId != $this->from->bookId) {
            // ranges are supposed to be within the same book
            throw new Exceptions\MismatchBooksException($this->from->bookId, $this->to->bookId);
        }

        $this->calcSortNum();
    }

    protected function calcSortNum() {
        // the overall sort num is based on from
        $this->sortNum = ($this->from->chapter ?? 0) * 1000 + ($this->from->verse ?? 0);

        // calculate individuals
        // special: for to, the defaults are 999, not 0 (because it includes the entire chapter/book then)
        $this->sortNumFrom = $this->sortNum;
        $this->sortNumTo = ($this->to->chapter ?? 999) * 1000 + ($this->to->verse ?? 999);
    }

    /**
     * Compare this ReferenceRange to another Reference or ReferenceRange
     * @param Reference $b The other reference
     * @return bool True if this is greater, otherwise false
     */
    public function compare(Reference $b) {
        if($b instanceof ReferenceRange) {
            if($this->bookId != $b->bookId) {
                return $this->bookId > $b->bookId;
            }
            if($this->sortNumFrom != $b->sortNumFrom) {
                return $this->sortNumFrom > $b->sortNumFrom;
            }
            return $this->sortNumTo > $b->sortNumTo;
        }
        else {
            return parent::compare($b);
        }
    }

    public function chapterVerse(bool $hideAdd = false) {
        // the from part
        $str = $this->from->chapter ?? '';
        if($this->from->verse) {
            $str .= ':'.$this->from->verse;
        }
        if(!$hideAdd) {
            $str.= $this->from->add;
        }

        // now check if we even have to present a range
        if($this->sortNumFrom == $this->sortNumTo) {
            // special case
            $str .= ($hideAdd == false && !empty($this->to->add)) ? '-'.$this->to->add : '';
            return $str;
        }

        // add the to section
        $str .= '-';
        if($this->from->chapter != $this->to->chapter) {
            $str .= $this->to->chapter;
            if($this->to->verse) {
                $str .= ':';
            }
        }
        if($this->to->verse) {
            $str .= $this->to->verse;
        }
        if(!$hideAdd) {
            $str.= $this->to->add;
        }

        // make sure this is not badly formatted
        if($str == '-')
            $str = '';

        return $str;
    }

    public static function parseStr(string $str, Reference $inherit = null, Translation $translation = null) {
    }

    public function getFrom() {
        return $this->from;
    }

    public function getTo() {
        return $this->to;
    }
}

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
        if(substr($str, -1) == '-')
            $str = substr($str, 0, -1);

        return $str;
    }

    public function coalesce(Reference $b) {
        if(!($b instanceof ReferenceRange) && !($b instanceof ReferenceGroup)) {
            // for all that are not ourselves, invert
            return $b->coalesce($this);
        }

        // we've got two ranges
        if($this->bookId != $b->bookId) {
            return null;
        }

        // abort if there is not chapter to coalesce
        if($this->chapter == null || $b->chapter == null) {
            return null;
        }
        // special case with group
        if($b instanceof ReferenceGroup) {
            // attempt to coalesce with any of the group items
            $merged = false;
            foreach($b->getList()->get() as $key => $ref) {
                $ret = $ref->coalesce($this);
                if($ret != null) {
                    $b->getList()->set($ret, $key);
                    $merged = true;
                    break;
                }
            }
            if(!$merged) {
                // just append to the list
                $b->getList()->push($this);
            }
            // this type will always merge as long as its the same book
            return $b;
        }

        // check sides
        if($this->sortNumFrom <= $b->sortNumFrom) {
            // this is left of b
            // check contain
            if($this->sortNumTo >= $b->sortNumTo) {
                return $this;
            }
            // check overlap
            if($this->sortNumTo + 1 >= $b->sortNumFrom) {
                return $this->from->toRange($b->to);
            }
        }
        else {
            // this is right of b
            // check contain
            if($this->sortNumTo <= $b->sortNumTo) {
                return $b;
            }
            // check overlap
            if($this->sortNumFrom - 1 <= $b->sortNumTo) {
                return $b->from->toRange($this->to);
            }
        }

        return null;
    }

    /**
     * 
     * @returns Reference
     */
    public static function parseStr(string $str, Reference $inherit = null, Translation $translation = null) {
        return Parser::parse($str, $inherit, $translation, Parser::$MODE_RANGE);
    }

    public function getFrom() {
        return $this->from;
    }

    public function getTo() {
        return $this->to;
    }

    public function getSimplified() {
        // simple check
        if($this->from->sortNum == $this->to->sortNum) {
            return $this->from;
        }
        return $this;
    }
}

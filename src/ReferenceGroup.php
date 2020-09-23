<?php

namespace ThomasSchaller\BibStruct;

/**
 * ReferenceGroup contains a non-empty list of References that are all in the same book
 */
class ReferenceGroup extends Reference {
    protected ReferenceList $list;

    public function __construct(ReferenceList $list) {
        $this->setList($list);
    }

    public function getList() {
        return $this->list;
    }

    /**
     * Set the list, it cannot be empty and all the books are the same
     */
    public function setList(ReferenceList $list) {
        // use first to write group info
        $first = $list->get(0);
        if(count($list) == null || $first === null) {
            throw new Exceptions\EmptyListException();
        }
        $bookId = $list->get(0)->getBookId();
        foreach($list->get() as $reference) {
            if($bookId != $reference->getBookId()) {
                throw new Exceptions\MismatchBooksException($bookId, $reference->getBookId());
            }
        }
        $this->list = $list;

        $this->translation = $first->translation;
        $this->bookId = $first->bookId;
        $this->chapter = $first->chapter;
        $this->verse = $first->verse;
        $this->add = $first->add;
        $this->sortNum = $first->sortNum;
    }

    

    public function chapterVerse(bool $hideAdd = false) {
        // get all the strings
        $strs = array_map(function($ref) use ($hideAdd) {
            return $ref->chapterVerse($hideAdd);
        }, $this->list->get());

        $str = implode('; ', $strs);

        return $str;
    }

    public static function parseStr(string $str, Reference $inherit = null, Translation $translation = null) {
        return Parser::parse($str, $inherit, $translation, Parser::$MODE_GROUP);
    }

    public function coalesce(Reference $b) {
        if(!($b instanceof ReferenceGroup)) {
            return $b->coalesce($this);
        }

        if($this->bookId != $b->bookId) {
            return null;
        }

        // simply merge the groups into a new one and implode the list
        $merged = array_merge($this->list->get(), $b->list->get());
        $merged = array_filter($merged, function($el) {
            return $el->chapter != null;
        });
        $this->setList(new ReferenceList($merged));

        $this->cleanup();

        return $this;
    }

    /**
     * Clean up this group with this sorthand to list sort and implode
     */
    public function cleanup() {
        // sort and implode the list
        $this->list->sort();
        $this->list->implode();
        return $this;
    }
}

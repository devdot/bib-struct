<?php

namespace ThomasSchaller\BibStruct;

class ReferenceList implements \Countable {

    protected array $list = [];

    public function __construct(array $list = []) {
        $this->list = $list;
    }

    public function push(Reference $ref) {
        array_push($this->list, $ref);
    }

    public function pop() {
        return array_pop($this->list);
    }

    public function shift() {
        return array_shift($this->list);
    }

    public function unshift(Reference $ref) {
        array_unshift($this->list, $ref);
    }

    public function count() {
        return count($this->list);
    }

    /**
     * Get the entire list or a given item (using paramter $index)
     * @param int $index Optional, the index of a specific item
     * @returns array or Reference
     */
    public function get($index = null) {
        if($index !== null) {
            return $this->list[$index] ?? null;
        }
        else {
            return $this->list;
        }
    }

    /**
     * Set the entire list or just a given item
     * @param mixed $list Either an array with References or a single instance of Reference. Use $index to determine index, else list will be reset to [$item].
     * @param int $index Optional, will set the item at index in list to the Reference $list given (if it is a reference).
     */
    public function set($list , $index = null) {
        if($list instanceof Reference) {
            if($index === null) {
                return $this->set([$list]);
            }
            else {
                $this->list[$index] = $list;
                return;
            }
        }
        // overwrite the list
        $this->list = $list;
    }

    /**
     * Transform this list into a sorted list
     * @param bool $asc Ascending if true, descending if false
     * @param bool $breakInnerGroups if true, inner groups will be broken and everything fully sorted
     * @return ReferenceList this
     */
    public function sort(bool $asc = true, bool $breakInnerGroups = true) {
        if($breakInnerGroups) {
            $this->breakInnerGroups();
        }

        usort($this->list, function(Reference $a, Reference $b) use($asc) {
            return $a->compare($b) xor !$asc;
        });
        return $this;
    }

    /**
     * Merge the References together as much as possible in their current ordered form
     * @return ReferenceList this
     */
    public function implode() {
        if($this->count() == null) {
            return $this;
        }

        // fill a new array and step through the old
        $arr = [];
        $last = $this->get(0);
        foreach($this->list as $next) {
            $merge = $last->coalesce($next);
            if($merge === null) {
                $arr[] = $last;
                $last = $next;
            }
            else {
                $last = $merge;
            }
        }
        $arr[] = $last;

        // write the new array
        $this->list = $arr;

        return $this;
    }

    /**
     * Return a copy of this list
     * @return ReferenceList copy
     */
    public function copy() {
        return new ReferenceList($this->list);
    }

    /**
     * Transform this list into a list containing groups, merging the references that are after each other and in the same book
     * @return ReferenceList this
     */
    public function toGroups(bool $breakInnerGroups = false) {
       $this->list = $this->generateGroups($breakInnerGroups);
       return $this;
    }

    /**
     * Generate grouped references from this list
     * @return array of ReferenceGroup
     */
    public function generateGroups(bool $breakInnerGroups = false) {
        if($this->count() === 0) {
            return [];
        }

        // first break them apart
        if($breakInnerGroups) {
            $old = $this->getReferencesBroken();
        }
        else {
            $old = $this->list;
        }

        $first = $this->get(0);
        $arr = [];
        $lastBookId = $first->getBookId();
        $lastList = new ReferenceList();

        foreach($old as $ref) {
            if($ref->getBookId() != $lastBookId) {
                // finish up the last list
                $arr[] = new ReferenceGroup($lastList);
                
                // create new list 
                $lastList = new ReferenceList();
                $lastBookId = $ref->getBookId();
            }
            // just append in the list
            $lastList->push($ref);
        }
        $arr[] = new ReferenceGroup($lastList);

        return $arr;
    }

    /**
     * Transforms this ReferenceList into a list that has no items of type ReferenceGroup,
     * but they are broken recursively into their separate References
     * 
     */
    public function breakInnerGroups() {
        $this->list = $this->getReferencesBroken();
        return $this;
    }

    /**
     * Return the items, but break open groups into single items recursively
     * @return array of Reference
     */
    public function getReferencesBroken() {
        $arr = [];
        foreach($this->list as $ref) {
            if($ref instanceof ReferenceGroup) {
                $inner = $ref->getList()->getReferencesBroken();
                foreach($inner as $in) {
                    $arr[] = $in;
                }
            }
            else {
                $arr[] = $ref;
            }
        }
        return $arr;
    }

    public function toStr(bool $transShort = false, bool $long = false) {
        // get all the strings
        $strs = array_map(function($ref) use ($transShort, $long) {
            return $ref->toStr($transShort, $long);
        }, $this->list);

        $str = implode('; ', $strs);

        return $str;
    }

    /**
     * Beautify the list by using sort, implode, toGroup in sequence
     * @param bool $toGroups True by default, set to false to return without grouping
     * @return ReferenceList this
     */
    public function cleanup(bool $toGroups = true) {
        $this->sort();
        $this->implode();
        if($toGroups)
            $this->toGroups();
        return $this;
    }
}

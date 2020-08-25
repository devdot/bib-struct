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

}

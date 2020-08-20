<?php

namespace ThomasSchaller\BibStruct;

use ThomasSchaller\BibStruct\Factory;

class Translation {
    protected string $name;
    protected string $short;
    protected string $language;

    protected array $bookShort;
    protected array $bookLong;
    protected array $bookMatchlist;

    public function getName() {
        return $this->name;
    }

    public function getShort() {
        return $this->short;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function __construct(string $short, string $name, string $language, array $bookShort, array $bookLong, array $bookMatchlist) {
        $this->short = $short;
        $this->name = $name;
        $this->language = $language;
        $this->bookShort = $bookShort;
        $this->bookLong = $bookLong;
        $this->bookMatchlist = $bookMatchlist;
    }

    public function bookShort($id) {
        if(!is_numeric($id)) {
            $id = Factory::bookId($id ?? '');
        }
        return $this->bookShort[$id] ?? null;
    }

    public function bookLong($id) {
        if(!is_numeric($id)) {
            $id = Factory::bookId($id ?? '');
        }
        return $this->bookLong[$id] ?? null;
    }

    public function matchToId(string $match) {
        return $this->bookMatchlist[$match] ?? null;
    }

    public function matchShort(string $match) {
        return $this->bookShort($this->matchToId($match));
    }

    public function matchLong(string $match) {
        return $this->bookLong($this->matchToId($match));
    }
}

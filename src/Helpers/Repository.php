<?php

namespace ThomasSchaller\BibStruct\Helpers;

class Repository {
    public static $PACKAGE_DIR = __DIR__.'/../..';
    public static $REPOSITORY_DIR = 'data';

    private array $items;
    private string $name;
    private string $path;

    public function __construct(string $name, bool $load = true) {
        $this->name = $name;
        $this->path = realpath(self::$PACKAGE_DIR.'/'.self::$REPOSITORY_DIR).'/'.str_replace('.', '/', $name).'.php';
        
        if($load)
            $this->load();
    }

    private function load() {
        if(!file_exists($this->path))
            throw new \Exception('Repository file '.$this->path.' not found');
        
        $this->items = require($this->path);
    }

    public function write(array $items) {
        $this->items = $items;
    }

    public function get($key, $default = null) {
        return self::arrGet($this->items, $key, $default);
    }

    private static function arrGet(array $array, $key, $default = null) {
        $ex = explode('.', $key, 2);
        if(isset($array[$ex[0]])) {
            if(count($ex) == 1)
                return $array[$key] ?? $default;
            else {
                return self::arrGet($array[$ex[0]], $ex[1], $default);
            }
        }
        else {
            return $default;
        }

    }

    public function getName() {
        return $this->name;
    }

    public function getPath() {
        return $this->path;
    }
}


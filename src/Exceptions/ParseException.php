<?php

namespace ThomasSchaller\BibStruct\Exceptions;

class ParseException extends \Exception {
    private string $str = '';

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function setString(string $str) {
        $this->str = $str;
        $this->message .= ', string: '.$str;
    }
}

<?php

namespace ThomasSchaller\BibStruct\Exceptions;

class EmptyListException extends \Exception {
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
        if($message == '') {
            $message = 'Empty List!';
        }

        parent::__construct($message, $code, $previous);
    }
}

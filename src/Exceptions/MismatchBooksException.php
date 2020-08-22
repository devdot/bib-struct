<?php

namespace ThomasSchaller\BibStruct\Exceptions;

class MismatchBooksException extends \Exception {
    public $bookIdA;
    public $bookIdB;

    public function __construct(int $bookIdA, int $bookIdB, string $message = '', int $code = 0, \Throwable $previous = null) {
        if($message == '') {
            $message = 'Mismatch book id '.$bookIdA.' and book id '.$bookIdB;
        }
        $this->bookIdA = $bookIdA;
        $this->bookIdB = $bookIdB;

        parent::__construct($message, $code, $previous);
    }
}

<?php
namespace ThomasSchaller\BibStruct;

class Parser {
    public static $MODE_ALL = 0;
    public static $MODE_GROUP = 1;
    public static $MODE_RANGE = 2;
    public static $MODE_SINGLE = 3;

    private static $STATE_READ_BOOK = 0;
    private static $STATE_IGNORE = 1;
    private static $STATE_READ_BOOK_NORMAL = 4;
    private static $STATE_READ_BOOK_NUM = 5;
    private static $STATE_READ_FROM = 30;
    private static $STATE_READ_FROM_VERSE = 31;
    private static $STATE_READ_FROM_ADD = 32;
    private static $STATE_READ_FROM_TRANS = 33;
    private static $STATE_READ_TO = 40;
    private static $STATE_READ_TO_VERSE = 41;
    private static $STATE_READ_TO_ADD = 42;
    private static $STATE_READ_TO_TRANS = 43;
    private static $STATE_FINISH_CURRENT = 2;

    private string $string = '';
    private int $pointer;
    private bool $finished;
    private int $state;
    private int $mode;
    private bool $throwExceptions = true;

    private ReferenceList $list;

    private $lastBookId = null;
    private Translation $translation;

    private string $lastBook = '';
    private string $lastFromChapter = '';
    private string $lastFromVerse = '';
    private string $lastFromAdd = '';
    private string $lastToChapter = '';
    private string $lastToVerse = '';
    private string $lastToAdd = '';
    private string $lastTranslation = '';

    public function __construct() {
        $this->state = self::$STATE_READ_BOOK;
        $this->pointer = 0;
        $this->mode = self::$MODE_ALL;

        $this->list = new ReferenceList();

        $this->translation = Factory::translation();
    }

    public function setString(string $str) {
        $this->string = trim($str);
    }

    public function setInherit(Reference $inherit) {
        $this->lastBookId = $inherit->getBookId();
        if($this->translation == null) {
            $this->setTranslation($inherit->translation);
        }
    }

    public function setTranslation(Translation $translation) {
        $this->translation = $translation;
    }

    public function setExceptions(bool $throwExceptions) {
        $this->throwExceptions = $throwExceptions;
    }

    public function setMode(int $mode) {
        $this->mode = $mode;
    }

    public function resetLastCache() {
        $this->lastBook = '';
        $this->lastFromChapter = '';
        $this->lastFromVerse = '';
        $this->lastFromAdd = '';
        $this->lastToChapter = '';
        $this->lastToVerse = '';
        $this->lastToAdd = '';
        $this->lastTranslation = '';
    }

    public function run(string $str = null, Reference $inherit = null, Translation $translation = null) {
        if($str) {
            $this->setString($str);
        }

        if($inherit) {
            $this->setInherit($inherit);
        }

        if($translation) {
            $this->setTranslation($translation);
        }

        $this->resetLastCache();

        $this->finished = false;
        while($this->finished == false) {
            // let the machine step forward
            $res = $this->step();
            
            // $this->debugPrintStep();
    
            // silent fail
            if($res === false)
                return null;
        }

        // extract result
        switch($this->mode) {
            case self::$MODE_ALL:
                return $this->list;
            case self::$MODE_GROUP:
                return $this->list->toGroups()->get(0);
            case self::$MODE_RANGE:
                $el = $this->list->get(0);
                if(!($el instanceof ReferenceRange))
                    return $el->toRange($el);
            case self::$MODE_SINGLE:
                return $this->list->get(0);
        }
    }

    private function step() {
        $thisChar = substr($this->string, $this->pointer, 1);

        // deal with the end of the string
        if($thisChar === false) {
            $this->finished = true;
            $this->state = self::$STATE_FINISH_CURRENT;
        }

        // just make sure to jump states on ; (terminates the current reference)
        if($thisChar == ';') {
            $this->state = self::$STATE_FINISH_CURRENT;
            $this->pointer++;
        }

        switch($this->state) {
            case self::$STATE_READ_BOOK:
                return $this->processReadBook($thisChar);
            case self::$STATE_READ_BOOK_NORMAL:
                return $this->processReadBookNormal($thisChar);
            case self::$STATE_READ_BOOK_NUM:
                return $this->processReadBookNum($thisChar);
            case self::$STATE_READ_FROM:
                return $this->processReadFrom($thisChar);
            case self::$STATE_READ_FROM_VERSE:
                return $this->processReadFromVerse($thisChar);
            case self::$STATE_READ_FROM_ADD:
                return $this->processReadFromAdd($thisChar);
            case self::$STATE_READ_FROM_TRANS:
                return $this->processReadFromTrans($thisChar);
            case self::$STATE_READ_TO:
                if($this->mode == self::$MODE_SINGLE) {
                    $this->finished = true;
                    return $this->finishCurrent();
                }
                else
                    return $this->processReadTo($thisChar);
            case self::$STATE_READ_TO_VERSE:
                return $this->processReadToVerse($thisChar);
            case self::$STATE_READ_TO_ADD:
                return $this->processReadToAdd($thisChar);
            case self::$STATE_READ_TO_TRANS:
                return $this->processReadToTrans($thisChar);

            case self::$STATE_FINISH_CURRENT:
                if($this->mode == self::$MODE_SINGLE || $this->mode == self::$MODE_RANGE)
                    $this->finished = true;
                return $this->finishCurrent();
            default:
                throw new \Exception('missing case in Parser statemachine for '.$this->state);
        }
    }

    private function finishCurrent() {
        // check if the lastBook entry is purely numeric and if so, move that to chapter
        if(is_numeric($this->lastBook) && empty($this->lastFromChapter)) {
            $this->lastFromChapter = $this->lastBook;
            $this->lastBook = '';
        }

        // make sure we have a book id
        if($this->lastBookId == null && !empty($this->lastBook)) {
            $this->loadBookId();
        }
        // check if it's still empty
        if($this->lastBookId == null) {
            return $this->exception(new Exceptions\ParseException('No book defined'));
        }

        if(!empty($this->lastFromVerse) && empty($this->lastToVerse) && !empty($this->lastToChapter)) {
            // if there was a verse in to, but there is no verse in last, make the to chapter the verse
            $this->lastToVerse = $this->lastToChapter;
            $this->lastToChapter = '';
        }
        
        $from = new Reference($this->translation, $this->lastBookId, (int) $this->lastFromChapter, (int) $this->lastFromVerse, trim($this->lastFromAdd));
        $ref = $from;

        // now check if we had anything to
        if(!empty($this->lastToChapter) || !empty($this->lastToVerse) || !empty($this->lastToAdd)) {
            if(empty($this->lastToChapter))
                $this->lastToChapter = $this->lastFromChapter;
            if(empty($this->lastToVerse) && !empty($this->lastFromVerse))
                $this->lastToVerse = $this->lastFromVerse;
            
            $to = new Reference($this->translation, $this->lastBookId, (int) $this->lastToChapter, (int) $this->lastToVerse, trim($this->lastToAdd));
            $ref = new ReferenceRange($from, $to);
        }

        $this->list->push($ref);

        $this->resetLastCache();
        $this->state = self::$STATE_READ_BOOK;
        return true;
    }

    private function debugPrintStep() {
        echo PHP_EOL;
        echo 'String:           '.$this->string.PHP_EOL;
        echo 'Pointer:          '.str_pad('', $this->pointer).'^'.PHP_EOL;
        echo 'State:            '.$this->state.PHP_EOL;
        echo 'LastBookId:       '.$this->lastBookId.PHP_EOL;
        echo 'LastBook:         '.$this->lastBook.PHP_EOL;
        echo 'LastFromChapter:  '.$this->lastFromChapter.PHP_EOL;
        echo 'LastFromVerse:    '.$this->lastFromVerse.PHP_EOL;
        echo 'LastFromAdd:      '.$this->lastFromAdd.PHP_EOL;
        echo 'LastToChapter:    '.$this->lastToChapter.PHP_EOL;
        echo 'LastToVerse:      '.$this->lastToVerse.PHP_EOL;
        echo 'LastToAdd:        '.$this->lastToAdd.PHP_EOL;
        echo 'LastTranslation:  '.$this->lastTranslation.PHP_EOL;
    }

    private function processReadBook(string $char) {
        if($char == ' ') {
            // skip upfront space
        }
        elseif(is_numeric($char)) {
            $this->state = self::$STATE_READ_BOOK_NUM;
            $this->lastBook .= $char;
        }
        else {
            $this->state = self::$STATE_READ_BOOK_NORMAL;
            $this->lastBook .= $char;
        }
        $this->pointer++;

        return true;
    }

    private function processReadBookNormal(string $char) {
        // see if we reached the end of the book reading
        if($char == ' ') {
            // make sure we actually read something so far
            if(empty($this->lastBook)) {
                // keep reading the book
                $this->pointer++;
                return true;
            }
            else {
                $this->state = self::$STATE_READ_FROM;
                $this->pointer++;
                return $this->loadBookId();
            }
        }
        elseif(is_numeric($char)) {
            $this->state = self::$STATE_READ_FROM;
            // don't increase pointer because we already have the first number here
            return $this->loadBookId();
        }
        else {
            // keep copying and read
            $this->lastBook .= $char;
            $this->pointer++;
        }
        return true;
    }

    private function processReadBookNum(string $char) {
        // we already read the initial number
        if($char == ' ') {
            // continue reading the book like a normal string
            $this->lastBook .= $char;
            $this->state = self::$STATE_READ_BOOK_NORMAL;
            $this->pointer++;
        }
        else {
            // the initial number was indeed a chapter
            $this->lastFromChapter = $this->lastBook;
            $this->lastBook = '';
            $this->state = self::$STATE_READ_FROM;
            // don't increase pointer, reprocess in another mode
        }
        return true;
    }

    private function processReadFrom(string $char) {
        if(is_numeric($char)) {
            $this->lastFromChapter .= $char;
            $this->pointer++;
            return true;
        }
        elseif($char == ':') {
            // keep moving into the next state
            $this->state = self::$STATE_READ_FROM_VERSE;
            $this->pointer++;
            return true;
        }
        elseif($char == '-') {
            // move right to the TO part
            $this->state = self::$STATE_READ_TO;
            $this->pointer++;
            return true;
        }
        elseif($char == ' ') {
            // we move into the (possible) translation
            $this->state = self::$STATE_READ_FROM_TRANS;
            $this->pointer++;
            return true;
        }
        else {
            // it was non numeric and has no semantic value, it must be add
            $this->state = self::$STATE_READ_FROM_ADD;
            return true;
        }
    }

    private function processReadFromAdd(string $char) {
        if($char == ' ') {
            // we hit the end or translation
            $this->state = self::$STATE_READ_FROM_TRANS;
            $this->pointer++;
            return true;
        }
        else {
            // just add this onto add
            $this->lastFromAdd .= $char;
            $this->pointer++;
            return true;
        }
    }

    private function processReadFromVerse(string $char) {
        if(is_numeric($char)) {
            $this->lastFromVerse .= $char;
            $this->pointer++;
            return true;
        }
        elseif($char == '-') {
            // move right to the TO part
            $this->state = self::$STATE_READ_TO;
            $this->pointer++;
            return true;
        }
        elseif($char == ' ') {
            // we move into the (possible) translation
            $this->state = self::$STATE_READ_FROM_TRANS;
            $this->pointer++;
            return true;
        }
        else {
            // it was non numeric and has no semantic value, it must be add
            $this->state = self::$STATE_READ_FROM_ADD;
            return true;
        }
    }

    private function processReadFromTrans(string $char) {
        if($char == ' ') {
            // just keep going until terminate or ;
            $this->pointer++;
            return true;
        }
        elseif($char == '-') {
            // first let's make sure we are not doing a mistake here (some translations have dashes)
            $this->pointer++;
            // check the upcoming character and see if that's a number or not
            if(is_numeric(substr($this->string, $this->pointer, 1))) {
                // yet it's a number so we go on ahead to TO
                $this->state = self::$STATE_READ_TO;
            }
            else {
                // else we will need this dash and stay in translation
                $this->lastTranslation .= $char;
            }
            return true;
        }
        elseif($char == ':' && empty($this->lastFromVerse)) {
            // apparently we did not have the translation yet, delete and go to verse
            $this->lastTrans = '';
            $this->state = self::$STATE_READ_FROM_VERSE;
            $this->pointer++;
            return true;
        }
        elseif(is_numeric($char) && empty($this->lastTranslation)) {
            // we just read spaces so far
            if(empty($this->lastFromChapter)) {
                $this->state = self::$STATE_READ_FROM;
            }
            else {
                $this->state = self::$STATE_READ_FROM_VERSE;
            }
            return true;
        }
        else {
            $this->lastTranslation .= $char;
            $this->pointer++;
            return true;
        }
    }

    private function processReadTo(string $char) {
        if(is_numeric($char)) {
            $this->lastToChapter .= $char;
            $this->pointer++;
            return true;
        }
        elseif($char == ':') {
            // keep moving into the next state
            $this->state = self::$STATE_READ_TO_VERSE;
            $this->pointer++;
            return true;
        }
        elseif($char == ' ') {
            // we move into the (possible) translation
            $this->state = self::$STATE_READ_TO_TRANS;
            $this->pointer++;
            return true;
        }
        else {
            // it was non numeric and has no semantic value, it must be add
            $this->state = self::$STATE_READ_TO_ADD;
            return true;
        }
    }

    private function processReadToVerse(string $char) {
        if(is_numeric($char)) {
            $this->lastToVerse .= $char;
            $this->pointer++;
            return true;
        }
        elseif($char == ' ') {
            // we move into the (possible) translation
            $this->state = self::$STATE_READ_TO_TRANS;
            $this->pointer++;
            return true;
        }
        else {
            // it was non numeric and has no semantic value, it must be add
            $this->state = self::$STATE_READ_TO_ADD;
            return true;
        }
    }

    private function processReadToAdd(string $char) {
        if($char == ' ') {
            // we hit the end or translation
            $this->state = self::$STATE_READ_TO_TRANS;
            $this->pointer++;
            return true;
        }
        else {
            // just add this onto add
            $this->lastToAdd .= $char;
            $this->pointer++;
            return true;
        }
    }

    private function processReadToTrans(string $char) {
        // clear if there is translation from the from part
        if(!empty($this->lastTranslation))
            $this->lastTranslation = '';

        if($char == ' ') {
            // just keep going until terminate or ;
            $this->pointer++;
            return true;
        }
        elseif($char == ':' && empty($this->lastToVerse)) {
            // apparently we did not have the translation yet, delete and go to verse
            $this->lastTrans = '';
            $this->state = self::$STATE_READ_TO_VERSE;
            $this->pointer++;
            return true;
        }
        elseif(is_numeric($char) && empty($this->lastTranslation)) {
            // we just read spaces so far
            if(empty($this->lastToChapter)) {
                $this->state = self::$STATE_READ_TO;
            }
            else {
                $this->state = self::$STATE_READ_TO_VERSE;
            }
            return true;
        }
        else {
            $this->lastTranslation .= $char;
            $this->pointer++;
            return true;
        }
    }

    private function loadBookId() {
        // try to load the current book string
        $bookId = $this->translation->matchToId($this->lastBook);

        // check if we found it
        if($bookId == null) {
            return $this->exception(new Exceptions\ParseException('Could not identify book for '.$this->lastBook));
        }

        $this->lastBookId = $bookId;
    }

    /**
     * Parse any string into a Reference, might throw a ParseException
     * @param string $str The string that is supposed to be parsed
     * @param Reference $inherit Another (optional) Reference that is used to inherit missing data from
     * @param Translation $translation The translation that is supposed to be set for this new Reference
     * @param int $mode The mode that is supposed to run, from the mode list
     * @return Reference The parsed Reference
     */
    public static function parse(string $str, Reference $inherit = null, Translation $translation = null, int $mode = null) {
        $parser = new Parser();
        $parser->setMode($mode);
        return $parser->run($str, $inherit, $translation);
    }

    private function exception(Exceptions\ParseException $e) {
        if($this->throwExceptions) {
            $e->setString($this->string);
            throw $e;
        }
        else
            return false;
    }

}

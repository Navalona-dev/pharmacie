<?php
namespace App\Exception;

class UniqPropertyException extends \Exception
{
    public function __construct($prmMessage, $prmCode = 0, \Exception $prmPreviousException = null) {
        parent::__construct($prmMessage, $prmCode, $prmPreviousException);
    }
}
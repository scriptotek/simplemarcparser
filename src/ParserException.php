<?php namespace Scriptotek\SimpleMarcParser;

class ParserException extends \Exception {

	public function __construct($message, $code = 0, Exception $previous = null) {

		// make sure everything is assigned properly
		parent::__construct($message, $code, $previous);
	}
}

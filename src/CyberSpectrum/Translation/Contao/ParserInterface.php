<?php

namespace CyberSpectrum\Translation\Contao;

interface ParserInterface
{
	function debug($message);

	function pushStack($value);

	function popStack();

	function resetStack();

	/**
	 * Check whether the current token matches the given value.
	 *
	 * @param mixed $type The type that is expected, either a string value or a tokenizer id.
	 *
	 * @return bool
	 */
	function tokenIs($type);

	function bailUnexpectedToken($expected = false);

	function getToken();

	function getNextToken($searchfor = false);

	function parse();
}
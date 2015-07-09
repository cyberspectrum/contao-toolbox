<?php
// In this file we have several "crank" definitions with all various code styles.

/**
 * Fields
 */
$GLOBALS['TL_LANG']['a']['a'] = array('a-a-1', 'a-a-2');
$GLOBALS['TL_LANG']['a']['b'] = array(
    'a-b-1',
    'a-b-2'
);
$GLOBALS['TL_LANG']['a']['c'] = array(
    'a-c-1',
    'a-c' . '-2'
);


$GLOBALS['TL_LANG']['a']['d'] = array(
    'a-d-1',
    'a-d' .
    '-2'
);

$GLOBALS['TL_LANG']['a']['e'] = array(
    'a-e-1',
    'a-e' . // comment
    '-2'
);

$GLOBALS['TL_LANG']['a']['f'] = array(
    'a-f-1',
    'a-f' . /* comment */
    '-2'
);

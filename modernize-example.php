<?php
ini_set('pcre.backtrack_limit', '1048576');
require('PhpArrayModernizer.php');

$mdrnzr = new PhpArrayModernizer();

$mdrnzr->traverse('sample_dir');

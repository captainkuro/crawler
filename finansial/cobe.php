<?php

try {
	echo "AA\n";
	throw new Exception('asdf');
} catch (Exception $exc) {
	echo "DD\n";

	throw new Exception('jklm');
} finally {
	echo "XX\n";
	// throw new Exception('pppp');
}
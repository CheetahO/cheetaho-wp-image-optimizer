<?php

class cheetahoMetaHelper {
	public static function isRetinaImg($path) {
		$baseName = pathinfo($path, PATHINFO_FILENAME);
		
		return strpos($baseName, "@2x") == strlen($baseName) - 3;
	}
	
	public static function retinaName($file) {
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		
		return substr($file, 0, strlen($file) - 1 - strlen($ext)) . "@2x." . $ext;
	}
}
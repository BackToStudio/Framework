<?php
namespace Bazooka;

class CreateProject {

	public static function init() {
		echo "\n\n";
		echo "🧠 Installing Bazooka Core \n\n";	

		$source = __DIR__ . DIRECTORY_SEPARATOR . "default/";
		$destination =  __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

		CreateProject::moveFiles($source, $destination);
	}

	public static function moveFiles(string $source, string $destination) {
		$dir = opendir($source);
		@mkdir($destination);

		while(( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($source . '/' . $file) ) {
					CreateProject::moveFiles($source .'/'. $file, $destination .'/'. $file);
				}
				else {
					copy($source .'/'. $file,$destination .'/'. $file);
				}
			}
		}

		closedir($dir);
	}
}

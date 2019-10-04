<?php
namespace Bazooka;

class CreateProject {

	public static function init() {
		echo "\n\n";
		echo "🧠 Installing Bazooka Core \n\n";	

		$source = __DIR__ . DIRECTORY_SEPARATOR . "default/";
		$destination =  __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

		CreateProject::moveFiles($source, $destination);
		CreateProject::createEnvFile($destination);
		CreateProject::removeScriptFolder(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.scripts');

		echo "🚀 Installation complete! \n\n";	
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

	public static function createEnvFile(string $destination) {
		$file = '.env';
		$handle = fopen($destination . $file, 'w');

		$dbName = readline( "What's your DB Name? : " );
		$dbHost = readline( "What's your DB Host? : " );
		$dbUser = readline( "What's your DB User? : " );
		$dbPassword = readline( "What's your DB Password? : " );

		$data = "DB_NAME=$dbName" . "\n";
		$data .= "DB_HOST=$dbHost" . "\n";
		$data .= "DB_USER=$dbUser" . "\n";
		$data .= "DB_PASSWORD=$dbPassword" . "\n";

		fwrite($handle, $data);
	}

	public static function removeScriptFolder(string $dir) {
		foreach(scandir($dir) as $file) {
			if ('.' === $file || '..' === $file) continue;
			if (is_dir("$dir/$file")) CreateProject::removeScriptFolder("$dir/$file");
			else unlink("$dir/$file");
		}
		
		rmdir($dir);
	}
}

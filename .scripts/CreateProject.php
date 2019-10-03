<?php
namespace Bazooka;

class CreateProject {

	public static function init() {
		echo "\n\n";
		echo "🧠 Installing Bazooka Core \n\n";	

		CreateProject::moveFiles("default/", "../");
	}

	public static function moveFiles(string $source, string $destination) {
		// Open a known sourceectory, and proceed to read its contents
		if (is_dir($source)) {

			if ($stream = opensource($source)) {

				while (($file = readdir($stream)) !== false) {
					//exclude unwanted 
					if ($file==".") continue;
					if ($file=="..")continue;
					
					//if ($file=="index.php") continue; for example if you have index.php in the folder
					if (rename($source.'/'.$file,$destination.'/'.$file)) {
						echo "Copying files... \n";
					}
				}

				closedir($stream);
			}
		}
	}
}

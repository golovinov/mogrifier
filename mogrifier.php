<?php
echo "\n\n";

$dir = isset($argv[1])? $argv[1] : dirname(__FILE__).'/';

$files = ifiler::getFilesRecursive($dir,'jpg');
printl('Founded '.count($files).' files');

printl('Start workinng');

$minsize = 45*1024; 

$totalsize = 0;

$ot = array();
foreach ($files as $i=>$file) {

	if (filesize($dir.$file) < $minsize) {
		continue;
	}

	$ot[]=$dir.$file;
	$totalsize+=filesize($dir.$file);
}


printl('Founded '.count($ot).' files more then '.$minsize.' bytes');
printl("Total size is ".round($totalsize/1024/1024).' Mb');

printl();
printl('Lets rock');
printl();
printl();

$newsize = 0;

$i = 1;

foreach ($ot as $file) {
	
	$od = filesize($file);
	printl('Preparing '.$file);
	exec('mogrify -strip -quality 68 '."'".$file."'");
	clearstatcache();
	$ns = filesize($file);

	$newsize += $ns;

	printl('DONE. New size is '.round($ns/1024).' Kb. Rate '.round($ns*100/$od).'%');
	printl('Operations '.$i.'/'.count($ot));
	
	printl();


	$i++;
}

exit();

function printl($st='') {
	echo $st."\n";
}

class ifiler {
	
	/**
	 * Folder permission by default
	 *
	 */
	const FOLD_PERM = 0777;
	
	const FILE_PERM = 0777;
	
	/**
	 * Add slash to path if need
	 *
	 * @param string $dir		directory name
	 * @param boolean $hasslash	need or not adding slash
	 * @return string
	 */
	public static function slash($dir,$hasslash=true) {
		return rtrim($dir,'/\\').(($hasslash)? '/' : '');
	}
	
	/**
	 * Return concated path
	 * 
	 * @param array $paths
	 * @param bool $lastSlash
	 */
	public static function concat($paths,$lastSlash=false) {
		$path='/';
		foreach ($paths as $one) {
			$path.=trim($one,'/').'/';
		}
		return $lastSlash? rtrim($path,'/').'/' : rtrim($path,'/');
	}
	
	/**
	 * Return an extention of file
	 *
	 * @param string $var	filename or path
	 * @return string
	 */
	public static function ext($var) {
		if (strpos($var,'.')===false) {
			return '';
		}
		return substr($var,strrpos($var,'.')+1);
	}
	
	/**
	 * Returl list of files in directory
	 *
	 * @param string $folder	path to directory
	 * @param string $onlyExt	return only files whith such extention
	 * @return array
	 */
	public static function getFiles($folder, $onlyExt = null) {
		
		if (!is_dir($folder)) {
			return array();
		}
		
		$dir = new DirectoryIterator($folder);
		$res = array();
		foreach ($dir as $file) {
			if ($file->isDot() || $file->isDir()) {
				continue;
			}
			
			if ($onlyExt!==null && self::ext($file->getFilename())!=$onlyExt) {
				continue; 
			}
			$res[]=$file->getFilename();
		}
		return $res;
	}
	
	static public function getFilesRecursive($folder,$onlyExt=null,$current='') {
		$dir = new RecursiveDirectoryIterator($folder);
		$res=array();
		if ($current!='') {
			$current=self::slash($current);
		}
		
		foreach ($dir as $file) {
		
			if ($file->getFilename()=='.'||$file->getFilename()=='..') {
				continue;
			}
			
			if ($file->isDir()) {
				$incs =  self::getFilesRecursive(self::slash($file->getRealPath()),$onlyExt,$current.$file->getFileName());
				$res = array_merge($res,$incs);
				continue;
			}
			if ($onlyExt!==null && self::ext($file->getFilename())!=$onlyExt) {
				continue; 
			}
			$res[]=$current.$file->getFilename();
		}
		return $res;	
	}
	
	/**
	 * 
	 * Clean directory. Remove all files and folders
	 * @param string $dir
	 * @TODO: need to return false|true
	 */
	public static function cleanDir($dir) {
		$dir = self::slash($dir);
		
		foreach (self::getFiles($dir) as $file) {
			unlink($dir.$file);
		}
		
		foreach (self::getFolders($dir,true) as $one) {
			self::cleanDir($one);
			rmdir($one);
		}
	}
	
	/**
	 * Return name of file that will be unical in directory
	 *
	 * @param string $dir	directory path
	 * @param string $filename	name of file
	 * @return string	new file name (full path)
	 */
	public static function unicInDir($dir,$filename) {
		$files = self::getFiles($dir);
		$ext = ifiler::ext(basename($filename));
		$basename = basename($filename,'.'.$ext);
		
		for ($i=0,$end='';in_array($basename.$end.'.'.$ext,$files);$i++) {
			$end='_'.$i;
		}
		return  self::slash($dir).$basename.$end.'.'.$ext;
	}
	
	/**
	 * Create directory if need
	 *
	 * @param string $dir
	 * @return true
	 */
	public static function createDir($dir) {
		if (!is_dir($dir)) {
			mkdir($dir,self::FOLD_PERM,true);
			chmod($dir,self::FOLD_PERM);
		}
		return $dir;
	}
	
	/**
	 * 
	 * TODO: need refactoring. Use call_user_func_array!!!
	 * @param unknown_type $folder
	 * @param unknown_type $onlyExt
	 * @param unknown_type $handler
	 */
	public static function directoryHandler($folder,$onlyExt,$handler) {
		$dir = new DirectoryIterator($folder);
		
		if (is_array($handler)) {
			$class = is_object($handler[0])? $handler[0] : new $handler[0]();
			$method = $handler[1];
		} else {
			$class = null;
			$method = $handler;
		}
						
		foreach ($dir as $file) {
			if ($file->isDot() || $file->isDir() || ($onlyExt!==null && self::ext($file->getFilename())!=$onlyExt)) {
				continue;
			}
			if ($class!==null) {
				$class->$method($file);
			} else {
				call_user_func_array($method,array($file));
			}
		}
		return true;
	}
	
	/**
	 * Return subdirs of current dir
	 *
	 * @param string $folder	folder path
	 * @param boolean $fullpath	return fullpathces
	 * @return array
	 */
	public static function getFolders($folder,$fullpath = true) {
		
		try {
			$iterator = new DirectoryIterator(self::slash($folder));
		} catch (Exception $e) {
			return array();
		}
		
		$dirs= array ();
		
		foreach ($iterator as $folder) {
			
			if ($folder->isDir() && !$folder->isDot()) {
				if ($fullpath) {
					$dirs[]=$folder->getPath().'/'.$folder->getFileName();
				} else {
					$dirs[]=$folder->getFileName();
				}
			}
		}
		
		return $dirs;
		
	}
	
	/**
	 * Old function. Depricated
	 */
	public static function _getFolders($folder,$fullpath=true) {
		$folder= self::slash($folder);
		
		
		if (($dr= @ opendir($folder)) === false) {
			return array ();
		}
	
		$dirs= array ();
		while (($filename= readdir($dr)) !== false) {
			if (is_dir($folder.$filename) && $filename[0] != '.') {
				if ($fullpath) {
					$dirs[]= $folder.$filename;
				} else {
					$dirs[] = $filename;
				}
			}
		}
		return $dirs;
	}
	
	public static function formSize($size,$point=2) {
		$i=0;
		 $iec = array("байт", "Кб", "Мб", "Гб", "Тб", "PB", "EB", "ZB", "YB");
		 while (($size/1024)>1) {
		   $size=$size/1024;
		   $i++;
		 }
		 $size = number_format($size,$point,',',' ');
		 
		 return $size.'&#0160;'.$iec[$i];
		
	}
}

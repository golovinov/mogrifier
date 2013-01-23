<?php
printl();

$minsize = 45*1024; 

$dir = isset($argv[1])? $argv[1] : dirname(__FILE__).'/';

$files = ifiler::getFilesRecursive($dir,'jpg');
printl('Founded '.count($files).' files');
printl('Start workinng');

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
	const FOLD_PERM = 0777;
	const FILE_PERM = 0777;
	public static function slash($dir,$hasslash=true) {
		return rtrim($dir,'/\\').(($hasslash)? '/' : '');
	}
	public static function ext($var) {
		if (strpos($var,'.')===false) {
			return '';
		}
		return substr($var,strrpos($var,'.')+1);
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
	public static function formSize($size,$point=2) {
		$i=0;
		 $iec = array("байт", "Кб", "Мб", "Гб", "Тб", "PB", "EB", "ZB", "YB");
		 while (($size/1024)>1) {
		   $size=$size/1024;
		   $i++;
		 }
		 $size = number_format($size,$point,',',' ');
		 return $size.' '.$iec[$i];
	}
}

<?php
/**
 * Create a new messages.POT file and update specified .po files.
 *
 * Uses find, xargs, sed, xgettext and msgmerge tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package internal
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */

echo "** gettext helper tool for b2evolution **\n";

// Check that all external tools are available:
foreach( array( 'xgettext', 'msgmerge', 'find', 'xargs', 'sed' ) as $testtool )
{
	exec( $testtool.' --version', $output, $return );
	if( $return !== 0 )
	{
		die( "This script needs the $testtool tool.\n" );
	}
}


function echo_usage()
{
	global $argv;

	echo "Usage: \n";
	echo basename($argv[0])." <CORE|CWD> [extract]\n";
	echo basename($argv[0])." <CORE|CWD> merge <locale> [locale..]\n";
	echo basename($argv[0])." <CORE|CWD> convert <locale> [locale..]\n";
	echo "CORE: work on the core application\n";
	echo "CWD: work on current working directory\n";
	echo "\n";
	echo "By default, to translatable strings get extracted into locales/messages.POT.\n";
	echo "\n";
	echo "By adding 'merge <locale>' to the command line arguments, you'll merge\n";
	echo "the locale's messages.PO file with the messages.POT file. This is useful\n";
	echo "after having updated the messages.POT file, obviously.\n";
	echo "\n";
	echo "By adding 'convert <locale>' to the command line arguments, you'll convert\n";
	echo "the locale's messages.PO file to _global.php, which b2evolution uses.\n";
	echo "\n";
	echo "E.g.,\n";
	echo "./xg.php CORE\n";
	echo "./xg.php CORE merge de_DE\n";
	echo "..edit .po file..\n";
	echo "./xg.php CORE convert de_DE\n";
	echo "\n";
}


if( ! isset($_SERVER['argc']) || ! isset( $_SERVER['argv'] ) )
{
	echo_usage();
	exit(1);
}


$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if( $argc < 2 )
{
	echo_usage();
	exit(1);
}

if( strtoupper($argv[1]) == 'CORE' )
{
	echo "CORE mode..\n";
	$mode = 'CORE';
	// The blogs directory:
	$dir_root = dirname(__FILE__).'/../blogs/';
}
elseif( strtoupper($argv[1]) == 'CWD' )
{
	echo "Using current working directory..\n";
	$mode = 'CWD';
	$dir_root = getcwd();
}
else
{
	/*
	$dir_root = $argv[1];
	*/
	echo_usage();
	exit(1);
}

if( ! isset($argv[2]) || strtoupper($argv[2]) == 'EXTRACT' )
{
	$action = 'extract';
}
elseif( isset($argv[2]) && strtoupper($argv[2]) == 'MERGE' )
{
	$action = 'merge';

	if( ! isset($argv[3]) ) // the to-get-merged locale
	{
		echo_usage();
		exit(1);
	}

	$locales_to_merge = array_slice( $argv, 3 );
}
elseif( isset($argv[2]) && strtoupper($argv[2]) == 'CONVERT' )
{
	$action = 'convert';

	if( ! isset($argv[3]) ) // the to-get-converted locale
	{
		echo_usage();
		exit(1);
	}

	$locales_to_convert = array_slice( $argv, 3 );
}
else
{
	echo_usage();
	die;
}


// ---- COMMON CHECKS: ----

if( ! realpath($dir_root) )
{
	die( "Fatal error: The path '$dir_root' was not found!\n" );
}
// Normalize path:
$dir_root = realpath($dir_root).'/';

// The messages.pot (template) file:
$file_pot = $dir_root.'locales/messages.pot';


if( $action == 'extract' )
{
	if( ! is_writable($file_pot) )
	{
		if( ! file_exists( $dir_root.'locales' ) )
		{
			echo "Directory {$dir_root}locales/ does not exist..\n";

			if( ! mkdir( $dir_root.'locales' ) )
			{
				die( "FATAL: could not create directory {$dir_root}locales/\n" );
			}
			echo "Created directory.\n";
		}

		if( ! file_exists( $file_pot ) )
		{
			touch( $file_pot );
		}


		if( ! is_writable($file_pot) )
		{
			die( "FATAL: The file $file_pot is not writable.\n" );
		}
	}

	// The locales dir is our working dir:
	chdir( $dir_root.'locales' );

	echo 'Extracting T_() and NT_() strings from all .php files below "'.basename($dir_root).'" into "'.basename($dir_root).'/locales/messages.pot".. ';
	system( 'find ../ -iname "*.php"'
		.' | xargs xgettext -o '.escapeshellarg($file_pot).' --from-code=iso-8859-15 --no-wrap --add-comments=TRANS --copyright-holder="Francois PLANQUE" --msgid-bugs-address=http://fplanque.net/ --keyword=T_ --keyword=NT_ --keyword=TS_ -F', $return_var );
	if( $return_var !== 0 )
	{
		die("Failed!\n");
	}
	echo "[ok]\n";


	// Replace various things (see comments)
	echo 'Automagically search&replace in messages.pot.. ';
	$search = array('~\r~'); // TODO: may need to be '\\r' (or even more escaped ;p)
	$replace = array('');

	$data = file_get_contents( $file_pot );
	$data = preg_replace( $search, $replace, $data );

	// Convert forward slashes (unix) in paths to backward slashes (windows)
	$function = ''; // used as callback
	if( $mode == 'CORE' )
	{ // make paths relative to the .po files
		$function .= '$m[0] = str_replace( " ../", " ../../../", $m[0] );';
	}
	$function .= 'return str_replace( "/", "\\\\", $m[0] );';

	$data = preg_replace_callback( '~^#: (.*)$~m', create_function( '$m', $function ), $data );

	file_put_contents( $file_pot, $data );

	if( $mode == 'CORE' )
	{ // Replace header "vars" in first 20 lines:
		system( 'sed -i 1,20"'
						.'s/PACKAGE/b2evolution/;'
						.'s/VERSION/1.6-CVS/;'
						.'s/# SOME DESCRIPTIVE TITLE./# b2evolution - Language file/;'
						.'s/(C) YEAR/(C) 2003-'.date('Y').'/;'
						.'s/YEAR(?!-MO)/'.date('Y').'/;'
						.'s/CHARSET/iso-8859-1/;'
						.'" '.escapeshellarg($file_pot) );
	}
	echo "[ok]\n";

	exit(0);
}


if( $action == 'merge' )
{ // Merge with existing .po files:
	if( ! @is_readable( $file_pot ) )
	{
		echo "FATAL: $file_pot is not readable!\n";
		exit(1);
	}

	foreach( $locales_to_merge as $l_locale )
	{
		$l_file_po = $dir_root.'locales/'.$l_locale.'/LC_MESSAGES/messages.po';

		echo 'Merging with '.$l_locale.'.. ';

		if( ! file_exists( $l_file_po ) )
		{
			echo "PO file $l_file_po not found!\n";
			continue;
		}

		system( 'msgmerge -U -F --no-wrap '.escapeshellarg($l_file_po).' '.escapeshellarg($file_pot) );
		# delete old TRANS comments and make automatic ones valid comments:
		system( 'sed -i -r "/^#\\s+TRANS:/d; s/^#\\. TRANS:/# TRANS:/;" '.$l_file_po );
		echo "Written $l_file_po .\n";
		echo "\n";
	}

	exit(0);
}


if( $action == 'convert' )
{ // convert messages.PO files to _global.php
	if( ! @is_readable( $file_pot ) )
	{
		echo "FATAL: $file_pot is not readable!\n";
		exit(1);
	}

	require_once dirname(__FILE__).'/pofile.class.php';

	foreach( $locales_to_convert as $l_locale )
	{
		$l_file_po = $dir_root.'locales/'.$l_locale.'/LC_MESSAGES/messages.po';

		echo 'Converting '.$l_locale.'.. ';

		if( !file_exists( $l_file_po ) )
		{
			echo "PO file $l_file_po not found!\n";
			continue;
		}

		$POFile = new POFile($l_file_po);
		$ttrans = $POFile->read(false);

		$global_file_path = $dir_root.'locales/'.$l_locale.'/_global.php';
		$fp = fopen( $global_file_path, 'w+' );

		if( ! $fp )
		{
			echo "Could not open $global_file_path for writing!\n";
			continue;
		}

		fwrite( $fp, "<?php\n" );
		fwrite( $fp, "/*\n" );
		fwrite( $fp, " * Global lang file\n" );
		fwrite( $fp, " * This file was generated automatically from messages.po\n" );
		fwrite( $fp, " */\n" );
		fwrite( $fp, "if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );" );
		fwrite( $fp, "\n\n" );


		fwrite( $fp, "\n\$trans['".$l_locale."'] = array(" );
		foreach( $ttrans as $msgid => $msginfo )
		{
			fwrite( $fp,
				"'".str_replace( array("'", '\"'), array("\'", '"'), $msgid )
				."' => '".str_replace( array("'", '\"'), array("\'", '"'), $msginfo['trans'] )."',\n" );
		}
		fwrite( $fp, "\n);\n?>" );
		fclose( $fp );

		echo "[ok]\n";
	}

	exit(0);
}

?>

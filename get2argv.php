<?php

/*
   $_GET to $argv, $argv to $_GET functions

   These algorithms came about while testing PHP code in both CLI and Web 
   environments. And within IDE debuggers such as Microsoft's Visual Studio 
   Code - which exposed problems with debugging CLI/Web in their environment. 
   (More on that later.)

   The purpose of these two algorithms are to test the same PHP code within 
   these different environments. (To my knowledge such differences have not 
   been examined and compared into any documentation online.)

   For this kind of code lots of comments are required. Lots of discussion 
   as well. See END for more that.

   NOTE: These algorithms are to make CLI code run in a Web environment and 
   vice versa. That is all.
*/

/*
   The easiest way for PHP code to determine CLI vs. Web environments is the 
   $argv variable - exists for the former, not for the latter.

   While $argv - when it exists - is a global, within functions, to avoid 
   the "evil" - according to Wikimedia code source comments - global keyword, 
   use of $_SERVER['argv'] is probably best used.
*/

// this just provides initial environment feedback - as this is test code

if (isset($argv)) {
	echo "\nterminal - argv2get()\n";
} else {
	echo "<p>server - get2argv()</p>\n";
}

/*
   Check $_GET to $argv algorithm - Web environment (returns NULL if not).
   Then check $argv to $_GET algorithm - CLI environment (returns NULL if not).
*/

if (get2argv()) {
	echo PHP_EOL,'<pre>$argv: ';
	var_dump($argv);
}
else // get2argv() creates $argv so this else (for this code) is necessary
if (argv2get()) {
	echo PHP_EOL,'$_GET: ';
	var_dump($_GET);
}


/*
   get2argv() - executes the $_GET to $argv algorithm if $argv not set

   Example use is to access this file via a URL in web browser:

      index.php?a=1&file"

   Output:

      server - get2argv()

      $argv array(3) {
        [0] => "get2argv.php"
        [1] => "-a=1"
        [2] => "file"
     }

   More discussion below.
*/

function get2argv() {
$nonopt = array('file');		// 1

	if (!isset($_SERVER['argv'])) {
		$i = 0;
		$argv = array();
		$argv[$i++] = $_SERVER['SCRIPT_NAME'];
		foreach ($_GET as $k => $v) {
			if ($nonopt && in_array($k,$nonopt)) {
				$argv[$i] = "$k";
			} else {
				$argv[$i] = "-$k";
			}
			if ($v !== '') {
				$argv[$i] .= "=$v";
			}
			$i++;
		}
		$_SERVER['argc'] = $i;
		$_SERVER['argv'] = $argv;

		$GLOBALS['argc'] = $i;
		$GLOBALS['argv'] = $argv;

		define('_HTML',1);	// 2

		return true;
	}
}
/*
   1. Typical CLI code is like `CMD [OPTIONS] [FILE]` - typical GNU/*nix 
      programs - this variable allows for that (though not "one size fits 
      all").
   2. Just an example if overall code wants to know if it is running in 
      Web or CLI mode. The name would be customized of course, and can 
      be define('_TERM',0) for example.
*/

/*
   argv2get() - executes the $argv to $_GET algorithm if $argv is set

   Example use is to access this file via CLI:

      $ ./phpscript -a=1 file

   Output:

      $_GET array(2) {
        ["file"] => "file"
        ["a"] => "1"
      }

   More discussion below.
*/

function argv2get() {
$nonopt = array('file');		// 1 (as above)

	if (isset($_SERVER['argv'])) {
		$i = 0;
		$argv = $_SERVER['argv'];
		array_shift($argv);
		foreach ($argv as $arg) {
			if ($arg[0] != '-') {
				if ($nonopt and isset($nonopt[$i])) {
					$_GET[$nonopt[$i]] = $arg;
					$i++;
				}
				continue;
			}
			if (!strpos($arg,'=')) {
				$arg .= '=';
			}
			list($opt,$val) = explode('=',$arg);
			$opt = ltrim($opt,'-');
			$_GET[$opt] = $val;
		}

		define('_TERM',1);	// 2 (as above)

		return true;
	}
}


// END

/*
   Important Notes:

   As said previously, this code is about "retrofitting" CLI code to be run in 
   a Web environment and vice versa, and not about how to develop CLI code to 
   handle arguments, nor Web code to parse the $_GET array to do the same.

   Notes on get2argv() - CLI code to run in a Web environment. 

   Here is the simple, outside of a function way to do the basic get -> arg:

      if (!$argv) {
         $i = 0;
         $argv[$i++] = $_SERVER['SCRIPT_NAME'];
         foreach ($_GET as $k => $v) {
            $argv[$i] = "-$k";
            if ($v !== '') {
               $argv[$i] .= "=$v";
            }
            $i++;
         }
         $argc = $i;
      }

   Without the "nonopt" data, all $_GET members become "-key[=value]" in the 
   $argv array. So that is not the equivalent of "prog [options] [file]" of 
   the typical 

   The more advanced options such as "--include '*.php'" aren't supported in 
   this code. Like the use "$nonopt" data, some kind of "$longopt" data 
   would need to be designed...

   As well, combining options, "-abc" same as "-a -b -c", also not supported. 
   Again, some meta data would be required for that.

   Notes on argv2get() - Web code to run in a CLI environment. 

   Here is the simple, outside of a function way to do the basic arg -> get:

      if (isset($argv)) {
         array_shift($argv);
         foreach ($argv as $arg) {
            if ($arg[0] != '-') {
               continue;
            }
            if (!strpos($arg,'=')) {
               $arg .= '=';
            }
            list($opt,$val) = explode('=',$arg);
            $opt = ltrim($opt,'-');
            $_GET[$opt] = $val;
      }

   Without the "nonopt" data, all non-options are ignored. And the other 
   limitations for get2argv() (long options, combined options) are here as 
   well.

   Implementation Notes:

   For a web application to run from a CLI and to still work, the arg2get() 
   code right above will at least enable it to run (albeit somewhat limited) 
   or at least not produce a fatal runtime error.

   So too for a CLI program to run in a web browser with the get2arg() code.

   Code Notes:

   Other examples are to place a call to get2arg()/arg2get() at the top of the 
   program:

      get2argv();

   But a way to lessen the complexity, increase the readability (code 
   reduction, or re-factoring), is to do:

      if (!isset($argv)) {
         get2argv();
      }

   And the function can be reduced somewhat by removing an indent (still with 
   the limitations described above):

      function get2argv() {
      $nonopt = array('file');

         $i = 0;
         $argv = array();
         $argv[$i++] = $_SERVER['SCRIPT_NAME'];
         foreach ($_GET as $k => $v) {
            if ($nonopt && in_array($k,$nonopt)) {
               $argv[$i] = "$k";
            } else {
               $argv[$i] = "-$k";
            }
            if ($v !== '') {
               $argv[$i] .= "=$v";
            }
            $i++;
         }
         $_SERVER['argc'] = $i;
         $_SERVER['argv'] = $argv;
      
         define('_HTML',1);
      }

   Now, one indent less is not a big deal, one might say. I think differently. 
   In my opinion, emphasis on opinion, when it comes to programming design, 
   things like braces and brace positions, a single function return point, 
   not using gotos, not using globals, etc., code indents beyond for or five 
   is an indication of poor code design. In my opinion.

*/

<?php
// Define ourselves as a parent file

// Try to get the path to the Joomla! installation
$joomlaPath = $_SERVER['HOME'] . '/Sites/dev3';

if (isset($_SERVER['JOOMLA_SITE']) && is_dir($_SERVER['JOOMLA_SITE']))
{
	$joomlaPath = $_SERVER['JOOMLA_SITE'];
}

if (!is_dir($joomlaPath))
{
	echo <<< TEXT


CONFIGURATION ERROR

Your configured path to the Joomla site does not exist. Rector requires loading
core Joomla classes to operate properly.

Please set the JOOMLA_SITE environment variable before running Rector.

Example:

JOOMLA_SITE=/var/www/joomla rector process $(pwd) --config rector.yaml \
  --dry-run

I will now error out. Bye-bye!

TEXT;

	throw new InvalidArgumentException("Invalid Joomla site root folder.");
}

// Required to run the boilerplate FOF CLI code
$originalDirectory = getcwd();
chdir($joomlaPath . '/cli');

// Setup and import the base CLI script
$minphp = '7.2.0';

// Boilerplate -- START
define('_JEXEC', 1);

foreach ([__DIR__, getcwd()] as $curdir)
{
	if (file_exists($curdir . '/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/defines.php';

		break;
	}

	if (file_exists($curdir . '/../includes/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/../includes/defines.php';

		break;
	}
}

defined('JPATH_LIBRARIES') || die ('This script must be placed in or run from the cli folder of your site.');

require_once JPATH_LIBRARIES . '/fof30/Cli/Application.php';
// Boilerplate -- END

// Undo the temporary change for the FOF CLI boilerplate code
chdir($originalDirectory);

// Load FOF 3
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('FOF 3.x is not installed', 500);
}

// Other classes
$autoloader = include(__DIR__ . '/../plugins/system/sociallogin/vendor/autoload.php');
$autoloader->addClassMap([
	# Plugins
	'plgSystemUsertype'                => __DIR__ . '/../plugins/system/usertype/usertype.php',
//	'plgSystemUsertypeInstallerScript' => __DIR__ . '/../plugins/system/usertype/script.plg_user_usertype.php',

	# Deprecated Joomla classes
	'JArrayHelper'                     => $joomlaPath . '/libraries/joomla/utilities/arrayhelper.php',
	'JEventDispatcher'                 => $joomlaPath . '/libraries/joomla/event/dispatcher.php',
]);

<?php
/*
 * @package   PlgSystemUsertype
 * @copyright Copyright (c)2020-2020 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * @var   array  $userTypes     The user type to display
 * @var   string $topContent    Content to display at the top of the page
 * @var   string $bottomContent Content to display at the bottom of the page
 */

$token            = Session::getFormToken();
$hasTopContent    = !empty(trim(strip_tags($topContent)));
$hasBottomContent = !empty(trim(strip_tags($bottomContent)));

?>
<section id="plg_system_usertype">
	<?php if ($hasTopContent): ?>
		<div class="plg_system_usertype_topcontent">
		<?= $topContent ?>
		</div>
	<?php endif; ?>
	<div class="plg_system_usertype_usertypes">
		<?php foreach ($userTypes as $key => $type): ?>
			<a class="plg_system_usertype_usertype" rel="nofollow"
			   href="<?= Route::_(sprintf("index.php?option=com_ajax&plugin=usertype&group=system&%s=1&typekey=%s&format=raw", $token, htmlentities($key))) ?>">
				<?= $type->title ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php if ($hasBottomContent): ?>
		<div class="plg_system_usertype_bottomcontent">
		<?= $bottomContent ?>
		</div>
	<?php endif; ?>
</section>

<?php
/*
 * @package   PlgSystemUsertype
 * @copyright Copyright (c)2020-2020 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;

class plgSystemUsertype extends CMSPlugin
{
	/**
	 * The application object
	 *
	 * @var   SiteApplication
	 * @since 1.0.0
	 */
	public $app;

	/**
	 * Triggers before Joomla renders the HTML page.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function onBeforeRender(): void
	{
		$user      = Factory::getUser();
		$userTypes = (array) $this->params->get('types');

		if (!$this->canTrigger($user))
		{
			return;
		}

		// Figure out which user types to display
		$userTypes = array_filter($userTypes, function (object $typeDef) use ($user) {
			return !$this->userInGroups($user, $typeDef->hide_for ?? []);
		});

		if (empty($userTypes))
		{
			return;
		}

		//Load layout
		$layoutFile    = PluginHelper::getLayoutPath('system', 'usertype', 'default');
		$layoutContent = $this->getTemplateContent($layoutFile, [
			'userTypes'  => $userTypes,
			'topContent' => $this->params->get('topContent', ''),
			'bottomContent' => $this->params->get('bottomContent', ''),
		]);

		// That's one underhanded way to disable the plugin...
		if (empty(trim($layoutContent)))
		{
			return;
		}

		// Load the CSS and display the plugin's template instead of the component content on the page. Magic.
		$mediaVersion = hash_hmac('md5', filesize(__FILE__) . '@' . filemtime(__FILE__), $this->app->get('secret'));
		HTMLHelper::_('stylesheet', 'plg_system_usertype/usertype.css', [
			'version'       => $mediaVersion,
			'relative'      => true,
			'detectDebug'   => true,
			'pathOnly'      => false,
			'detectBrowser' => true,
		], [
			'type' => 'text/css',
		]);

		$this->app->getDocument()->setBuffer($layoutContent, [
			'type'  => 'component',
			'name'  => '',
			'title' => '',
		]);

		$this->app->getSession()->set('plg_system_usertype.redirect', Uri::current());
	}

	/**
	 * Handler for the com_ajax URL pointing to this plugin. Redirect on completion.
	 *
	 * @return  void
	 * @throws  NotAllowed When the security and consistency checks fail.
	 * @throws  RuntimeException When the user save fails.
	 * @since   1.0.0
	 */
	public function onAjaxUsertype(): void
	{
		$token       = Session::getFormToken();
		$user        = Factory::getUser();
		$userTypeKey = $this->app->input->getCmd('typekey');
		$userTypes   = (array) $this->params->get('types');

		// Figure out which user types to display
		$userTypes = array_filter($userTypes, function (object $typeDef) use ($user) {
			return !$this->userInGroups($user, $typeDef->hide_for ?? []);
		});

		/**
		 * Security check: we need a valid form token, ensure that the user is not already assigned to user groups which
		 * would prevent displaying the user type selection page and that the requested user type does exist.
		 */
		if (($this->app->input->getInt($token, 0) != 1) || !$this->canTrigger($user, true) || !array_key_exists($userTypeKey, $userTypes))
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Assign the new groups to the user object
		$user->groups = $user->getAuthorisedGroups();

		if (!empty($userTypes[$userTypeKey]->assign))
		{
			$user->groups = array_unique(array_merge($user->groups, $userTypes[$userTypeKey]->assign));
		}

		// Remove groups from the user object
		if (!empty($userTypes[$userTypeKey]->remove))
		{
			$user->groups = array_unique(array_diff($user->groups, $userTypes[$userTypeKey]->remove));
		}

		if (!$user->save(true))
		{
			throw new RuntimeException($user->getError(), 500);
		}

		// Redirect to the next page
		$redirect = $this->app->getSession()->get('plg_system_usertype.redirect', 'index.php');

		$this->app->redirect($redirect);
	}

	/**
	 * Returns the HTML output of a template layout
	 *
	 * @param   string  $file       The absolute filesystem path of the template layout file
	 * @param   array   $extraData  Any additional variables to set in the template layout's scope
	 *
	 * @return  string  The HTML output of the template layout. Empty string on failure.
	 * @since   1.0.0
	 */
	private function getTemplateContent(string $file, array $extraData = []): string
	{
		if (!file_exists($file) || !is_readable($file))
		{
			return '';
		}

		if (!empty($extraData))
		{
			extract($extraData);
		}

		@ob_start();
		@include_once $file;

		$content = @ob_get_clean();

		return is_string($content) ? $content : '';
	}

	/**
	 * Should I trigger the plugin for the given user?
	 *
	 * Performs a series of checks e.g. the user must be logged in, not already assigned to one or more groups set in
	 * the user types and so on.
	 *
	 * @param   User|null  $user
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private function canTrigger(?User $user, bool $forAjax = false): bool
	{
		// Only trigger for logged in users
		if (is_null($user) || $user->guest)
		{
			return false;
		}

		// Only trigger in the front-end of the site
		if (!$this->app->isClient('site'))
		{
			return false;
		}

		// Only trigger for HTML display
		/** @var HtmlDocument $document */
		$document = $this->app->getDocument();

		if (!$forAjax && !($document instanceof HtmlDocument))
		{
			return false;
		}

		// Do not trigger when a captive login component has taken over
		if (!$forAjax && $this->isExemptComponent($this->app->input->getCmd('option'), $this->app->input->getCmd('view'), $this->app->input->getCmd('task')))
		{
			return false;
		}

		// Check user group exclusion rules
		$excludeGroups = $this->params->get('excludeGroups', []) ?? [];

		if ($this->userInGroups($user, $excludeGroups))
		{
			return false;
		}

		// Do we have any user types defined in the plugin?
		$userTypes = (array) $this->params->get('types');

		if (empty($userTypes))
		{
			return false;
		}

		// Is the user already assigned in a user group defined in the user types?
		$alreadyAssigned = array_reduce($userTypes, function (bool $carry, object $typeDef) use ($user) {
			return $carry || $this->userInGroups($user, $typeDef->assign);
		}, false);

		if ($alreadyAssigned)
		{
			return false;
		}

		return true;
	}

	/**
	 * Does the user belong in any of the given user groups?
	 *
	 * @param   User|null   $user         The user to test.
	 * @param   array|null  $checkGroups  The user groups to check membership in.
	 *
	 * @return  bool  True if the user belongs to one or more groups
	 * @since   1.0.0
	 */
	private function userInGroups(?User $user, ?array $checkGroups): bool
	{
		if (empty($user) || empty($checkGroups))
		{
			return false;
		}

		$isNoGroup = array_reduce($checkGroups, function (bool $carry, int $v) {
			return $carry && ($v === 0);
		}, true);

		if ($isNoGroup)
		{
			return false;
		}

		$groups = $user->getAuthorisedGroups();

		return !empty(array_intersect($groups, $checkGroups));
	}

	/**
	 * Is the current option / view / task combination exempt from showing the user type selection page?
	 *
	 * We use this to allow captive logins, such as LoginGuard or Joomla User Privacy, to work with our plugin without
	 * causing an infinite redirection loop.
	 *
	 * @param   string|null  $option  The current component
	 * @param   string|null  $view    The current view
	 * @param   string|null  $task    The current task
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private function isExemptComponent(?string $option, ?string $view, ?string $task): bool
	{
		// If Joomla requires a password reset we should not try to redirect or it'll cause an infinite redirection loop
		if (Factory::getUser()->get('requireReset', 0))
		{
			return true;
		}

		$rawConfig = $this->params->get('exemptComponents', '');
		$rawConfig = trim($rawConfig);

		if (empty($rawConfig))
		{
			return false;
		}

		$rawConfig  = str_replace("\r\n", ',', $rawConfig);
		$rawConfig  = str_replace("\n", ',', $rawConfig);
		$rawConfig  = str_replace("\r", ',', $rawConfig);
		$configList = explode(',', $rawConfig);

		foreach ($configList as $configItem)
		{
			// Explode the item up to three levels deep (option.view.task)
			$explodedItem = explode('.', $configItem, 3);

			// Only an option was provided
			if (count($explodedItem) == 1)
			{
				$explodedItem[] = '*';
				$explodedItem[] = '*';
			}

			// Only an option and view was provided
			if (count($explodedItem) == 2)
			{
				$explodedItem[] = '*';
			}


			[$checkOption, $checkView, $checkTask] = $explodedItem;

			// If the option is not '*' and does not match the current one this is not a match; move on
			if (($checkOption != '*') && (strtolower($checkOption) != strtolower($option)))
			{
				continue;
			}

			// If the view is not '*' and does not match the current one this is not a match; move on
			if (($checkView != '*') && (strtolower($checkView) != strtolower($view)))
			{
				continue;
			}

			// If the task is not '*' and does not match the current one this is not a match; move on
			if (($checkTask != '*') && (strtolower($checkTask) != strtolower($task)))
			{
				continue;
			}

			// We have a match! We can return early.
			return true;
		}

		// No match found.
		return false;
	}
}
<?php
/**
 * @package		Joomla.Plugin
 * @subpackage	plg_content_setimages
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2013 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * Content - Set Images is free software. This version may have been modified
 * pursuant to the GNU General Public License, and as distributed it includes
 * or  is  derivative  of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

/**
 *
 * @version 3.9.25
 * @since 3.3.24
 */
class PlgContentSetimagesInstallerScript
{

	public function install ($parent)
	{
		// Enable plugin
		$db = JFactory::getDbo();
		$db->setQuery(
				$db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('enabled') . ' = 1')
					->where($db->qn('type') . ' = ' . $db->q('plugin'))
					->where($db->qn('folder') . ' = ' . $db->q('content'))
					->where($db->qn('element') . ' = ' . $db->q('setimages')))
			->execute();
	}
}

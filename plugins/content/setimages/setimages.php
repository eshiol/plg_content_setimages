<?php
/**
 * @version		3.6.26 plugins/content/setimages/setimages.php
 * 
 * @package		J2XML Set Images
 * @subpackage	plg_content_setimages
 * @since		2.5
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2013, 2017 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License 
 * or other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.folder');

use Joomla\Registry\Registry;

class plgContentSetimages extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param  object  $subject  The object to observe
	 * @param  array   $config   An array that holds the plugin configuration
	 */
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if ($this->params->get('debug') || defined('JDEBUG') && JDEBUG)
		{
			JLog::addLogger(array('text_file' => $this->params->get('log', 'eshiol.log.php'), 'extension' => 'plg_content_setimages_file'), JLog::ALL, array('plg_content_setimages'));
		}
		if (PHP_SAPI == 'cli')
		{
			JLog::addLogger(array('logger' => 'echo', 'extension' => 'plg_content_setimages'), JLOG::ALL & ~JLOG::DEBUG, array('plg_content_setimages'));
		}
		else
		{
			JLog::addLogger(array('logger' => 'messagequeue', 'extension' => 'plg_content_setimages'), JLOG::ALL & ~JLOG::DEBUG, array('plg_content_setimages'));
			if ($this->params->get('phpconsole'))
			{
				if (jimport('eshiol.core.logger.phpconsole'))
				{
					JLog::addLogger(['logger' => 'phpconsole', 'extension' => 'plg_content_setimages_phpconsole'],  JLOG::DEBUG, array('plg_content_setimages'));
				}
			}
		}
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_setimages'));
	}

	/**
	 * Before save content method
	 * Article is passed by value.
	 * Method is called right before the content is saved
	 *
	 * @param	string		The context of the content passed to the plugin (added in 1.6)
	 * @param	object		A JTableContent object
	 * @param	bool		If the content is just about to be created
	 * @since   2.5
	 */
	public function onContentBeforeSave($context, $article, $isNew)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_setimages'));
		JLog::add(new JLogEntry('context: '.$context, JLOG::DEBUG, 'plg_content_setimages'));

		$allowed = ['com_content.article', 'com_j2xml.article'];
		if (!in_array($context, $allowed))
		{
			return true;
		}

		if (!($images = json_decode($article->images)))
		{
			$images = new stdClass();
			$images->image_intro = '';
			$images->image_fulltext = '';
		}

		$intro_ok = false;
		if (!$images->image_fulltext)
		{
			if ($fulltext_mode = $this->params->get('image_fulltext', false))
			{
				if ($fulltext_mode == 3)
				{
					self::getImage($article->fulltext, $src, $title, $alt);
				}
				else if ($fulltext_mode == 2)
				{
					self::getImage($article->fulltext, $src, $title, $alt);
					if (!$src)
					{
						self::getImage($article->introtext, $src, $title, $alt);
						$intro_ok = true;
					}
				}
				else if ($fulltext_mode == 1)
				{
					self::getImage($article->introtext, $src, $title, $alt);
					$intro_ok = true;
				}

				if ($src)
				{
					$images->image_fulltext = $src;
					if ($float_fulltext = $this->params->get('float_fulltext', false))
					{
						$images->float_fulltext = $float_fulltext;
					}
					if ($this->params->get('image_fulltext_alt', false))
					{
						$images->image_fulltext_alt = $alt;
					}
					if ($this->params->get('image_fulltext_caption', false))
					{
						$images->image_fulltext_caption = $title;
					}
					JLog::add(new JLogEntry(JText::sprintf('PLG_CONTENT_SETIMAGES_MSG_IMAGE_FULLTEXT_SAVED', $images->image_fulltext), JLOG::INFO, 'plg_content_setimages'));
				}
			}
		}

		if (!$images->image_intro) 
		{
			if ($this->params->get('image_intro', false))
			{
				if ($intro_ok)
				{
					$images->image_intro = $images->image_fulltext;
					if ($float_intro = $this->params->get('float_intro', false))
					{
						$images->float_intro = $float_intro;
					}
					if ($this->params->get('image_intro_alt', false))
					{
						$images->image_intro_alt = $images->image_fulltext_alt;
					}
					if ($this->params->get('image_intro_caption', false))
					{
						$images->image_intro_caption = $images->image_fulltext_caption;
					}
				}
				else
				{
					self::getImage($article->introtext, $src, $title, $alt);
					$images->image_intro = $src;
					if ($float_intro = $this->params->get('float_intro', false))
					{
						$images->float_intro = $float_intro;
					}
					if ($this->params->get('image_intro_alt', false))
					{
						$images->image_intro_alt = $alt;
					}
					if ($this->params->get('image_intro_caption', false))
					{
						$images->image_intro_caption = $title;
					}
				}
				JLog::add(new JLogEntry(JText::sprintf('PLG_CONTENT_SETIMAGES_MSG_IMAGE_INTRO_SAVED', $images->image_intro), JLOG::INFO, 'plg_content_setimages'));
			}
		}

		if ($this->params->get('image_fulltext_force', false) && !$images->image_fulltext)
		{
			$images->image_fulltext = $images->image_intro;
			if ($float_fulltext = $this->params->get('float_fulltext', false))
			{
				$images->float_fulltext = $float_fulltext;
			}
			if ($this->params->get('image_fulltext_alt', false))
			{
				$images->image_fulltext_alt = $alt;
			}
			if ($this->params->get('image_fulltext_caption', false))
			{
				$images->image_fulltext_caption = $title;
			}
			JLog::add(new JLogEntry(JText::sprintf('PLG_CONTENT_SETIMAGES_MSG_IMAGE_FULLTEXT_SAVED', $images->image_fulltext), JLOG::INFO, 'plg_content_setimages'));
		}
		$article->images = json_encode($images);
		return true;
	}

	private static function getImage(&$text, &$src, &$title, &$alt)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_setimages'));

		preg_match('/<img(.*)>/i', $text, $matches);
		if (is_array($matches) && !empty($matches))
		{
			$text = preg_replace("/<img[^>]+\>/i","",$text,1);
			preg_match_all('/(src|alt|title)=("[^"]*")/i',$matches[0], $attr);
			for ($i = 0; $i < count($attr[0]); $i++)
			{
				$$attr[1][$i] = substr($attr[2][$i], 1, -1);
			}
		}
		JLog::add(new JLogEntry(print_r($attr, true), JLOG::DEBUG, 'plg_content_setimages'));
	}

	/**
	 * Method to convert image type to file extension
	 *
	 * @param	string		The image type
	 * @return	string		The file extension
	 * @since   2.5
	 */
	function get_extension($imagetype)
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_content_setimages'));

		if(empty($imagetype)) return false;
		switch($imagetype)
		{
			case 'image/bmp': return '.bmp';
			case 'image/cis-cod': return '.cod';
			case 'image/gif': return '.gif';
			case 'image/ief': return '.ief';
			case 'image/jpeg': return '.jpg';
			case 'image/pipeg': return '.jfif';
			case 'image/tiff': return '.tif';
			case 'image/x-cmu-raster': return '.ras';
			case 'image/x-cmx': return '.cmx';
			case 'image/x-icon': return '.ico';
			case 'image/x-portable-anymap': return '.pnm';
			case 'image/x-portable-bitmap': return '.pbm';
			case 'image/x-portable-graymap': return '.pgm';
			case 'image/x-portable-pixmap': return '.ppm';
			case 'image/x-rgb': return '.rgb';
			case 'image/x-xbitmap': return '.xbm';
			case 'image/x-xpixmap': return '.xpm';
			case 'image/x-xwindowdump': return '.xwd';
			case 'image/png': return '.png';
			case 'image/x-jps': return '.jps';
			case 'image/x-freehand': return '.fh';
			default: return false;
		}
	}
}
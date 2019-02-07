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

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.folder');

use Joomla\Registry\Registry;

/**
 *
 * @version 3.9.25
 * @since 2.5
 */
class plgContentSetimages extends JPlugin
{

	/**
	 * Load the language file on instantiation.
	 *
	 * @var boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param object $subject
	 *        	The object to observe
	 * @param array $config
	 *        	An array that holds the plugin configuration
	 */
	function __construct (&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		if ($this->params->get('debug') || defined('JDEBUG') && JDEBUG)
		{
			JLog::addLogger(
					array(
							'text_file' => $this->params->get('log', 'eshiol.log.php'),
							'extension' => 'plg_content_setimages_file'
					), JLog::ALL, array(
							'plg_content_setimages'
					));
		}
		if (PHP_SAPI == 'cli')
		{
			JLog::addLogger(array(
					'logger' => 'echo',
					'extension' => 'plg_content_setimages'
			), JLog::ALL & ~ JLog::DEBUG, array(
					'plg_content_setimages'
			));
		}
		else
		{
			JLog::addLogger(
					array(
							'logger' => (null !== $this->params->get('logger')) ? $this->params->get('logger') : 'messagequeue',
							'extension' => 'plg_content_setimages'
					), JLog::ALL & ~ JLog::DEBUG, array(
							'plg_content_setimages'
					));
			if ($this->params->get('phpconsole') && class_exists('JLogLoggerPhpconsole'))
			{
				JLog::addLogger(array(
						'logger' => 'phpconsole',
						'extension' => 'plg_content_setimages_phpconsole'
				), JLog::DEBUG, array(
						'plg_content_setimages'
				));
			}
		}
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_content_setimages'));
	}

	/**
	 * Before save content method
	 * Article is passed by value.
	 * Method is called right before the content is saved
	 *
	 * @param
	 *        	string The context of the content passed to the plugin (added
	 *        	in 1.6)
	 * @param
	 *        	object A JTableContent object
	 * @param
	 *        	bool If the content is just about to be created
	 * @since 2.5
	 */
	public function onContentBeforeSave ($context, $article, $isNew)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_content_setimages'));
		
		if (! in_array($context, array(
				'com_content.article',
				'com_content.form'
		)))
		{
			return;
		}
		
		if (! ($images = json_decode($article->images)))
		{
			$images = new stdClass();
			$images->image_intro = '';
			$images->image_fulltext = '';
		}
		
		$intro_ok = false;
		if (! $images->image_fulltext)
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
					if (! $src)
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
					JLog::add(new JLogEntry(JText::sprintf('PLG_CONTENT_SETIMAGES_MSG_IMAGE_FULLTEXT_SAVED', $images->image_fulltext)), JLog::INFO,
							'plg_content_setimages');
				}
			}
		}
		
		if (! $images->image_intro)
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
				JLog::add(new JLogEntry(JText::sprintf('PLG_CONTENT_SETIMAGES_MSG_IMAGE_INTRO_SAVED', $images->image_intro)), JLog::INFO,
						'plg_content_setimages');
			}
		}
		
		if ($this->params->get('image_fulltext_force', false) && ! $images->image_fulltext)
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
			JLog::add(new JLogEntry(JText::sprintf('PLG_CONTENT_SETIMAGES_MSG_IMAGE_FULLTEXT_SAVED', $images->image_fulltext)), JLog::INFO,
					'plg_content_setimages');
		}
		$article->images = json_encode($images);
		
		if ($this->params->get('p', false))
		{
			$re = '/<p[^>]*>[\x00-\x1F\x7F\s]*<\/p[^>]*>/mi';
			$article->introtext = preg_replace($re, '', $article->introtext);
			$article->fulltext = preg_replace($re, '', $article->fulltext);
		}
		
		return true;
	}

	/**
	 * Get the first image from text and remove it
	 *
	 * @param string $text
	 *        	the string to search in
	 * @param string $src
	 *        	the URL of the image
	 * @param string $title
	 *        	the extra information about the image
	 * @param string $alt
	 *        	the alternate text for the image
	 */
	private static function getImage (&$text, &$src, &$title, &$alt)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_content_setimages'));
		
		$pattern = (strpos($text, '<figure>') === false) || strpos($text, '<img ') < strpos($text, '<figure>') ? '/<img (.*)[\/]>/is' : '/<figure>.*<img(.*)[\/]>.*<\/figure>/is';
		JLog::add(new JLogEntry('pattern: ' . $pattern, JLog::DEBUG, 'plg_content_setimages'));
		preg_match($pattern, $text, $matches);
		JLog::add(new JLogEntry(print_r($matches, true), JLog::DEBUG, 'plg_content_setimages'));
		
		if (is_array($matches) && ! empty($matches))
		{
			$text = preg_replace($pattern, '', $text, 1);
			preg_match_all('/(src|alt|title)=("[^"]*")/i', $matches[0], $attr);
			JLog::add(new JLogEntry(print_r($attr, true), JLog::DEBUG, 'plg_content_setimages'));
			
			for ($i = 0; $i < count($attr[0]); $i ++)
			{
				JLog::add(new JLogEntry($attr[1][$i] . ' = ' . substr($attr[2][$i], 1, - 1), JLog::DEBUG, 'plg_content_setimages'));
				${$attr[1][$i]} = substr($attr[2][$i], 1, - 1);
			}
		}
	}
}

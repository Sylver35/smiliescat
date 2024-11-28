<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2024 Sylver35  https://breizhcode.com
* @license		https://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use sylver35\smiliescat\core\diffusion;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\template\template;
use phpbb\language\language;

class listener implements EventSubscriberInterface
{
	/* @var \sylver35\smiliescat\core\diffusion */
	protected $diffusion;

	/** @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	 * Constructor
	 */
	public function __construct(diffusion $diffusion, config $config, helper $helper, template $template, language $language, $root_path)
	{
		$this->diffusion = $diffusion;
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->language = $language;
		$this->root_path = $root_path;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.user_setup'					=> 'load_language_on_setup',
			'core.page_header'					=> 'add_page_header',
			'core.posting_modify_template_vars'	=> 'add_categories',
			'breizhshoutbox.smilies'			=> 'smilies',
			'breizhshoutbox.smilies_popup'		=> 'smilies_popup',
		];
	}

	/**
	 * Load language files during user setup
	 *
	 * @param array $event
	 *
	 * @return void
	 * @access public
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'sylver35/smiliescat',
			'lang_set' => ['smilies_category'],
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Add the category popup link
	 *
	 * @return void
	 * @access public
	 */
	public function add_page_header()
	{
		$this->diffusion->url_to_page();
	}

	/**
	 * Add the Smilies Categories on posting form
	 *
	 * @return void
	 * @access public
	 */
	public function add_categories($event)
	{
		$this->diffusion->cats_to_posting_form($event);
	}

	/**
	 * @param array $event
	 *
	 * @return void
	 * @access public
	 */
	public function smilies($event)
	{
		$event['content'] = array_merge($event['content'], [
			'title_cat'		=> $this->language->lang('ACP_SC_SMILIES'),
			'categories'	=> $this->diffusion->list_cats(false),
		]);
	}

	/**
	 * @param array $event
	 *
	 * @return void
	 * @access public
	 */
	public function smilies_popup($event)
	{
		$cat = (int) $event['cat'];
		$start = (int) $event['start'];
		$event['content'] = array_merge($event['content'], [
			'title_cat'		=> $this->language->lang('ACP_SC_SMILIES'),
			'categories'	=> $this->diffusion->list_cats($cat),
		]);

		$list = $this->diffusion->smilies_popup($cat, $start);
		if ($list['in_cat'] !== false)
		{
			$event['content'] = array_merge($event['content'], [
				'in_cat'		=> $list['in_cat'],
				'total'			=> $list['total'],
				'cat'			=> $list['cat'],
				'smilies'		=> $list['smilies'],
				'emptyRow'		=> $list['emptyRow'],
				'title'			=> $list['title'],
				'start'			=> $list['start'],
				'pagination'	=> $list['pagination'],
			]);
		}
	}
}

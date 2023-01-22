<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use sylver35\smiliescat\core\category;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\template\template;

class listener implements EventSubscriberInterface
{
	/* @var \sylver35\smiliescat\core\category */
	protected $category;

	/** @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/**
	 * Constructor
	 */
	public function __construct(category $category, config $config, helper $helper, template $template)
	{
		$this->category = $category;
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.user_setup'				=> 'load_language_on_setup',
			'core.page_header'				=> 'add_page_header',
			'breizhshoutbox.smilies'		=> 'shoutbox_smilies',
			'breizhshoutbox.smilies_popup'	=> 'shoutbox_smilies_popup',
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
		$this->template->assign_var('U_CATEGORY_POPUP', $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => $this->config['smilies_category_nb']]));
	}

	/**
	 * @param array $event
	 *
	 * @return void
	 * @access public
	 */
	public function shoutbox_smilies($event)
	{
		$this->category->shoutbox_smilies($event);
	}

	/**
	 * @param array $event
	 *
	 * @return void
	 * @access public
	 */
	public function shoutbox_smilies_popup($event)
	{
		$this->category->shoutbox_smilies($event);
		$this->category->shoutbox_smilies_popup($event);
	}
}

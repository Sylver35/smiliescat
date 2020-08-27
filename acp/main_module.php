<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\acp;

class main_module
{
	/** @var string */
	public $u_action;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $page_title;

	/**
	 * @param int		$id
	 * @param string	$mode
	 *
	 * @return void
	 * @access public
	 */
	public function main(/** @scrutinizer ignore-unused */$id, $mode)
	{
		global $phpbb_container;

		/** @type \phpbb\language\language $language Language object */
		$language = $phpbb_container->get('language');
		/** @type \phpbb\template\template $template Template object */
		$template = $phpbb_container->get('template');
		/** @type \sylver35\smiliescat\controller\admin_controller $admin_controller */
		$admin_controller = $phpbb_container->get('sylver35.smiliescat.admin.controller');
		/** @type \sylver35\smiliescat\core\category $category */
		$category = $phpbb_container->get('sylver35.smiliescat.category');
		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		$language->add_lang('smilies_category', 'sylver35/smiliescat');
		$this->tpl_name = 'category_' . $mode;
		$this->page_title = 'ACP_SC_' . strtoupper($mode);
		$meta = $category->get_version();

		switch ($mode)
		{
			case 'config':
				$admin_controller->acp_categories_config();
			break;

			case 'smilies':
				$admin_controller->acp_smilies_category();
			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			/* No break here */
		}

		$template->assign_vars(array(
			'U_ACTION'			=> $this->u_action,
			'TITLE'				=> $language->lang($this->page_title),
			'TITLE_EXPLAIN'		=> $language->lang($this->page_title . '_EXPLAIN'),
			'CATEGORY_VERSION'	=> $language->lang('SC_VERSION_COPY', $meta['homepage'], $meta['version']),
		));
	}
}

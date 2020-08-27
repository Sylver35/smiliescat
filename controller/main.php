<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\controller;

use sylver35\smiliescat\core\category;
use phpbb\request\request;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface as db;
use phpbb\template\template;
use phpbb\user;
use phpbb\language\language;
use phpbb\pagination;

class main
{
	/* @var \sylver35\smiliescat\core\category */
	protected $category;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\pagination */
	protected $pagination;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, request $request, config $config, helper $helper, db $db, template $template, user $user, language $language, pagination $pagination, $smilies_category_table)
	{
		$this->category = $category;
		$this->request = $request;
		$this->config = $config;
		$this->helper = $helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->pagination = $pagination;
		$this->smilies_category_table = $smilies_category_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function popup_smilies_category()
	{
		$start = (int) $this->request->variable('start', 0);
		$cat = (int) $this->request->variable('select', $this->config['smilies_category_nb']);
		$count = (int) $this->category->smilies_count($cat);
		$title = $this->category->extract_list_categories($cat);
		$data = $this->category->get_version();
		$url = $this->helper->route('sylver35_smiliescat_smilies_pop');

		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> '*',
			'FROM'		=> array(SMILIES_TABLE => ''),
			'WHERE'		=> 'category = ' . $cat,
			'ORDER_BY'	=> 'smiley_order ASC',
		));
		$result = $this->db->sql_query_limit($sql, (int) $this->config['smilies_per_page_cat'], $start);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('smilies', array(
				'SMILEY_CODE'		=> $row['code'],
				'SMILEY_EMOTION'	=> $row['emotion'],
				'SMILEY_WIDTH'		=> $row['smiley_width'],
				'SMILEY_HEIGHT'		=> $row['smiley_height'],
				'SMILEY_SRC'		=> generate_board_url() . '/' . $this->config['smilies_path'] . '/' . $row['smiley_url'],
			));
		}
		$this->db->sql_freeresult($result);

		$start = $this->pagination->validate_start($start, (int) $this->config['smilies_per_page_cat'], $count);
		$this->pagination->generate_template_pagination("{$url}?select={$cat}", 'pagination', 'start', $count, (int) $this->config['smilies_per_page_cat'], $start);

		$this->template->assign_vars(array(
			'U_SELECT_CAT'		=> $url,
			'LIST_CATEGORY'		=> $this->category->select_categories($cat, false, true),
			'POPUP_TITLE'		=> $this->language->lang('SC_CATEGORY_IN', $title),
			'SC_VERSION'		=> $this->language->lang('SC_VERSION_COPY', $data['homepage'], $data['version']),
		));

		page_header($this->language->lang('SC_CATEGORY_IN', $title));

		$this->template->set_filenames(array(
			'body' => '@sylver35_smiliescat/smilies_category.html')
		);

		page_footer();
	}
}

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
use phpbb\path_helper;
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

	/* @var \phpbb\path_helper */
	protected $path_helper;

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

	/** @var string phpBB root path */
	protected $root_path;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, request $request, config $config, helper $helper, path_helper $path_helper, db $db, template $template, user $user, language $language, pagination $pagination, $root_path, $smilies_category_table)
	{
		$this->category = $category;
		$this->request = $request;
		$this->config = $config;
		$this->helper = $helper;
		$this->path_helper = $path_helper;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->pagination = $pagination;
		$this->root_path = $root_path;
		$this->smilies_category_table = $smilies_category_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function popup_smilies_category()
	{
		$start = $this->request->variable('start', 0);
		$cat = $this->request->variable('select', -1);
		$cat = ($cat == -1) ? $this->config['smilies_category_nb'] : $cat;
		$count = $this->category->smilies_count($cat);
		$url = $this->helper->route('sylver35_smiliescat_smilies_pop');

		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'smiley_url, MIN(smiley_id) AS smiley_id, MIN(code) AS code, MIN(smiley_order) AS min_smiley_order, MIN(smiley_width) AS smiley_width, MIN(smiley_height) AS smiley_height, MIN(emotion) AS emotion',
			'FROM'		=> array(SMILIES_TABLE => ''),
			'WHERE'		=> "category = $cat",
			'GROUP_BY'	=> 'smiley_url',
			'ORDER_BY'	=> 'min_smiley_order ASC',
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

		$title = $this->category->extract_categories($cat);
		$data = $this->category->get_version();
		$this->template->assign_vars(array(
			'U_SELECT_CAT'		=> $url,
			'LIST_CATEGORY'		=> $this->category->select_categories($cat),
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

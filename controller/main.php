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
		$this->category_table = $smilies_category_table;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function popup_smilies_category()
	{
		$title	= '';
		$cat_id	= $cat_order = $i = 0;
		$start	= $this->request->variable('start', 0);
		$cat	= $this->request->variable('select', -1);
		$cat	= ($cat == -1) ? $this->config['smilies_category_nb'] : $cat;
		$max_id	= $this->category->get_max_id();
		$count	= $this->category->smilies_count($cat);
		$url	= $this->helper->route('sylver35_smiliescat_smilies_pop');
		$lang	= $this->user->lang_name;
		$root_path = (defined('PHPBB_USE_BOARD_URL_PATH') && PHPBB_USE_BOARD_URL_PATH) ? generate_board_url() . '/' : $this->path_helper->get_web_root_path();

		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'smiley_url, MIN(smiley_id) AS smiley_id, MIN(code) AS code, MIN(smiley_order) AS min_smiley_order, MIN(smiley_width) AS smiley_width, MIN(smiley_height) AS smiley_height, MIN(emotion) AS emotion',
			'FROM'		=> array(SMILIES_TABLE => ''),
			'WHERE'		=> "category = $cat",
			'GROUP_BY'	=> 'smiley_url',
			'ORDER_BY'	=> 'min_smiley_order ASC',
		));
		$result = $this->db->sql_query_limit($sql, $this->config['smilies_per_page_cat'], $start);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('smilies', array(
				'SMILEY_CODE'		=> $row['code'],
				'SMILEY_EMOTION'	=> $row['emotion'],
				'SMILEY_WIDTH'		=> $row['smiley_width'],
				'SMILEY_HEIGHT'		=> $row['smiley_height'],
				'SMILEY_SRC'		=> $root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
			));
		}
		$this->db->sql_freeresult($result);

		$start = $this->pagination->validate_start($start, $this->config['smilies_per_page_cat'], $count);
		$this->pagination->generate_template_pagination("{$url}?select={$cat}", 'pagination', 'start', $count, $this->config['smilies_per_page_cat'], $start);

		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> '*',
			'FROM'		=> array($this->category_table => ''),
			'WHERE'		=> "cat_lang = '$lang'",
			'ORDER_BY'	=> 'cat_order ASC',
		));
		$result = $this->db->sql_query($sql, 3600);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('categories', array(
				'CLASS'			=> ($cat == $row['cat_id']) ? 'cat-active' : 'cat-inactive',
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CAT_ID'		=> $row['cat_id'],
				'CAT_ORDER'		=> $row['cat_order'],
				'CAT_NAME'		=> $row['cat_name'],
				'CAT_NB'		=> $row['cat_nb'],
			));
			$i++;
			$title = ($row['cat_id'] == $cat) ? $row['cat_name'] : $title;
			$cat_order = $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category
		$unclassified = $this->language->lang('SC_CATEGORY_DEFAUT');
		$this->template->assign_block_vars('categories', array(
			'CLASS'			=> ($cat == $cat_id) ? 'cat-active' : 'cat-inactive',
			'SEPARATE'		=> ($i > 0) ? ' - ' : '',
			'CAT_ID'		=> $cat_id,
			'CAT_ORDER'		=> $cat_order + 1,
			'CAT_NAME'		=> $unclassified,
			'CAT_NB'		=> $this->category->smilies_count($cat_id),
		));
		$title = ($cat == $cat_id) ? $unclassified : $title;

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

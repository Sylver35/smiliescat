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
	public function __construct(category $category, request $request, config $config, helper $helper, db $db, template $template, user $user, language $language, pagination $pagination, $root_path, $smilies_category_table)
	{
		$this->category			= $category;
		$this->request			= $request;
		$this->config			= $config;
		$this->helper			= $helper;
		$this->db 				= $db;
		$this->template			= $template;
		$this->user				= $user;
		$this->language			= $language;
		$this->pagination		= $pagination;
		$this->root_path		= $root_path;
		$this->category_table	= $smilies_category_table;
	}

	/**
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/

	public function popup_smilies_category()
	{
		$this->user->setup('posting');
		$start	= $this->request->variable('start', 0);
		$cat	= $this->request->variable('select', -1);
		$max_id	= $this->category->get_max_id();
		$cat	= ($cat == -1) ? $this->category->get_first_order() : $cat;
		$count	= $this->category->smilies_count($cat);
		$select	= $this->category->select_categories($cat);
		$data	= $this->category->get_version();
		$url	= $this->helper->route('sylver35_smiliescat_controller_smilies_pop');
		$lang	= $this->user->lang_name;
		$title	= '';
		$cat_order = 0;

		if ($cat !== '')
		{
			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> 's.smiley_id, s.smiley_url, s.code, s.smiley_order, s.emotion, s.smiley_width, s.smiley_height, s.category, c.cat_lang_id, c.cat_id , c.cat_order, c.cat_lang , c.cat_name , c.cat_title, c.cat_nb',
				'FROM'		=> array(SMILIES_TABLE => 's'),
				'LEFT_JOIN'	=> array(
					array(
						'FROM'	=> array($this->category_table => 'c'),
						'ON'	=> "c.cat_id = s.category AND c.cat_lang = '$lang'"
					),
				),
				'WHERE'		=> "s.category = $cat",
				'GROUP_BY'	=> 's.emotion, s.smiley_id',
				'ORDER_BY'	=> 's.smiley_order ASC',
			));
			$result = $this->db->sql_query_limit($sql, $this->config['smilies_per_page'], $start);
			if ($row = $this->db->sql_fetchrow($result))
			{
				do
				{
					$this->template->assign_block_vars('smilies', array(
						'SMILEY_CODE'		=> $row['code'],
						'SMILEY_EMOTION'	=> $row['emotion'],
						'SMILEY_WIDTH'		=> $row['smiley_width'],
						'SMILEY_HEIGHT'		=> $row['smiley_height'],
						'CATEGORY'			=> $row['cat_name'],
						'SMILEY_SRC'		=> $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
					));
					$title = $row['cat_name'];
				} while ($row = $this->db->sql_fetchrow($result));
			}
			$this->db->sql_freeresult($result);

			$start = $this->pagination->validate_start($start, $this->config['smilies_per_page'], $count);
			$this->pagination->generate_template_pagination($url, 'pagination', 'start', $count, $this->config['smilies_per_page'], $start);

			$i = 0;
			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> '*',
				'FROM'		=> array($this->category_table => ''),
				'WHERE'		=> "cat_lang = '$lang'",
				'ORDER_BY'	=> 'cat_order ASC',
			));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->template->assign_block_vars('categories', array(
					'CLASS'			=> ($row['cat_id'] == $cat) ? 'cat-active' : 'cat-inactive',
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
		}

		// Add the Unclassified category
		$this->template->assign_block_vars('categories', array(
			'CLASS'			=> ($cat == 0) ? 'cat-active' : 'cat-inactive',
			'SEPARATE'		=> ($i > 0) ? ' - ' : '',
			'CAT_ID'		=> 0,
			'CAT_ORDER'		=> $cat_order + 1,
			'CAT_NAME'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
			'CAT_NB'		=> $this->category->smilies_count_defaut(),
		));
		$title = ($cat == 0) ? $this->language->lang('SC_CATEGORY_DEFAUT') : $title;

		$this->template->assign_vars(array(
			'U_SELECT_CAT'		=> $url,
			'LIST_CATEGORY'		=> $select['select'],
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

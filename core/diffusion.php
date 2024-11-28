<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2024 Sylver35  https://breizhcode.com
 * @license		https://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace sylver35\smiliescat\core;

use sylver35\smiliescat\core\category;
use phpbb\db\driver\driver_interface as db;
use phpbb\config\config;
use phpbb\user;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\controller\helper;

class diffusion
{
	/* @var \sylver35\smiliescat\core\category */
	protected $category;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, db $db, config $config, user $user, language $language, template $template, helper $helper, $smilies_category_table)
	{
		$this->category = $category;
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->helper = $helper;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function url_to_page()
	{
		$first = $this->category->get_first_order();
		$this->template->assign_var('U_CATEGORY_POPUP', $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => $first['first']]));
	}

	public function cats_to_posting_form($event)
	{
		if (in_array($event['mode'], ['post', 'reply', 'edit', 'quote']))
		{
			$first = $this->category->get_first_order();
			$this->template->assign_vars([
				'U_CATEGORY_AJAX'	=> $this->helper->route('sylver35_smiliescat_ajax_smilies'),
				'ID_FIRST_CAT'		=> $first['cat_nb'],
				'PER_PAGE'			=> $this->config['smilies_per_page_cat'],
				'U_SMILIES_PATH'	=> generate_board_url() . '/' . $this->config['smilies_path'] . '/',
				'IN_CATEGORIES'		=> true,
			]);
		}
	}

	public function list_cats($cat)
	{
		$i = 0;
		$cat_order = 1;
		$list_cat = [];
		$lang = (string) $this->user->lang_name;

		$sql = 'SELECT * 
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			// Select only non-empty categories
			if ($row['cat_nb'])
			{
				$list_cat[$i] = [
					'cat_id'		=> (int) $row['cat_id'],
					'cat_order'		=> (int) $row['cat_order'],
					'cat_nb'		=> (int) $row['cat_nb'],
					'cat_name'		=> (string) $row['cat_name'],
					'css'			=> ($row['cat_id'] == $cat) ? 'cat-active' : 'cat-inactive',
				];
				$i++;
			}
			// Keep this value in memory
			$cat_order = (int) $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->category->smilies_count(0))
		{
			$list_cat[$i] = [
				'cat_id'		=> 0,
				'cat_order'		=> $cat_order,
				'cat_nb'		=> $nb,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'css'			=> ($cat == 0) ? 'cat-active' : 'cat-inactive',
			];
		}

		return $list_cat;
	}

	public function smilies_popup($cat, $start)
	{
		if ($cat !== -1)
		{
			$i = 0;
			$smilies = [];
			$pagin = (int) $this->config['shout_smilies_per_page'];

			$sql = [
				'SELECT'	=> 'smiley_url, smiley_id, code, smiley_order, emotion, smiley_width, smiley_height',
				'FROM'		=> [SMILIES_TABLE => ''],
				'WHERE'		=> "category = $cat",
				'ORDER_BY'	=> 'smiley_order ASC',
			];
			$result = $this->db->sql_query_limit($this->db->sql_build_query('SELECT', $sql), $pagin, $start);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$smilies[$i] = [
					'nb'		=> (int) $i,
					'code'		=> (string) $row['code'],
					'emotion'	=> (string) $row['emotion'],
					'image'		=> (string) $row['smiley_url'],
					'width'		=> (int) $row['smiley_width'],
					'height'	=> (int) $row['smiley_height'],
				];
				$i++;
			}
			$this->db->sql_freeresult($result);

			return [
				'in_cat'		=> true,
				'total'			=> $i,
				'cat'			=> $cat,
				'smilies'		=> $smilies,
				'emptyRow'		=> ($i === 0) ? $this->language->lang('SC_SMILIES_EMPTY_CATEGORY') : '',
				'title'			=> $this->language->lang('SC_CATEGORY_IN', '<span class="cat-title">' . $this->category->cat_name($cat) . '</span>'),
				'start'			=> $start,
				'pagination'	=> $this->category->smilies_count($cat),
			];
		}

		return ['in_cat' => false];
	}
}

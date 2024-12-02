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
use phpbb\log\log;

class diffusion
{
	private const DEFAULT_CAT = 9998;
	private const NOT_DISPLAY = 9999;

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

	/** @var \phpbb\log\log */
	protected $log;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, db $db, config $config, user $user, language $language, template $template, helper $helper, log $log, $smilies_category_table)
	{
		$this->category = $category;
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->helper = $helper;
		$this->log = $log;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function url_to_page()
	{
		$this->template->assign_var('U_CATEGORY_POPUP', $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => $this->config['smilies_first_cat']]));
	}

	public function cats_to_posting_form($event)
	{
		if (in_array($event['mode'], ['post', 'reply', 'edit', 'quote', 'rules']))
		{
			$this->template->assign_vars([
				'U_CATEGORY_AJAX'	=> $this->helper->route('sylver35_smiliescat_ajax_smilies'),
				'ID_FIRST_CAT'		=> $this->config['smilies_first_cat'],
				'NB_FIRST_CAT'		=> $this->category->smilies_count($this->config['smilies_first_cat']),
				'PER_PAGE'			=> $this->config['smilies_per_page_cat'],
				'U_SMILIES_PATH'	=> generate_board_url() . '/' . $this->config['smilies_path'] . '/',
				'IN_CATEGORIES'		=> true,
			]);
		}
	}

	public function delete_categories_lang($lang)
	{
		$i = 0;
		$list = [];
		$sql = 'SELECT cat_name
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list[$i] = $row['cat_name'];
			$i++;
		}
		$list = implode(', ', $list);
		
		$this->db->sql_query('DELETE FROM ' . $this->smilies_category_table . " WHERE cat_lang = '$lang'");
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_DELETE_CAT_LANG', time(), [$lang, $list]);
	}

	public function list_cats($cat)
	{
		$i = 0;
		$list_cat = [];
		$lang = (string) $this->user->lang_name;

		$sql = 'SELECT * 
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang' AND cat_nb > 0
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list_cat[$i] = [
				'cat_id'		=> (int) $row['cat_id'],
				'cat_nb'		=> (int) $row['cat_nb'],
				'cat_name'		=> (string) $row['cat_name'],
				'css'			=> ($row['cat_id'] == $cat) ? 'cat-active' : 'cat-inactive',
			];
			$i++;
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->category->smilies_count(self::DEFAULT_CAT))
		{
			$list_cat[$i] = [
				'cat_id'		=> self::DEFAULT_CAT,
				'cat_nb'		=> $nb,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'css'			=> ($cat == self::DEFAULT_CAT) ? 'cat-active' : 'cat-inactive',
			];
			$i++;
		}

		return ['list_cat' => $list_cat, 'nb_cats' => $i];
	}

	public function smilies_popup($cat, $start)
	{
		if ($cat)
		{
			$i = 0;
			$smilies = [];
			$pagin = (int) $this->config['shout_smilies_per_page'];

			$sql = [
				'SELECT'	=> 'smiley_id, smiley_url, code, smiley_order, emotion, smiley_width, smiley_height',
				'FROM'		=> [SMILIES_TABLE => ''],
				'WHERE'		=> 'display_on_cat = 1 AND category = ' . $cat,
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
				'title'			=> $this->language->lang('SC_CATEGORY_IN', '<span class="cat-title">' . $this->category->return_name($cat, '', true) . '</span>'),
				'start'			=> $start,
				'pagination'	=> $this->category->smilies_count($cat),
			];
		}

		return ['in_cat' => false];
	}

	public function extract_list_categories($cat)
	{
		$title = '';
		$i = 0;
		$lang = (string) $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> '*',
			'FROM'		=> [$this->smilies_category_table => ''],
			'WHERE'		=> "cat_lang = '$lang' AND cat_nb > 0",
			'ORDER_BY'	=> 'cat_order ASC',
		]);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$actual_cat = $row['cat_id'] == $cat;
			$this->template->assign_block_vars('categories', [
				'CAT_ID'		=> $row['cat_id'],
				'CAT_NAME'		=> $row['cat_name'] ?: $row['cat_title'],
				'CAT_NB'		=> $row['cat_nb'],
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CLASS'			=> $actual_cat ? 'cat-active' : 'cat-inactive',
				'U_CAT'			=> $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => $row['cat_id']]),
			]);
			$i++;

			// Keep these values in memory
			$title = $actual_cat ? $row['cat_name'] : $title;
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->category->smilies_count(self::DEFAULT_CAT))
		{
			$title = ($cat == self::DEFAULT_CAT) ? $this->language->lang('SC_CATEGORY_DEFAUT') : $title;
			$this->template->assign_block_vars('categories', [
				'CAT_ID'		=> self::DEFAULT_CAT,
				'CAT_NAME'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'CAT_NB'		=> $nb,
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CLASS'			=> ($cat === self::DEFAULT_CAT) ? 'cat-active' : 'cat-inactive',
				'U_CAT'			=> $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => self::DEFAULT_CAT]),
			]);
		}

		return $title;
	}

	public function category_smilies()
	{
		$this->extract_list_categories($this->config['smilies_first_cat']);
		$this->template->assign_vars([
			'U_CATEGORY_AJAX'	=> $this->helper->route('sylver35_smiliescat_ajax_smilies'),
			'ID_FIRST_CAT'		=> $this->config['smilies_first_cat'],
			'NB_FIRST_CAT'		=> $this->category->smilies_count($this->config['smilies_first_cat']),
			'PER_PAGE'			=> $this->config['smilies_per_page_cat'],
			'U_SMILIES_PATH'	=> generate_board_url() . '/' . $this->config['smilies_path'] . '/',
			'CATEGORY'			=> $this->extract_first_cat(),
			'IN_CATEGORIES'		=> true,
		]);
	}

	private function extract_first_cat()
	{
		$lang = $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> 's.*, c.*',
			'FROM'		=> [SMILIES_TABLE => 's'],
			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [$this->smilies_category_table => 'c'],
					'ON'	=> "c.cat_id = s.category AND c.cat_lang = '$lang'",
				],
			],
			'WHERE'		=> 's.category = ' . $this->config['smilies_first_cat'],
			'ORDER_BY'	=> 's.smiley_order ASC',
		]);
		$result = $this->db->sql_query_limit($sql, (int) $this->config['smilies_per_page'], 0);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('items', [
				'SMILEY_SRC'		=> $row['smiley_url'],
				'SMILEY_WIDTH'		=> $row['smiley_width'],
				'SMILEY_HEIGHT'		=> $row['smiley_height'],
				'SMILEY_ID'			=> $row['smiley_id'],
				'CAT_ID'			=> $row['category'],
				'SMILEY_CODE'		=> $row['code'],
				'SMILEY_EMOTION'	=> $row['emotion'],
				'CATEGORY'			=> $row['cat_name'],
			]);
			$category = $row['cat_name'];
		}
		$this->db->sql_freeresult($result);
		
		return $this->language->lang('SC_CATEGORY_IN', $category);
	}
}

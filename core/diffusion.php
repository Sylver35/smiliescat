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

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, db $db, config $config, user $user, language $language, $smilies_category_table)
	{
		$this->category = $category;
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function list_cats($cat)
	{
		$i = $cat_order = 0;
		$list_cat = [];
		$lang = (string) $this->user->lang_name;

		$sql = 'SELECT * 
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			// Choose only non-empty categories
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
		if ($nb = $this->category->smilies_count(0, true))
		{
			$list_cat[$i] = [
				'cat_id'		=> 0,
				'cat_order'		=> $cat_order + 1,
				'cat_nb'		=> $nb,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'css'			=> ($cat == 0) ? 'cat-active' : 'cat-inactive',
			];
		}

		return $list_cat;
	}

	public function get_cat_name($cat)
	{
		$cat_name = $this->language->lang('SC_CATEGORY_DEFAUT');
		if ($cat > 0)
		{
			$lang = (string) $this->user->lang_name;
			$sql = 'SELECT cat_name
				FROM ' . $this->smilies_category_table . "
					WHERE cat_lang = '$lang'
					AND cat_id = $cat";
			$result = $this->db->sql_query_limit($sql, 1);
			$row = $this->db->sql_fetchrow($result);
			$cat_name = $row['cat_name'];
			$this->db->sql_freeresult($result);
		}

		return $cat_name;
	}

	public function smilies_popup($cat, $start)
	{
		if ($cat !== -1)
		{
			$i = 0;
			$smilies = [];
			$cat_name = $this->get_cat_name($cat);
			$pagin = (int) $this->config['shout_smilies_per_page'];

			$sql = [
				'SELECT'	=> 'smiley_url, MIN(smiley_id) AS smiley_id, MIN(code) AS code, MIN(smiley_order) AS min_smiley_order, MIN(smiley_width) AS smiley_width, MIN(smiley_height) AS smiley_height, MIN(emotion) AS emotion',
				'FROM'		=> [SMILIES_TABLE => ''],
				'WHERE'		=> "category = $cat",
				'GROUP_BY'	=> 'smiley_url',
				'ORDER_BY'	=> 'min_smiley_order ASC',
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
				'title'			=> $this->language->lang('SC_CATEGORY_IN', '<span class="cat-title">' . $cat_name . '</span>'),
				'start'			=> $start,
				'pagination'	=> $this->category->smilies_count($cat),
			];
		}

		return ['in_cat' => false];
	}
}

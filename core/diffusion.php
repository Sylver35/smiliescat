<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
 * @license		https://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace sylver35\smiliescat\core;

use sylver35\smiliescat\core\smiley;
use phpbb\db\driver\driver_interface as db;
use phpbb\user;
use phpbb\language\language;

class diffusion
{
	/* @var \sylver35\smiliescat\core\smiley */
	protected $smiley;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

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
	public function __construct(smiley $smiley, db $db, user $user, language $language, $smilies_category_table)
	{
		$this->smiley = $smiley;
		$this->db = $db;
		$this->user = $user;
		$this->language = $language;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function smilies($event)
	{
		$i = $cat_order = 0;
		$list_cat = [];
		$lang = $this->user->lang_name;

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
					'cat_name'		=> (string) $row['cat_name'],
					'cat_nb'		=> (int) $row['cat_nb'],
				];
				$i++;
			}
			// Keep this value in memory
			$cat_order = (int) $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->smiley->smilies_count(0, true))
		{
			$list_cat[$i] = [
				'cat_id'		=> 0,
				'cat_order'		=> $cat_order + 1,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'cat_nb'		=> $nb,
			];
		}

		$event['content'] = array_merge($event['content'], [
			'title_cat'		=> $this->language->lang('ACP_SC_SMILIES'),
			'categories'	=> $list_cat,
		]);
	}

	public function smilies_popup($event)
	{
		$cat = (int) $event['cat'];
		if ($cat !== -1)
		{
			$i = 0;
			$smilies = [];
			$cat_name = $this->smiley->get_cat_name($cat);

			$sql = [
				'SELECT'	=> 'smiley_url, MIN(smiley_id) AS smiley_id, MIN(code) AS code, MIN(smiley_order) AS min_smiley_order, MIN(smiley_width) AS smiley_width, MIN(smiley_height) AS smiley_height, MIN(emotion) AS emotion',
				'FROM'		=> [SMILIES_TABLE => ''],
				'WHERE'		=> 'category = ' . $cat,
				'GROUP_BY'	=> 'smiley_url',
				'ORDER_BY'	=> 'min_smiley_order ASC',
			];
			$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql));
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

			$event['content'] = array_merge($event['content'], [
				'in_cat'		=> true,
				'cat'			=> $cat,
				'total'			=> $i,
				'smilies'		=> $smilies,
				'emptyRow'		=> ($i === 0) ? $this->language->lang('SC_SMILIES_EMPTY_CATEGORY') : '',
				'title'			=> $this->language->lang('SC_CATEGORY_IN', $cat_name),
			]);
		}
	}
}

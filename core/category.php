<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
 * @license		https://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace sylver35\smiliescat\core;

use phpbb\cache\driver\driver_interface as cache;
use phpbb\db\driver\driver_interface as db;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\user;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\extension\manager;
use phpbb\log\log;

class category
{
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

	/** @var \phpbb\log\log */
	protected $log;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	 * Constructor
	 */
	public function __construct(cache $cache, db $db, config $config, helper $helper, user $user, language $language, template $template, manager $ext_manager, log $log, $smilies_category_table, $root_path)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->config = $config;
		$this->helper = $helper;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->ext_manager = $ext_manager;
		$this->log = $log;
		$this->smilies_category_table = $smilies_category_table;
		$this->root_path = $root_path;
	}

	public function get_version()
	{
		if (($data = $this->cache->get('_smiliescat_version')) === false)
		{
			$md_manager = $this->ext_manager->create_extension_metadata_manager('sylver35/smiliescat');
			$meta = $md_manager->get_metadata();

			$data = [
				'version'	=> $meta['version'],
				'homepage'	=> $meta['homepage'],
			];
			// cache for 7 days
			$this->cache->put('_smiliescat_version', $data, 604800);
		}

		return $data;
	}

	public function smilies_count($cat, $compact = false)
	{
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> (!$compact) ? 'COUNT(DISTINCT smiley_id) AS smilies_count' : 'COUNT(DISTINCT smiley_url) AS smilies_count',
			'FROM'		=> [SMILIES_TABLE => ''],
			'WHERE'		=> ($cat > -1) ? 'category = ' . (int) $cat : "code <> ''",
		]);
		$result = $this->db->sql_query($sql);
		$nb = (int) $this->db->sql_fetchfield('smilies_count');
		$this->db->sql_freeresult($result);

		return $nb;
	}

	public function capitalize($var)
	{
		return ucfirst(strtolower(trim($var)));
	}

	public function get_langs()
	{
		$return = [];
		$sql = 'SELECT *
			FROM ' . LANG_TABLE;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$return[$row['lang_id']] = $row['lang_iso'];
		}
		$this->db->sql_freeresult($result);

		return $return;
	}

	public function verify_cat_langs($langs, $cat, $i, $lang_cat, $ajax)
	{
		$return = [];
		foreach ($langs as $id => $iso)
		{
			if (!isset($lang_cat[$cat][$id]))
			{
				$return[] = $iso;
			}
		}

		if ($ajax === false)
		{
			$this->cat_to_template($i, $return);
		}
		else
		{
			return $this->cat_to_ajax($i, $return);
		}
	}

	private function cat_to_template($i, $return)
	{
		if (($i !== 0) && !empty($return))
		{
			$this->template->assign_block_vars('categories', [
				'ERROR'			=> true,
				'LANG_EMPTY'	=> $this->language->lang('SC_LANGUAGE_EMPTY', (count($return) > 1) ? 2 : 1) . implode(', ', $return),
			]);
		}
	}

	private function cat_to_ajax($i, $return)
	{
		$values = [
			'error'			=> false,
			'lang_empty'	=> '',
		];

		if (($i !== 0) && !empty($return))
		{
			$values = [
				'error'			=> true,
				'lang_empty'	=> $this->language->lang('SC_LANGUAGE_EMPTY', (count($return) > 1) ? 2 : 1) . implode(', ', $return),
			];
		}

		return $values;
	}

	public function category_exist()
	{
		$sql = 'SELECT COUNT(cat_id) AS total
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	public function cat_name($cat)
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
			if (isset($row['cat_name']))
			{
				$cat_name = $row['cat_name'];
				$this->db->sql_freeresult($result);
			}
			else
			{
				$sql = 'SELECT cat_name
					FROM ' . $this->smilies_category_table . "
						WHERE cat_lang = 'en'
						AND cat_id = $cat";
				$result = $this->db->sql_query_limit($sql, 1);
				$row = $this->db->sql_fetchrow($result);
				$cat_name = $row['cat_name'];
				$this->db->sql_freeresult($result);
			}
		}

		return $cat_name;
	}

	public function get_max_order()
	{
		// Get max order id...
		$sql = 'SELECT MAX(cat_order) AS maxi
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$max = (int) $this->db->sql_fetchfield('maxi');
		$this->db->sql_freeresult($result);

		return $max;
	}

	public function get_max_id()
	{
		// Get max id...
		$sql = 'SELECT MAX(cat_id) AS id_max
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$id_max = (int) $this->db->sql_fetchfield('id_max');
		$this->db->sql_freeresult($result);

		return $id_max;
	}

	public function get_first_order()
	{
		// Get first order id...
		$sql = 'SELECT cat_order, cat_id
			FROM ' . $this->smilies_category_table . '
			ORDER BY cat_order ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$first = (int) $this->db->sql_fetchfield('cat_id');
		$this->db->sql_freeresult($result);

		return $first;
	}

	public function get_cat_id($id)
	{
		$sql = 'SELECT category
			FROM ' . SMILIES_TABLE . '
				WHERE smiley_id = ' . $id;
		$result = $this->db->sql_query($sql);
		$cat = (int) $this->db->sql_fetchfield('category');
		$this->db->sql_freeresult($result);

		return $cat;
	}

	public function get_cat_nb($id)
	{
		$sql = 'SELECT cat_nb
			FROM ' . $this->smilies_category_table . '
				WHERE cat_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$cat_nb = (int) $this->db->sql_fetchfield('cat_nb');
		$this->db->sql_freeresult($result);

		return $cat_nb;
	}

	public function extract_list_categories($cat)
	{
		$title = '';
		$cat_order = $i = 0;
		$lang = (string) $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> '*',
			'FROM'		=> [$this->smilies_category_table => ''],
			'WHERE'		=> "cat_nb <> 0 AND cat_lang = '$lang'",
			'ORDER_BY'	=> 'cat_order ASC',
		]);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$actual_cat = (int) $row['cat_id'] === $cat;
			$this->template->assign_block_vars('categories', [
				'CLASS'			=> $actual_cat ? 'cat-active' : 'cat-inactive',
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CAT_NAME'		=> $row['cat_name'] ?: $row['cat_title'],
				'CAT_ORDER'		=> $row['cat_order'],
				'CAT_ID'		=> $row['cat_id'],
				'CAT_NB'		=> $row['cat_nb'],
				'U_CAT'			=> $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => $row['cat_id']]),
			]);
			$i++;

			// Keep these values in memory
			$title = $actual_cat ? $row['cat_name'] : $title;
			$cat_order = $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->smilies_count(0))
		{
			$this->template->assign_block_vars('categories', [
				'CLASS'			=> ($cat === 0) ? 'cat-active' : 'cat-inactive',
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CAT_NAME'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'CAT_ORDER'		=> $cat_order + 1,
				'CAT_ID'		=> 0,
				'CAT_NB'		=> $nb,
				'U_CAT'			=> $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => 0]),
			]);
		}

		return (!$cat) ? $this->language->lang('SC_CATEGORY_DEFAUT') : $title;
	}

	public function set_order($action, $current_order)
	{
		if ($current_order === 1 && $action === 'move_up')
		{
			return 0;
		}

		$max_order = $this->get_max_order();
		if (($current_order === $max_order) && ($action === 'move_down'))
		{
			return 0;
		}

		// on move_down, switch position with next order_id...
		// on move_up, switch position with previous order_id...
		$switch_order_id = ($action === 'move_down') ? $current_order + 1 : $current_order - 1;

		return $switch_order_id;
	}

	public function reset_first_cat($current_order, $switch_order_id)
	{
		$first = $this->get_first_order();
		if ($current_order === 1 || $switch_order_id === 1)
		{
			if ($this->get_cat_nb($first) > 0)
			{
				$this->config->set('smilies_category_nb', $first);
			}
		}
	}

	public function move_cat($id, $action)
	{
		// Get current order id and title...
		$sql = 'SELECT cat_order, cat_title
			FROM ' . $this->smilies_category_table . '
				WHERE cat_id = ' . $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$current_order = (int) $row['cat_order'];
		$title = $row['cat_title'];
		$this->db->sql_freeresult($result);

		$switch_order_id = $this->set_order($action, $current_order);
		if ($switch_order_id === 0)
		{
			return;
		}

		$this->db->sql_query('UPDATE ' . $this->smilies_category_table . " SET cat_order = $current_order WHERE cat_order = $switch_order_id AND cat_id <> $id");
		$move_executed = (bool) $this->db->sql_affectedrows();

		// Only update the other entry too if the previous entry got updated
		if ($move_executed)
		{
			$this->db->sql_query('UPDATE ' . $this->smilies_category_table . " SET cat_order = $switch_order_id WHERE cat_order = $current_order AND cat_id = $id");
		}

		$this->reset_first_cat($current_order, $switch_order_id);
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action) . '_CAT', time(), [$title]);
	}
}

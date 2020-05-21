<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\core;

use phpbb\cache\driver\driver_interface as cache;
use phpbb\db\driver\driver_interface as db;
use phpbb\config\config;
use phpbb\user;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\request\request;
use phpbb\extension\manager;

class category
{
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

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

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

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
	public function __construct(cache $cache, db $db, config $config, user $user, language $language, template $template, request $request, manager $ext_manager, $smilies_category_table, $root_path)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->request = $request;
		$this->ext_manager = $ext_manager;
		$this->smilies_category_table = $smilies_category_table;
		$this->root_path = $root_path;
	}

	public function get_version()
	{
		if (($data = $this->cache->get('_smiliescat_version')) === false)
		{
			$md_manager = $this->ext_manager->create_extension_metadata_manager('sylver35/smiliescat');
			$meta = $md_manager->get_metadata();

			$data = array(
				'version'	=> $meta['version'],
				'homepage'	=> $meta['homepage'],
			);
			// cache for 7 days
			$this->cache->put('_smiliescat_version', $data, 604800);
		}

		return $data;
	}

	public function smilies_count($cat)
	{
		$sql_where = ($cat == -1) ? "code <> ''" : "category = $cat";
		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'COUNT(DISTINCT smiley_id) AS smilies_count',
			'FROM'		=> array(SMILIES_TABLE => ''),
			'WHERE'		=> $sql_where,
		));
		$result = $this->db->sql_query($sql);
		$smilies_count = (int) $this->db->sql_fetchfield('smilies_count');
		$this->db->sql_freeresult($result);

		return $smilies_count;
	}

	public function select_categories($cat, $modify = false)
	{
		$lang = $this->user->lang_name;
		$select = '<option>' . $this->language->lang('SC_CATEGORY_SELECT') . '</option>';
		if (!$modify)
		{
			$select .= '<option value="-1"' . (($cat == -1) ? ' selected="selected"' : '') . '>' . $this->language->lang('SC_CATEGORY_ANY') . '</option>';
		}

		$sql = 'SELECT *
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = ($cat == $row['cat_id']) ? ' selected="selected"' : '';
			$select .= '<option title="' . $row['cat_name'] . '" value="' . $row['cat_id'] . '"' . $selected . '> ' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		$selected_defaut = ($cat == 0) ? ' selected="selected"' : '';
		$select .= '<option title="' . $this->language->lang('SC_CATEGORY_DEFAUT') . '" value="0"' . $selected_defaut . '> ' . $this->language->lang('SC_CATEGORY_DEFAUT') . '</option>';

		return $select;
	}

	public function capitalize($var)
	{
		return $this->db->sql_escape(ucfirst(strtolower(trim($var))));
	}

	public function get_max_order()
	{
		// Get max order id...
		$sql = 'SELECT MAX(cat_order) AS maxi
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$max = (int) $this->db->sql_fetchfield('maxi', $result);
		$this->db->sql_freeresult($result);

		return $max;
	}

	public function get_max_id()
	{
		// Get max id...
		$sql = 'SELECT MAX(cat_id) AS id_max
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$id_max = (int) $this->db->sql_fetchfield('id_max', $result);
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
		$mini = (int) $this->db->sql_fetchfield('cat_id');
		$this->db->sql_freeresult($result);

		return $mini;
	}

	public function shoutbox_smilies($event)
	{
		$i = $cat_order = $cat_id = 0;
		$list_cat = [];
		$lang = $this->user->lang_name;

		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> '*',
			'FROM'		=> array($this->smilies_category_table => ''),
			'WHERE'		=> "cat_lang = '$lang'",
			'ORDER_BY'	=> 'cat_order ASC',
		));
		$result = $this->db->sql_query($sql, 3600);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list_cat[$i] = array(
				'cat_id'		=> $row['cat_id'],
				'cat_order'		=> $row['cat_order'],
				'cat_name'		=> $row['cat_name'],
				'cat_nb'		=> $row['cat_nb'],
			);
			$i++;
			$cat_order = $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		if ($i > 0)
		{
			// Add the Unclassified category
			$list_cat[$i] = array(
				'cat_id'		=> $cat_id,
				'cat_order'		=> $cat_order + 1,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'cat_nb'		=> $this->smilies_count($cat_id),
			);

			$event['content'] = array_merge($event['content'], array(
				'title_cat'		=> $this->language->lang('ACP_SC_SMILIES'),
				'categories'	=> $list_cat,
			));
		}
	}

	public function shoutbox_smilies_popup($event)
	{
		$cat = $event['cat'];
		if ($cat > -1)
		{
			$i = 0;
			$smilies = array();
			$lang = $this->user->lang_name;
			$cat_name = $this->language->lang('SC_CATEGORY_DEFAUT');

			if ($cat > 0)
			{
				$sql = 'SELECT cat_name
					FROM ' . $this->smilies_category_table . "
						WHERE cat_lang = '$lang'
						AND cat_id = $cat";
				$result = $this->db->sql_query_limit($sql, 1);
				$row = $this->db->sql_fetchrow($result);
				$cat_name = $row['cat_name'];
				$this->db->sql_freeresult($result);
			}

			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> 'smiley_url, MIN(smiley_id) AS smiley_id, MIN(code) AS code, MIN(smiley_order) AS min_smiley_order, MIN(smiley_width) AS smiley_width, MIN(smiley_height) AS smiley_height, MIN(emotion) AS emotion, MIN(display_on_shout) AS display_on_shout',
				'FROM'		=> array(SMILIES_TABLE => ''),
				'WHERE'		=> "category = $cat",
				'GROUP_BY'	=> 'smiley_url',
				'ORDER_BY'	=> 'min_smiley_order ASC',
			));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$smilies[$i] = array(
					'nb'		=> $i,
					'code'		=> $row['code'],
					'emotion'	=> $row['emotion'],
					'width'		=> $row['smiley_width'],
					'height'	=> $row['smiley_height'],
					'image'		=> $row['smiley_url'],
				);
				$i++;
			}

			$empty_row = ($i == 0) ? $this->language->lang('SC_SMILIES_EMPTY_CATEGORY') : false;

			$event['content'] = array_merge($event['content'], array(
				'in_cat'		=> true,
				'cat'			=> $cat,
				'total'			=> $i,
				'smilies'		=> $smilies,
				'emptyRow'		=> $empty_row,
				'title'			=> $this->language->lang('SC_CATEGORY_IN', $cat_name),
			));
		}
	}

	public function adm_add_cat()
	{
		$max = $this->get_max_order();
		$sql = 'SELECT lang_local_name, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('categories', array(
				'CAT_LANG'		=> $row['lang_local_name'],
				'CAT_ISO'		=> $row['lang_iso'],
				'CAT_ORDER'		=> $max + 1,
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'CAT_ORDER'		=> $max + 1,
		));
	}
	
	public function adm_edit_cat($id)
	{
		// Get total lang id...
		$sql = 'SELECT COUNT(lang_id) as total
			FROM ' . LANG_TABLE;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total', $result);
		$this->db->sql_freeresult($result);

		$title = '';
		$i = $cat_order = 0;
		$list_id = [];
		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'l.*, c.*',
			'FROM'		=> array(LANG_TABLE => 'l'),
			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array($this->smilies_category_table => 'c'),
					'ON'	=> 'c.cat_lang = l.lang_iso',
				),
			),
			'WHERE'		=> "cat_id = $id",
			'ORDER_BY'	=> 'lang_id ASC',
		));
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('category_lang', array(
				'CAT_LANG'			=> $row['lang_local_name'],
				'CAT_ISO'			=> $row['lang_iso'],
				'CAT_ORDER'			=> $row['cat_order'],
				'CAT_ID'			=> $row['cat_id'],
				'CAT_TRADUCT'		=> $row['cat_name'],
				'CAT_SORT'			=> 'edit',
			));
			$i++;
			$list_id[$i] = $row['lang_id'];
			$cat_order = $row['cat_order'];
			$title = $row['cat_title'];
		}
		$this->db->sql_freeresult($result);

		// Add rows for empty langs in this category
		if ($i !== $total)
		{
			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> '*',
				'FROM'		=> array(LANG_TABLE => 'l'),
				'WHERE'		=> $this->db->sql_in_set('lang_id', $list_id, true, true),
			));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->template->assign_block_vars('category_lang', array(
					'CAT_LANG'			=> $row['lang_local_name'],
					'CAT_ISO'			=> $row['lang_iso'],
					'CAT_ORDER'			=> $cat_order,
					'CAT_ID'			=> $id,
					'CAT_TRADUCT'		=> '',
					'CAT_SORT'			=> 'create',
				));
			}
			$this->db->sql_freeresult($result);
		}

		$this->template->assign_vars(array(
			'CAT_ORDER'		=> $cat_order,
			'CAT_TITLE'		=> $title,
		));
	}

	public function adm_list_cat($u_action)
	{
		$i = 1;
		$cat = 0;
		$empty_row = false;
		$max = $this->get_max_order();
		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'l.lang_iso, l.lang_local_name, c.*',
			'FROM'		=> array(LANG_TABLE => 'l'),
			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array($this->smilies_category_table => 'c'),
					'ON'	=> 'c.cat_lang = l.lang_iso',
				),
			),
			'ORDER_BY'	=> 'cat_order ASC, c.cat_lang_id ASC',
		));
		$result = $this->db->sql_query($sql);
		if ($row = $this->db->sql_fetchrow($result))
		{
			do
			{
				$this->template->assign_block_vars('categories', array(
					'CAT_NR'			=> $i,
					'CAT_LANG'			=> $row['lang_local_name'],
					'CAT_ISO'			=> $row['lang_iso'],
					'CAT_ID'			=> $row['cat_id'],
					'CAT_ORDER'			=> $row['cat_order'],
					'CAT_TRADUCT'		=> $row['cat_name'],
					'CAT_NB'			=> $row['cat_nb'],
					'ROW'				=> ($row['cat_id'] !== $cat) ? true : false,
					'ROW_MAX'			=> ($row['cat_order'] == $max) ? true : false,
					'SPACER_CAT'		=> $this->language->lang('SC_CATEGORY_IN', $row['cat_title']),
					'U_EDIT'			=> $u_action . '&amp;action=edit&amp;id=' . $row['cat_id'],
					'U_DELETE'			=> $u_action . '&amp;action=delete&amp;id=' . $row['cat_id'],
					'U_MOVE_UP'			=> $u_action . '&amp;action=move_up&amp;id=' . $row['cat_id'] . '&amp;hash=' . generate_link_hash('acp-main_module'),
					'U_MOVE_DOWN'		=> $u_action . '&amp;action=move_down&amp;id=' . $row['cat_id'] . '&amp;hash=' . generate_link_hash('acp-main_module'),
				));
				$i++;
				$cat = $row['cat_id'];
				$empty_row = (!$cat) ? true : false;
			} while ($row = $this->db->sql_fetchrow($result));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'EMPTY_ROW'		=> $empty_row,
		));
	}

	public function adm_edit_smiley($id, $u_action, $start)
	{
		$lang = $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 's.*, c.*',
			'FROM'		=> array(SMILIES_TABLE => 's'),
			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array($this->smilies_category_table => 'c'),
					'ON'	=> "c.cat_id = s.category AND c.cat_lang = '$lang'",
				),
			),
			'WHERE'	=> "smiley_id = $id",
		));
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$this->template->assign_vars(array(
			'WIDTH'				=> $row['smiley_width'],
			'HEIGHT'			=> $row['smiley_height'],
			'CODE'				=> $row['code'],
			'EMOTION'			=> $row['emotion'],
			'CATEGORY'			=> $row['cat_name'],
			'EX_CAT'			=> $row['cat_id'],
			'SELECT_CATEGORY'	=> $this->select_categories($row['cat_id'], true),
			'IMG_SRC'			=> $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
			'U_MODIFY'			=> $u_action . '&amp;action=modify&amp;id=' . $row['smiley_id'] . '&amp;start=' . $start,
			'U_BACK'			=> $u_action,
		));
		$this->db->sql_freeresult($result);
	}

	public function reset_first_cat($current_order, $switch_order_id)
	{
		if ($current_order == 1 || $switch_order_id == 1)
		{
			$this->config->set('smilies_category_nb', $this->get_first_order());
		}
	}
}

<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
* @license		https://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\controller;

use sylver35\smiliescat\core\category;
use sylver35\smiliescat\core\smiley;
use sylver35\smiliescat\core\work;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as db;
use phpbb\pagination;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use phpbb\language\language;
use phpbb\log\log;

class admin_controller
{
	/* @var \sylver35\smiliescat\core\category */
	protected $category;

	/* @var \sylver35\smiliescat\core\smiley */
	protected $smiley;

	/* @var \sylver35\smiliescat\core\work */
	protected $work;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, smiley $smiley, work $work, config $config, db $db, pagination $pagination, request $request, template $template, user $user, language $language, log $log, $root_path, $smilies_category_table)
	{
		$this->category = $category;
		$this->smiley = $smiley;
		$this->work = $work;
		$this->config = $config;
		$this->db = $db;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->log = $log;
		$this->root_path = $root_path;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function acp_smilies_category($id, $action)
	{
		$this->language->add_lang('acp/posting');
		$start = (int) $this->request->variable('start', 0);
		$select = (int) $this->request->variable('select', -1);
		$cat_id = (int) $this->request->variable('cat_id', 0);
		$ex_cat = (int) $this->request->variable('ex_cat', 0);
		$list = $this->request->variable('list', [0]);
		$form_key = 'sylver35/smiliescat';
		add_form_key($form_key);

		if ($action)
		{
			switch ($action)
			{
				case 'edit':
					$this->smiley->edit_smiley($id, $start, $this->u_action);
				break;

				case 'edit_multi':
					$list = $this->request->variable('mark', [0]);
					$this->smiley->edit_multi_smiley($list, $start, $this->u_action);
				break;

				case 'modify':
					if (!check_form_key($form_key))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->smiley->modify_smiley($id, $cat_id, $ex_cat);
					trigger_error($this->language->lang('SMILIES_EDITED', 1) . adm_back_link($this->u_action . '&amp;start=' . $start . '#acp_smilies_category'));
				break;

				case 'modify_list':
					foreach ($list as $smiley)
					{
						$this->smiley->modify_smiley($smiley, $cat_id);
					}
					trigger_error($this->language->lang('SMILIES_EDITED', count($list)) . adm_back_link($this->u_action . '&amp;start=' . $start . '#acp_smilies_category'));
				break;
			}

			$this->template->assign_vars([
				'IN_ACTION' => true,
			]);
		}
		else
		{
			$this->smiley->extract_list_smilies($select, $start, $this->u_action);

			$this->template->assign_vars([
				'LIST_CATEGORY'		=> $this->smiley->select_categories($select, true, true),
				'U_SELECT_CAT'		=> $this->u_action . '&amp;select=' . $select,
				'U_MODIFY_LIST'		=> $this->u_action . '&amp;action=edit_multi&amp;start=' . $start,
				'U_BACK'			=> $this->u_action,
			]);
		}

		$this->template->assign_vars([
			'CATEGORIE_SMILIES'	=> true,
		]);
	}

	public function acp_categories_config($id, $action, $mode)
	{
		$this->language->add_lang('acp/language');
		$form_key = 'sylver35/smiliescat';
		add_form_key($form_key);

		if ($action)
		{
			if (in_array($action, ['config_cat', 'add_cat', 'edit_cat']) && !check_form_key($form_key))
			{
				trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			switch ($action)
			{
				case 'config_cat':
					$this->config->set('smilies_per_page_cat', (int) $this->request->variable('smilies_per_page_cat', 15));
					$this->config->set('smilies_per_page_acp', (int) $this->request->variable('smilies_per_page_acp', 15));

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_CONFIG', time());
					trigger_error($this->language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
				break;

				case 'add':
					$this->work->add_cat($this->u_action);
				break;

				case 'add_cat':
					$this->work->add_category($this->u_action);
				break;

				case 'edit':
					$this->work->edit_cat((int) $id, $this->u_action);
				break;

				case 'edit_cat':
					$this->work->edit_category((int) $id, $this->u_action);
				break;

				case 'delete':
					if (confirm_box(true))
					{
						$this->work->delete_cat((int) $id, $this->u_action);
					}
					else
					{
						confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
							'mode'		=> $mode,
							'id'		=> $id,
							'action'	=> $action,
						]));
					}
				break;
			}

			$this->template->assign_vars([
				'IN_ACTION'	=> true,
			]);
		}
		else
		{
			$this->work->adm_list_cat($this->u_action);
		}

		$this->template->assign_vars([
			'CATEGORIE_CONFIG'		=> true,
			'SMILIES_PER_PAGE_CAT'	=> $this->config['smilies_per_page_cat'],
			'SMILIES_PER_PAGE_ACP'	=> $this->config['smilies_per_page_acp'],
			'U_ACTION_CONFIG'		=> $this->u_action . '&amp;action=config_cat',
			'U_ADD'					=> $this->u_action . '&amp;action=add',
		]);
	}

	/**
	 * Set page url
	 *
	 * @param string $u_action Custom form action
	 * @return null
	 * @access public
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}

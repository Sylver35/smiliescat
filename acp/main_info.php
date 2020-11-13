<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\acp;

class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\sylver35\smiliescat\acp\main_module',
			'title'		=> 'ACP_SC_CATEGORY',
			'modes'		=> [
				'config'	=> [
					'title'	=> 'ACP_SC_CATEGORY',
					'auth'	=> 'ext_sylver35/smiliescat && acl_a_icons',
					'cat'	=> ['ACP_MESSAGES'],
				],
				'smilies'	=> [
					'title'	=> 'ACP_SC_SMILIES',
					'auth'	=> 'ext_sylver35/smiliescat && acl_a_icons',
					'cat'	=> ['ACP_MESSAGES'],
				],
			],
		];
	}
}

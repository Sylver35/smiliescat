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
	function module()
	{
		return array(
			'filename'	=> '\sylver35\smiliescat\acp\main_module',
			'title'		=> 'ACP_SC_CATEGORY',
			'modes'		=> array(
				'config'		=> array(
					'title'	=> 'ACP_SC_CATEGORY',
					'auth'	=> 'ext_sylver35/smiliescat && acl_a_icons',
					'cat'	=> array('ACP_MESSAGES'),
				),
				'smilies'			=> array(
					'title'	=> 'ACP_SC_SMILIES',
					'auth'	=> 'ext_sylver35/smiliescat && acl_a_icons',
					'cat'	=> array('ACP_MESSAGES'),
				),
			),
		);
	}
}

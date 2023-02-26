<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
* @license		https://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\migrations;

use phpbb\db\migration\migration;

class smiliescat_1_6_0 extends migration
{
	public function effectively_installed()
	{
		return isset($this->config['smilies_per_page_acp']);
	}

	static public function depends_on()
	{
		return ['\sylver35\smiliescat\migrations\v110_data'];
	}

	public function update_data()
	{
		return [
			// Config
			['config.add', ['smilies_per_page_acp', 15]],
		];
	}
}

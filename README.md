# Breizh Smilies Categories Extension

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Sylver35/smiliescat/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Sylver35/smiliescat/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/Sylver35/smiliescat/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Sylver35/smiliescat/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/Sylver35/smiliescat/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

## Minimum Requirements
* phpBB 3.3.13
* PHP 7.2

## Install
1. Download the latest release.
2. Unzip the downloaded release, and change the name of the folder to `smiliescat`.
3. In the `ext` directory of your phpBB board, create a new directory named `sylver35` (if it does not already exist).
4. Copy the `smiliescat` folder to `/ext/sylver35/` (if done correctly, you'll have the main extension class at (your forum root)/ext/sylver35/smiliescat/composer.json).
5. Navigate in the ACP to `Customise -> Manage extensions`.
6. Look for `Breizh Smilies Categories` under the Disabled Extensions list, and click its `Enable` link.

## Uninstall
1. Navigate in the ACP to `Customise -> Extension Management -> Extensions`.
2. Look for `Breizh Smilies Categories` under the Enabled Extensions list, and click its `Disable` link.
3. To permanently uninstall, click `Delete Data` and then delete the `/ext/sylver35/smiliescat` folder.

## Management
1. Navigate in the ACP to `Posting -> Configuration of categories`.
2. Create categories in active languages and assign the correct order.
3. Navigate in the ACP to `Posting -> Smilies categories`.
4. Assign the desired category for smilies.

## License
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)

© 2024 - Sylver35
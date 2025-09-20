<?php
/**
 * @package      SiSmOS.Sismosexampleoauth2
 *
 * @copyright    (C) 2025, SimplySmart-IT - Martina Scholz <https://simplysmart-it.de>
 * @license      GNU General Public License version 3 or later; see LICENSE
 */

/**
 * This is the configuration file for php-cs-fixer header
 *
 * @link https://github.com/FriendsOfPHP/PHP-CS-Fixer
 * @link https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0
 *
 */

// All files in tmp folder created in build process
$finder = PhpCsFixer\Finder::create()
    ->in(
        [
            dirname(__DIR__) . '/build/tmp',
        ]
        );

$header = <<<EOF
@package    SiSmOS.Plugin
@subpackage System.sismosexampleoauth2

@author     Martina Scholz <support@simplysmart-it.de>

@copyright  (C) 2025, SimplySmart-IT - Martina Scholz <https://simplysmart-it.de>
@license    GNU General Public License version 3 or later; see LICENSE
@link       https://simplysmart-it.de
EOF;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setHideProgress(false)
    ->setUsingCache(false)
    ->setRules(
        [
            'header_comment' => [
                'comment_type' => 'PHPDoc',
                'header' => $header,
                'location' => 'after_open',
                'separate' => 'both',
            ],

        ]
    )
    ->setFinder($finder);

return $config;

<?php
/**
 * Plugin Name: Rapidmail For Fluent Forms
 * Plugin URI:  https://github.com/TheZoker/rapidmail-for-fluent-forms
 * Description: Integrate Rapidmail with Fluentform.
 * Author: Florian Gareis
 * Author URI:  https://gareis.io
 * Version: 1.0.0
 * Text Domain: ffrapidmail
 */

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2022 Florian Gareis. All rights reserved.
 */


defined('ABSPATH') or die;
define('FFRAPIDMAIL_DIR', plugin_dir_path(__FILE__));
define('FFRAPIDMAIL_URL', plugin_dir_url(__FILE__));

class FluentFormRapidmail
{
    public function boot()
    {
        if (!defined('FLUENTFORM')) {
            return $this->injectDependency();
        }

        $this->includeFiles();

        add_action('fluentform/loaded', function ($app) {
            $this->registerHooks($app);
        });
    }

    protected function includeFiles()
    {
        include_once FFRAPIDMAIL_DIR . 'Integrations/Bootstrap.php';
        include_once FFRAPIDMAIL_DIR . 'Integrations/API.php';
    }

    protected function registerHooks($app)
    {
        new \FluentFormRapidmail\Integrations\Bootstrap($app);
    }

    /**
     * Notify the user about the FluentForm dependency and instructs to install it.
     */
    protected function injectDependency()
    {
        add_action('admin_notices', function () {
            $pluginInfo = $this->getFluentFormInstallationDetails();

            $class = 'notice notice-error';

            $install_url_text = 'Click Here to Install the Plugin';

            if ($pluginInfo->action == 'activate') {
                $install_url_text = 'Click Here to Activate the Plugin';
            }

            $message = 'FluentForm Rapidmail Add-On Requires Fluent Forms Add On Plugin, <b><a href="' . $pluginInfo->url
                . '">' . $install_url_text . '</a></b>';

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        });
    }

    protected function getFluentFormInstallationDetails()
    {
        $activation = (object)[
            'action' => 'install',
            'url' => ''
        ];

        $allPlugins = get_plugins();

        if (isset($allPlugins['fluentform/fluentform.php'])) {
            $url = wp_nonce_url(
                self_admin_url('plugins.php?action=activate&plugin=fluentform/fluentform.php'),
                'activate-plugin_fluentform/fluentform.php'
            );

            $activation->action = 'activate';
        } else {
            $api = (object)[
                'slug' => 'fluentform'
            ];

            $url = wp_nonce_url(
                self_admin_url('update.php?action=install-plugin&plugin=' . $api->slug),
                'install-plugin_' . $api->slug
            );
        }

        $activation->url = $url;

        return $activation;
    }
}

register_activation_hook(__FILE__, function () {
    $globalModules = get_option('fluentform_global_modules_status');
    if (!$globalModules || !is_array($globalModules)) {
        $globalModules = [];
    }

    $globalModules['rapidmail'] = 'yes';
    update_option('fluentform_global_modules_status', $globalModules);
});

add_action('plugins_loaded', function () {
    (new FluentFormRapidmail())->boot();
}, 1);

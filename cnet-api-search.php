<?php
/*
Plugin Name: CNET API Search
Version: 0.9
Plugin URI: http://www.billygrahampresents.com/blog/cnet-api-plugin/
Description: CNET API Search adds the ability to search and include items from CNET in your blog entries.
Author: Bill Graham
Author URI: http://www.billygrahampresents.com/blog/cnet-api-plugin/

CNET API Search Plugin for Wordpress 2.1+
Copyright (C) 2007 Bill Graham

Some code modified and redistributed from Rich Manalang:
Copyright (C) 2005-2007 Rich Manalang
Version 0.9

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License as
published by the Free Software Foundation; either version 2 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
USA
*/

class WP_CNET {

    var $version;
    var $country;
    var $associate_id;
    var $subscription_id;
    var $plugin_home_url;

    function wp_cnet () {
        // load i18n translations
        load_plugin_textdomain('wpcnet');

        // initialize all the variables
        $this->version = '0.9';
        $this->plugin_home_url = 'http://www.billygrahampresents.com/cnet-api-search';
        $this->country = get_option('wpcnet_country_tld');
        $this->associate_id = get_option('wpcnet_associate_id');

        // Set defaults if properties aren't set
        if ( !$this->country ) update_option('wpcnet_country_tld', 'US');
    }

    function check_for_updates() {
        $request  = "GET http://www.billygrahampresents.com/cnet-api-search/plugin/latest-version.txt HTTP/1.1\n";
        $request .= "Host: www.billygrahampresents.com\n";
        $request .= "Referer: " . $_SERVER["SCRIPT_URI"] . "\n";
        $request .= "Connection: close\n";
        $request .= "\n";

        //$fp = fsockopen("localhost", 80);
        $fp = fsockopen("www.billygrahampresents.com", 80);
        fputs($fp, $request);
        while(!feof($fp)) {
        $result .= fgets($fp, 128);
        }
        fclose($fp);

        $result = split("\r\n", $result);

        foreach($result as $k) {
            if(!strncmp($k, "Version: ", 9)) {
                $result = $k;
                break;
            }
        }

        $version = split(": ", $k);
        $version = $version[1];

        return $version;
    }

    function options_page() {
        if(isset($_POST['submitted'])) {

            update_option('wpcnet_country_tld', $_POST['wpcnet_country_tld']);
            update_option('wpcnet_associate_id', $_POST['wpcnet_associate_id']);

            echo '<div class="updated"><p><strong>' . __('Options saved.', 'wpcnet') . '</strong></p></div>';
        }
        //get any new variables
        $this->wp_cnet();

        $this->country = get_option('wpcnet_country_tld');
        $this->associate_id = get_option('wpcnet_associate_id');
        $var[$this->country] = "selected";

        $formaction = $_SERVER['PHP_SELF'] . "?page=cnet-api-search/cnet-api-search.php";

        // Check if there is a new version of CNET API Search
        $version_synch_val = get_option('wpcnet_check_version');

        if ( empty($version_synch_val) )
            add_option('wpcnet_check_version', '0');

        if (get_option('wpcnet_check_version') < ( time() - 1200 ) ) {
            $latest_version = $this->check_for_updates();
            update_option('wpcnet_check_version', time());
            update_option('wpcnet_latest_version', $latest_version);
        } else {
            $latest_version = get_option('wpcnet_latest_version');
        }

        if ($this->version != $latest_version )
            $update = "<a href=\"$this->plugin_home_url\" style=\"color:red\">Click here to get the latest update.</a>";

// Start outputting XHMTL
?>
        <div class="wrap">
            <h2><?php _e('General Options', 'wpcnet'); ?></h2>

            <form name="wpcnet_options" method="post" action="<?php echo $formaction; ?>">
            <input type="hidden" name="submitted" value="1" />

            <fieldset class="options">
                <legend>
                    <label><?php _e('CNET API Developers Key', 'wpcnet'); ?></label>
                </legend>

                <p>
                <?php _e('CNET requires a developer key to access the CNET API. To get your key, visit the <a href="http://api.cnet.com">CNET API website</a> for details.', 'wpcnet'); ?>
                </p>
                <p>
                <?php _e('To use CNET API Search specify your CNET API Developers Key here.', 'wpcnet'); ?>
                </p>

                <table width="100%" cellspacing="2" cellpadding="5" class="editform">
                <tr>
                    <th width="33%" valign="top" scope="row"><?php _e('CNET API Developers Key:', 'wpcnet'); ?> </th>
                    <td>
                        <input name="wpcnet_associate_id" type="text" id="wpcnet_associate_id" value="<?php echo $this->associate_id; ?>" size="50" /><br />
                    </td>
                </tr>
                </table>
            </fieldset>
            <p><?php printf(__('This version of CNET API Search is %1$s and the latest version is %2$s. %3$s', 'wpcnet'), $this->version, $latest_version, $update); ?></p>
            <p class="submit">
                <input type="submit" name="Submit" value="<?php _e('Update Options &raquo;', 'wpcnet'); ?>" />
            </p>
        </form>


        </div>

    <?php
    }

    // Adds javascript function to launch a new window for the search page
    function add_head() {
        if (!(strstr($_SERVER['PHP_SELF'], 'post-new.php') || strstr($_SERVER['PHP_SELF'], 'page-new.php')
            || strstr($_SERVER['PHP_SELF'], 'post.php') || strstr($_SERVER['PHP_SELF'], 'page.php')))
            return 0;
        ?>
            <link rel="stylesheet" href="../wp-content/plugins/cnet-api-search/css/cnet-api-search.css" type="text/css" />
            <script type="text/javascript">
            <?php
            echo("var wpa2AssociatesId = '" . $this->associate_id . "';");
            echo("var wpa2CountryTLD = '" . $this->country . "';");
            ?>
            </script>
            <script type="text/javascript" src="../wp-content/plugins/cnet-api-search/js/cnet-api-search.js"></script>
        <?php
    }

    function show_options_page() {
        global $wp_cnet;
        add_options_page(__('CNET API Search Options', 'wpcnet'), __('CNET', 'wpcnet'), 8, __FILE__, array(&$wp_cnet, 'options_page'));
    }

} // Class WP_CNET



// Add actions to call the function
add_action('plugins_loaded', create_function('$a', 'global $wp_cnet; $wp_cnet = new WP_CNET;'));
add_action('admin_head', array(&$wp_cnet, 'add_head'));
add_action('admin_menu', array(&$wp_cnet, 'show_options_page'));

?>

<?php
/**
 * Plugin Name:         Ultimate Member - Force Password Reset
 * Description:         Extension to Ultimate Member for resetting password at first User login.
 * Version:             1.0.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Plugin URI:          https://github.com/MissVeronica/um-force-password-reset
 * Update URI:          https://github.com/MissVeronica/um-force-password-reset
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.9.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

class UM_Force_Password_Reset {

    function __construct() {

        define( 'Plugin_Basename_FPR', plugin_basename(__FILE__));

        add_action( 'wp_login',                        array( $this, 'force_password_reset' ), 9, 1 );
        add_action( 'um_after_changing_user_password', array( $this, 'force_password_reset_clear' ), 10, 1 );
        add_filter( 'um_settings_structure',           array( $this, 'um_settings_structure_force_password_reset' ), 10, 1 );
        add_filter( 'plugin_action_links_' . Plugin_Basename_FPR, array( $this, 'force_password_reset_settings_link' ), 10 );
    }

    function force_password_reset_settings_link( $links ) {

        $url = get_admin_url() . 'admin.php?page=um_options&tab=access&section=other';
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings' ) . '</a>';

        return $links;
    }

    public function force_password_reset( $login ) {

        $user = get_user_by( 'login', $login );
        if ( false !== $user ) {

            if ( $this->contributor_special_login( $user->ID ) !== false ) {
                $reset_url = UM()->password()->reset_url();
                wp_logout();
                exit( wp_redirect( $reset_url ) );
            }
        }
    }

    public function force_password_reset_clear( $user_id ) {

        $user_id = $this->contributor_special_login( $user_id );

        if ( $user_id !== false ) {
            update_user_meta( $user_id, '_um_last_login', current_time( 'mysql', true ) );
        }
    }

    public function contributor_special_login( $user_id ) {

        $result = false;

        $selected_roles = array_map( 'sanitize_text_field', UM()->options()->get( 'um_force_pwd_roles' ));
        if ( in_array( UM()->roles()->get_priority_user_role( $user_id ), $selected_roles )) {

            if ( empty( get_user_meta( $user_id, '_um_last_login' ) )) {
                $result = $user_id;
            }
        }

        return $result;
    }

    public function um_settings_structure_force_password_reset( $settings ) {

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'um_options' ) {
            if ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'access' ) {
                if ( isset( $_REQUEST['section'] ) && $_REQUEST['section'] == 'other' ) {

                    $plugin_data = get_plugin_data( __FILE__ );
                    $prefix = '&nbsp; * &nbsp;';
                    $section_fields = array();

                    $documention = sprintf( ' <a href="%s" target="_blank" title="%s">%s</a>',
                                                esc_url( $plugin_data['PluginURI'] ),
                                                esc_html__( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                                esc_html__( 'Documentation', 'ultimate-member' ));

                    $section_fields[] = array(
                        'id'             => 'um_force_pwd_roles',
                        'type'           => 'select',
                        'multi'          => true,
                        'label'          => $prefix . esc_html__( 'User Roles to include', 'ultimate-member' ),
                        'description'    => esc_html__( 'Select the User Role(s) to be included in Force Password Reset at first login.', 'ultimate-member' ),
                        'options'        => UM()->roles()->get_roles(),
                        'size'           => 'medium',
                    );

                    $settings['access']['sections']['other']['form_sections']['force_pwd']['title']       = esc_html__( 'Force Password Reset', 'ultimate-member' );
                    $settings['access']['sections']['other']['form_sections']['force_pwd']['description'] = sprintf( esc_html__( 'Plugin version %s - tested with UM %s - %s', 'ultimate-member' ), $plugin_data['Version'], '2.9.2', $documention );
                    $settings['access']['sections']['other']['form_sections']['force_pwd']['fields']      = $section_fields;
                }
            }
        }

        return $settings;
    }

}

new UM_Force_Password_Reset();


<?php
/**
 * Plugin Name: WP Studio Manager
 * Description: Manage classes, staff and clients with industry specific terminology.
 * Version: 0.1.0
 * Author: Example
 * License: GPL2+
 * Text Domain: wsm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_Studio_Manager' ) ) :
final class WP_Studio_Manager {

    const VERSION = '0.1.0';

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'install' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'maybe_setup_redirect' ) );
    }

    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$wpdb->prefix}wsm_clients (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_staff (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_classes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            level varchar(100) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_enrollments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            class_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        ";

        foreach ( explode( ';', $sql ) as $statement ) {
            $statement = trim( $statement );
            if ( $statement ) {
                dbDelta( $statement );
            }
        }

        add_option( 'wsm_version', self::VERSION );
        add_option( 'wsm_activation_redirect', true );
    }

    public function maybe_setup_redirect() {
        if ( get_option( 'wsm_activation_redirect', false ) ) {
            delete_option( 'wsm_activation_redirect' );
            if ( ! isset( $_GET['activate-multi'] ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=wsm-setup' ) );
                exit;
            }
        }
    }

    public function admin_menu() {
        add_menu_page(
            __( 'Studio Manager', 'wsm' ),
            __( 'Studio Manager', 'wsm' ),
            'manage_options',
            'wsm-dashboard',
            array( $this, 'dashboard_page' ),
            'dashicons-admin-generic'
        );

        add_submenu_page(
            'wsm-dashboard',
            __( 'Settings', 'wsm' ),
            __( 'Settings', 'wsm' ),
            'manage_options',
            'wsm-settings',
            array( $this, 'settings_page' )
        );

        add_submenu_page(
            null,
            __( 'Setup', 'wsm' ),
            __( 'Setup', 'wsm' ),
            'manage_options',
            'wsm-setup',
            array( $this, 'setup_page' )
        );
    }

    public function dashboard_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Studio Manager', 'wsm' ) . '</h1></div>';
    }

    public function setup_page() {
        if ( isset( $_POST['wsm_industry'] ) ) {
            update_option( 'wsm_industry', sanitize_text_field( $_POST['wsm_industry'] ) );
            update_option( 'wsm_installed', true );
            echo '<div class="updated"><p>' . esc_html__( 'Setup complete.', 'wsm' ) . '</p></div>';
        }

        $industry = get_option( 'wsm_industry', 'sports' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Studio Manager Setup', 'wsm' ); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wsm_industry"><?php esc_html_e( 'Industry', 'wsm' ); ?></label></th>
                        <td>
                            <select name="wsm_industry" id="wsm_industry">
                                <option value="sports" <?php selected( $industry, 'sports' ); ?>><?php esc_html_e( 'Sports', 'wsm' ); ?></option>
                                <option value="education" <?php selected( $industry, 'education' ); ?>><?php esc_html_e( 'Education', 'wsm' ); ?></option>
                                <option value="fitness" <?php selected( $industry, 'fitness' ); ?>><?php esc_html_e( 'Fitness', 'wsm' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save', 'wsm' ) ); ?>
            </form>
        </div>
        <?php
    }

    public function settings_page() {
        if ( isset( $_POST['wsm_industry'] ) ) {
            update_option( 'wsm_industry', sanitize_text_field( $_POST['wsm_industry'] ) );
            echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'wsm' ) . '</p></div>';
        }

        $industry = get_option( 'wsm_industry', 'sports' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Studio Manager Settings', 'wsm' ); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wsm_industry"><?php esc_html_e( 'Industry', 'wsm' ); ?></label></th>
                        <td>
                            <select name="wsm_industry" id="wsm_industry">
                                <option value="sports" <?php selected( $industry, 'sports' ); ?>><?php esc_html_e( 'Sports', 'wsm' ); ?></option>
                                <option value="education" <?php selected( $industry, 'education' ); ?>><?php esc_html_e( 'Education', 'wsm' ); ?></option>
                                <option value="fitness" <?php selected( $industry, 'fitness' ); ?>><?php esc_html_e( 'Fitness', 'wsm' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Changes', 'wsm' ) ); ?>
            </form>
        </div>
        <?php
    }

    public static function get_labels() {
        $industry = get_option( 'wsm_industry', 'sports' );
        $defaults = array(
            'sports' => array(
                'client' => __( 'Athlete', 'wsm' ),
                'staff'  => __( 'Coach', 'wsm' ),
                'class'  => __( 'Class', 'wsm' ),
                'level'  => __( 'Level', 'wsm' ),
            ),
            'education' => array(
                'client' => __( 'Student', 'wsm' ),
                'staff'  => __( 'Instructor', 'wsm' ),
                'class'  => __( 'Period', 'wsm' ),
                'level'  => __( 'Grade', 'wsm' ),
            ),
            'fitness' => array(
                'client' => __( 'Client', 'wsm' ),
                'staff'  => __( 'Trainer', 'wsm' ),
                'class'  => __( 'Class', 'wsm' ),
                'level'  => __( 'Level', 'wsm' ),
            ),
        );
        return isset( $defaults[ $industry ] ) ? $defaults[ $industry ] : $defaults['sports'];
    }
}
endif;

function wsm() {
    return WP_Studio_Manager::instance();
}

wsm();

<?php
/**
 * Plugin Name: WP Studio Manager
 * Description: Manage classes, staff and clients with industry specific terminology.
 * Version: 0.2.0
 * Author: Example
 * License: GPL2+
 * Text Domain: wsm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_Studio_Manager' ) ) :
final class WP_Studio_Manager {

    const VERSION = '0.2.0';

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
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_ajax_wsm_add_client', array( $this, 'ajax_add_client' ) );
    }

    public function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$wpdb->prefix}wsm_clients (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            first_name varchar(191) NOT NULL,
            last_name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            dob date NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_staff (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            first_name varchar(191) NOT NULL,
            last_name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_levels (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_classes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            level_id bigint(20) unsigned NOT NULL,
            schedule datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_enrollments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            class_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_parents (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            first_name varchar(191) NOT NULL,
            last_name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
       CREATE TABLE {$wpdb->prefix}wsm_client_parents (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;
        CREATE TABLE {$wpdb->prefix}wsm_class_staff (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            class_id bigint(20) unsigned NOT NULL,
            staff_id bigint(20) unsigned NOT NULL,
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

        $labels = self::get_labels();

        add_submenu_page(
            'wsm-dashboard',
            sprintf( __( '%s', 'wsm' ), $labels['client'] . 's' ),
            sprintf( __( '%s', 'wsm' ), $labels['client'] . 's' ),
            'manage_options',
            'wsm-clients',
            array( $this, 'clients_page' )
        );

        add_submenu_page(
            'wsm-dashboard',
            sprintf( __( '%s', 'wsm' ), $labels['staff'] . 's' ),
            sprintf( __( '%s', 'wsm' ), $labels['staff'] . 's' ),
            'manage_options',
            'wsm-staff',
            array( $this, 'staff_page' )
        );

        add_submenu_page(
            'wsm-dashboard',
            sprintf( __( '%s', 'wsm' ), $labels['class'] . 'es' ),
            sprintf( __( '%s', 'wsm' ), $labels['class'] . 'es' ),
            'manage_options',
            'wsm-classes',
            array( $this, 'classes_page' )
        );

        add_submenu_page(
            'wsm-dashboard',
            sprintf( __( '%s', 'wsm' ), $labels['level'] . 's' ),
            sprintf( __( '%s', 'wsm' ), $labels['level'] . 's' ),
            'manage_options',
            'wsm-levels',
            array( $this, 'levels_page' )
        );

        add_submenu_page(
            'wsm-dashboard',
            __( 'Parents', 'wsm' ),
            __( 'Parents', 'wsm' ),
            'manage_options',
            'wsm-parents',
            array( $this, 'parents_page' )
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

    public function clients_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'wsm_clients';

        if ( isset( $_POST['new_client'] ) ) {
            $wpdb->insert( $table, array(
                'first_name' => sanitize_text_field( $_POST['client_first_name'] ),
                'last_name'  => sanitize_text_field( $_POST['client_last_name'] ),
                'email'      => sanitize_email( $_POST['client_email'] ),
                'dob'        => sanitize_text_field( $_POST['client_dob'] ),
            ) );
            echo '<div class="updated"><p>' . esc_html__( 'Athlete added.', 'wsm' ) . '</p></div>';
        }

        if ( isset( $_GET['delete'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['delete'] ) ) );
            echo '<div class="updated"><p>' . esc_html__( 'Client deleted.', 'wsm' ) . '</p></div>';
        }

        $clients = $wpdb->get_results( "SELECT * FROM {$table}" );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( self::get_labels()['client'] . 's' ); ?></h1>
            <table class="widefat wsm-grid">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'First Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Last Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'DOB', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wsm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $clients as $client ) : ?>
                    <tr>
                        <td><?php echo esc_html( $client->id ); ?></td>
                        <td><?php echo esc_html( $client->first_name ); ?></td>
                        <td><?php echo esc_html( $client->last_name ); ?></td>
                        <td><?php echo esc_html( $client->email ); ?></td>
                        <td><span class="wsm-dob-mask">*****</span><span class="wsm-dob" style="display:none;"><?php echo esc_html( $client->dob ); ?></span> <button type="button" class="toggle-dob button"><?php esc_html_e( 'Show', 'wsm' ); ?></button></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wsm-clients&delete=' . $client->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wsm' ); ?>');">
                                <?php esc_html_e( 'Delete', 'wsm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Add New', 'wsm' ); ?></h2>
            <form method="post" id="wsm-add-client">
                <input type="text" name="client_first_name" placeholder="<?php esc_attr_e( 'First Name', 'wsm' ); ?>" required />
                <input type="text" name="client_last_name" placeholder="<?php esc_attr_e( 'Last Name', 'wsm' ); ?>" required />
                <input type="email" name="client_email" placeholder="<?php esc_attr_e( 'Email', 'wsm' ); ?>" required />
                <input type="date" name="client_dob" required />
                <?php submit_button( __( 'Add', 'wsm' ), 'primary', 'new_client', false ); ?>
            </form>
        </div>
        <?php
    }

    public function staff_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'wsm_staff';

        if ( isset( $_POST['new_staff'] ) ) {
            $wpdb->insert( $table, array(
                'first_name' => sanitize_text_field( $_POST['staff_first_name'] ),
                'last_name'  => sanitize_text_field( $_POST['staff_last_name'] ),
                'email'      => sanitize_email( $_POST['staff_email'] ),
            ) );
            echo '<div class="updated"><p>' . esc_html__( 'Coach added.', 'wsm' ) . '</p></div>';
        }

        if ( isset( $_GET['delete'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['delete'] ) ) );
            echo '<div class="updated"><p>' . esc_html__( 'Staff deleted.', 'wsm' ) . '</p></div>';
        }

        $staff = $wpdb->get_results( "SELECT * FROM {$table}" );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( self::get_labels()['staff'] . 's' ); ?></h1>
            <table class="widefat wsm-grid">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'First Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Last Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wsm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $staff as $member ) : ?>
                    <tr>
                        <td><?php echo esc_html( $member->id ); ?></td>
                        <td><?php echo esc_html( $member->first_name ); ?></td>
                        <td><?php echo esc_html( $member->last_name ); ?></td>
                        <td><?php echo esc_html( $member->email ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wsm-staff&delete=' . $member->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wsm' ); ?>');">
                                <?php esc_html_e( 'Delete', 'wsm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Add New', 'wsm' ); ?></h2>
            <form method="post">
                <input type="text" name="staff_first_name" placeholder="<?php esc_attr_e( 'First Name', 'wsm' ); ?>" required />
                <input type="text" name="staff_last_name" placeholder="<?php esc_attr_e( 'Last Name', 'wsm' ); ?>" required />
                <input type="email" name="staff_email" placeholder="<?php esc_attr_e( 'Email', 'wsm' ); ?>" required />
                <?php submit_button( __( 'Add', 'wsm' ), 'primary', 'new_staff', false ); ?>
            </form>
        </div>
        <?php
    }

    public function levels_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'wsm_levels';

        if ( isset( $_POST['new_level'] ) ) {
            $wpdb->insert( $table, array( 'name' => sanitize_text_field( $_POST['level_name'] ) ) );
            echo '<div class="updated"><p>' . esc_html__( 'Level added.', 'wsm' ) . '</p></div>';
        }

        if ( isset( $_GET['delete'] ) ) {
            $wpdb->delete( $table, array( 'id' => absint( $_GET['delete'] ) ) );
            echo '<div class="updated"><p>' . esc_html__( 'Level deleted.', 'wsm' ) . '</p></div>';
        }

        $levels = $wpdb->get_results( "SELECT * FROM {$table}" );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( self::get_labels()['level'] . 's' ); ?></h1>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wsm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $levels as $level ) : ?>
                    <tr>
                        <td><?php echo esc_html( $level->id ); ?></td>
                        <td><?php echo esc_html( $level->name ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wsm-levels&delete=' . $level->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wsm' ); ?>');">
                                <?php esc_html_e( 'Delete', 'wsm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Add New', 'wsm' ); ?></h2>
            <form method="post">
                <input type="text" name="level_name" placeholder="<?php esc_attr_e( 'Name', 'wsm' ); ?>" required />
                <?php submit_button( __( 'Add', 'wsm' ), 'primary', 'new_level', false ); ?>
            </form>
        </div>
        <?php
    }

    public function classes_page() {
        global $wpdb;
        $classes_table      = $wpdb->prefix . 'wsm_classes';
        $levels_table       = $wpdb->prefix . 'wsm_levels';
        $staff_table        = $wpdb->prefix . 'wsm_staff';
        $class_staff_table  = $wpdb->prefix . 'wsm_class_staff';

        if ( isset( $_POST['new_class'] ) ) {
            $wpdb->insert( $classes_table, array(
                'title'     => sanitize_text_field( $_POST['class_title'] ),
                'level_id'  => absint( $_POST['class_level'] ),
                'schedule'  => sanitize_text_field( $_POST['class_schedule'] ),
            ) );
            $class_id = $wpdb->insert_id;
            if ( ! empty( $_POST['class_staff'] ) && is_array( $_POST['class_staff'] ) ) {
                foreach ( $_POST['class_staff'] as $sid ) {
                    $wpdb->insert( $class_staff_table, array(
                        'class_id' => $class_id,
                        'staff_id' => absint( $sid ),
                    ) );
                }
            }
            echo '<div class="updated"><p>' . esc_html__( 'Class added.', 'wsm' ) . '</p></div>';
        }

        if ( isset( $_GET['delete'] ) ) {
            $class_id = absint( $_GET['delete'] );
            $wpdb->delete( $classes_table, array( 'id' => $class_id ) );
            $wpdb->delete( $class_staff_table, array( 'class_id' => $class_id ) );
            echo '<div class="updated"><p>' . esc_html__( 'Class deleted.', 'wsm' ) . '</p></div>';
        }

        $classes = $wpdb->get_results( "SELECT c.*, l.name AS level_name, GROUP_CONCAT(CONCAT(s.first_name,' ',s.last_name) SEPARATOR ', ') AS staff_names FROM {$classes_table} c LEFT JOIN {$levels_table} l ON c.level_id = l.id LEFT JOIN {$class_staff_table} cs ON c.id = cs.class_id LEFT JOIN {$staff_table} s ON cs.staff_id = s.id GROUP BY c.id" );
        $levels  = $wpdb->get_results( "SELECT * FROM {$levels_table}" );
        $staff   = $wpdb->get_results( "SELECT * FROM {$staff_table}" );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( self::get_labels()['class'] . 'es' ); ?></h1>
            <table class="widefat wsm-grid">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Title', 'wsm' ); ?></th>
                        <th><?php echo esc_html( self::get_labels()['level'] ); ?></th>
                        <th><?php esc_html_e( 'Schedule', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Coaches', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wsm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $classes as $class ) : ?>
                    <tr>
                        <td><?php echo esc_html( $class->id ); ?></td>
                        <td><?php echo esc_html( $class->title ); ?></td>
                        <td><?php echo esc_html( $class->level_name ); ?></td>
                        <td><?php echo esc_html( $class->schedule ); ?></td>
                        <td><?php echo esc_html( $class->staff_names ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wsm-classes&delete=' . $class->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wsm' ); ?>');">
                                <?php esc_html_e( 'Delete', 'wsm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Add New', 'wsm' ); ?></h2>
            <form method="post">
                <input type="text" name="class_title" placeholder="<?php esc_attr_e( 'Title', 'wsm' ); ?>" required />
                <select name="class_level">
                    <?php foreach ( $levels as $level ) : ?>
                        <option value="<?php echo esc_attr( $level->id ); ?>"><?php echo esc_html( $level->name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <fieldset>
                    <legend><?php esc_html_e( 'Coaches', 'wsm' ); ?></legend>
                    <?php foreach ( $staff as $coach ) : ?>
                        <label>
                            <input type="checkbox" name="class_staff[]" value="<?php echo esc_attr( $coach->id ); ?>" />
                            <?php echo esc_html( $coach->first_name . ' ' . $coach->last_name ); ?>
                        </label><br />
                    <?php endforeach; ?>
                </fieldset>
                <input type="datetime-local" name="class_schedule" required />
                <?php submit_button( __( 'Add', 'wsm' ), 'primary', 'new_class', false ); ?>
            </form>
        </div>
        <?php
    }

    public function parents_page() {
        global $wpdb;
        $parents_table = $wpdb->prefix . 'wsm_parents';
        $link_table    = $wpdb->prefix . 'wsm_client_parents';
        $clients_table = $wpdb->prefix . 'wsm_clients';

        if ( isset( $_POST['new_parent'] ) ) {
            $wpdb->insert( $parents_table, array(
                'first_name' => sanitize_text_field( $_POST['parent_first_name'] ),
                'last_name'  => sanitize_text_field( $_POST['parent_last_name'] ),
                'email'      => sanitize_email( $_POST['parent_email'] ),
            ) );
            $parent_id = $wpdb->insert_id;

            if ( ! empty( $_POST['client_ids'] ) && is_array( $_POST['client_ids'] ) ) {
                foreach ( $_POST['client_ids'] as $cid ) {
                    $wpdb->insert( $link_table, array(
                        'client_id' => absint( $cid ),
                        'parent_id' => $parent_id,
                    ) );
                }
            }

            echo '<div class="updated"><p>' . esc_html__( 'Parent added.', 'wsm' ) . '</p></div>';
        }

        if ( isset( $_GET['delete'] ) ) {
            $parent_id = absint( $_GET['delete'] );
            $wpdb->delete( $parents_table, array( 'id' => $parent_id ) );
            $wpdb->delete( $link_table, array( 'parent_id' => $parent_id ) );
            echo '<div class="updated"><p>' . esc_html__( 'Parent deleted.', 'wsm' ) . '</p></div>';
        }

        $parents = $wpdb->get_results( "SELECT * FROM {$parents_table}" );
        $clients = $wpdb->get_results( "SELECT * FROM {$clients_table}" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Parents', 'wsm' ); ?></h1>
            <table class="widefat wsm-grid">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'First Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Last Name', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Athletes', 'wsm' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'wsm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $parents as $parent ) : ?>
                    <tr>
                        <td><?php echo esc_html( $parent->id ); ?></td>
                        <td><?php echo esc_html( $parent->first_name ); ?></td>
                        <td><?php echo esc_html( $parent->last_name ); ?></td>
                        <td><?php echo esc_html( $parent->email ); ?></td>
                        <td>
                            <?php
                            $client_ids = $wpdb->get_col( $wpdb->prepare( "SELECT client_id FROM {$link_table} WHERE parent_id = %d", $parent->id ) );
                            $names = array();
                            if ( $client_ids ) {
                                $placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
                                $sql = "SELECT CONCAT(first_name, ' ', last_name) FROM {$clients_table} WHERE id IN ($placeholders)";
                                $prepared = $wpdb->prepare( $sql, $client_ids );
                                $names = $wpdb->get_col( $prepared );
                            }
                            echo esc_html( implode( ', ', $names ) );
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wsm-parents&delete=' . $parent->id ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wsm' ); ?>');">
                                <?php esc_html_e( 'Delete', 'wsm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Add New', 'wsm' ); ?></h2>
            <form method="post">
                <input type="text" name="parent_first_name" placeholder="<?php esc_attr_e( 'First Name', 'wsm' ); ?>" required />
                <input type="text" name="parent_last_name" placeholder="<?php esc_attr_e( 'Last Name', 'wsm' ); ?>" required />
                <input type="email" name="parent_email" placeholder="<?php esc_attr_e( 'Email', 'wsm' ); ?>" required />
                <fieldset>
                    <legend><?php echo esc_html( self::get_labels()['client'] . 's' ); ?></legend>
                    <?php foreach ( $clients as $client ) : ?>
                        <label>
                            <input type="checkbox" name="client_ids[]" value="<?php echo esc_attr( $client->id ); ?>" />
                            <?php echo esc_html( $client->first_name . ' ' . $client->last_name ); ?>
                        </label><br />
                    <?php endforeach; ?>
                </fieldset>
                <?php submit_button( __( 'Add', 'wsm' ), 'primary', 'new_parent', false ); ?>
            </form>
        </div>
        <?php
    }

    public function admin_assets( $hook ) {
        if ( strpos( $hook, 'wsm-' ) !== false ) {
            wp_enqueue_script( 'wsm-admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin/admin.js', array( 'jquery' ), self::VERSION, true );
            wp_enqueue_style( 'wsm-admin', plugin_dir_url( __FILE__ ) . 'assets/css/admin/admin.css', array(), self::VERSION );
        }
    }

    public function ajax_add_client() {
        global $wpdb;
        $table = $wpdb->prefix . 'wsm_clients';
        $wpdb->insert( $table, array(
            'first_name' => sanitize_text_field( $_POST['client_first_name'] ),
            'last_name'  => sanitize_text_field( $_POST['client_last_name'] ),
            'email'      => sanitize_email( $_POST['client_email'] ),
            'dob'        => sanitize_text_field( $_POST['client_dob'] ),
        ) );
        wp_send_json_success( __( 'Athlete added.', 'wsm' ) );
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

<?php
/**
 * Plugin Name: WP S3 Sync Plugin
 * Plugin URI: https://github.com/jeromeheissler/wp-s3-sync-plugin
 * Description: Automatically synchronizes a directory to an Amazon S3 bucket.
 * Version: 1.0
 * Author: Jérôme Heissler
 * Author URI: https://github.com/jeromeheissler
 * Text Domain: wp-s3-sync
 * Domain Path: /languages
 * License: MIT
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * You can define these constants in wp-config.php to force values via the environment.
 * For example:
 *   define('WP_S3_SYNC_WATCH_DIR', ABSPATH . 'wp-content/static-export');
 *   define('WP_S3_SYNC_DEST_BUCKET', 'your-bucket');
 *   define('WP_S3_SYNC_REGION', 'eu-west-3');
 *   define('WP_S3_SYNC_HOOK_NAME', 'custom_hook');
 *   define('WP_S3_SYNC_AWS_ACCESS_KEY', 'your-access-key');
 *   define('WP_S3_SYNC_AWS_SECRET_KEY', 'your-secret-key');
 *   define('WP_S3_SYNC_SYNC_ENABLED', '1');
 */
if ( ! defined( 'WP_S3_SYNC_WATCH_DIR' ) ) {
    define( 'WP_S3_SYNC_WATCH_DIR', '' );
}
if ( ! defined( 'WP_S3_SYNC_DEST_BUCKET' ) ) {
    define( 'WP_S3_SYNC_DEST_BUCKET', '' );
}
if ( ! defined( 'WP_S3_SYNC_REGION' ) ) {
    define( 'WP_S3_SYNC_REGION', '' );
}
if ( ! defined( 'WP_S3_SYNC_HOOK_NAME' ) ) {
    define( 'WP_S3_SYNC_HOOK_NAME', '' );
}
if ( ! defined( 'WP_S3_SYNC_AWS_ACCESS_KEY' ) ) {
    define( 'WP_S3_SYNC_AWS_ACCESS_KEY', '' );
}
if ( ! defined( 'WP_S3_SYNC_AWS_SECRET_KEY' ) ) {
    define( 'WP_S3_SYNC_AWS_SECRET_KEY', '' );
}
if ( ! defined( 'WP_S3_SYNC_SYNC_ENABLED' ) ) {
    define( 'WP_S3_SYNC_SYNC_ENABLED', '' );
}

define( 'WP_S3_SYNC_OPTION_GROUP', 'wp_s3_sync_options' );
define( 'WP_S3_SYNC_OPTION_NAME', 'wp_s3_sync_settings' );

/**
 * Adds the admin menu for the plugin.
 */
function wp_s3_sync_add_admin_menu() {
    $options = get_option( WP_S3_SYNC_OPTION_NAME );
    $sync_enabled = isset( $options['sync_enabled'] ) ? (bool) $options['sync_enabled'] : true;
    $menu_icon = $sync_enabled ? 'dashicons-upload' : 'dashicons-no-alt';
    add_menu_page(
        __( 'WP S3 Sync Settings', 'wp-s3-sync' ),
        __( 'S3 Sync', 'wp-s3-sync' ) . ( ! $sync_enabled ? ' (' . __( 'disabled', 'wp-s3-sync' ) . ')' : '' ),
        'manage_options',
        'wp-s3-sync',
        'wp_s3_sync_options_page',
        $menu_icon
    );
}
add_action( 'admin_menu', 'wp_s3_sync_add_admin_menu' );

/**
 * Registers plugin settings.
 */
function wp_s3_sync_register_settings() {
    register_setting( WP_S3_SYNC_OPTION_GROUP, WP_S3_SYNC_OPTION_NAME );
}
add_action( 'admin_init', 'wp_s3_sync_register_settings' );

/**
 * Gets the setting value, prioritizing environment variables or constants with prefix WP_S3_SYNC_.
 */
function wp_s3_sync_get_setting( $key, $default = '' ) {
    $envKey = 'WP_S3_SYNC_' . strtoupper( $key );
    if ( defined( $envKey ) && constant( $envKey ) !== '' ) {
        return constant( $envKey );
    }
    $env = getenv( $envKey );
    if ( ! empty( $env ) ) {
        return $env;
    }
    $options = get_option( WP_S3_SYNC_OPTION_NAME );
    return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Displays the plugin settings page.
 */
function wp_s3_sync_options_page() {
    $export_dir = wp_s3_sync_get_setting( 'watch_dir', ABSPATH . 'wp-content/static-export' );
    $s3_bucket  = wp_s3_sync_get_setting( 'dest_bucket', '' );
    $s3_region  = wp_s3_sync_get_setting( 'region', 'eu-west-3' );
    $aws_access_key = wp_s3_sync_get_setting( 'aws_access_key', '' );
    $aws_secret_key = wp_s3_sync_get_setting( 'aws_secret_key', '' );
    $hook_name  = wp_s3_sync_get_setting( 'hook_name', '' );
    $sync_enabled = wp_s3_sync_get_setting( 'sync_enabled', '1' );

    $disable_export_dir = ( defined( 'WP_S3_SYNC_WATCH_DIR' ) && WP_S3_SYNC_WATCH_DIR !== '' ) || !empty( getenv( 'WP_S3_SYNC_WATCH_DIR' ) );
    $disable_s3_bucket  = ( defined( 'WP_S3_SYNC_DEST_BUCKET' ) && WP_S3_SYNC_DEST_BUCKET !== '' ) || !empty( getenv( 'WP_S3_SYNC_DEST_BUCKET' ) );
    $disable_s3_region  = ( defined( 'WP_S3_SYNC_REGION' ) && WP_S3_SYNC_REGION !== '' ) || !empty( getenv( 'WP_S3_SYNC_REGION' ) );
    $disable_hook_name  = ( defined( 'WP_S3_SYNC_HOOK_NAME' ) && WP_S3_SYNC_HOOK_NAME !== '' ) || !empty( getenv( 'WP_S3_SYNC_HOOK_NAME' ) );
    $disable_aws_access = ( defined( 'WP_S3_SYNC_AWS_ACCESS_KEY' ) && WP_S3_SYNC_AWS_ACCESS_KEY !== '' ) || !empty( getenv( 'WP_S3_SYNC_AWS_ACCESS_KEY' ) );
    $disable_aws_secret = ( defined( 'WP_S3_SYNC_AWS_SECRET_KEY' ) && WP_S3_SYNC_AWS_SECRET_KEY !== '' ) || !empty( getenv( 'WP_S3_SYNC_AWS_SECRET_KEY' ) );
    $disable_sync_enabled = ( defined( 'WP_S3_SYNC_SYNC_ENABLED' ) && WP_S3_SYNC_SYNC_ENABLED !== '' ) || !empty( getenv( 'WP_S3_SYNC_SYNC_ENABLED' ) );
    ?>
    <div class="wrap">
        <h1><?php _e( 'WP S3 Sync Settings', 'wp-s3-sync' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( WP_S3_SYNC_OPTION_GROUP ); ?>
            <?php do_settings_sections( WP_S3_SYNC_OPTION_GROUP ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Export Directory (Watch Dir)', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="text" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[watch_dir]" value="<?php echo esc_attr( $export_dir ); ?>" size="50" <?php echo $disable_export_dir ? 'readonly' : ''; ?> />
                        <p class="description"><?php _e( 'Absolute path where Simply Static generates files.', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'S3 Bucket (Dest Bucket)', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="text" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[dest_bucket]" value="<?php echo esc_attr( $s3_bucket ); ?>" size="50" <?php echo $disable_s3_bucket ? 'readonly' : ''; ?> />
                        <p class="description"><?php _e( 'Name of the S3 bucket to sync files to.', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'S3 Region', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="text" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[region]" value="<?php echo esc_attr( $s3_region ); ?>" size="20" <?php echo $disable_s3_region ? 'readonly' : ''; ?> />
                        <p class="description"><?php _e( 'Region of the S3 bucket (default is eu-west-3).', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'AWS Access Key ID', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="text" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[aws_access_key]" value="<?php echo esc_attr( $aws_access_key ); ?>" size="50" <?php echo $disable_aws_access ? 'readonly' : ''; ?> />
                        <p class="description"><?php _e( 'Leave blank to use the IAM role.', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'AWS Secret Access Key', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="password" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[aws_secret_key]" value="<?php echo esc_attr( $aws_secret_key ); ?>" size="50" <?php echo $disable_aws_secret ? 'readonly' : ''; ?> />
                        <p class="description"><?php _e( 'Leave blank to use the IAM role.', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Trigger Hook', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="text" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[hook_name]" value="<?php echo esc_attr( $hook_name ); ?>" size="50" <?php echo $disable_hook_name ? 'readonly' : ''; ?> />
                        <p class="description"><?php _e( 'Name of the hook on which to trigger the sync.', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Enable Sync', 'wp-s3-sync' ); ?></th>
                    <td>
                        <input type="hidden" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[sync_enabled]" value="0" />
                        <label>
                            <input type="checkbox" name="<?php echo WP_S3_SYNC_OPTION_NAME; ?>[sync_enabled]" value="1" <?php checked( $sync_enabled, '1' ); ?> <?php echo $disable_sync_enabled ? 'readonly' : ''; ?> />
                            <?php _e( 'Enable', 'wp-s3-sync' ); ?>
                        </label>
                        <p class="description"><?php _e( 'Uncheck to disable automatic synchronization.', 'wp-s3-sync' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Syncs the export directory to S3.
 */
function wp_s3_sync_static_export() {
    $options = get_option( WP_S3_SYNC_OPTION_NAME );
    if ( empty( $options['watch_dir'] ) || empty( $options['dest_bucket'] ) ) {
        error_log( __( 'WP S3 Sync: Incomplete settings. Please configure the export directory and S3 bucket.', 'wp-s3-sync' ) );
        return;
    }
    if ( empty( $options['sync_enabled'] ) || $options['sync_enabled'] != '1' ) {
        error_log( __( 'WP S3 Sync: Synchronization is disabled.', 'wp-s3-sync' ) );
        return;
    }
    $export_dir = $options['watch_dir'];
    $bucket = $options['dest_bucket'];
    $region = ! empty( $options['region'] ) ? $options['region'] : 'eu-west-3';
    $aws_access_key = ! empty( $options['aws_access_key'] ) ? $options['aws_access_key'] : null;
    $aws_secret_key = ! empty( $options['aws_secret_key'] ) ? $options['aws_secret_key'] : null;

    if ( ! is_dir( $export_dir ) ) {
        error_log( sprintf( __( 'WP S3 Sync: Export directory does not exist: %s', 'wp-s3-sync' ), $export_dir ) );
        return;
    }

    $s3Config = [
        'region'  => $region,
        'version' => 'latest',
    ];

    if ( $aws_access_key && $aws_secret_key ) {
        $s3Config['credentials'] = [
            'key'    => $aws_access_key,
            'secret' => $aws_secret_key,
        ];
    }
    $s3Client = new Aws\S3\S3Client($s3Config);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $export_dir, RecursiveDirectoryIterator::SKIP_DOTS )
    );

    foreach ( $iterator as $file ) {
        if ( ! $file->isFile() ) {
            continue;
        }
        $filePath = $file->getPathname();
        $relativePath = ltrim( str_replace( $export_dir, '', $filePath ), DIRECTORY_SEPARATOR );

        try {
            $s3Client->putObject([
                'Bucket'     => $bucket,
                'Key'        => $relativePath,
                'SourceFile' => $filePath,
                'ACL'        => 'public-read',
            ]);
            error_log( sprintf( __( 'WP S3 Sync: Uploaded file: %s', 'wp-s3-sync' ), $relativePath ) );
        } catch (Aws\Exception\AwsException $e) {
            error_log( sprintf( __( 'WP S3 Sync: Error uploading %s: %s', 'wp-s3-sync' ), $relativePath, $e->getMessage() ) );
        }
    }
}

/**
 * Sets up the sync hook dynamically based on settings.
 */
function wp_s3_sync_setup_hook() {
    $options = get_option( WP_S3_SYNC_OPTION_NAME );
    $hook = ! empty( $options['hook_name'] ) ? $options['hook_name'] : '';
    if ( ! empty( $options['sync_enabled'] ) && $options['sync_enabled'] == '1' ) {
        add_action( $hook, 'wp_s3_sync_static_export' );
        error_log( sprintf( __( 'WP S3 Sync: Sync enabled on hook: %s', 'wp-s3-sync' ), $hook ) );
    } else {
        error_log( __( 'WP S3 Sync: Sync disabled.', 'wp-s3-sync' ) );
    }
}
add_action( 'init', 'wp_s3_sync_setup_hook' );

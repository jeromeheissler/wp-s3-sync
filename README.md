# WP S3 Sync Plugin

**WP S3 Sync Plugin** automatically synchronizes a specified directory (generated by Simply Static) to an Amazon S3 bucket. All options can be configured via the WordPress admin settings page or through environment variables prefixed with `WP_S3_SYNC_`.

## Features

- **Directory Synchronization:** Automatically uploads files from a chosen local export directory to an S3 bucket.
- **Configurable Trigger:** Set the hook on which the synchronization runs.
- **IAM or AWS Credentials:** Configure AWS access via IAM role or by specifying AWS Access Key ID and Secret Access Key.
- **Multilingual:** The plugin is available in English, French, and Spanish.
- **Easy Setup:** Use environment variables to force settings in production.

## Installation

1. **Upload the Plugin:**
   - Clone or download this repository and place the `wp-s3-sync-plugin.php` file (and associated files) in your `wp-content/plugins/wp-s3-sync-plugin/` directory.
   - Ensure the `languages/` folder is also included.

2. **Install Dependencies:**
   - Navigate to the plugin directory and run `composer install` to install the AWS SDK for PHP.
   - (Make sure you have [Composer](https://getcomposer.org/) installed.)

3. **Activate the Plugin:**
   - Log in to your WordPress admin dashboard.
   - Go to **Plugins** and activate **WP S3 Sync Plugin**.

4. **Configuration:**
   - You can configure the plugin settings from **S3 Sync** in the admin menu.
   - Alternatively, you can define environment variables or constants in your `wp-config.php` file with the prefix `WP_S3_SYNC_`. For example:
     ```php
     define('WP_S3_SYNC_WATCH_DIR', ABSPATH . 'wp-content/static-export');
     define('WP_S3_SYNC_DEST_BUCKET', 'your-bucket-name');
     define('WP_S3_SYNC_REGION', 'eu-west-3');
     define('WP_S3_SYNC_HOOK_NAME', 'ss_after_cleanup'); // or any custom hook
     define('WP_S3_SYNC_AWS_ACCESS_KEY', 'your-access-key'); // leave empty to use IAM role
     define('WP_S3_SYNC_AWS_SECRET_KEY', 'your-secret-key'); // leave empty to use IAM role
     define('WP_S3_SYNC_SYNC_ENABLED', '1');
     ```
   - When environment variables are set, the corresponding input fields in the settings page will be read-only.

## Usage

- The plugin uses a hook (default: `ss_after_cleanup`) to trigger synchronization. You can change this hook via the settings page.
- Once triggered, the plugin scans the specified export directory and uploads all files to the S3 bucket using the AWS SDK.
- To disable automatic synchronization, simply uncheck the **Enable Synchronization** option in the settings.

## Multilingual Support

The plugin uses the text domain `wp-s3-sync` for internationalization. Translation files (POT, PO, and MO) should be placed in the `languages/` folder. Contributions for French and Spanish translations are welcome.

## Contributing

Feel free to fork this repository and submit pull requests. Please ensure your code follows WordPress coding standards and includes proper internationalization functions.

## License

This plugin is licensed under the MIT License.

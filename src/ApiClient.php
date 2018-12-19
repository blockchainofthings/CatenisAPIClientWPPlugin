<?php

namespace Catenis\WP;

use Exception;
use Catenis\WP\Catenis\ApiClient as CatenisApiClient;


class ApiClient {
    private $pluginPath;

    private static function trimArray(&$arr) {
        if (is_array($arr)) {
            foreach ($arr as $idx => $value) {
                if (is_array($value)) {
                    self::trimArray($value);

                    if (!count($value)) {
                        unset($arr[$idx]);
                    }
                }
                else {
                    $arr[$idx] = trim($value);

                    if ($arr[$idx] == '') {
                        unset($arr[$idx]);
                    }
                }
            }

            if (!count($arr)) {
                $arr = NULL;
            }
        }
    }

    public static function sanitizeOptions($opts) {
        self::trimArray($opts);

        return $opts;
    }

    function __construct($pluginPath) {
        $this->pluginPath = $pluginPath;

        // Wire up action handlers
        add_action('admin_init', [$this, 'adminInitHandler']);
        add_action('admin_menu', [$this, 'adminMenuHandler']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScriptsHandler']);
        add_action('wp_ajax_call_api_method', [$this, 'callApiMethod']);
        // Note: the following is required so non-logged-in users can execute the ajax call
        add_action('wp_ajax_nopriv_call_api_method', [$this, 'callApiMethod']);
    }

    function enqueueScriptsHandler() {
        global $post;

        if ($post->post_type === 'page') {
            // Make sure that plugin's JavaScript files are only added to pages that request it
            $postMetadata = get_post_meta($post->ID, '_ctn_api_client', true);

            if (!empty($postMetadata['ctn_load_client']) && $postMetadata['ctn_load_client'] === 'on') {
                wp_enqueue_script('ctn_api_proxy',
                    plugins_url('/js/CatenisApiProxy.js', $this->pluginPath),
                    ['jquery']
                );

                wp_localize_script('ctn_api_proxy',
                    'ctn_api_proxy_obj', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce(__FILE__),
                        'post_id' => $post->ID
                    ]
                );
            }
        }
    }

    function adminInitHandler() {
        // Set up Catenis API client options
        register_setting( 'ctn_api_client_opts', 'ctn_client_credentials', ['sanitize_callback' => ['\Catenis\WP\ApiClient', 'sanitizeOptions']]);
        register_setting( 'ctn_api_client_opts', 'ctn_client_options', ['sanitize_callback' => ['\Catenis\WP\ApiClient', 'sanitizeOptions']]);

        add_settings_section('ctn_client_credentials', 'Client Credentials', [$this, 'displayClientCredentialsSectionInfo'],
            'ctn_api_client_opts'
        );
        add_settings_section('ctn_client_options', 'Client Options', [$this, 'displayClientOptionsSectionInfo'],
            'ctn_api_client_opts'
        );

        // Client credentials fields
        add_settings_field('ctn_device_id', 'Device ID', [$this, 'displayDeviceIdFieldContents'],
            'ctn_api_client_opts', 'ctn_client_credentials', [
            'label_for' => 'ctn_device_id'
        ]);
        add_settings_field('ctn_api_access_secret', 'API Access Secret', [$this, 'displayApiAccessSecretFieldContents'],
            'ctn_api_client_opts', 'ctn_client_credentials', [
            'label_for' => 'ctn_api_access_secret'
        ]);

        // Client options fields
        add_settings_field('ctn_host', 'Host', [$this, 'displayHostFieldContents'],
            'ctn_api_client_opts', 'ctn_client_options', [
            'label_for' => 'ctn_host'
        ]);
        add_settings_field('ctn_environment', 'Environment', [$this, 'displayEnvironmentFieldContents'],
            'ctn_api_client_opts', 'ctn_client_options', [
            'label_for' => 'ctn_environment'
        ]);
        add_settings_field('ctn_secure', 'Secure Connection', [$this, 'displaySecureConnectionFieldContents'],
            'ctn_api_client_opts', 'ctn_client_options', [
            'label_for' => 'ctn_secure'
        ]);

        // Add Catenis API client config panel to pages (post type = 'page')
        add_meta_box('ctn_api_client_meta', 'Catenis API Client', [$this, 'displayPostMetadataFormContents'], 'page', 'normal', 'high');

        // Wire up action handler to save post metadata
        add_action('save_post', [$this, 'savePostMetadata']);
    }

    function adminMenuHandler() {
        add_options_page('Catenis API Client', 'Catenis API Client', 'manage_options',
            'ctn_api_client_opts', [$this, 'displayOptionsPage']
        );
    }

    function displayOptionsPage() {
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
<?php
        // output security fields for the registered setting "ctn_api_client_opts"
        settings_fields( 'ctn_api_client_opts' );
        // output setting sections and their fields
        do_settings_sections( 'ctn_api_client_opts' );
        // output save settings button
        submit_button( 'Save Settings' );
?>
    </form>
</div>
<?php
    }

    function displayClientCredentialsSectionInfo() {
        echo '<p>Enter credentials for the Catenis device to use with the Catenis API client</p>';
    }

    function displayClientOptionsSectionInfo() {
        echo '<p>Enter the options for instantiating the Catenis API client<br><span style="color:gray">(leave blank for default settings)</span></p>';
    }

    function displayDeviceIdFieldContents($args) {
        $ctnClientCredentials = get_option('ctn_client_credentials');
?>
<input type="text" id="<?php echo $args['label_for'] ?>" name="ctn_client_credentials[<?php echo $args['label_for'] ?>]" class="regular-text" maxlength="20" autocomplete="off"
       <?php echo !empty($ctnClientCredentials[$args['label_for']]) ? 'value="' . esc_attr($ctnClientCredentials[$args['label_for']]) . '"' : '' ?>>
<?php
    }

    function displayApiAccessSecretFieldContents($args) {
        $ctnClientCredentials = get_option('ctn_client_credentials');
?>
<input type="text" id="<?php echo $args['label_for'] ?>" name="ctn_client_credentials[<?php echo $args['label_for'] ?>]" class="regular-text" maxlength="128" autocomplete="off"
       <?php echo !empty($ctnClientCredentials[$args['label_for']]) ? 'value="' . esc_attr($ctnClientCredentials[$args['label_for']]) . '"' : '' ?>>
<?php
    }

    function displayHostFieldContents($args) {
        $ctnClientOptions = get_option('ctn_client_options');
?>
<input type="text" id="<?php echo $args['label_for'] ?>" name="ctn_client_options[<?php echo $args['label_for'] ?>]" class="regular-text" maxlength="80" autocomplete="off"
    <?php echo !empty($ctnClientOptions[$args['label_for']]) ? 'value="' . esc_attr($ctnClientOptions[$args['label_for']]) . '"' : '' ?>>
<?php
    }

    function displayEnvironmentFieldContents($args) {
        $ctnClientOptions = get_option('ctn_client_options');
?>
<select id="<?php echo $args['label_for'] ?>" name="ctn_client_options[<?php echo $args['label_for'] ?>]">
    <option value=""></option>
    <option value="prod" <?php echo !empty($ctnClientOptions[$args['label_for']]) ? selected($ctnClientOptions[$args['label_for']], 'prod', false) : ''; ?>>Production</option>
    <option value="sandbox" <?php echo !empty($ctnClientOptions[$args['label_for']]) ? selected($ctnClientOptions[$args['label_for']], 'sandbox', false) : ''; ?>>Sandbox</option>
</select>
<?php
    }

    function displaySecureConnectionFieldContents($args) {
        $ctnClientOptions = get_option('ctn_client_options');
?>
<select id="<?php echo $args['label_for'] ?>" name="ctn_client_options[<?php echo $args['label_for'] ?>]">
    <option value=""></option>
    <option value="on" <?php echo !empty($ctnClientOptions[$args['label_for']]) ? selected($ctnClientOptions[$args['label_for']], 'on', false) : ''; ?>>On</option>
    <option value="off" <?php echo !empty($ctnClientOptions[$args['label_for']]) ? selected($ctnClientOptions[$args['label_for']], 'off', false) : ''; ?>>Off</option>
</select>
<?php
    }

    function displayPostMetadataFormContents() {
        global $post;

        $postMetadata = get_post_meta($post->ID,'_ctn_api_client',true);

        // Include post metadata form contents
        include_once __DIR__ . '/../inc/PostMetadataFormContents.php';

        // Create a custom nonce for submit verification later
        echo '<input type="hidden" name="catenis_api_client_nonce" value="' . wp_create_nonce(__FILE__) . '" />';
    }

    function savePostMetadata($post_id) {
        // Make sure data came from our post metadata form
        if (! isset($_POST['catenis_api_client_nonce']) || !wp_verify_nonce($_POST['catenis_api_client_nonce'],__FILE__)) {
            return $post_id;
        }
        
        // Make sure that user has required permission
        if ($_POST['post_type'] === 'page') {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        // Save post metadata
        $currPostMetadata = get_post_meta($post_id, '_ctn_api_client', true);
        $newPostMetadata = $_POST['_ctn_api_client'];
        self::trimArray($newPostMetadata);

        if ($currPostMetadata) {
            if (is_null($newPostMetadata)) {
                delete_post_meta($post_id,'_ctn_api_client');
            }
            else {
                update_post_meta($post_id,'_ctn_api_client', $newPostMetadata);
            }
        }
        else if (!is_null($newPostMetadata)) {
            add_post_meta($post_id,'_ctn_api_client', $newPostMetadata,true);
        }

        return $post_id;
    }

    function callApiMethod() {
        check_ajax_referer(__FILE__);

        $postID = $_POST['post_id'];

        if (empty($postID) || empty($postMetadata = get_post_meta($postID, '_ctn_api_client', true))) {
            wp_send_json_error('Invalid or undefined post ID', 500);
            return;
        }

        // Prepare to instantiate Catenis API client
        $globalCtnClientCredentials = get_option('ctn_client_credentials');
        $deviceId = !empty($postMetadata['ctn_device_id']) ? $postMetadata['ctn_device_id']
                : (!empty($globalCtnClientCredentials['ctn_device_id']) ? $globalCtnClientCredentials['ctn_device_id'] : '');
        $apiAccessSecret = !empty($postMetadata['ctn_api_access_secret']) ? $postMetadata['ctn_api_access_secret']
                : (!empty($globalCtnClientCredentials['ctn_api_access_secret']) ? $globalCtnClientCredentials['ctn_api_access_secret'] : '');

        $globalCtnClientOptions = get_option('ctn_client_options');
        $options = [];

        if (!empty($postMetadata['ctn_host'])) {
            $options['host'] = $postMetadata['ctn_host'];
        }
        elseif (!empty($globalCtnClientOptions['ctn_host'])) {
            $options['host'] = $globalCtnClientOptions['ctn_host'];
        }

        if (!empty($postMetadata['ctn_environment'])) {
            $options['environment'] = $postMetadata['ctn_environment'];
        }
        elseif (!empty($globalCtnClientOptions['ctn_environment'])) {
            $options['environment'] = $globalCtnClientOptions['ctn_environment'];
        }

        if (!empty($postMetadata['ctn_secure'])) {
            $options['secure'] = $postMetadata['ctn_secure'] === 'on';
        }
        elseif (!empty($globalCtnClientOptions['ctn_secure'])) {
            $options['secure'] = $globalCtnClientOptions['ctn_secure'] === 'on';
        }

        try {
            // Instantiate Catenis API client
            $ctnApiClient = new CatenisApiClient($deviceId, $apiAccessSecret, !empty($options) ? $options : null);
        }
        catch (Exception $ex) {
            wp_send_json_error('Error instantiating Catenis API client: ' . $ex->getMessage(), 500);
            return;
        }

        try {
            // Get passed in parameter
            $methodName = $_POST['method_name'];
            $methodParams = json_decode(stripslashes($_POST['method_params']), true);

            // Call Catenis API method
            $result = call_user_func_array([$ctnApiClient, $methodName], $methodParams);

            wp_send_json_success($result);
        } catch (Exception $ex) {
            wp_send_json_error($ex->getMessage(), 500);
        }
    }
}
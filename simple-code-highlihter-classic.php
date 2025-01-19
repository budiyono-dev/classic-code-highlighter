<?php
/**
 * Plugin Name: Classic Code Highlighter
 * Description: Code highlighter for classic editor by Codingduluaja.com
 * Version: 1.1.0
 * Author: Budiyono
 */

namespace CDA\CodeHighlighter\Classic;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class ClassicCodeHighlighter {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        add_action('enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_shortcode('dynamic_fields', [$this, 'shortcode']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('dcf-styles', plugins_url('assets/style.css', __FILE__), [], '1.0');
        wp_enqueue_script('dcf-scripts', plugins_url('assets/script.js', __FILE__), ['jquery'], '1.0', true);
        wp_localize_script( 'dcf-scripts', 'dcf_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

    }

    public function add_meta_box() {
        add_meta_box(
            'dcf_meta_box',
            'Dynamic Custom Fields',
            [$this, 'meta_box_callback'],
            'post', // Adjust post types as needed
            'normal',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('dcf_nonce', 'dcf_nonce_field');
        $fields = get_post_meta($post->ID, '_dcf_fields', true);
        if (!$fields) {
            $fields = [];
        }
        ?>
        <div id="dcf-fields-container">
            <?php
            if ($fields) {
                foreach ($fields as $key => $field) {
                    $this->render_field($key, $field);
                }
            }
            ?>
        </div>
        <button type="button" class="button" id="dcf-add-field">Add Field</button>
        <script type="text/javascript">
            var nextFieldKey = <?php echo count($fields); ?>;
        </script>
        <div id="shortcode-output">
            <?php
            if($fields){
                foreach ($fields as $key => $field) {
                    echo '<p>Shortcode for "'. esc_attr($field['title']) . '": <code>[dynamic_fields id="'. $key .'"]</code></p>';
                }
            }
            ?>
        </div>
        <?php
    }
    private function render_field($key, $field) {
        ?>
        <div class="dcf-field">
            <input type="text" name="dcf_fields[<?php echo $key; ?>][title]" placeholder="Field Title" value="<?php echo esc_attr($field['title']); ?>">
            <textarea name="dcf_fields[<?php echo $key; ?>][content]" placeholder="Field Content"><?php echo esc_textarea($field['content']); ?></textarea>
            <button type="button" class="button dcf-remove-field">Remove</button>
        </div>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['dcf_nonce_field']) || !wp_verify_nonce($_POST['dcf_nonce_field'], 'dcf_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        if (isset($_POST['dcf_fields'])) {
            $fields = array_map(function($field){
                return array(
                    'title' => sanitize_text_field($field['title']),
                    'content' => wp_kses_post($field['content'])
                );
            }, $_POST['dcf_fields']);
            update_post_meta($post_id, '_dcf_fields', $fields);
        } else {
            delete_post_meta($post_id, '_dcf_fields');
        }
    }
    public function shortcode($atts) {
        $atts = shortcode_atts( array(
            'id' => null, // Add an 'id' attribute to the shortcode
        ), $atts );

        if (is_null($atts['id'])) {
            return ''; // Return nothing if no ID is provided
        }

        $post_id = get_the_ID();
        $fields = get_post_meta($post_id, '_dcf_fields', true);

        if ($fields && isset($fields[$atts['id']])) { // Check if the field with the given ID exists
            $field = $fields[$atts['id']];
            $output = '<div class="dcf-item">';
            $output .= '<h3>' . esc_html($field['title']) . '</h3>';
            $output .= '<div class="dcf-content">' . wp_kses_post($field['content']) . '</div>';
            $output .= '</div>';
            return $output;
        }

        return ''; // Return nothing if the field is not found
    }
}

// Initialize the plugin
ClassicCodeHighlighter::get_instance();

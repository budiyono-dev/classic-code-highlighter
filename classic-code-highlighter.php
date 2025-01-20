<?php
/**
 * Plugin Name: Classic Code Highlighter
 * Description: Code highlighter for classic editor by Codingduluaja.com
 * Version: 1.1.0
 * Author: Budiyono
 */

namespace CDA\CodeHighlighter\Classic;

if ( ! defined( 'WPINC' ) ) {
    die;
}

class ClassicCodeHighlighter {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        add_action( 'enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scrips_theme' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_meta_box_data' ] );
        add_shortcode( 'cch', [ $this, 'shortcode' ] );
    }

    public function enqueue_scrips_theme() {
        wp_enqueue_style( 'cch-th-gh-dark', plugins_url( 'assets/hjs/styles/github-dark.css', __FILE__ ), [], '1.0' );
        wp_enqueue_style( 'cch-th-hjs-copy', plugins_url( 'assets/hjs/highlightjs-copy.min.css', __FILE__ ), [], '1.0' );
        wp_enqueue_style( 'cch-th-script', plugins_url( 'assets/main.css', __FILE__ ), [], '1.0' );

        wp_enqueue_script( 'cch-th-hjs', plugins_url( 'assets/hjs/highlight.min.js', __FILE__ ), [], '1.0', true );
        wp_enqueue_script( 'cch-th-hjs-copy', plugins_url( 'assets/hjs/highlightjs-copy.min.js', __FILE__ ), [], '1.0', true );
        wp_enqueue_script( 'cch-th-scripts', plugins_url( 'assets/main.js', __FILE__ ), ['jquery','cch-th-hjs','cch-th-hjs-copy'], '1.0', true );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'cch-styles', plugins_url( 'assets/style.css', __FILE__ ), [], '1.0' );
        wp_enqueue_script( 'cch-scripts', plugins_url( 'assets/script.js', __FILE__ ), [ 'jquery' ], '1.0', true );
//		wp_localize_script( 'cch-scripts', 'dcf_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

    }

    public function add_meta_box() {
        add_meta_box(
            'cch_meta_box',
            'Code Highlighter',
            [ $this, 'callback' ],
            'post',
            'normal',
            'high'
        );
    }

    public function callback( $post ) {
        wp_nonce_field( 'cch_nonce', 'cch_nonce_field' );
        $fields = get_post_meta( $post->ID, 'cch_fields', true );
        if ( ! $fields ) {
            $fields = [];
        }

        ?>
        <div id="cch-fields-container">
            <?php
            if ( $fields ) {
                foreach ( $fields as $key => $field ) {
                    $this->render_field( $key, $field );
                }
            }
            ?>
        </div>
        <button type="button" class="button" id="cch-add-field">Add Code</button>
        <script type="text/javascript">
            var nextFieldKey = <?php echo array_key_last($fields); ?>;
        </script>
        <?php
    }

    private function render_field( $key, $field ) {
        ?>
        <div class="cch-field">
            <p>Shortcode for <?php echo esc_attr( $field['filename'] ); ?>: <code>[cch id="<?php echo $key; ?>"]</code></p>
            <select  name="cch_fields[<?php echo $key; ?>][language]">
                <option value="language-plaintext" <?php if($field['language'] === 'language-plaintext') echo 'selected' ;?>>plaintext</option>
                <option value="language-json"  <?php if($field['language'] === 'language-json') echo 'selected'; ?>>JSON</option>
                <option value="language-php"  <?php if($field['language'] === 'language-php') echo 'selected'; ?>>PHP</option>
                <option value="language-javascript"  <?php if($field['language'] === 'language-javascript') echo 'selected' ;?>>Javascript</option>
                <option value="language-java"  <?php if($field['language'] === 'language-java') echo 'selected' ;?>>Java</option>
                <option value="language-xml"  <?php if($field['language'] === 'language-xml') echo 'selected'; ?>>XML</option>
                <option value="language-sql"  <?php if($field['language'] === 'language-sql') echo 'selected' ;?>>SQL</option>
                <option value="language-css"  <?php if($field['language'] === 'language-css') echo 'selected' ;?>>CSS</option>
            </select>
            <input type="text" name="cch_fields[<?php echo $key; ?>][filename]" placeholder="Filename"
                   value="<?php echo esc_attr( $field['filename'] ); ?>">
            <textarea name="cch_fields[<?php echo $key; ?>][source_code]"
                      placeholder="Source Code"><?php echo esc_textarea( $field['source_code'] ); ?></textarea>
            <button type="button" class="button cch-remove-field">Remove</button>
        </div>
        <?php
    }

    public function save_meta_box_data( $post_id ) {
        if ( ! isset( $_POST['cch_nonce_field'] ) || ! wp_verify_nonce( $_POST['cch_nonce_field'], 'cch_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }
        if ( isset( $_POST['cch_fields'] ) ) {
            error_log(json_encode($_POST['cch_fields']) );
            $fields = array_map( function ( $field ) {
                return array(
                    'language'    => sanitize_text_field( $field['language'] ),
                    'filename'    => sanitize_text_field( $field['filename'] ),
                    'source_code' => wp_kses_post( $field['source_code'] )
                );
            }, $_POST['cch_fields'] );
            update_post_meta( $post_id, 'cch_fields', $fields );
        } else {
            delete_post_meta( $post_id, 'cch_fields' );
        }
    }

    public function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => null ), $atts );

        if ( is_null( $atts['id'] ) ) {
            return '';
        }

        $post_id = get_the_ID();
        $fields  = get_post_meta( $post_id, 'cch_fields', true );
        if ( $fields && isset( $fields[ $atts['id'] ] ) ) {
            $field  = $fields[ $atts['id'] ];
            $idField = 'cch_sc_'.$atts['id'];

            $output = '<div class="cch-container"><div class="filename">'.$field['filename'].'</div>';
            $output .= '<pre><code id="'.$idField.'" class="cch_sc '.$field['language'].'">'. esc_html( $field['source_code'] ) .'</code></pre>';
            $output .= '</div>';
            return $output;
        }

        return '';
    }
}

ClassicCodeHighlighter::get_instance();

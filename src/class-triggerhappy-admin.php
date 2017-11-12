<?php
class TriggerHappyAdmin {
    private static $instance = null;

    public static function init() {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new TriggerHappyAdmin();
        self::$instance->init_hooks();
    }
    public function init_hooks() {
        add_filter( 'mce_external_plugins', array($this,'load_external_plugins') );
        add_action( 'add_meta_boxes', array(self::$instance, 'add_meta_boxes' ));
        add_action( 'admin_enqueue_scripts', array(self::$instance, 'add_admin_scripts') );
        add_action( 'admin_init', array(self::$instance, 'add_editor_style') );
        add_action( 'save_post', array(self::$instance,'save_post') );

    }
    public function load_external_plugins( $plugins ) {
        $plugins['wpflowexpression'] = plugin_dir_url( dirname( __FILE__ ) ) . '/../assets/tinymce/nodesettings.js';
        return $plugins;
    }


    public function add_admin_scripts( $hook ) {

        global $post;

        $is_editing_post = $hook == 'post-new.php' || $hook == 'post.php';

        if ( $is_editing_post && 'th_flow' === $post->post_type ) {
            wp_enqueue_script(  'wpflowscript',  plugins_url( 'assets/trigger-happy.js', dirname(__FILE__) ), array(), '1.0',true );
            wp_enqueue_style(  'trigger-happy-css',  plugins_url( 'assets/trigger-happy.css', dirname(__FILE__) ) );
            wp_enqueue_style(  'codemirror',  plugins_url( 'assets/codemirror.css', dirname(__FILE__) ) );
            wp_enqueue_style( 'font-awesome',  plugins_url( 'assets/font-awesome/font-awesome.min.css', dirname(__FILE__) ), '4.7.0' );
            //'//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
            wp_localize_script( 'wpflowscript', 'TH', array(
                'expression_css_url' => plugins_url( 'build/expression.css', dirname(__FILE__) )
            ));
            wp_enqueue_editor();
        }
    }
    public function save_post( $post_id ) {
        if (isset($_POST['triggerhappy_data'])) {
            $has_kses = ( false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' ) );
            if ( $has_kses ) {
                kses_remove_filters(); // Prevent KSES from corrupting JSON in post_content.
            }
            remove_action('save_post', array($this,'save_post'));
            wp_update_post(array('ID'=>$post_id, 'post_content'=>$_POST['triggerhappy_data']));
            if ( $has_kses ) {
                kses_init_filters();
            }
            add_action('save_post',array($this,'save_post'));
        }
    }

    public function add_meta_boxes() {
        add_meta_box('triggerhappy_editor', 'Flow Editor', array($this,'render_editor'), 'th_flow' );

    }
    public function render_editor( $post ) {
        ?>
        <div id="flow-editor-errors" style="display:none"></div>
        <div id="flow-editor-container"></div>
        <textarea id="flow-editor-data-source" style="display:none"><?php echo $post->post_content; ?></textarea>
        <input type='hidden' name='triggerhappy_data' id='flow-editor-data' />
        <input type='hidden' id='triggerhappy-x-nonce' value='<?php echo wp_create_nonce( 'wp_rest' ); ?>' />
        <input type='hidden' id='triggerhappy-rest-url' value='<?php echo esc_url_raw( rest_url() ); ?>' />
        <?php

    }



    public function add_editor_style() {

      add_editor_style( plugins_url( 'assets/expression.css', dirname( __FILE__ ) )  );
    }

}

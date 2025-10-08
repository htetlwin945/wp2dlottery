<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class Inertia_Adapter
 *
 * Handles rendering Inertia.js responses for the WordPress admin.
 */
class Inertia_Adapter {

    private static $instance;
    private $manifest = [];

    /**
     * Get the singleton instance of the class.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to set up the manifest.
     */
    private function __construct() {
        $this->load_manifest();
    }

    /**
     * Load the Vite manifest file.
     */
    private function load_manifest() {
        $manifest_path = CUSTOM_LOTTERY_PLUGIN_PATH . 'frontend/dist/manifest.json';
        if ( file_exists( $manifest_path ) ) {
            $this->manifest = json_decode( file_get_contents( $manifest_path ), true );
        }
    }

    /**
     * Get the URL for a frontend asset from the manifest.
     *
     * @param string $entry The entry file name (e.g., 'src/main.js').
     * @return array An array containing 'file' and 'css' URLs.
     */
    private function get_asset_urls( $entry = 'src/main.js' ) {
        $urls = [
            'file' => '',
            'css'  => [],
        ];

        if ( isset( $this->manifest[ $entry ] ) ) {
            $manifest_entry = $this->manifest[ $entry ];
            $base_url       = CUSTOM_LOTTERY_PLUGIN_URL . 'frontend/dist/';

            // Main JS file
            if ( isset( $manifest_entry['file'] ) ) {
                $urls['file'] = $base_url . $manifest_entry['file'];
            }

            // Associated CSS files
            if ( isset( $manifest_entry['css'] ) ) {
                foreach ( $manifest_entry['css'] as $css_file ) {
                    $urls['css'][] = $base_url . $css_file;
                }
            }
        }

        return $urls;
    }

    /**
     * Render the Inertia response.
     *
     * @param string $component The name of the Vue component to render.
     * @param array  $props     The props to pass to the component.
     */
    public function render( $component, $props = [] ) {
        $page = [
            'component' => $component,
            'props'     => $props,
            'url'       => remove_query_arg('noheader'), // Current URL without the 'noheader' param
            'version'   => CUSTOM_LOTTERY_VERSION,
        ];

        // If this is an Inertia request, send JSON.
        if ( isset( $_SERVER['HTTP_X_INERTIA'] ) ) {
            wp_send_json( $page, 200, [ 'X-Inertia' => 'true' ] );
            exit;
        }

        // Otherwise, send the full HTML response.
        $this->render_html_shell( $page );
    }

    /**
     * Render the full HTML shell for the initial page load.
     *
     * @param array $page The page data.
     */
    private function render_html_shell( $page ) {
        $assets = $this->get_asset_urls();

        // We are hijacking the admin page, so we need to manually include the header and footer.
        // The 'noheader' query param is used to prevent WordPress from rendering its own header.
        require_once( ABSPATH . 'wp-admin/admin-header.php' );

        ?>
        <div id="app" data-page="<?php echo esc_attr( json_encode( $page ) ); ?>"></div>
        <?php

        // Enqueue CSS files
        foreach ( $assets['css'] as $css_url ) {
            echo '<link rel="stylesheet" href="' . esc_url( $css_url ) . '">';
        }

        // Enqueue the main JS file as a module
        if ( ! empty( $assets['file'] ) ) {
            echo '<script type="module" src="' . esc_url( $assets['file'] ) . '"></script>';
        }

        require_once( ABSPATH . 'wp-admin/admin-footer.php' );
    }
}

/**
 * Helper function to render an Inertia page.
 *
 * @param string $component The name of the Vue component.
 * @param array  $props     The props for the component.
 */
function custom_lottery_render_inertia( $component, $props = [] ) {
    Inertia_Adapter::get_instance()->render( $component, $props );
}
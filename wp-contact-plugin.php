<?php
/**
 * Plugin Name: WP Contact Plugin
 * Description: Dodaje dolną belkę kontaktową z szybkim dostępem do WhatsApp, telefonu i e-maila.
 * Version: 1.0.0
 * Author: OpenAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Contact_Plugin {
    const OPTION_KEY = 'wp_contact_plugin_options';
    const VERSION    = '1.0.0';

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        if ( ! is_admin() ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
            add_action( 'wp_footer', [ $this, 'render_contact_bar' ] );
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'wp-contact-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function register_settings_page() {
        add_options_page(
            __( 'Kontakt – belka', 'wp-contact-plugin' ),
            __( 'Kontakt – belka', 'wp-contact-plugin' ),
            'manage_options',
            'wp-contact-plugin',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( self::OPTION_KEY, self::OPTION_KEY, [ $this, 'sanitize_options' ] );

        add_settings_section(
            'wp_contact_plugin_section',
            __( 'Ustawienia belki kontaktowej', 'wp-contact-plugin' ),
            '__return_false',
            'wp-contact-plugin'
        );

        add_settings_field(
            'phone_number',
            __( 'Numer telefonu (tel:)', 'wp-contact-plugin' ),
            [ $this, 'render_phone_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'whatsapp_number',
            __( 'Numer WhatsApp (https://wa.me/)', 'wp-contact-plugin' ),
            [ $this, 'render_whatsapp_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'email_address',
            __( 'Adres e-mail (mailto:)', 'wp-contact-plugin' ),
            [ $this, 'render_email_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'bar_color',
            __( 'Kolor belki i ikon', 'wp-contact-plugin' ),
            [ $this, 'render_color_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'visibility',
            __( 'Widoczność', 'wp-contact-plugin' ),
            [ $this, 'render_visibility_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'position',
            __( 'Położenie belki', 'wp-contact-plugin' ),
            [ $this, 'render_position_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );
    }

    public function sanitize_options( $input ) {
        $options = $this->get_options();

        $options['phone_number']    = isset( $input['phone_number'] ) ? $this->sanitize_contact_number( $input['phone_number'] ) : '';
        $options['whatsapp_number'] = isset( $input['whatsapp_number'] ) ? $this->sanitize_contact_number( $input['whatsapp_number'] ) : '';
        $options['email_address']   = isset( $input['email_address'] ) ? sanitize_email( $input['email_address'] ) : '';

        if ( isset( $input['bar_color'] ) && preg_match( '/^#(?:[0-9a-fA-F]{3}){1,2}$/', $input['bar_color'] ) ) {
            $options['bar_color'] = $input['bar_color'];
        } else {
            $options['bar_color'] = '#1e73be';
        }

        $visibility_options = [ 'everywhere', 'mobile', 'desktop' ];
        $options['visibility'] = in_array( $input['visibility'] ?? 'everywhere', $visibility_options, true ) ? $input['visibility'] : 'everywhere';

        $position_options = [ 'right', 'left' ];
        $options['position']  = in_array( $input['position'] ?? 'right', $position_options, true ) ? $input['position'] : 'right';

        return $options;
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Belka kontaktowa', 'wp-contact-plugin' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_KEY );
                do_settings_sections( 'wp-contact-plugin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function get_options() {
        $defaults = [
            'phone_number'    => '',
            'whatsapp_number' => '',
            'email_address'   => '',
            'bar_color'       => '#1e73be',
            'visibility'      => 'everywhere',
            'position'        => 'right',
        ];

        $options = get_option( self::OPTION_KEY, [] );

        return wp_parse_args( $options, $defaults );
    }

    private function sanitize_contact_number( $number ) {
        $cleaned = preg_replace( '/[^0-9+]/', '', $number );

        return ltrim( $cleaned );
    }

    public function render_phone_field() {
        $options = $this->get_options();
        ?>
        <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[phone_number]" value="<?php echo esc_attr( $options['phone_number'] ); ?>" class="regular-text" placeholder="+48 600 000 000" />
        <?php
    }

    public function render_whatsapp_field() {
        $options = $this->get_options();
        ?>
        <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[whatsapp_number]" value="<?php echo esc_attr( $options['whatsapp_number'] ); ?>" class="regular-text" placeholder="48600000000" />
        <p class="description"><?php esc_html_e( 'Numer zostanie dodany do https://wa.me/', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_email_field() {
        $options = $this->get_options();
        ?>
        <input type="email" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[email_address]" value="<?php echo esc_attr( $options['email_address'] ); ?>" class="regular-text" placeholder="kontakt@example.com" />
        <?php
    }

    public function render_color_field() {
        $options = $this->get_options();
        ?>
        <input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[bar_color]" value="<?php echo esc_attr( $options['bar_color'] ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Wybierz kolor tła belki i ikon.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_visibility_field() {
        $options    = $this->get_options();
        $visibility = $options['visibility'];
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[visibility]">
            <option value="everywhere" <?php selected( $visibility, 'everywhere' ); ?>><?php esc_html_e( 'Wszędzie', 'wp-contact-plugin' ); ?></option>
            <option value="mobile" <?php selected( $visibility, 'mobile' ); ?>><?php esc_html_e( 'Tylko mobile', 'wp-contact-plugin' ); ?></option>
            <option value="desktop" <?php selected( $visibility, 'desktop' ); ?>><?php esc_html_e( 'Tylko desktop', 'wp-contact-plugin' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Kontroluj, na jakich urządzeniach wyświetlać belkę.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_position_field() {
        $options  = $this->get_options();
        $position = $options['position'];
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[position]">
            <option value="right" <?php selected( $position, 'right' ); ?>><?php esc_html_e( 'Prawa strona', 'wp-contact-plugin' ); ?></option>
            <option value="left" <?php selected( $position, 'left' ); ?>><?php esc_html_e( 'Lewa strona', 'wp-contact-plugin' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Wybierz, po której stronie ekranu wyświetlać belkę i przycisk menu.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function enqueue_assets() {
        $options = $this->get_options();

        if ( empty( $options['phone_number'] ) && empty( $options['whatsapp_number'] ) && empty( $options['email_address'] ) ) {
            return;
        }

        $plugin_url = plugin_dir_url( __FILE__ );
        wp_enqueue_style( 'wp-contact-plugin', $plugin_url . 'assets/css/contact-bar.css', [], self::VERSION );
        wp_enqueue_script( 'wp-contact-plugin', $plugin_url . 'assets/js/contact-bar.js', [], self::VERSION, true );
    }

    public function render_contact_bar() {
        $options = $this->get_options();

        if ( empty( $options['phone_number'] ) && empty( $options['whatsapp_number'] ) && empty( $options['email_address'] ) ) {
            return;
        }

        $visibility_class = 'wp-contact-bar--everywhere';
        if ( 'mobile' === $options['visibility'] ) {
            $visibility_class = 'wp-contact-bar--mobile';
        } elseif ( 'desktop' === $options['visibility'] ) {
            $visibility_class = 'wp-contact-bar--desktop';
        }

        $position_class = 'right' === $options['position'] ? 'wp-contact-bar--right' : 'wp-contact-bar--left';

        $color = esc_attr( $options['bar_color'] );
        ?>
        <div class="wp-contact-bar <?php echo esc_attr( $visibility_class ); ?> <?php echo esc_attr( $position_class ); ?>" style="--wp-contact-bar-color: <?php echo $color; ?>;">
            <button class="wp-contact-bar__toggle" aria-expanded="false" aria-controls="wp-contact-bar-panel">
                <span class="wp-contact-bar__icon" aria-hidden="true">☰</span>
                <span class="screen-reader-text"><?php esc_html_e( 'Pokaż opcje kontaktu', 'wp-contact-plugin' ); ?></span>
            </button>
            <div class="wp-contact-bar__panel" id="wp-contact-bar-panel">
                <?php if ( ! empty( $options['whatsapp_number'] ) ) : ?>
                    <a class="wp-contact-bar__link" href="<?php echo esc_url( 'https://wa.me/' . $options['whatsapp_number'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <span aria-hidden="true">💬</span>
                        <span class="screen-reader-text"><?php esc_html_e( 'WhatsApp', 'wp-contact-plugin' ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( ! empty( $options['phone_number'] ) ) : ?>
                    <a class="wp-contact-bar__link" href="<?php echo esc_url( 'tel:' . $options['phone_number'] ); ?>">
                        <span aria-hidden="true">📞</span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Telefon', 'wp-contact-plugin' ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( ! empty( $options['email_address'] ) ) : ?>
                    <a class="wp-contact-bar__link" href="<?php echo esc_url( 'mailto:' . $options['email_address'] ); ?>">
                        <span aria-hidden="true">✉️</span>
                        <span class="screen-reader-text"><?php esc_html_e( 'E-mail', 'wp-contact-plugin' ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

new WP_Contact_Plugin();

<?php
/**
 * Plugin Name: Kontakt Dock
 * Description: Dodaje dolną belkę lub pływające koło kontaktowe z szybkim dostępem do WhatsApp, telefonu i e-maila.
 * Version: 1.2.0
 * Author: Jakub Jędrys
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Contact_Plugin {
    const OPTION_KEY = 'wp_contact_plugin_options';
    const VERSION    = '1.2.0';

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
        add_menu_page(
            __( 'Kontakt Dock', 'wp-contact-plugin' ),
            __( 'Kontakt Dock', 'wp-contact-plugin' ),
            'manage_options',
            'wp-contact-plugin',
            [ $this, 'render_settings_page' ],
            'dashicons-phone',
            56
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
            'layout',
            __( 'Układ', 'wp-contact-plugin' ),
            [ $this, 'render_layout_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'bar_color',
            __( 'Kolor globalny', 'wp-contact-plugin' ),
            [ $this, 'render_color_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'button_colors',
            __( 'Kolory przycisków', 'wp-contact-plugin' ),
            [ $this, 'render_button_colors_field' ],
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

        add_settings_field(
            'offsets',
            __( 'Odstępy i narożniki', 'wp-contact-plugin' ),
            [ $this, 'render_offsets_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );

        add_settings_field(
            'icons',
            __( 'Ikony i rozmiar', 'wp-contact-plugin' ),
            [ $this, 'render_icons_field' ],
            'wp-contact-plugin',
            'wp_contact_plugin_section'
        );
    }

    public function sanitize_options( $input ) {
        $options = $this->get_options();

        $options['phone_number']    = isset( $input['phone_number'] ) ? $this->sanitize_contact_number( $input['phone_number'] ) : '';
        $options['whatsapp_number'] = isset( $input['whatsapp_number'] ) ? $this->sanitize_contact_number( $input['whatsapp_number'] ) : '';
        $options['email_address']   = isset( $input['email_address'] ) ? sanitize_email( $input['email_address'] ) : '';

        $options['bar_color'] = $this->sanitize_hex_color_or_default( $input['bar_color'] ?? '' );
        $options['whatsapp_color'] = $this->sanitize_hex_color_or_default( $input['whatsapp_color'] ?? '', '#25D366' );
        $options['phone_color']    = $this->sanitize_hex_color_or_default( $input['phone_color'] ?? '', '#1e73be' );
        $options['email_color']    = $this->sanitize_hex_color_or_default( $input['email_color'] ?? '', '#ed6a5a' );

        $visibility_options    = [ 'everywhere', 'mobile', 'desktop' ];
        $options['visibility'] = in_array( $input['visibility'] ?? 'everywhere', $visibility_options, true ) ? $input['visibility'] : 'everywhere';

        $layout_options     = [ 'bar', 'floating' ];
        $options['layout']  = in_array( $input['layout'] ?? 'bar', $layout_options, true ) ? $input['layout'] : 'bar';

        $position_options = [ 'right', 'left' ];
        $options['position']  = in_array( $input['position'] ?? 'right', $position_options, true ) ? $input['position'] : 'right';

        $vertical_options      = [ 'bottom', 'top' ];
        $options['vertical']   = in_array( $input['vertical'] ?? 'bottom', $vertical_options, true ) ? $input['vertical'] : 'bottom';

        $options['offset_x']     = isset( $input['offset_x'] ) ? intval( $input['offset_x'] ) : 0;
        $options['offset_y']     = isset( $input['offset_y'] ) ? intval( $input['offset_y'] ) : 0;
        $options['cookie_offset'] = isset( $input['cookie_offset'] ) ? intval( $input['cookie_offset'] ) : 0;

        $size_options    = [ 'sm', 'md', 'lg' ];
        $options['size'] = in_array( $input['size'] ?? 'md', $size_options, true ) ? $input['size'] : 'md';

        $options['toggle_icon_closed'] = sanitize_text_field( $input['toggle_icon_closed'] ?? '☰' );
        $options['toggle_icon_open']   = sanitize_text_field( $input['toggle_icon_open'] ?? '✕' );

        $options['icon_whatsapp'] = $this->sanitize_icon_markup( $input['icon_whatsapp'] ?? '' );
        $options['icon_phone']    = $this->sanitize_icon_markup( $input['icon_phone'] ?? '' );
        $options['icon_email']    = $this->sanitize_icon_markup( $input['icon_email'] ?? '' );

        $icon_modes          = [ 'default', 'custom', 'svg', 'official' ];
        $options['icon_mode_whatsapp'] = in_array( $input['icon_mode_whatsapp'] ?? 'default', $icon_modes, true ) ? $input['icon_mode_whatsapp'] : 'default';
        $options['icon_mode_phone']    = in_array( $input['icon_mode_phone'] ?? 'default', $icon_modes, true ) ? $input['icon_mode_phone'] : 'default';
        $options['icon_mode_email']    = in_array( $input['icon_mode_email'] ?? 'default', $icon_modes, true ) ? $input['icon_mode_email'] : 'default';

        $options['pulse'] = ! empty( $input['pulse'] ) ? 'yes' : 'no';

        return $options;
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Kontakt Dock', 'wp-contact-plugin' ); ?></h1>
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
            'layout'          => 'bar',
            'bar_color'       => '#1e73be',
            'whatsapp_color'  => '#25D366',
            'phone_color'     => '#1e73be',
            'email_color'     => '#ed6a5a',
            'visibility'      => 'everywhere',
            'position'        => 'right',
            'vertical'        => 'bottom',
            'offset_x'        => 16,
            'offset_y'        => 16,
            'cookie_offset'   => 0,
            'size'            => 'md',
            'toggle_icon_closed' => '☰',
            'toggle_icon_open'   => '✕',
            'icon_whatsapp'      => '',
            'icon_phone'         => '',
            'icon_email'         => '',
            'icon_mode_whatsapp' => 'default',
            'icon_mode_phone'    => 'default',
            'icon_mode_email'    => 'default',
            'pulse'              => 'no',
        ];

        $options = get_option( self::OPTION_KEY, [] );

        return wp_parse_args( $options, $defaults );
    }

    private function sanitize_hex_color_or_default( $color, $default = '#1e73be' ) {
        if ( is_string( $color ) && preg_match( '/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color ) ) {
            return $color;
        }

        return $default;
    }

    private function sanitize_icon_markup( $icon ) {
        if ( empty( $icon ) ) {
            return '';
        }

        $allowed_tags = [
            'svg'  => [
                'xmlns'        => true,
                'viewBox'      => true,
                'fill'         => true,
                'stroke'       => true,
                'stroke-width' => true,
                'aria-hidden'  => true,
                'focusable'    => true,
                'role'         => true,
                'width'        => true,
                'height'       => true,
                'class'        => true,
            ],
            'path' => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
            'i'    => [ 'class' => true, 'aria-hidden' => true ],
            'span' => [ 'class' => true, 'aria-hidden' => true ],
        ];

        return wp_kses( $icon, $allowed_tags );
    }

    private function render_icon_picker( $channel, $label, $options, $icon_modes ) {
        $mode_key   = 'icon_mode_' . $channel;
        $icon_key   = 'icon_' . $channel;
        $mode_value = $options[ $mode_key ] ?? 'default';
        ?>
        <fieldset style="margin-bottom:12px; border:1px solid #ccd0d4; padding:10px;">
            <legend style="padding:0 6px; font-weight:600;">
                <?php echo esc_html( sprintf( __( 'Ikona: %s', 'wp-contact-plugin' ), $label ) ); ?>
            </legend>
            <label style="display:block; margin-bottom:6px;">
                <?php esc_html_e( 'Źródło ikony', 'wp-contact-plugin' ); ?>
                <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $mode_key ); ?>]">
                    <?php foreach ( $icon_modes as $mode_value_key => $mode_label ) : ?>
                        <option value="<?php echo esc_attr( $mode_value_key ); ?>" <?php selected( $mode_value, $mode_value_key ); ?>><?php echo esc_html( $mode_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="display:block; margin-bottom:6px;">
                <?php esc_html_e( 'Klasa / SVG (dla trybu Własny SVG lub Biblioteka ikon)', 'wp-contact-plugin' ); ?>
                <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $icon_key ); ?>]" rows="2" class="large-text code"><?php echo esc_textarea( $options[ $icon_key ] ); ?></textarea>
            </label>
        </fieldset>
        <?php
    }

    private function sanitize_contact_number( $number ) {
        $cleaned = preg_replace( '/[^0-9+]/', '', $number );

        return ltrim( $cleaned );
    }

    private function get_icon_markup( $channel, $options ) {
        $mode      = $options[ 'icon_mode_' . $channel ] ?? 'default';
        $icon_data = $options[ 'icon_' . $channel ] ?? '';

        if ( 'official' === $mode ) {
            return $this->get_official_icon_svg( $channel );
        }

        if ( 'svg' === $mode && ! empty( $icon_data ) ) {
            return wp_kses_post( $icon_data );
        }

        if ( 'custom' === $mode && ! empty( $icon_data ) ) {
            $class = sanitize_html_class( wp_strip_all_tags( $icon_data ) );

            return $class ? '<span class="' . esc_attr( $class ) . '" aria-hidden="true"></span>' : esc_html( $this->get_default_icon( $channel ) );
        }

        return esc_html( $this->get_default_icon( $channel ) );
    }

    private function get_official_icon_svg( $channel ) {
        switch ( $channel ) {
            case 'whatsapp':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" role="img" aria-hidden="true"><path fill="#25D366" d="M16 2.7c-7.3 0-13.3 5.9-13.3 13.3 0 2.3.6 4.5 1.7 6.4L3 30l8-1.7c1.8.9 3.8 1.3 5.9 1.3 7.3 0 13.3-5.9 13.3-13.3S23.3 2.7 16 2.7Z"/><path fill="#fff" d="M23.2 19.1c-.1-.2-.5-.3-1-.5-.5-.1-2.8-1.4-3.2-1.6-.4-.1-.7-.2-1 .2-.3.4-1.2 1.6-1.4 1.9-.3.3-.5.3-1 0s-1.9-.7-3.6-2.2c-1.3-1.1-2.2-2.5-2.4-3-.3-.5 0-.7.2-.9.2-.2.5-.5.7-.7.2-.2.3-.3.5-.5.2-.2.3-.4.2-.6-.1-.2-.9-2.1-1.2-2.8-.3-.7-.6-.6-.9-.6-.2 0-.5-.1-.8-.1s-.7.1-1 .5c-.3.4-1.3 1.2-1.3 3s1.3 3.4 1.5 3.6c.2.2 2.6 3.9 6.3 5.5 3.8 1.6 3.8 1.1 4.5 1 .7-.1 2.3-.9 2.6-1.7.3-.8.3-1.5.2-1.7Z"/></svg>';
            case 'phone':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#0B67C2" d="M6.6 2.1c.4-.2.8 0 1 .4l1.7 4c.2.4 0 .8-.3 1.1l-1.3 1.2c.3.8 1 2 2.2 3.2 1.3 1.3 2.5 2 3.3 2.3l1.3-1.3c.3-.3.8-.5 1.2-.3l3.9 1.8c.4.2.6.6.4 1l-1.3 3.3c-.2.4-.6.7-1.1.7-4.4-.1-8.1-1.8-11-4.8-2.9-2.9-4.6-6.7-4.7-11-.1-.4.2-.9.6-1.1l3.1-1.5Z"/></svg>';
            case 'email':
            default:
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true"><path fill="#D64550" d="M3 5.5C3 4.7 3.7 4 4.5 4h15c.8 0 1.5.7 1.5 1.5v13c0 .8-.7 1.5-1.5 1.5h-15C3.7 20 3 19.3 3 18.5v-13Z"/><path fill="#fff" d="M19 7.5V7l-7 4.6L5 7v.5l7 4.5 7-4.5Z"/></svg>';
        }
    }

    private function get_default_icon( $channel ) {
        switch ( $channel ) {
            case 'whatsapp':
                return '💬';
            case 'phone':
                return '📞';
            case 'email':
            default:
                return '✉️';
        }
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
        <p class="description"><?php esc_html_e( 'Wybierz kolor tła belki / koła oraz kolor domyślny ikon.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_button_colors_field() {
        $options = $this->get_options();
        ?>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'WhatsApp', 'wp-contact-plugin' ); ?>
            <input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[whatsapp_color]" value="<?php echo esc_attr( $options['whatsapp_color'] ); ?>" />
        </label>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'Telefon', 'wp-contact-plugin' ); ?>
            <input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[phone_color]" value="<?php echo esc_attr( $options['phone_color'] ); ?>" />
        </label>
        <label style="display:block;">
            <?php esc_html_e( 'E-mail', 'wp-contact-plugin' ); ?>
            <input type="color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[email_color]" value="<?php echo esc_attr( $options['email_color'] ); ?>" />
        </label>
        <p class="description"><?php esc_html_e( 'Kolory per przycisk. Jeśli puste, użyty zostanie kolor globalny.', 'wp-contact-plugin' ); ?></p>
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

    public function render_layout_field() {
        $options = $this->get_options();
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[layout]">
            <option value="bar" <?php selected( $options['layout'], 'bar' ); ?>><?php esc_html_e( 'Dolna belka', 'wp-contact-plugin' ); ?></option>
            <option value="floating" <?php selected( $options['layout'], 'floating' ); ?>><?php esc_html_e( 'Pływające koło', 'wp-contact-plugin' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Wybierz czy wyświetlać szeroką belkę, czy kompaktowy pływający przycisk.', 'wp-contact-plugin' ); ?></p>
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
        <p class="description"><?php esc_html_e( 'Wybierz, po której stronie ekranu wyświetlać belkę / koło.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_offsets_field() {
        $options = $this->get_options();
        ?>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'Wyrównanie pionowe', 'wp-contact-plugin' ); ?>
            <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[vertical]">
                <option value="bottom" <?php selected( $options['vertical'], 'bottom' ); ?>><?php esc_html_e( 'Dół', 'wp-contact-plugin' ); ?></option>
                <option value="top" <?php selected( $options['vertical'], 'top' ); ?>><?php esc_html_e( 'Góra', 'wp-contact-plugin' ); ?></option>
            </select>
        </label>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'Odstęp X (px)', 'wp-contact-plugin' ); ?>
            <input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[offset_x]" value="<?php echo esc_attr( $options['offset_x'] ); ?>" />
        </label>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'Odstęp Y / offset pod belkę cookies (px)', 'wp-contact-plugin' ); ?>
            <input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[offset_y]" value="<?php echo esc_attr( $options['offset_y'] ); ?>" />
        </label>
        <label style="display:block;">
            <?php esc_html_e( 'Dodatkowy offset (np. na belkę cookies)', 'wp-contact-plugin' ); ?>
            <input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cookie_offset]" value="<?php echo esc_attr( $options['cookie_offset'] ); ?>" />
        </label>
        <p class="description"><?php esc_html_e( 'Ustaw doklejenie do narożnika (0,0) lub dodatkowe przesunięcia iOS safe-area.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_icons_field() {
        $options = $this->get_options();
        ?>
        <?php $icon_modes = [
            'default'  => __( 'Domyślna', 'wp-contact-plugin' ),
            'official' => __( 'Oficjalna ikona marki', 'wp-contact-plugin' ),
            'svg'      => __( 'Własny SVG', 'wp-contact-plugin' ),
            'custom'   => __( 'Biblioteka ikon / klasa', 'wp-contact-plugin' ),
        ]; ?>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'Ikona zamknięta (menu)', 'wp-contact-plugin' ); ?>
            <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[toggle_icon_closed]" value="<?php echo esc_attr( $options['toggle_icon_closed'] ); ?>" />
        </label>
        <label style="display:block;margin-bottom:12px;">
            <?php esc_html_e( 'Ikona otwarta', 'wp-contact-plugin' ); ?>
            <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[toggle_icon_open]" value="<?php echo esc_attr( $options['toggle_icon_open'] ); ?>" />
        </label>

        <?php $this->render_icon_picker( 'whatsapp', __( 'WhatsApp', 'wp-contact-plugin' ), $options, $icon_modes ); ?>
        <?php $this->render_icon_picker( 'phone', __( 'Telefon', 'wp-contact-plugin' ), $options, $icon_modes ); ?>
        <?php $this->render_icon_picker( 'email', __( 'E-mail', 'wp-contact-plugin' ), $options, $icon_modes ); ?>

        <p class="description">
            <?php esc_html_e( 'Wybierz domyślną ikonę, oficjalny znak marki (WhatsApp, Facebook, Instagram), własny kod SVG lub klasę z biblioteki ikon. Pamiętaj o zachowaniu kolorów i kształtów zgodnie z brand guidelines.', 'wp-contact-plugin' ); ?>
        </p>
        <label style="display:block;margin-bottom:6px;">
            <?php esc_html_e( 'Rozmiar', 'wp-contact-plugin' ); ?>
            <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[size]">
                <option value="sm" <?php selected( $options['size'], 'sm' ); ?>><?php esc_html_e( 'Mały', 'wp-contact-plugin' ); ?></option>
                <option value="md" <?php selected( $options['size'], 'md' ); ?>><?php esc_html_e( 'Średni', 'wp-contact-plugin' ); ?></option>
                <option value="lg" <?php selected( $options['size'], 'lg' ); ?>><?php esc_html_e( 'Duży', 'wp-contact-plugin' ); ?></option>
            </select>
        </label>
        <label style="display:block;">
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[pulse]" value="1" <?php checked( $options['pulse'], 'yes' ); ?> />
            <?php esc_html_e( 'Pulsujące koło menu', 'wp-contact-plugin' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Możesz wkleić własne SVG lub użyć klas ikon (np. Font Awesome) i zmieniać rozmiar / animację.', 'wp-contact-plugin' ); ?></p>
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

        wp_localize_script(
            'wp-contact-plugin',
            'wpContactPluginData',
            [
                'layout'      => $options['layout'],
                'position'    => $options['position'],
                'vertical'    => $options['vertical'],
                'offsetX'     => (int) $options['offset_x'],
                'offsetY'     => (int) $options['offset_y'],
                'cookieOffset'=> (int) $options['cookie_offset'],
            ]
        );
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
        $vertical_class  = 'top' === $options['vertical'] ? 'wp-contact-bar--top' : 'wp-contact-bar--bottom';
        $layout_class    = 'floating' === $options['layout'] ? 'wp-contact-bar--floating' : 'wp-contact-bar--inline';

        $color = esc_attr( $options['bar_color'] );
        $whatsapp_color = 'official' === ( $options['icon_mode_whatsapp'] ?? 'default' ) ? '#25D366' : ( $options['whatsapp_color'] ?: $color );
        ?>
        <div
            class="wp-contact-bar <?php echo esc_attr( $visibility_class ); ?> <?php echo esc_attr( $position_class ); ?> <?php echo esc_attr( $vertical_class ); ?> <?php echo esc_attr( $layout_class ); ?><?php echo 'yes' === $options['pulse'] ? ' wp-contact-bar--pulse' : ''; ?>"
            style="--wp-contact-bar-color: <?php echo $color; ?>; --wp-contact-whatsapp-color: <?php echo esc_attr( $whatsapp_color ); ?>; --wp-contact-phone-color: <?php echo esc_attr( $options['phone_color'] ?: $color ); ?>; --wp-contact-email-color: <?php echo esc_attr( $options['email_color'] ?: $color ); ?>; --wp-contact-offset-x: <?php echo intval( $options['offset_x'] ); ?>px; --wp-contact-offset-y: <?php echo intval( $options['offset_y'] + $options['cookie_offset'] ); ?>px; --wp-contact-size: <?php echo esc_attr( $this->map_size_to_px( $options['size'] ) ); ?>px;"
            data-floating="<?php echo esc_attr( $options['layout'] ); ?>"
        >
            <button class="wp-contact-bar__toggle" aria-expanded="false" aria-controls="wp-contact-bar-panel">
                <span class="wp-contact-bar__icon wp-contact-bar__icon--closed" aria-hidden="true"><?php echo esc_html( $options['toggle_icon_closed'] ); ?></span>
                <span class="wp-contact-bar__icon wp-contact-bar__icon--open" aria-hidden="true"><?php echo esc_html( $options['toggle_icon_open'] ); ?></span>
                <span class="screen-reader-text"><?php esc_html_e( 'Pokaż opcje kontaktu', 'wp-contact-plugin' ); ?></span>
            </button>
            <div class="wp-contact-bar__panel" id="wp-contact-bar-panel">
                <?php if ( ! empty( $options['whatsapp_number'] ) ) : ?>
                    <a class="wp-contact-bar__link wp-contact-bar__link--whatsapp" href="<?php echo esc_url( 'https://wa.me/' . $options['whatsapp_number'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <span aria-hidden="true" class="wp-contact-bar__icon-slot">
                            <?php echo $this->get_icon_markup( 'whatsapp', $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </span>
                        <span class="screen-reader-text"><?php esc_html_e( 'WhatsApp', 'wp-contact-plugin' ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( ! empty( $options['phone_number'] ) ) : ?>
                    <a class="wp-contact-bar__link wp-contact-bar__link--phone" href="<?php echo esc_url( 'tel:' . $options['phone_number'] ); ?>">
                        <span aria-hidden="true" class="wp-contact-bar__icon-slot">
                            <?php echo $this->get_icon_markup( 'phone', $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Telefon', 'wp-contact-plugin' ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( ! empty( $options['email_address'] ) ) : ?>
                    <a class="wp-contact-bar__link wp-contact-bar__link--email" href="<?php echo esc_url( 'mailto:' . $options['email_address'] ); ?>">
                        <span aria-hidden="true" class="wp-contact-bar__icon-slot">
                            <?php echo $this->get_icon_markup( 'email', $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </span>
                        <span class="screen-reader-text"><?php esc_html_e( 'E-mail', 'wp-contact-plugin' ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function map_size_to_px( $size ) {
        switch ( $size ) {
            case 'sm':
                return 44;
            case 'lg':
                return 64;
            case 'md':
            default:
                return 54;
        }
    }
}

new WP_Contact_Plugin();

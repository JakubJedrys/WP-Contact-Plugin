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

    private $icon_base_url;

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

        $this->icon_base_url = plugin_dir_url( __FILE__ ) . 'assets/icons/';

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

        $allowed_corners = [ 'bottom_right', 'bottom_left', 'top_right', 'top_left' ];
        $existing_corner = $options['corner'] ?? '';

        if ( empty( $existing_corner ) && isset( $options['position'], $options['vertical'] ) ) {
            $existing_corner = $options['vertical'] . '_' . $options['position'];
        }

        $corner_input = $input['corner'] ?? $existing_corner;
        $corner       = in_array( $corner_input, $allowed_corners, true ) ? $corner_input : 'bottom_right';
        $options['corner'] = $corner;

        $options['position'] = false !== strpos( $corner, 'left' ) ? 'left' : 'right';
        $options['vertical'] = false !== strpos( $corner, 'top' ) ? 'top' : 'bottom';

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
            'corner'          => 'bottom_right',
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
            return $this->get_official_icon_markup( $channel );
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

    private function get_official_icon_markup( $channel ) {
        $icons = [
            'whatsapp' => 'whatsapp.svg',
            'phone'    => 'phone.svg',
            'email'    => 'email.svg',
        ];

        if ( ! isset( $icons[ $channel ] ) ) {
            return '';
        }

        $src = esc_url( $this->icon_base_url . $icons[ $channel ] );

        return '<img src="' . $src . '" class="wp-contact-bar__icon-image" alt="" aria-hidden="true" loading="lazy" decoding="async" />';
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
        $corner = $options['corner'] ?? 'bottom_right';
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[corner]">
            <option value="bottom_right" <?php selected( $corner, 'bottom_right' ); ?>><?php esc_html_e( 'Dolny prawy róg', 'wp-contact-plugin' ); ?></option>
            <option value="bottom_left" <?php selected( $corner, 'bottom_left' ); ?>><?php esc_html_e( 'Dolny lewy róg', 'wp-contact-plugin' ); ?></option>
            <option value="top_right" <?php selected( $corner, 'top_right' ); ?>><?php esc_html_e( 'Górny prawy róg', 'wp-contact-plugin' ); ?></option>
            <option value="top_left" <?php selected( $corner, 'top_left' ); ?>><?php esc_html_e( 'Górny lewy róg', 'wp-contact-plugin' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Wybierz róg ekranu, w którym ma pojawić się belka / koło.', 'wp-contact-plugin' ); ?></p>
        <?php
    }

    public function render_offsets_field() {
        $options = $this->get_options();
        ?>
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
                'corner'      => $options['corner'] ?? 'bottom_right',
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

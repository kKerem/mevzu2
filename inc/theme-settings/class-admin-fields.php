<?php
/**
 * Mevzu² Ayarlar & Stüdyo — Bootstrap floating label alanları
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mevzu_Admin_Fields {

    /** Bootstrap floating label için zorunlu placeholder */
    const FLOAT_PLACEHOLDER = ' ';

    /**
     * @param string $context 'settings' | 've'
     */
    public static function field_id( string $key, string $context = 'settings' ): string {
        return 've' === $context ? 'mevzu_ve_' . sanitize_key( $key ) : 'mevzu_' . sanitize_key( $key );
    }

    /**
     * @param string $context 'settings' | 've'
     */
    public static function wrap_class( string $context = 'settings', string $extra = '' ): string {
        $base = 've' === $context ? 'mevzu-ve-field' : 'mevzu-field';
        $classes = trim( $base . ' form-floating mb-3 ' . $extra );
        return $classes;
    }

    /**
     * @param array $args id, name, label, value, desc, type, input_class, attrs, rows, options, context
     */
    public static function render_floating_input( array $args ): void {
        $type         = $args['type'] ?? 'text';
        $id           = $args['id'] ?? '';
        $name         = $args['name'] ?? '';
        $label        = $args['label'] ?? '';
        $value        = $args['value'] ?? '';
        $desc         = $args['desc'] ?? '';
        $input_class  = $args['input_class'] ?? 'form-control';
        $attrs        = $args['attrs'] ?? '';
        $rows         = (int) ( $args['rows'] ?? 3 );
        $context      = $args['context'] ?? 'settings';
        $wrap_extra   = $args['wrap_class'] ?? '';

        if ( ! $id || ! $name ) {
            return;
        }

        $wrap = self::wrap_class( $context, $wrap_extra );
        ?>
        <div class="<?php echo esc_attr( $wrap ); ?>">
            <?php if ( 'textarea' === $type ) : ?>
                <textarea
                    id="<?php echo esc_attr( $id ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    class="<?php echo esc_attr( $input_class ); ?>"
                    rows="<?php echo esc_attr( (string) max( 2, $rows ) ); ?>"
                    placeholder="<?php echo esc_attr( self::FLOAT_PLACEHOLDER ); ?>"
                    style="min-height: calc(3.5rem + <?php echo esc_attr( (string) max( 0, ( $rows - 1 ) * 24 ) ); ?>px);"
                ><?php echo esc_textarea( $value ); ?></textarea>
            <?php else : ?>
                <input
                    type="<?php echo esc_attr( $type ); ?>"
                    id="<?php echo esc_attr( $id ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    value="<?php echo 'password' === $type ? esc_attr( $value ) : esc_attr( $value ); ?>"
                    class="<?php echo esc_attr( $input_class ); ?>"
                    placeholder="<?php echo esc_attr( self::FLOAT_PLACEHOLDER ); ?>"
                    <?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                >
            <?php endif; ?>
            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
            <?php if ( $desc ) : ?>
                <p class="description mt-1 mb-0"><?php echo esc_html( $desc ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * @param array $args id, name, label, value, desc, options, select_class, context
     */
    public static function render_floating_select( array $args ): void {
        $id            = $args['id'] ?? '';
        $name          = $args['name'] ?? '';
        $label         = $args['label'] ?? '';
        $value         = $args['value'] ?? '';
        $desc          = $args['desc'] ?? '';
        $options       = $args['options'] ?? array();
        $select_class  = $args['select_class'] ?? 'form-select';
        $context       = $args['context'] ?? 'settings';
        $wrap_extra    = $args['wrap_class'] ?? '';

        if ( ! $id || ! $name ) {
            return;
        }

        $wrap = self::wrap_class( $context, $wrap_extra );
        ?>
        <div class="<?php echo esc_attr( $wrap ); ?>">
            <select
                id="<?php echo esc_attr( $id ); ?>"
                name="<?php echo esc_attr( $name ); ?>"
                class="<?php echo esc_attr( $select_class ); ?>"
                aria-label="<?php echo esc_attr( $label ); ?>"
            >
                <?php foreach ( $options as $val => $text ) : ?>
                    <option value="<?php echo esc_attr( (string) $val ); ?>" <?php selected( $value, $val ); ?>>
                        <?php echo esc_html( $text ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
            <?php if ( $desc ) : ?>
                <p class="description mt-1 mb-0"><?php echo esc_html( $desc ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

/* global wp, jQuery */
/**
 * File customizer.js.
 *
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */

( function( $ ) {
	// Site title and description.
	wp.customize( 'blogname', function( value ) {
		value.bind( function( to ) {
			$( '.site-title a' ).text( to );
		} );
	} );
	wp.customize( 'blogdescription', function( value ) {
		value.bind( function( to ) {
			$( '.site-description' ).text( to );
		} );
	} );

	// Header text color.
	wp.customize( 'header_textcolor', function( value ) {
		value.bind( function( to ) {
			if ( 'blank' === to ) {
				$( '.site-title, .site-description' ).css( {
					clip: 'rect(1px, 1px, 1px, 1px)',
					position: 'absolute',
				} );
			} else {
				$( '.site-title, .site-description' ).css( {
					clip: 'auto',
					position: 'relative',
				} );
				$( '.site-title a, .site-description' ).css( {
					color: to,
				} );
			}
		} );
	} );
}( jQuery ) );

(function ($) {
    function hexToRgb(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) {
            hex = hex.split('').map(h => h + h).join('');
        }
        const bigint = parseInt(hex, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return { r, g, b };
    }

    function darkenColor(rgb, percentage) {
        return {
            r: Math.max(0, Math.round(rgb.r * (1 - percentage / 100))),
            g: Math.max(0, Math.round(rgb.g * (1 - percentage / 100))),
            b: Math.max(0, Math.round(rgb.b * (1 - percentage / 100)))
        };
    }

    wp.customize('mevzu_primary_color', function (value) {
        value.bind(function (newValue) {
            const rgbValue = hexToRgb(newValue);
            const darkenedRgb = darkenColor(rgbValue, 20); // %20 karartma
            const darkenedRgbString = `${darkenedRgb.r}, ${darkenedRgb.g}, ${darkenedRgb.b}`;
            const darkenedHex = `#${((1 << 24) + (darkenedRgb.r << 16) + (darkenedRgb.g << 8) + darkenedRgb.b).toString(16).slice(1)}`;

            // Önceki dinamik style tag'ini kaldır
            let dynamicStyleTag = document.getElementById('mevzu-dynamic-customizer');
            if (dynamicStyleTag) {
                dynamicStyleTag.remove();
            }

            // Yeni bir style tag oluştur
            dynamicStyleTag = document.createElement('style');
            dynamicStyleTag.id = 'mevzu-dynamic-customizer';
            dynamicStyleTag.textContent = `
                :root {
                    --mevzu-primary: ${newValue} !important;
                    --mevzu-primary-rgb: rgba(${rgbValue.r}, ${rgbValue.g}, ${rgbValue.b}, 1) !important;
                }
                body.dark {
                    --mevzu-primary: ${darkenedHex} !important;
                    --mevzu-primary-rgb: rgba(${darkenedRgbString}, 1) !important;
                }
            `;

            // Yeni style tag'i <head>'e ekle
            document.head.appendChild(dynamicStyleTag);
        });
    });

	wp.customize('mevzu_background_color', function (value) {
        value.bind(function (newValue) {
            // Önceki dinamik style tag'ini kaldır
            let dynamicStyleTag = document.getElementById('mevzu-dynamic-background');
            if (dynamicStyleTag) {
                dynamicStyleTag.remove();
            }

            // Yeni bir style tag oluştur
            dynamicStyleTag = document.createElement('style');
            dynamicStyleTag.id = 'mevzu-dynamic-background';
            dynamicStyleTag.textContent = `
                body {
                    background-color: ${newValue} !important;
                }
            `;

            // Yeni style tag'i <head>'e ekle
            document.head.appendChild(dynamicStyleTag);
        });
    });
})(jQuery);


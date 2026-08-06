/**
 * Ön yüz admin bar: Özelleştirici ile aynı theme_mod renkleri — canlı önizleme + AJAX kayıt.
 */
(function ($) {
	'use strict';

	if (typeof mevzuFrontColors === 'undefined') {
		return;
	}

	var PREVIEW_ID = 'mevzu-fcp-preview-style';
	var cfg = mevzuFrontColors;
	var i18n = cfg.i18n;

	function hexToRgb(hex) {
		hex = String(hex).replace('#', '');
		if (hex.length === 3) {
			hex = hex.split('').map(function (c) { return c + c; }).join('');
		}
		var n = parseInt(hex, 16);
		return {
			r: (n >> 16) & 255,
			g: (n >> 8) & 255,
			b: n & 255
		};
	}

	function rgbToHex(r, g, b) {
		return '#' + [r, g, b].map(function (x) {
			var h = Math.max(0, Math.min(255, x)).toString(16);
			return h.length === 1 ? '0' + h : h;
		}).join('');
	}

	/** PHP darken_color ile uyumlu (%10) */
	function darkenRgb(rgb, percentage) {
		var p = percentage / 100;
		return {
			r: Math.max(0, Math.round(rgb.r * (1 - p))),
			g: Math.max(0, Math.round(rgb.g * (1 - p))),
			b: Math.max(0, Math.round(rgb.b * (1 - p)))
		};
	}

	function applyPreview(bg, primaryHex) {
		var rgb = hexToRgb(primaryHex);
		var d = darkenRgb(rgb, 10);
		var darkHex = rgbToHex(d.r, d.g, d.b);
		var tag = document.getElementById(PREVIEW_ID);
		if (!tag) {
			tag = document.createElement('style');
			tag.id = PREVIEW_ID;
			document.head.appendChild(tag);
		}
		tag.textContent =
			'body{background-color:' + bg + ' !important;}' +
			':root{' +
			'--mevzu-primary:' + primaryHex + ' !important;' +
			'--mevzu-primary-rgb:' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ' !important;' +
			'--mevzu-link-color-rgb:' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ' !important;' +
			'}' +
			'body.dark{' +
			'--mevzu-primary:' + darkHex + ' !important;' +
			'--mevzu-primary-rgb:' + d.r + ', ' + d.g + ', ' + d.b + ' !important;' +
			'--mevzu-link-color-rgb:' + d.r + ', ' + d.g + ', ' + d.b + ' !important;' +
			'}';
	}

	function removePreview() {
		var tag = document.getElementById(PREVIEW_ID);
		if (tag && tag.parentNode) {
			tag.parentNode.removeChild(tag);
		}
	}

	function snapshotFromInputs() {
		return {
			bg: $('#mevzu-fcp-bg').val(),
			primary: $('#mevzu-fcp-primary').val()
		};
	}

	function resetInputsToDefaults() {
		$('#mevzu-fcp-bg').val(cfg.defaults.bg);
		$('#mevzu-fcp-primary').val(cfg.defaults.primary);
	}

	function buildPrimaryPresets() {
		var $wrap = $('#mevzu-fcp-primary-presets');
		if (!$wrap.length || !cfg.primaryPresets || !cfg.primaryPresets.length) {
			return;
		}
		$wrap.empty();
		cfg.primaryPresets.forEach(function (hex) {
			if (!hex || typeof hex !== 'string') {
				return;
			}
			$('<button type="button" class="mevzu-fcp-preset" />')
				.attr('title', hex)
				.attr('aria-label', hex)
				.attr('data-color', hex)
				.css('background-color', hex)
				.appendTo($wrap);
		});
	}

	$(function () {
		var $panel = $('#mevzu-front-color-panel');
		if (!$panel.length) {
			return;
		}

		$('.mevzu-fcp-title').text(i18n.panelTitle);
		$('.mevzu-fcp-bg-label').text(i18n.bgLabel);
		$('.mevzu-fcp-primary-label').text(i18n.primaryLabel);
		$('#mevzu-fcp-cancel').text(i18n.cancel);
		$('#mevzu-fcp-save').text(i18n.save);

		$('#wp-admin-bar-mevzu-screen-options > a').attr('title', i18n.toolbarTitle || i18n.panelTitle);

		buildPrimaryPresets();
		resetInputsToDefaults();

		$('#mevzu-fcp-primary-presets').on('click', '.mevzu-fcp-preset', function () {
			var hex = $(this).attr('data-color');
			if (!hex) {
				return;
			}
			$('#mevzu-fcp-primary').val(hex).trigger('input');
		});

		function closePanel() {
			removePreview();
			resetInputsToDefaults();
			$panel.attr('hidden', 'hidden').attr('aria-hidden', 'true').removeClass('is-open');
			$('#mevzu-fcp-status').removeClass('is-error is-ok').text('');
			$('#mevzu-fcp-save').prop('disabled', false);
		}

		function openPanel() {
			resetInputsToDefaults();
			$panel.removeAttr('hidden').attr('aria-hidden', 'false').addClass('is-open');
			$('#mevzu-fcp-status').removeClass('is-error is-ok').text('');
		}

		$('#wp-admin-bar-mevzu-screen-options > a').on('click.mevzuFcp', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if ($panel.hasClass('is-open')) {
				closePanel();
				return;
			}
			openPanel();
		});

		$('#mevzu-fcp-bg, #mevzu-fcp-primary').on('input change', function () {
			var s = snapshotFromInputs();
			applyPreview(s.bg, s.primary);
		});

		$('#mevzu-fcp-cancel').on('click', function () {
			closePanel();
		});

		$('#mevzu-fcp-save').on('click', function () {
			var $btn = $(this);
			var s = snapshotFromInputs();
			$btn.prop('disabled', true);
			$('#mevzu-fcp-status').removeClass('is-error is-ok').text('');

			$.post(cfg.ajaxUrl, {
				action: 'mevzu_save_front_theme_colors',
				nonce: cfg.nonce,
				mevzu_background_color: s.bg,
				mevzu_primary_color: s.primary
			})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						cfg.defaults.bg = resp.data.mevzu_background_color;
						cfg.defaults.primary = resp.data.mevzu_primary_color;
						$('#mevzu-fcp-status').addClass('is-ok').text(i18n.saved);
						window.setTimeout(function () {
							window.location.reload();
						}, 500);
					} else {
						$('#mevzu-fcp-status').addClass('is-error').text(i18n.error);
						$btn.prop('disabled', false);
					}
				})
				.fail(function () {
					$('#mevzu-fcp-status').addClass('is-error').text(i18n.error);
					$btn.prop('disabled', false);
				});
		});

		$(document).on('keydown.mevzuFcp', function (e) {
			if (e.key === 'Escape' && $panel.hasClass('is-open')) {
				closePanel();
			}
		});
	});
})(jQuery);

/**
 * Mevzu² AI — yazı editörü: panel görünürlüğü + arka plan ses durumu.
 */
(function ($) {
    'use strict';

    if (typeof mevzuTtsEditor === 'undefined') {
        return;
    }

    var cfg = mevzuTtsEditor;
    var pollTimer = null;

    function $metaBox() {
        return $('#kkerem-tts-audio');
    }

    function $wrapper() {
        return $('#kkerem-tts-meta-box-wrapper');
    }

    function isYzMansetChecked() {
        return $('#manset_konum_yapay_zeka_manset').is(':checked');
    }

    function getSelectedCategoryIds() {
        var ids = [];
        $('.categorychecklist input:checked, #categorychecklist input:checked').each(function () {
            ids.push(parseInt($(this).val(), 10));
        });
        if (window.wp && wp.data && wp.data.select) {
            try {
                var cats = wp.data.select('core/editor').getEditedPostAttribute('categories');
                if (Array.isArray(cats)) {
                    ids = cats.map(function (c) {
                        return parseInt(c, 10);
                    });
                }
            } catch (e) {}
        }
        return ids;
    }

    function isTargetCategorySelected() {
        if (!cfg.targetCategory) {
            return false;
        }
        var target = parseInt(cfg.targetCategory, 10);
        return getSelectedCategoryIds().some(function (id) {
            return id === target;
        });
    }

    function shouldShowPanel(state) {
        if (state && state.panel_visible) {
            return true;
        }
        if (isYzMansetChecked()) {
            return true;
        }
        if (isTargetCategorySelected()) {
            return true;
        }
        if (state && (state.job_status === 'queued' || state.job_status === 'processing')) {
            return true;
        }
        return false;
    }

    function updatePanelVisibility(state) {
        if (shouldShowPanel(state)) {
            $metaBox().show();
            $wrapper().show();
        } else {
            $metaBox().hide();
        }
    }

    function setBusyMessage(msg) {
        $('#kkerem-tts-loading').show().find('span:last-child').text(msg || cfg.strings.processing);
        $('#kkerem-tts-error').hide();
        $('#kkerem-tts-success').hide();
    }

    function renderAudioPlayer(fileInfo) {
        if (!fileInfo || !fileInfo.file_url) {
            return;
        }
        var html = '';
        html += '<div class="kkerem-tts-audio-info">';
        html += '<audio class="m-0" controls controlslist="nodownload noplaybackrate" style="width:100%;margin:10px 0;">';
        html += '<source src="' + fileInfo.file_url + '" type="audio/mpeg">';
        html += '</audio>';
        if (fileInfo.created_date) {
            html += '<p class="fz-10 mt-0 text-end text-muted">' + fileInfo.created_date;
            if (fileInfo.file_size_formatted) {
                html += ', ≈' + fileInfo.file_size_formatted;
            }
            html += '</p>';
        }
        html += '<button type="button" class="button button-secondary w-100 my-2" id="kkerem-tts-regenerate">' + cfg.strings.regenerate + '</button>';
        html += '</div>';
        $('#kkerem-tts-status-area').html(html);
        $('#kkerem-tts-loading').hide();
        $('#kkerem-tts-success').show().find('small').text(cfg.strings.ready);
    }

    function applyState(state) {
        if (!state) {
            return;
        }
        updatePanelVisibility(state);

        if (state.job_status === 'queued' || state.job_status === 'processing') {
            setBusyMessage(state.job_message || cfg.strings.processing);
            startPolling();
            return;
        }

        stopPolling();

        if (state.job_status === 'error') {
            $('#kkerem-tts-loading').hide();
            $('#kkerem-tts-error').show().find('p').text(state.job_message || cfg.strings.error);
            return;
        }

        if (state.audio_exists && state.file_info) {
            renderAudioPlayer(state.file_info);
            return;
        }

        if (state.job_status === 'done' && !state.audio_exists) {
            startPolling();
        }
    }

    function fetchState(cb) {
        $.post(cfg.ajaxUrl, {
            action: 'mevzu_yz_get_tts_state',
            post_id: cfg.postId,
            nonce: cfg.nonce
        }).done(function (res) {
            if (res.success && res.data) {
                applyState(res.data);
            }
            if (typeof cb === 'function') {
                cb(res);
            }
        });
    }

    function startPolling() {
        if (pollTimer) {
            return;
        }
        pollTimer = setInterval(function () {
            fetchState();
        }, 4000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function queueGeneration($btn) {
        $btn.prop('disabled', true);
        setBusyMessage(cfg.strings.queued);
        updatePanelVisibility({ panel_visible: true, job_status: 'queued' });

        $.post(cfg.ajaxUrl, {
            action: 'mevzu_yz_queue_audio',
            post_id: cfg.postId,
            nonce: cfg.nonce
        }).done(function (res) {
            if (res.success) {
                startPolling();
                fetchState();
            } else {
                $('#kkerem-tts-loading').hide();
                $('#kkerem-tts-error').show().find('p').text(
                    (res.data && res.data.message) ? res.data.message : (res.data || cfg.strings.error)
                );
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            $('#kkerem-tts-loading').hide();
            $('#kkerem-tts-error').show().find('p').text(cfg.strings.error);
            $btn.prop('disabled', false);
        });
    }

    $(document).on('click', '#kkerem-tts-generate, #kkerem-tts-regenerate', function (e) {
        e.preventDefault();
        queueGeneration($(this));
    });

    $('#manset_konum_yapay_zeka_manset').on('change', function () {
        updatePanelVisibility();
    });

    $(document).on('change', '.categorychecklist input, #categorychecklist input', function () {
        updatePanelVisibility();
    });

    if (window.wp && wp.data && wp.data.subscribe) {
        var lastCats = null;
        wp.data.subscribe(function () {
            try {
                var cats = wp.data.select('core/editor').getEditedPostAttribute('categories');
                if (JSON.stringify(cats) !== JSON.stringify(lastCats)) {
                    lastCats = cats;
                    updatePanelVisibility();
                }
            } catch (err) {}
        });
    }

    $(function () {
        fetchState(function (res) {
            if (res.success && res.data) {
                if (res.data.job_status === 'queued' || res.data.job_status === 'processing') {
                    startPolling();
                }
            }
        });
    });

    $(window).on('beforeunload', stopPolling);
})(jQuery);

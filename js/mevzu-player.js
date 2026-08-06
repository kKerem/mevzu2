/**
 * Mevzu² Modern Video Player
 */
(function () {
    'use strict';

    var icons = {
        play:   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16.3944 12.0001L10 7.7371V16.263L16.3944 12.0001ZM19.376 12.4161L8.77735 19.4818C8.54759 19.635 8.23715 19.5729 8.08397 19.3432C8.02922 19.261 8 19.1645 8 19.0658V4.93433C8 4.65818 8.22386 4.43433 8.5 4.43433C8.59871 4.43433 8.69522 4.46355 8.77735 4.5183L19.376 11.584C19.6057 11.7372 19.6678 12.0477 19.5146 12.2774C19.478 12.3323 19.4309 12.3795 19.376 12.4161Z"></path></svg>',
        pause:  '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>',
        volOn:  '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>',
        volOff: '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>',
        fsIn:   '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>',
        fsOut:  '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/></svg>',
        dl:     '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5z"/></svg>',
        dlBusy: '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14v-4H7l5-6 5 6h-4v4h-2z" opacity=".4"/></svg>',
    };

    function fmt(s) {
        s = isFinite(s) ? Math.floor(s) : 0;
        var m = Math.floor(s / 60), sec = s % 60;
        return m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    /* ── Slider yardımcısı: mousedown + touchstart → sürüklenebilir ── */
    function makeSlider(track, fill, onValue) {
        var dragging = false;

        function calc(e) {
            var r   = track.getBoundingClientRect();
            var cx  = e.changedTouches ? e.changedTouches[0].clientX
                    : e.touches       ? e.touches[0].clientX
                    : e.clientX;
            return Math.max(0, Math.min(1, (cx - r.left) / r.width));
        }

        function apply(e) {
            var v = calc(e);
            fill.style.width = (v * 100) + '%';
            onValue(v);
        }

        track.addEventListener('mousedown', function (e) {
            dragging = true;
            apply(e);
            e.preventDefault();
        });
        track.addEventListener('touchstart', function (e) {
            dragging = true;
            apply(e);
        }, { passive: true });

        document.addEventListener('mousemove', function (e) {
            if (dragging) apply(e);
        });
        document.addEventListener('touchmove', function (e) {
            if (dragging) apply(e);
        }, { passive: true });
        document.addEventListener('mouseup',  function () { dragging = false; });
        document.addEventListener('touchend', function () { dragging = false; });
    }

    function initPlayer(wrap) {
        if (wrap._mvpInit) return;
        wrap._mvpInit = true;

        var video    = wrap.querySelector('.mvp-video');
        var overlay  = wrap.querySelector('.mvp-overlay');
        var bigWrap  = wrap.querySelector('.mvp-big-play');
        var spinner  = wrap.querySelector('.mvp-spinner');
        var bar      = wrap.querySelector('.mvp-controls');
        var btnPlay  = wrap.querySelector('.mvp-btn-play');
        var progress = wrap.querySelector('.mvp-progress');
        var bufBar   = wrap.querySelector('.mvp-progress-buffered');
        var fillBar  = wrap.querySelector('.mvp-progress-filled');
        var tCur     = wrap.querySelector('.mvp-time-cur');
        var tDur     = wrap.querySelector('.mvp-time-dur');
        var btnMute  = wrap.querySelector('.mvp-btn-mute');
        var volTrack = wrap.querySelector('.mvp-volume');
        var volFill  = wrap.querySelector('.mvp-volume-filled');
        var btnFs    = wrap.querySelector('.mvp-btn-fullscreen');
        var btnDl    = wrap.querySelector('.mvp-btn-download');

        if (!video) return;

        /* İlk ikonlar */
        btnPlay.innerHTML = icons.play;
        btnMute.innerHTML = icons.volOn;
        btnFs.innerHTML   = icons.fsIn;
        if (btnDl) btnDl.innerHTML = icons.dl;
        bigWrap.innerHTML = icons.play;

        /* ── Oynat / Duraklat ── */
        function setPlayIcon() {
            var paused = video.paused || video.ended;
            btnPlay.innerHTML     = paused ? icons.play : icons.pause;
            bigWrap.style.opacity = paused ? '1' : '0';
            wrap.classList.toggle('mvp-playing', !paused);
        }
        function togglePlay() { video.paused ? video.play() : video.pause(); }

        video.addEventListener('play',  setPlayIcon);
        video.addEventListener('pause', setPlayIcon);
        video.addEventListener('ended', function () { video.currentTime = 0; setPlayIcon(); });

        overlay.addEventListener('click', togglePlay);
        bigWrap.addEventListener('click', togglePlay);
        btnPlay.addEventListener('click', togglePlay);

        /* ── Süre ── */
        video.addEventListener('loadedmetadata', function () {
            tDur.textContent = fmt(video.duration);
        });
        video.addEventListener('timeupdate', function () {
            if (!video.duration) return;
            fillBar.style.width = (video.currentTime / video.duration * 100) + '%';
            tCur.textContent    = fmt(video.currentTime);
        });
        video.addEventListener('progress', function () {
            if (video.duration && video.buffered.length) {
                bufBar.style.width = (video.buffered.end(video.buffered.length - 1) / video.duration * 100) + '%';
            }
        });

        /* ── Seek slider ── */
        makeSlider(progress, fillBar, function (v) {
            if (video.duration) video.currentTime = v * video.duration;
        });

        /* ── Ses slider (sürüklenebilir) ── */
        function updateVolUI() {
            var v = video.muted ? 0 : video.volume;
            volFill.style.width = (v * 100) + '%';
            btnMute.innerHTML   = v === 0 ? icons.volOff : icons.volOn;
        }

        makeSlider(volTrack, volFill, function (v) {
            video.volume = v;
            video.muted  = v === 0;
            updateVolUI();
        });

        btnMute.addEventListener('click', function () {
            video.muted = !video.muted;
            if (!video.muted && video.volume === 0) video.volume = 0.7;
            updateVolUI();
        });
        updateVolUI();

        /* ── Tam ekran ── */
        btnFs.addEventListener('click', function () {
            if (document.fullscreenElement || document.webkitFullscreenElement) {
                (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            } else {
                var fn = wrap.requestFullscreen || wrap.webkitRequestFullscreen || wrap.mozRequestFullScreen;
                if (fn) fn.call(wrap);
            }
        });
        function syncFsIcon() {
            var inFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
            btnFs.innerHTML = inFs ? icons.fsOut : icons.fsIn;
        }
        document.addEventListener('fullscreenchange',       syncFsIcon);
        document.addEventListener('webkitfullscreenchange', syncFsIcon);

        /* ── İndir (fetch → blob → <a>) ── */
        if (btnDl) {
            btnDl.addEventListener('click', function () {
                var src = video.currentSrc || (video.querySelector('source') || {}).src || '';
                if (!src) return;

                var filename = src.split('/').pop().split('?')[0] || 'video.mp4';
                btnDl.innerHTML  = icons.dlBusy;
                btnDl.disabled   = true;

                fetch(src)
                    .then(function (r) { return r.blob(); })
                    .then(function (blob) {
                        var url = URL.createObjectURL(blob);
                        var a   = document.createElement('a');
                        a.href     = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
                    })
                    .catch(function () {
                        /* CORS yoksa proxy URL ile dene */
                        var proxyUrl = src + (src.indexOf('?') === -1 ? '?' : '&') + 'response-content-disposition=attachment';
                        fetch(proxyUrl, { mode: 'cors' })
                            .then(function (r) { return r.blob(); })
                            .then(function (blob) {
                                var url = URL.createObjectURL(blob);
                                var a   = document.createElement('a');
                                a.href = url; a.download = filename;
                                document.body.appendChild(a); a.click();
                                document.body.removeChild(a);
                                setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
                            })
                            .catch(function () {
                                /* Son çare: tarayıcı indirme diyaloğunu tetikle */
                                var a = document.createElement('a');
                                a.href = 'data:application/octet-stream,' + encodeURIComponent(src);
                                a.download = filename;
                                document.body.appendChild(a); a.click();
                                document.body.removeChild(a);
                            });
                    })
                    .finally(function () {
                        btnDl.innerHTML = icons.dl;
                        btnDl.disabled  = false;
                    });
            });
        }

        /* ── Spinner ── */
        video.addEventListener('waiting', function () { spinner.style.opacity = '1'; });
        video.addEventListener('playing', function () { spinner.style.opacity = '0'; });

        /* ── Kontrol çubuğu otomatik gizle ── */
        var hideT;
        function showBar() {
            bar.style.opacity = '1';
            clearTimeout(hideT);
            if (!video.paused) hideT = setTimeout(function () { bar.style.opacity = '0'; }, 2600);
        }
        wrap.addEventListener('mousemove',  showBar);
        wrap.addEventListener('touchstart', showBar, { passive: true });
        wrap.addEventListener('mouseleave', function () { if (!video.paused) bar.style.opacity = '0'; });
        video.addEventListener('pause', function () { clearTimeout(hideT); bar.style.opacity = '1'; });

        /* ── Klavye kısayolları ── */
        wrap.setAttribute('tabindex', '0');
        wrap.addEventListener('keydown', function (e) {
            switch (e.key) {
                case ' ': case 'k': e.preventDefault(); togglePlay(); break;
                case 'ArrowRight':  video.currentTime = Math.min(video.duration || 0, video.currentTime + 10); break;
                case 'ArrowLeft':   video.currentTime = Math.max(0, video.currentTime - 10); break;
                case 'ArrowUp':     video.volume = Math.min(1, video.volume + 0.1); video.muted = false; updateVolUI(); break;
                case 'ArrowDown':   video.volume = Math.max(0, video.volume - 0.1); updateVolUI(); break;
                case 'm':           video.muted = !video.muted; updateVolUI(); break;
                case 'f':           btnFs.click(); break;
            }
        });
    }

    function bootstrap() {
        document.querySelectorAll('.mvp-wrapper').forEach(initPlayer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
})();
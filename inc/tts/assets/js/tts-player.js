/**
 * Mevzu TTS Player — mevzu-player (video) ile aynı slider/ikon mantığı, ses için.
 */
(function () {
    'use strict';

    var icons = {
        play: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
        pause: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>',
        volOn: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>',
        volOff: '<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3z"/></svg>',
    };

    function fmt(s) {
        s = isFinite(s) ? Math.floor(s) : 0;
        var m = Math.floor(s / 60);
        var sec = s % 60;
        return m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function makeSlider(track, fill, onValue) {
        var dragging = false;

        function calc(e) {
            var r = track.getBoundingClientRect();
            var cx = e.changedTouches ? e.changedTouches[0].clientX
                : e.touches ? e.touches[0].clientX
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
        document.addEventListener('mouseup', function () { dragging = false; });
        document.addEventListener('touchend', function () { dragging = false; });
    }

    function initPlayerWave(wrap) {
        if (wrap._mtpInit) return;
        wrap._mtpInit = true;

        var audio = wrap.querySelector('.mtp-audio');
        if (!audio) return;

        var shell = wrap.closest('.kkerem-tts-player--wave') || wrap;
        var labelPlay = shell.getAttribute('data-label-play') || 'Sesli dinle';
        var labelPause = shell.getAttribute('data-label-pause') || 'Duraklat';

        function setState() {
            var playing = !audio.paused && !audio.ended;
            wrap.classList.toggle('mtp-playing', playing);
            shell.classList.toggle('mtp-playing', playing);
            shell.setAttribute('aria-label', playing ? labelPause : labelPlay);
        }

        function togglePlay() {
            if (audio.paused || audio.ended) {
                audio.play().catch(function () {});
            } else {
                audio.pause();
            }
        }

        audio.addEventListener('play', setState);
        audio.addEventListener('pause', setState);
        audio.addEventListener('ended', setState);

        shell.addEventListener('click', togglePlay);
        shell.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePlay();
            }
        });

        setState();
    }

    function initPlayerFull(wrap) {
        if (wrap._mtpInit) return;
        wrap._mtpInit = true;

        var audio = wrap.querySelector('.mtp-audio');
        var btnPlay = wrap.querySelector('.mtp-btn-play');
        var progress = wrap.querySelector('.mtp-progress');
        var bufBar = wrap.querySelector('.mtp-progress-buffered');
        var fillBar = wrap.querySelector('.mtp-progress-filled');
        var tCur = wrap.querySelector('.mtp-time-cur');
        var tDur = wrap.querySelector('.mtp-time-dur');
        var btnMute = wrap.querySelector('.mtp-btn-mute');
        var volTrack = wrap.querySelector('.mtp-volume');
        var volFill = wrap.querySelector('.mtp-volume-filled');

        if (!audio || !btnPlay) return;

        btnPlay.innerHTML = icons.play;
        if (btnMute) btnMute.innerHTML = icons.volOn;

        function setPlayIcon() {
            var paused = audio.paused || audio.ended;
            btnPlay.innerHTML = paused ? icons.play : icons.pause;
            wrap.classList.toggle('mtp-playing', !paused);
        }

        function togglePlay() {
            if (audio.paused) {
                audio.play().catch(function () {});
            } else {
                audio.pause();
            }
        }

        audio.addEventListener('play', setPlayIcon);
        audio.addEventListener('pause', setPlayIcon);
        audio.addEventListener('ended', setPlayIcon);

        btnPlay.addEventListener('click', togglePlay);

        audio.addEventListener('loadedmetadata', function () {
            if (tDur) tDur.textContent = fmt(audio.duration);
        });

        audio.addEventListener('timeupdate', function () {
            if (!audio.duration) return;
            if (fillBar) fillBar.style.width = (audio.currentTime / audio.duration * 100) + '%';
            if (tCur) tCur.textContent = fmt(audio.currentTime);
        });

        audio.addEventListener('progress', function () {
            if (bufBar && audio.duration && audio.buffered.length) {
                bufBar.style.width = (audio.buffered.end(audio.buffered.length - 1) / audio.duration * 100) + '%';
            }
        });

        if (progress && fillBar) {
            makeSlider(progress, fillBar, function (v) {
                if (audio.duration) audio.currentTime = v * audio.duration;
            });
        }

        if (btnMute && volTrack && volFill) {
            function updateVolUI() {
                var v = audio.muted ? 0 : audio.volume;
                volFill.style.width = (v * 100) + '%';
                btnMute.innerHTML = v === 0 ? icons.volOff : icons.volOn;
            }
            makeSlider(volTrack, volFill, function (v) {
                audio.volume = v;
                audio.muted = v === 0;
                updateVolUI();
            });
            btnMute.addEventListener('click', function () {
                audio.muted = !audio.muted;
                if (!audio.muted && audio.volume === 0) audio.volume = 0.7;
                updateVolUI();
            });
            updateVolUI();
        }
    }

    function initPlayer(wrap) {
        var variant = wrap.getAttribute('data-mtp-variant') || (wrap.classList.contains('mtp-wrapper--wave') ? 'wave' : 'full');
        if (variant === 'wave') {
            initPlayerWave(wrap);
        } else {
            initPlayerFull(wrap);
        }
    }

    function bootstrap() {
        document.querySelectorAll('[data-mtp-player]').forEach(function (wrap) {
            initPlayer(wrap);
            var audio = wrap.querySelector('.mtp-audio');
            if (audio && audio.getAttribute('data-autoplay') === '1') {
                audio.play().catch(function () {});
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }

    window.MevzuTTSPlayer = { init: bootstrap, initOne: initPlayer, initWave: initPlayerWave, initFull: initPlayerFull };
})();

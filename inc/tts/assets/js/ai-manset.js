/**
 * Yapay Zeka Manşeti — karşılama + haberler (AJAX) + kapanış + Spotify tarzı satır vurgusu.
 */
(function () {
    'use strict';

    function getBootstrapModal() {
        if (typeof mevzu2 !== 'undefined' && mevzu2.Modal) {
            return mevzu2.Modal;
        }
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            return bootstrap.Modal;
        }
        return null;
    }

    function fetchLineAudio(text, onUrl) {
        if (!window.mevzuYzm || !mevzuYzm.ajaxUrl || !mevzuYzm.nonce) {
            onUrl(null);
            return;
        }
        var body = new FormData();
        body.append('action', 'mevzu_yzm_line_audio');
        body.append('nonce', mevzuYzm.nonce);
        body.append('text', text);

        fetch(mevzuYzm.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data && data.success && data.data && data.data.url) {
                    onUrl(data.data.url);
                } else {
                    onUrl(null);
                }
            })
            .catch(function () {
                onUrl(null);
            });
    }

    function fetchModalSlides() {
        if (!window.mevzuYzm || !mevzuYzm.ajaxUrl || !mevzuYzm.modalNonce) {
            return Promise.reject(new Error('config'));
        }
        var body = new FormData();
        body.append('action', 'mevzu_yzm_modal_slides');
        body.append('nonce', mevzuYzm.modalNonce);

        return fetch(mevzuYzm.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data && data.success && data.data) {
                    return data.data;
                }
                throw new Error('slides');
            });
    }

    /** TTS ile uyum için kelime ağırlıklı satır zamanlaması. */
    var YZM_LYRICS_SYNC_LEAD = 0.18;

    function countLyricWords(text) {
        var parts = String(text || '').trim().split(/\s+/).filter(Boolean);
        return Math.max(parts.length, 1);
    }

    function buildLineTimings(lines, duration) {
        if (!lines.length || !duration || !isFinite(duration)) {
            return [];
        }
        var total = 0;
        var i;
        for (i = 0; i < lines.length; i++) {
            total += countLyricWords(lines[i]);
        }
        var t = 0;
        var out = [];
        for (i = 0; i < lines.length; i++) {
            var share = countLyricWords(lines[i]) / total;
            var seg = share * duration;
            out.push({ start: t, end: t + seg, index: i });
            t += seg;
        }
        if (out.length) {
            out[out.length - 1].end = duration;
        }
        return out;
    }

    function findActiveLineIndex(timings, currentTime) {
        if (!timings.length) {
            return 0;
        }
        var t = currentTime + YZM_LYRICS_SYNC_LEAD;
        var i;
        for (i = timings.length - 1; i >= 0; i--) {
            if (t >= timings[i].start) {
                return timings[i].index;
            }
        }
        return 0;
    }

    function initBar(barEl) {
        var modalId = barEl.getAttribute('data-modal-id');
        var modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        var playlist = [];
        var introText = barEl.getAttribute('data-intro') || (window.mevzuYzm && mevzuYzm.intro) || '';
        var outroText = barEl.getAttribute('data-outro') || (window.mevzuYzm && mevzuYzm.outro) || '';
        var expectedCount = parseInt(barEl.getAttribute('data-count') || '0', 10);

        if (expectedCount < 1) {
            barEl.style.display = 'none';
            return;
        }

        var lastNewsIndex = 0;
        var slideCount = 2;
        var swiper = null;
        var swiperSpeed = 520;
        var audio = null;
        var loadedAudioUrl = '';
        var slideIndex = 0;
        var playing = false;
        var paused = false;
        var completed = false;
        var userSlideChange = false;
        var modalPayload = null;
        var modalLoadPromise = null;
        var pendingAdvance = false;
        var playbackStarted = false;
        var lyricsTimings = null;
        var lyricsTimeHandler = null;
        var lyricsMetaHandler = null;
        var lyricsPauseHandler = null;
        var lyricsEndHandler = null;
        var lyricsRafId = null;
        var lastLyricScrollAt = 0;

        var dotsEl = modal.querySelector('.yzm-progress-dots');
        var btnWave = modal.querySelector('[data-yzm-wave]');
        var btnPrev = modal.querySelector('.yzm-btn-prev');
        var btnNext = modal.querySelector('.yzm-btn-next');
        var loadingEl = modal.querySelector('[data-yzm-loading]');
        var swiperWrap = modal.querySelector('.yzm-swiper-wrap');
        var i18n = (window.mevzuYzm && mevzuYzm.i18n) || {};

        function isWelcome() {
            return slideIndex === 0;
        }

        function isOutro() {
            return slideIndex === slideCount - 1;
        }

        function isNews() {
            return slideIndex >= 1 && slideIndex <= lastNewsIndex;
        }

        function playlistIndex() {
            return slideIndex - 1;
        }

        function setLoading(show) {
            if (loadingEl) {
                loadingEl.classList.toggle('d-none', !show);
                loadingEl.classList.toggle('d-flex', show);
            }
            if (swiperWrap) {
                swiperWrap.classList.toggle('yzm-swiper-wrap--loading', show);
            }
        }

        function getOutroSlide() {
            return modal.querySelector('[data-yzm-outro-slide]');
        }

        function injectNewsSlides(slidesHtml) {
            if (modal.querySelector('.yzm-slide--news[data-post-id]')) {
                return;
            }
            var outro = getOutroSlide();
            var wrapper = modal.querySelector('.yzm-swiper .swiper-wrapper');
            if (!outro || !wrapper || !slidesHtml || !slidesHtml.length) {
                return;
            }
            var frag = document.createElement('div');
            frag.innerHTML = slidesHtml.join('');
            var nodes = Array.prototype.slice.call(frag.children);
            nodes.forEach(function (node) {
                wrapper.insertBefore(node, outro);
            });
        }

        function ensureModalPayload() {
            if (modalPayload) {
                return Promise.resolve(modalPayload);
            }
            if (modalLoadPromise) {
                return modalLoadPromise;
            }
            setLoading(true);
            modalLoadPromise = fetchModalSlides()
                .then(function (data) {
                    modalPayload = data;
                    playlist = data.playlist || [];
                    lastNewsIndex = playlist.length;
                    slideCount = playlist.length + 2;
                    injectNewsSlides(data.slides || []);
                    setLoading(false);
                    return data;
                })
                .catch(function () {
                    setLoading(false);
                    if (loadingEl) {
                        var p = loadingEl.querySelector('.yzm-modal-loading__text');
                        if (p) {
                            p.textContent = i18n.loadError || 'Haberler yüklenemedi.';
                        }
                    }
                    throw new Error('load');
                });
            return modalLoadPromise;
        }

        function getCurrentSlideEl() {
            var slides = modal.querySelectorAll('.yzm-swiper .swiper-slide');
            return slides[slideIndex] || null;
        }

        function getSlideLyrics() {
            var slide = getCurrentSlideEl();
            return slide ? slide.querySelector('.yzm-lyrics') : null;
        }

        function resetLyricsHighlight() {
            modal.querySelectorAll('.yzm-lyric-line').forEach(function (line) {
                line.classList.remove('yzm-lyric-line--past', 'yzm-lyric-line--active', 'yzm-lyric-line--future');
            });
        }

        function stopLyricsSync() {
            if (lyricsRafId) {
                cancelAnimationFrame(lyricsRafId);
                lyricsRafId = null;
            }
            if (audio) {
                if (lyricsTimeHandler) {
                    audio.removeEventListener('timeupdate', lyricsTimeHandler);
                    audio.removeEventListener('play', lyricsTimeHandler);
                }
                if (lyricsMetaHandler) {
                    audio.removeEventListener('loadedmetadata', lyricsMetaHandler);
                }
                if (lyricsPauseHandler) {
                    audio.removeEventListener('pause', lyricsPauseHandler);
                }
                if (lyricsEndHandler) {
                    audio.removeEventListener('ended', lyricsEndHandler);
                }
            }
            lyricsTimeHandler = null;
            lyricsMetaHandler = null;
            lyricsPauseHandler = null;
            lyricsEndHandler = null;
            lyricsTimings = null;
            resetLyricsHighlight();
            modal.querySelectorAll('.yzm-slide--news, .yzm-slide--welcome, .yzm-slide--outro').forEach(function (s) {
                s.classList.remove('yzm-slide--lyrics-active');
            });
        }

        function startLyricsSync(container) {
            stopLyricsSync();
            if (!container || !audio) {
                return;
            }
            var lineEls = container.querySelectorAll('.yzm-lyric-line');
            if (!lineEls.length) {
                return;
            }
            var lines = Array.prototype.map.call(lineEls, function (el) {
                return el.textContent.trim();
            });

            var slideEl = container.closest('.yzm-slide--news, .yzm-slide--welcome, .yzm-slide--outro');
            if (slideEl) {
                slideEl.classList.add('yzm-slide--lyrics-active');
            }

            lineEls.forEach(function (el) {
                el.classList.add('yzm-lyric-line--future');
            });

            function applyHighlight() {
                if (!audio || !lyricsTimings) {
                    return;
                }
                var idx = findActiveLineIndex(lyricsTimings, audio.currentTime);
                var now = Date.now();
                lineEls.forEach(function (el, i) {
                    el.classList.remove('yzm-lyric-line--past', 'yzm-lyric-line--active', 'yzm-lyric-line--future');
                    if (i < idx) {
                        el.classList.add('yzm-lyric-line--past');
                    } else if (i === idx) {
                        el.classList.add('yzm-lyric-line--active');
                    } else {
                        el.classList.add('yzm-lyric-line--future');
                    }
                });
                if (lineEls[idx] && now - lastLyricScrollAt > 280) {
                    lastLyricScrollAt = now;
                    lineEls[idx].scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            }

            function rebuildTimings() {
                if (audio.duration && isFinite(audio.duration)) {
                    lyricsTimings = buildLineTimings(lines, audio.duration);
                    applyHighlight();
                }
            }

            function lyricsTick() {
                if (!lyricsTimings && audio.duration) {
                    rebuildTimings();
                }
                applyHighlight();
                if (audio && !audio.paused && !audio.ended) {
                    lyricsRafId = requestAnimationFrame(lyricsTick);
                } else {
                    lyricsRafId = null;
                }
            }

            lyricsTimeHandler = function () {
                if (!lyricsTimings && audio.duration) {
                    rebuildTimings();
                }
                applyHighlight();
                if (audio && !audio.paused && !audio.ended && !lyricsRafId) {
                    lyricsRafId = requestAnimationFrame(lyricsTick);
                }
            };

            lyricsMetaHandler = rebuildTimings;
            lyricsPauseHandler = function () {
                if (lyricsRafId) {
                    cancelAnimationFrame(lyricsRafId);
                    lyricsRafId = null;
                }
            };
            lyricsEndHandler = lyricsPauseHandler;

            audio.addEventListener('timeupdate', lyricsTimeHandler);
            audio.addEventListener('loadedmetadata', lyricsMetaHandler);
            audio.addEventListener('play', lyricsTimeHandler);
            audio.addEventListener('pause', lyricsPauseHandler);
            audio.addEventListener('ended', lyricsEndHandler);
            rebuildTimings();
            if (audio && !audio.paused) {
                lyricsTimeHandler();
            }
        }

        function syncWaveUi() {
            var isSpeaking = playing && !paused && audio && !audio.paused && !audio.ended;
            if (btnWave) {
                btnWave.classList.toggle('yzm-robot-player--speaking', isSpeaking);
                btnWave.setAttribute('aria-label', isSpeaking ? (i18n.pause || 'Duraklat') : (i18n.play || 'Oynat'));
            }
        }

        function updateNav() {
            if (btnPrev) {
                btnPrev.disabled = slideIndex <= 0;
            }
            if (btnNext) {
                btnNext.disabled = slideIndex >= slideCount - 1;
            }
        }

        function buildDots() {
            if (dotsEl) {
                dotsEl.innerHTML = '';
                for (var i = 0; i < slideCount; i++) {
                    var d = document.createElement('span');
                    d.className = 'yzm-dot' + (i === slideIndex ? ' active' : '');
                    dotsEl.appendChild(d);
                }
            }
            updateNav();
        }

        function updateDots() {
            if (dotsEl) {
                dotsEl.querySelectorAll('.yzm-dot').forEach(function (d, i) {
                    d.classList.toggle('active', i === slideIndex);
                });
            }
            updateNav();
        }

        function goSlide(i, anim) {
            slideIndex = i;
            stopLyricsSync();
            if (swiper) {
                userSlideChange = true;
                swiper.slideTo(slideIndex, anim ? swiperSpeed : 0);
            }
            updateDots();
        }

        function ensureAudio() {
            if (!audio) {
                audio = document.createElement('audio');
                audio.preload = 'auto';
                audio.addEventListener('play', syncWaveUi);
                audio.addEventListener('pause', syncWaveUi);
                audio.addEventListener('ended', syncWaveUi);
            }
            return audio;
        }

        function stopAudio() {
            stopLyricsSync();
            if (audio) {
                audio.pause();
            }
            syncWaveUi();
        }

        function resetAll() {
            playing = false;
            paused = false;
            completed = false;
            pendingAdvance = false;
            playbackStarted = false;
            slideIndex = 0;
            loadedAudioUrl = '';
            stopAudio();
            if (audio) {
                audio.removeAttribute('src');
                audio.load();
            }
            if (swiper) {
                swiper.slideTo(0, 0);
            }
            updateDots();
            syncWaveUi();
        }

        function pausePlayback() {
            playing = true;
            paused = true;
            stopAudio();
        }

        function playUrl(url, onEnded, withLyrics) {
            var el = ensureAudio();
            stopLyricsSync();
            if (loadedAudioUrl !== url) {
                el.src = url;
                loadedAudioUrl = url;
            }
            el.onended = function () {
                stopLyricsSync();
                syncWaveUi();
                if (onEnded) {
                    onEnded();
                }
            };
            el.onerror = function () {
                stopLyricsSync();
                syncWaveUi();
                if (onEnded) {
                    onEnded();
                }
            };
            playing = true;
            paused = false;
            el.play()
                .then(function () {
                    syncWaveUi();
                    if (withLyrics) {
                        var lyrics = getSlideLyrics();
                        if (lyrics) {
                            startLyricsSync(lyrics);
                        }
                    }
                })
                .catch(syncWaveUi);
        }

        function playLineTts(text, onDone, withLyrics) {
            if (!text) {
                onDone();
                return;
            }
            fetchLineAudio(text, function (url) {
                if (!playing || paused) {
                    return;
                }
                if (!url) {
                    onDone();
                    return;
                }
                playUrl(url, onDone, !!withLyrics);
            });
        }

        function playIntro() {
            playLineTts(
                introText,
                function () {
                    if (playing && !paused && isWelcome()) {
                        advanceFromWelcome();
                    }
                },
                true
            );
        }

        function advanceFromWelcome() {
            if (!isWelcome() || !playlist.length) {
                return;
            }
            if (!modalPayload) {
                pendingAdvance = true;
                return;
            }
            goSlide(1, true);
            playNews();
        }

        function playNews() {
            var pi = playlistIndex();
            if (pi < 0 || !playlist[pi]) {
                return;
            }
            var item = playlist[pi];
            playUrl(item.audio, function () {
                if (!playing || paused) {
                    return;
                }
                if (slideIndex < lastNewsIndex) {
                    goSlide(slideIndex + 1, true);
                    playNews();
                } else {
                    goSlide(slideCount - 1, true);
                    playOutro();
                }
            }, true);
        }

        function playOutro() {
            playLineTts(
                outroText,
                function () {
                    playing = false;
                    paused = false;
                    completed = true;
                    syncWaveUi();
                },
                true
            );
        }

        function playCurrentSlide() {
            if (!modalPayload && !isWelcome()) {
                return;
            }
            completed = false;
            playing = true;
            paused = false;
            if (isWelcome()) {
                playIntro();
            } else if (isOutro()) {
                playOutro();
            } else if (isNews()) {
                playNews();
            }
        }

        function togglePlayback() {
            if (playing && !paused && audio && !audio.paused) {
                pausePlayback();
                return;
            }
            if (paused) {
                paused = false;
                playing = true;
                if (audio && loadedAudioUrl && audio.currentTime > 0 && !audio.ended) {
                    audio.play()
                        .then(function () {
                            syncWaveUi();
                            if (isNews()) {
                                var lyrics = getSlideLyrics();
                                if (lyrics) {
                                    startLyricsSync(lyrics);
                                }
                            }
                        })
                        .catch(syncWaveUi);
                } else {
                    playCurrentSlide();
                }
                return;
            }
            if (completed) {
                completed = false;
                goSlide(0, false);
                playCurrentSlide();
                return;
            }
            playCurrentSlide();
        }

        function jumpTo(newIndex) {
            if (newIndex < 0 || newIndex >= slideCount) {
                return;
            }
            stopAudio();
            loadedAudioUrl = '';
            goSlide(newIndex, true);
            if (playing && !paused) {
                playCurrentSlide();
            }
        }

        function initSwiper() {
            var el = modal.querySelector('.yzm-swiper');
            if (!el || typeof Swiper === 'undefined') {
                return;
            }
            if (swiper) {
                swiper.destroy(true, true);
                swiper = null;
            }
            swiper = new Swiper(el, {
                direction: 'vertical',
                effect: 'creative',
                slidesPerView: 1,
                spaceBetween: 0,
                speed: swiperSpeed,
                grabCursor: true,
                allowTouchMove: slideCount > 1,
                watchSlidesProgress: true,
                observer: true,
                observeParents: true,
                creativeEffect: {
                    limitProgress: 2,
                    shadowPerProgress: false,
                    progressMultiplier: 1,
                    perspective: true,
                    prev: {
                        translate: [0, '-100%', 0],
                        opacity: 0,
                        scale: 0.88,
                        origin: 'center bottom',
                    },
                    next: {
                        translate: [0, '100%', 0],
                        opacity: 0,
                        scale: 0.94,
                        origin: 'center bottom',
                    },
                },
                on: {
                    slideChange: function () {
                        if (userSlideChange) {
                            userSlideChange = false;
                            return;
                        }
                        if (swiper.activeIndex === slideIndex) {
                            return;
                        }
                        var wasPlaying = playing && !paused;
                        slideIndex = swiper.activeIndex;
                        updateDots();
                        loadedAudioUrl = '';
                        stopAudio();
                        if (wasPlaying) {
                            playCurrentSlide();
                        }
                    },
                },
            });
            swiper.slideTo(slideIndex, 0);
        }

        function beginPlayback() {
            buildDots();
            initSwiper();
            if (swiper) {
                swiper.update();
            }
            playing = true;
            paused = false;
            completed = false;
            slideIndex = 0;
            goSlide(0, false);
            playIntro();
        }

        function onModalShown() {
            ensureModalPayload()
                .then(function () {
                    buildDots();
                    initSwiper();
                    if (swiper) {
                        swiper.update();
                    }
                    if (pendingAdvance) {
                        pendingAdvance = false;
                        if (playing && !paused && isWelcome()) {
                            advanceFromWelcome();
                        }
                        return;
                    }
                    if (!playbackStarted) {
                        playbackStarted = true;
                        beginPlayback();
                    }
                })
                .catch(function () {
                    /* hata mesajı loading alanında */
                });
        }

        barEl.addEventListener('click', function () {
            var Modal = getBootstrapModal();
            if (Modal) {
                Modal.getOrCreateInstance(modal).show();
            } else {
                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
                onModalShown();
            }
        });

        modal.addEventListener('shown.bs.modal', onModalShown);
        modal.addEventListener('hidden.bs.modal', resetAll);

        if (btnWave) {
            btnWave.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                togglePlayback();
            });
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (slideIndex > 0) {
                    jumpTo(slideIndex - 1);
                }
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (slideIndex < slideCount - 1) {
                    jumpTo(slideIndex + 1);
                }
            });
        }

        modal.addEventListener('click', function (e) {
            var shareBtn = e.target.closest('.yzm-slide-share');
            if (shareBtn) {
                e.preventDefault();
                e.stopPropagation();
                var url = shareBtn.getAttribute('data-share-url');
                var title = shareBtn.getAttribute('data-share-title') || '';
                if (!url) {
                    return;
                }
                if (navigator.share) {
                    navigator.share({ title: title, url: url }).catch(function () {});
                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url);
                } else {
                    window.prompt(i18n.copied || 'Bağlantı', url);
                }
            }
        });
    }

    function bootstrapBars() {
        document.querySelectorAll('[data-yzm-bar]').forEach(initBar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapBars);
    } else {
        bootstrapBars();
    }
})();

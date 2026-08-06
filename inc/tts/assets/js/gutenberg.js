/* Gutenberg JavaScript */
(function() {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { createElement } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, Button, Spinner, Notice } = wp.components;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    
    registerBlockType('kkerem/tts-player', {
        title: __('kKerem TTS Player', 'kkerem-tts'),
        icon: 'microphone',
        category: 'media',
        description: __('Text-to-Speech ses oynatıcısı', 'kkerem-tts'),
        
        attributes: {
            postId: {
                type: 'number',
                default: 0
            },
            showTitle: {
                type: 'boolean',
                default: true
            },
            showInfo: {
                type: 'boolean',
                default: true
            },
            autoplay: {
                type: 'boolean',
                default: false
            },
            style: {
                type: 'string',
                default: 'default'
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { postId, showTitle, showInfo, autoplay, style } = attributes;
            
            const [audioInfo, setAudioInfo] = useState(null);
            const [loading, setLoading] = useState(false);
            const [error, setError] = useState(null);
            
            // Post ID'yi otomatik olarak ayarla
            useEffect(() => {
                if (!postId && wp.data.select('core/editor').getCurrentPostId()) {
                    setAttributes({ postId: wp.data.select('core/editor').getCurrentPostId() });
                }
            }, [postId, setAttributes]);
            
            // Ses dosyası bilgilerini yükle
            useEffect(() => {
                if (postId) {
                    loadAudioInfo();
                }
            }, [postId]);
            
            function loadAudioInfo() {
                setLoading(true);
                setError(null);
                
                wp.apiRequest({
                    path: '/wp/v2/posts/' + postId + '/audio-info',
                    method: 'GET'
                }).then(function(response) {
                    setAudioInfo(response);
                    setLoading(false);
                }).catch(function(error) {
                    setError(error.message || 'Ses dosyası bilgileri yüklenemedi');
                    setLoading(false);
                });
            }
            
            function generateAudio() {
                setLoading(true);
                setError(null);
                
                wp.apiRequest({
                    path: '/wp/v2/posts/' + postId + '/generate-audio',
                    method: 'POST'
                }).then(function(response) {
                    loadAudioInfo(); // Bilgileri yeniden yükle
                }).catch(function(error) {
                    setError(error.message || 'Ses dosyası oluşturulamadı');
                    setLoading(false);
                });
            }
            
            return createElement('div', { className: 'kkerem-tts-gutenberg-panel' },
                createElement(InspectorControls, null,
                    createElement(PanelBody, { title: __('TTS Ayarları', 'kkerem-tts') },
                        createElement('p', null, __('Post ID: ', 'kkerem-tts') + postId),
                        createElement('label', null, 
                            createElement('input', {
                                type: 'checkbox',
                                checked: showTitle,
                                onChange: (e) => setAttributes({ showTitle: e.target.checked })
                            }),
                            ' ' + __('Başlığı göster', 'kkerem-tts')
                        ),
                        createElement('br'),
                        createElement('label', null,
                            createElement('input', {
                                type: 'checkbox',
                                checked: showInfo,
                                onChange: (e) => setAttributes({ showInfo: e.target.checked })
                            }),
                            ' ' + __('Bilgileri göster', 'kkerem-tts')
                        ),
                        createElement('br'),
                        createElement('label', null,
                            createElement('input', {
                                type: 'checkbox',
                                checked: autoplay,
                                onChange: (e) => setAttributes({ autoplay: e.target.checked })
                            }),
                            ' ' + __('Otomatik oynat', 'kkerem-tts')
                        ),
                        createElement('br'),
                        createElement('label', null, __('Stil: ', 'kkerem-tts')),
                        createElement('select', {
                            value: style,
                            onChange: (e) => setAttributes({ style: e.target.value })
                        },
                            createElement('option', { value: 'default' }, __('Varsayılan', 'kkerem-tts')),
                            createElement('option', { value: 'minimal' }, __('Minimal', 'kkerem-tts')),
                            createElement('option', { value: 'compact' }, __('Kompakt', 'kkerem-tts'))
                        )
                    )
                ),
                
                createElement('h3', null, __('kKerem Text-to-Speech', 'kkerem-tts')),
                
                loading && createElement(Spinner),
                
                error && createElement(Notice, {
                    status: 'error',
                    isDismissible: false
                }, error),
                
                audioInfo && createElement('div', null,
                    createElement('audio', {
                        controls: true,
                        className: 'kkerem-tts-gutenberg-audio',
                        src: audioInfo.file_url
                    }),
                    showInfo && createElement('div', { className: 'kkerem-tts-gutenberg-info' },
                        createElement('span', null, __('Dosya Boyutu: ', 'kkerem-tts') + audioInfo.file_size_formatted),
                        createElement('span', null, __('Oluşturulma: ', 'kkerem-tts') + audioInfo.created_date)
                    )
                ),
                
                !audioInfo && !loading && createElement('div', null,
                    createElement('p', null, __('Bu post için ses dosyası bulunmuyor.', 'kkerem-tts')),
                    createElement(Button, {
                        isPrimary: true,
                        onClick: generateAudio
                    }, __('Ses Dosyası Oluştur', 'kkerem-tts'))
                )
            );
        },
        
        save: function() {
            return null; // Server-side rendering
        }
    });
})();

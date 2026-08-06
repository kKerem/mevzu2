/* Frontend JavaScript */
jQuery(document).ready(function($) {
    // Audio element'ler için temel event listener'lar
    $('.kkerem-tts-player audio').each(function() {
        var audio = $(this)[0];
        
        // Audio yüklendiğinde
        audio.addEventListener('loadedmetadata', function() {
            console.log('Audio metadata loaded');
        });
        
        // Audio çalmaya başladığında
        audio.addEventListener('play', function() {
            console.log('Audio started playing');
        });
        
        // Audio durduğunda
        audio.addEventListener('pause', function() {
            console.log('Audio paused');
        });
        
        // Audio bittiğinde
        audio.addEventListener('ended', function() {
            console.log('Audio ended');
        });
        
        // Hata durumunda
        audio.addEventListener('error', function(e) {
            console.error('Audio error:', e);
        });
    });
});
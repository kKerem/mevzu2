/* Admin JavaScript */
jQuery(document).ready(function($) {
    // Range slider değerlerini güncelle
    $('input[type="range"]').on('input', function() {
        var value = $(this).val();
        var targetId = $(this).attr('name').replace('kkerem_tts_', '') + '-value';
        $('#' + targetId).text(value);
    });
    
    // Ses seçimine göre dil kodunu otomatik güncelle
    $('select[name="kkerem_tts_voice_name"]').on('change', function() {
        var selectedVoice = $(this).val();
        var languageCode = selectedVoice.split('-').slice(0, 2).join('-');
        $('input[name="kkerem_tts_language_code"]').val(languageCode);
    });
    
    // Test textarea'sının değerini takip et
    $('#test-text').on('input change keyup', function() {
        console.log('Textarea değeri değişti:', $(this).val());
    });
    
    // Test TTS
    $('#test-tts').on('click', function() {
        var button = $(this);
        
        // Textarea değerini güncel olarak al - birden fazla yöntemle
        var testText = $('#test-text').val();
        if (!testText) {
            testText = document.getElementById('test-text').value;
        }
        testText = testText.trim();
        
        if (!testText) {
            alert('Lütfen test metni girin.');
            return;
        }
        
        // Debug için konsola yazdır
        console.log('Test metni:', testText);
        console.log('Textarea element:', $('#test-text')[0]);
        
        button.prop('disabled', true).text('Oluşturuluyor...');
        
        $.ajax({
            url: mevzuYZ.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mevzu_yz_test_synthesis',
                text: testText,
                nonce: mevzuYZ.nonce
            },
            beforeSend: function() {
                console.log('AJAX isteği gönderiliyor:', testText);
            },
            success: function(response) {
                if (response.success && response.data) {
                    var audioHtml = '<audio controls style="width: 100%; margin-top: 10px;">';
                    audioHtml += '<source src="' + response.data.file_url + '" type="audio/mpeg">';
                    audioHtml += 'Tarayıcınız ses dosyasını desteklemiyor.';
                    audioHtml += '</audio>';
                    
                    $('#test-audio-container').html(audioHtml);
                } else {
                    var errorMsg = response.data || 'Bilinmeyen hata oluştu';
                    alert('Mevzu² AI ses oluşturulamadı:\n\n' + errorMsg + '\n\nLütfen lisansınızı, günlük kotanızı ve seçili ses modelini kontrol edin.');
                }
            },
            error: function() {
                alert('AJAX hatası oluştu');
            },
            complete: function() {
                button.prop('disabled', false).text('Ses Oluştur ve Dinle');
            }
        });
    });
    
    // Ayarları kaydetme uyarısı kaldırıldı (JS tarafında zorunlu alan kontrolüne gerek yok)
});

wp.domReady(() => {
    const { dispatch } = wp.data;
    
    // Gutenberg editörüne özel bir notice ekle
    dispatch('core/notices').createNotice(
        'info', // Notice türü: 'info', 'success', 'warning', 'error'
        'Yazdığınız haber kayan haberler bölümüne 2dk sonra eklenecektir.', // Notice mesajı
        {
            isDismissible: true, // Kullanıcı tarafından kapatılabilir mi
            type: 'default', // Notice türü: 'default', 'snackbar'
            id: 'custom-editor-notice', // Notice'in benzersiz ID'si (isteğe bağlı)
        }
    );
});

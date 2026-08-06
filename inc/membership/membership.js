// Membership and Notification AJAX
jQuery(document).ready(function($) {

    // Toggle Category Subscription
    $(document).on('click', '.mevzu-follow-category', function(e) {
        e.preventDefault();
        var button = $(this);
        var catId = button.data('cat-id');
        var originalHtml = button.html();
        
        button.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
        
        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_toggle_category_subscription',
                nonce: mevzu_membership.nonce,
                category_id: catId
            },
            success: function(response) {
                if (response.success) {
                    if (button.attr('data-bs-toggle') === 'tooltip') {
                        // Yeni tip dairesel + / - stil butonu (Tooltip ile çalışan)
                        if (response.data.action === 'subscribed') {
                            button.html('<i class="ri-subtract-line fz-12"></i>');
                            button.attr('data-bs-title', '<small>Takipten Çıkar</small>');
                        } else {
                            button.html('<i class="ri-add-line fz-12"></i>');
                            button.attr('data-bs-title', '<small>Kategoriyi takip et</small>');
                        }
                        
                        // Tooltip varsa güncelle ve göster/gizle
                        if (typeof mevzu2 !== 'undefined' && mevzu2.Tooltip) {
                            var tooltipObj = mevzu2.Tooltip.getInstance(button[0]);
                            if (tooltipObj) {
                                button.attr('data-bs-original-title', button.attr('data-bs-title'));
                                tooltipObj.setContent({ '.tooltip-inner': button.attr('data-bs-title') });
                            }
                        }
                    } else {
                        // Eski tip metinli buton (Kimi Takip Etmeli / İlgini Çekebilir v.s)
                        if (response.data.action === 'subscribed') {
                            button.html('<i class="ri-check-line"></i> <span class="text ps-1 small">Takipten Çık</span>');
                        } else {
                            button.html('<i class="ri-add-line"></i> <span class="text ps-1 small">Takip Et</span>');
                        }
                    }
                    button.prop('disabled', false);
                } else {
                    button.html(originalHtml).prop('disabled', false);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            },
            error: function() {
                button.html(originalHtml).prop('disabled', false);
            }
        });
    });

    // Toggle Tag Subscription
    $(document).on('click', '.mevzu-follow-tag', function(e) {
        e.preventDefault();
        var button = $(this);
        var tagId = button.data('tag-id');
        var originalHtml = button.html();
        
        button.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
        
        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_toggle_tag_subscription',
                nonce: mevzu_membership.nonce,
                tag_id: tagId
            },
            success: function(response) {
                if (response.success) {
                    if (button.attr('data-bs-toggle') === 'tooltip') {
                        // Yeni tip dairesel + / - stil butonu (Tooltip ile çalışan)
                        if (response.data.action === 'subscribed') {
                            button.html('<i class="ri-subtract-line fz-12"></i>');
                            button.attr('data-bs-title', '<small>Takipten Çıkar</small>');
                        } else {
                            button.html('<i class="ri-add-line fz-12"></i>');
                            button.attr('data-bs-title', '<small>Etiketi takip et</small>');
                        }
                        
                        // Tooltip varsa güncelle ve göster/gizle
                        if (typeof mevzu2 !== 'undefined' && mevzu2.Tooltip) {
                            var tooltipObj = mevzu2.Tooltip.getInstance(button[0]);
                            if (tooltipObj) {
                                button.attr('data-bs-original-title', button.attr('data-bs-title'));
                                tooltipObj.setContent({ '.tooltip-inner': button.attr('data-bs-title') });
                            }
                        }
                    } else {
                        // Eski tip metinli buton (Kimi Takip Etmeli / İlgini Çekebilir v.s)
                        if (response.data.action === 'subscribed') {
                            button.html('<i class="ri-check-line"></i> <span class="text ps-1 small">Takipten Çık</span>');
                        } else {
                            button.html('<i class="ri-add-line"></i> <span class="text ps-1 small">Etiketi Takip Et</span>');
                        }
                    }
                    button.prop('disabled', false);
                } else {
                    button.html(originalHtml).prop('disabled', false);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            },
            error: function() {
                button.html(originalHtml).prop('disabled', false);
            }
        });
    });

    // Toggle Author Subscription
    $(document).on('click', '.mevzu-follow-author', function(e) {
        e.preventDefault();
        var button = $(this);
        var authorId = button.data('author-id');
        var originalHtml = button.html();
        
        button.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> İşleniyor...');
        
        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_toggle_author_subscription',
                nonce: mevzu_membership.nonce,
                author_id: authorId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.action === 'subscribed') {
                        button.html('<i class="ri-user-unfollow-line fz-12"></i> <span class="text">Takipten Çık</span>');
                    } else {
                        button.html('<i class="ri-user-follow-line fz-12"></i> <span class="text">Takip Et</span>');
                    }
                    button.prop('disabled', false);
                } else {
                    button.html(originalHtml).prop('disabled', false);
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            },
            error: function() {
                button.html(originalHtml).prop('disabled', false);
            }
        });
    });

    // Toggle Post Bookmark
    $(document).on('click', '.mevzu-toggle-bookmark', function(e) {
        e.preventDefault();
        var button = $(this);
        var postId = button.data('post-id');
        var originalHtml = button.html();
        
        button.css('opacity', '0.5');

        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_toggle_bookmark',
                nonce: mevzu_membership.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.action === 'bookmarked') {
                        button.addClass('text-primary');
                        button.html('<i class="ri-bookmark-2-fill fz-24"></i>');
                        button.attr('data-bs-title', '<small>Yer İşaretini Kaldır</small>');
                    } else {
                        button.removeClass('text-primary');
                        button.html('<i class="ri-bookmark-line fz-24"></i>');
                        button.attr('data-bs-title', '<small>Yer İşaretlerine Ekle</small>');
                    }

                    // Tooltip varsa güncelle ve göster/gizle
                    if (typeof mevzu2 !== 'undefined' && mevzu2.Tooltip) {
                        var tooltipObj = mevzu2.Tooltip.getInstance(button[0]);
                        if (tooltipObj) {
                            button.attr('data-bs-original-title', button.attr('data-bs-title'));
                            tooltipObj.setContent({ '.tooltip-inner': button.attr('data-bs-title') });
                        }
                    }
                }
                button.css('opacity', '1');
            },
            error: function() {
                button.css('opacity', '1');
            }
        });
    });

    // Mark Notification as read on hover
    $(document).on('mouseenter', '.mevzu-notification-item.unread', function() {
        var item = $(this);
        var notifId = item.data('notif-id');
        
        if (!item.hasClass('processing')) {
            item.addClass('processing');
            
            $.ajax({
                url: mevzu_membership.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mevzu_mark_notification_read',
                    nonce: mevzu_membership.nonce,
                    notification_id: notifId
                },
                success: function(response) {
                    if (response.success) {
                        item.removeClass('unread processing unread bg-info bg-opacity-10');
                        
                        // Decrement counter
                        var badge = $('.mevzu-notif-badge');
                        var count = parseInt(badge.text());
                        if (count > 0) {
                            count--;
                            badge.text(count);
                            if (count === 0) badge.hide();
                        }
                    } else {
                        item.removeClass('processing');
                    }
                },
                error: function() {
                    item.removeClass('processing');
                }
            });
        }
    });

    // Delete Notification
    $(document).on('click', '.mevzu-delete-notification', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Avoid triggering the parent link
        
        var button = $(this);
        var notifId = button.data('notif-id');
        var item = button.closest('.mevzu-notification-item');
        
        button.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');

        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_delete_notification',
                nonce: mevzu_membership.nonce,
                notification_id: notifId
            },
            success: function(response) {
                if (response.success) {
                    item.fadeOut(300, function() {
                        $(this).remove();
                        // If no notifications left
                        if ($('.mevzu-notification-item').length === 0) {
                            $('.list-group').html('<div class="p-3 py-5 small text-muted text-center">Henüz hiç bildiriminiz bulunmuyor.</div>');
                        }
                    });
                } else {
                    button.prop('disabled', false).html('<i class="ri-delete-bin-line"></i>');
                }
            },
            error: function() {
                button.prop('disabled', false).html('<i class="ri-delete-bin-line"></i>');
            }
        });
    });

    // Toggle Like (Post / Comment)
    $(document).on('click', '.mevzu-toggle-like', function(e) {
        e.preventDefault();
        var button = $(this);
        var itemId = button.data('item-id');
        var itemType = button.data('type'); // 'post' or 'comment'
        var originalHtml = button.html();
        
        button.css('opacity', '0.5');

        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_toggle_like',
                nonce: mevzu_membership.nonce,
                item_id: itemId,
                type: itemType
            },
            success: function(response) {
                if (response.success) {
                    var count = response.data.count;
                    if (response.data.action === 'liked') {
                        button.addClass('bg-primary text-white border-primary').removeClass('text-body');
                        button.html('<i class="ri-thumb-up-fill fz-16 me-1"></i> <span class="count">' + count + '</span>');
                    } else {
                        button.removeClass('bg-primary text-white border-primary').addClass('text-body');
                        button.html('<i class="ri-thumb-up-line fz-16 me-1"></i> <span class="count">' + count + '</span>');
                    }
                }
                button.css('opacity', '1');
            },
            error: function() {
                button.css('opacity', '1');
            }
        });
    });

    // Mark All Notifications as Read
    $(document).on('click', '#mevzu-mark-all-read', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i>İşleniyor...');

        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_mark_all_notifications_read',
                nonce: mevzu_membership.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.mevzu-notification-item').removeClass('unread bg-info bg-opacity-10 bg-light');
                    $('.mevzu-notification-item .badge.bg-danger').remove();
                    $('.mevzu-notif-badge').text('0').addClass('d-none');
                    button.html('<i class="ri-check-double-line me-1"></i>Tümü Okundu').prop('disabled', false);
                    setTimeout(function() {
                        button.html('<i class="ri-check-double-line me-1"></i>Tümünü Oku');
                    }, 2000);
                } else {
                    button.html('<i class="ri-check-double-line me-1"></i>Tümünü Oku').prop('disabled', false);
                }
            },
            error: function() {
                button.html('<i class="ri-check-double-line me-1"></i>Tümünü Oku').prop('disabled', false);
            }
        });
    });

    // Delete All Notifications
    $(document).on('click', '#mevzu-delete-all-notifications', function(e) {
        e.preventDefault();
        if (!confirm('Tüm bildirimleri silmek istediğinize emin misiniz?')) return;

        var button = $(this);
        button.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i>Siliniyor...');

        $.ajax({
            url: mevzu_membership.ajaxurl,
            type: 'POST',
            data: {
                action: 'mevzu_delete_all_notifications',
                nonce: mevzu_membership.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.mevzu-notification-item').fadeOut(300, function() {
                        $(this).remove();
                    });
                    setTimeout(function() {
                        $('.list-group').html('<div class="p-3 py-5 small text-muted text-center">Henüz hiç bildiriminiz bulunmuyor.</div>');
                    }, 350);
                    $('.mevzu-notif-badge').text('0').addClass('d-none');
                    button.html('<i class="ri-delete-bin-line me-1"></i>Tümünü Sil').prop('disabled', false);
                } else {
                    button.html('<i class="ri-delete-bin-line me-1"></i>Tümünü Sil').prop('disabled', false);
                }
            },
            error: function() {
                button.html('<i class="ri-delete-bin-line me-1"></i>Tümünü Sil').prop('disabled', false);
            }
        });
    });

});

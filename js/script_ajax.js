(function ($) {
  let posts = [];

  function updateFixedTitle() {
    const fixedTitle = document.getElementById('fixed-post-title');
    if (!fixedTitle) return;

    for (let post of posts) {
      const rect = post.getBoundingClientRect();
      if (rect.top <= 100 && rect.bottom > 100) {
        const title = post.getAttribute('data-title');
        if (fixedTitle.textContent !== title) {
          fixedTitle.textContent = title;
        }
        break;
      }
    }
  }

  function refreshPostListAndUpdateTitle() {
    posts = document.querySelectorAll('.icerik');
    updateFixedTitle();
  }

  window.addEventListener('scroll', updateFixedTitle);
  window.addEventListener('resize', updateFixedTitle);
  document.addEventListener('DOMContentLoaded', refreshPostListAndUpdateTitle);

  // 🔄 AJAX yükleyici sınıfı
  class LoadMore {
    constructor() {
      this.ajaxUrl = siteConfig?.ajaxUrl ?? '';
      this.ajaxNonce = siteConfig?.ajax_nonce ?? '';
      this.loadMoreBtn = $('#load-more');

      this.options = {
        root: null,
        rootMargin: '0px',
        threshold: 1.0,
      };

      this.init();
    }

    init() {
      if (!this.loadMoreBtn.length) {
        return;
      }

      this.totalPagesCount = $('#post-pagination').data('max-pages');

      let observer = new IntersectionObserver(
        (entries) => this.intersectionObserverCallback(entries),
        this.options
      );
      observer.observe(this.loadMoreBtn[0]);
    }

    intersectionObserverCallback(entries) {
      entries.forEach((entry) => {
        if (entry?.isIntersecting) {
          this.handleLoadMorePosts();
        }
      });
    }

    handleLoadMorePosts() {
      const page = this.loadMoreBtn.data('page');
      if (!page) {
        return null;
      }

      const nextPage = parseInt(page) + 1;
      const currentPostId = $('#current-post').data('id');
      const isArchivePage = this.loadMoreBtn.data('archive') === '1';

      $.ajax({
        url: this.ajaxUrl,
        type: 'post',
        data: {
          page: page,
          action: 'load_more',
          ajax_nonce: this.ajaxNonce,
          archive: isArchivePage,
          current_post_id: currentPostId,
        },
        success: (response) => {
          this.loadMoreBtn.data('page', nextPage);
          $('#load-more-content').append(response);
          this.removeLoadMoreIfOnLastPage(nextPage);

          // ✅ Yeni postlar geldikten sonra başlıkları güncelle
          refreshPostListAndUpdateTitle();
        },
        error: (response) => {
          console.log(response);
        },
      });
    }

    removeLoadMoreIfOnLastPage(nextPage) {
      if (nextPage > this.totalPagesCount) {
        this.loadMoreBtn.remove();
      }
    }
  }

  new LoadMore();
})(jQuery);

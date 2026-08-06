function kabulEt() {
    document.cookie = "ceresKabul=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/", document.getElementById("ceresBildirimi").style.display = "none"
  }
  
  function reddet() {
    document.cookie = "ceresKabul=false; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/", document.getElementById("ceresBildirimi").style.display = "none"
  }
  
  function kontrolEt() {
    window.addEventListener("load", kontrolEt);
    var e = document.cookie.indexOf("ceresKabul=");
    if (-1 == e) document.getElementById("ceresBildirimi").style.display = "block";
    else {
        document.cookie.substring(e + 11);
        document.getElementById("ceresBildirimi").style.display = "none"
    }
  }
  
  function darken_screen(e) {
    1 == e ? document.querySelector(".screen-darken").classList.add("active") : 0 == e && document.querySelector(".screen-darken").classList.remove("active")
  }
  
  function close_offcanvas() {
    darken_screen(!1), document.querySelector(".mobile-offcanvas.show").classList.remove("show"), document.body.classList.remove("offcanvas-active")
  }
  
  function show_offcanvas(e) {
    darken_screen(!0), document.getElementById(e).classList.add("show"), document.body.classList.add("offcanvas-active")
  }
  
  jQuery(document).ready((function(e) {
        e(".img-arkaplan").hover((function() {
            e(".img-arkaplan.active").removeClass("active"), e(this).addClass("active")
        }), (function() {}))
    })), window.addEventListener("load", kontrolEt), document.addEventListener("DOMContentLoaded", (function() {
        document.querySelectorAll("[data-trigger]").forEach((function(e) {
            let t = e.getAttribute("data-trigger");
            e.addEventListener("click", (function(e) {
                e.preventDefault(), show_offcanvas(t)
            }))
        })), document.querySelectorAll(".btn-close").forEach((function(e) {
            e.addEventListener("click", (function(e) {
                e.preventDefault(), close_offcanvas()
            }))
        })), document.querySelector(".screen-darken").addEventListener("click", (function(e) {
            close_offcanvas()
        }))
    })),
    function() {
      const toggleButtons = document.querySelectorAll(".dark-mode-toggle, .form-check-input-darkmode-label");
  
      const themeToggleContent = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="moon-icon">
        <path fill="currentColor" d="M11.3805 2.01929C9.91573 3.38768 9 5.3369 9 7.5C9 11.6421 12.3579 15 16.5 15C18.6631 15 20.6123 14.0843 21.9807 12.6195C21.6613 17.8537 17.3149 22 12 22C6.47715 22 2 17.5228 2 12C2 6.68514 6.14629 2.33871 11.3805 2.01929Z"></path>
      </svg>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sun-icon">
        <path fill="currentColor" d="M12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 15.3137 15.3137 18 12 18ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16ZM11 1H13V4H11V1ZM11 20H13V23H11V20ZM3.51472 4.92893L4.92893 3.51472L7.05025 5.63604L5.63604 7.05025L3.51472 4.92893ZM16.9497 18.364L18.364 16.9497L20.4853 19.0711L19.0711 20.4853L16.9497 18.364ZM19.0711 3.51472L20.4853 4.92893L18.364 7.05025L16.9497 5.63604L19.0711 3.51472ZM5.63604 16.9497L7.05025 18.364L4.92893 20.4853L3.51472 19.0711L5.63604 16.9497ZM23 11V13H20V11H23ZM4 11V13H1V11H4Z"></path>
      </svg>
      `;
      
      toggleButtons.forEach(btn => {
          if (btn.tagName === 'LABEL') {
              const inputId = btn.getAttribute("for");
              btn.innerHTML = (inputId ? `<input type="checkbox" class="form-check-input-darkmode d-none" id="${inputId}">` : '') + themeToggleContent;
          } else {
              btn.innerHTML = themeToggleContent;
          }
      });

      function updateTheme() {
          const isDark = document.body.classList.contains("dark");
          localStorage.setItem("lightSwitch", isDark ? "dark" : "light");
      }
      
      toggleButtons.forEach(btn => {
          btn.addEventListener("click", (e) => {
              if (btn.tagName === 'LABEL') {
                  // Prevent label from triggering the underlying input click causing "double toggles"
                  e.preventDefault(); 
              }
              document.body.classList.toggle("dark");
          
              document.querySelectorAll("table").forEach(table => {
                  table.classList.toggle("table-dark", document.body.classList.contains("dark"));
              });
          
              updateTheme();
          });
      });

      if (localStorage.getItem("lightSwitch") === "dark") {
          document.body.classList.add("dark");
          document.querySelectorAll("table").forEach(t => t.classList.add("table-dark"));
      }
      
      updateTheme();
    }(),
     $(".sizing-buttons .sizing").click((function() {
    $(this).hasClass("fontplus") ? (currentSize = parseInt($(".content").css("font-size")) + 1, currentSize <= 22 && $(".content").css("font-size", currentSize)) : $(this).hasClass("fontminus") && (currentSize = parseInt($(".content").css("font-size")) - 1, currentSize >= 11 && $(".content").css("font-size", currentSize))
  }));
  
  // Swiper slider initializations with null checks - wrapped in IIFE to avoid conflicts
  (function() {
      // Hava Durumu Swiper
      const havaDurumuEl = document.querySelector(".HavaDurumu");
      if (havaDurumuEl) {
          new Swiper(".HavaDurumu", {
            breakpoints: {
                640: { slidesPerView: 2 },
                768: { slidesPerView: 4 },
                1024: { slidesPerView: 4 }
            },
            spaceBetween: 20,
            grabCursor: true,
            autoplay: {
                delay: 10000,
                disableOnInteraction: true,
            },
          });
      }

      // Slider 2
      const swiperSicakGundemEl = document.querySelector('#swiper-sicak-gundem');
      if (swiperSicakGundemEl) {
          new Swiper('#swiper-sicak-gundem', {
            loop: true,
            lazy: { loadPrevNext: true },
            spaceBetween: 20,
            slidesPerView: 1,
            speed: 300,
            autoplay: {
                delay: 8000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: {
                el: '#swiper-sicak-gundem .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                0: { slidesPerView: 1 },
                600: { slidesPerView: 3 },
                1000: { slidesPerView: 4, loop: false }
            }
          });
      }

      // Yazarlar Swiper (Slider 2 ile aynÄ± ayarlar ama farklÄ± ID)
      const yazarlarEl = document.querySelector('#swiper-yazarlar');
      if (yazarlarEl) {
          new Swiper('#swiper-yazarlar', {
            loop: false,
            lazy: { loadPrevNext: true },
            spaceBetween: 15,
            slidesPerView: 1,
            speed: 400,
            autoplay: {
                delay: 9000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            breakpoints: {
                0: { slidesPerView: 1 },
                600: { slidesPerView: 2 },
                1000: { slidesPerView: 4 }
            }
          });
      }

      // Slider 3
      const slider3El = document.querySelector('#swiper-slider-3');
      if (slider3El) {
          new Swiper('#swiper-slider-3', {
            loop: true,
            lazy: { loadPrevNext: true },
            spaceBetween: 10,
            slidesPerView: 1,
            speed: 300,
            autoplay: {
                delay: 8000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: {
                el: '#swiper-slider-3 .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                0: { slidesPerView: 1 },
                600: { slidesPerView: 3 },
                1000: { slidesPerView: 3, loop: false }
            }
          });
      }

      // Default Swiper
      const swiperDefaultEl = document.querySelector(".swiper-default");
      if (swiperDefaultEl) {
          new Swiper(".swiper-default", {
            slidesPerView: "auto",
            spaceBetween: 20,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            loop: true,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            pagination: {
                el: '.swiper-pagination-swiper-manset',
                clickable: true,
                renderBullet: function(index, className) {
                    return '<span class="' + className + '">' + (index + 1) + "</span>";
                },
            },
            speed: 1000,
            rewind: true,
          });
      }

      // Home Swiper
      const swiperHomeEl = document.querySelector('.slider-1');
      if (swiperHomeEl) {
          new Swiper('.slider-1', {
            dynamicBullets: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                renderBullet: function(index, className) {
                    return '<span class="' + className + '">' + (index + 1) + "</span>";
                },
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            }
          });
          $('.slider-1 .swiper-pagination-bullet').hover(function() {
              $(this).trigger("click");
          });
      }

      // Arşiv: .tema-kategori ilk 15 haber (anasayfa ana manşet / slider-1 ayarlarıyla uyumlu)
      const swiperArchiveKategoriEl = document.querySelector('#swiper-archive-kategori-manset');
      if (swiperArchiveKategoriEl) {
          var archiveMansetSlideCount = swiperArchiveKategoriEl.querySelectorAll('.swiper-slide').length;
          new Swiper('#swiper-archive-kategori-manset', {
              dynamicBullets: true,
              autoplay: archiveMansetSlideCount > 1 ? {
                  delay: 5000,
                  disableOnInteraction: false,
              } : false,
              loop: archiveMansetSlideCount > 1,
              watchOverflow: true,
              pagination: {
                  el: '#swiper-archive-kategori-manset .swiper-pagination',
                  clickable: true,
                  renderBullet: function(index, className) {
                      return '<span class="' + className + '">' + (index + 1) + "</span>";
                  },
              },
              navigation: {
                  nextEl: '#swiper-archive-kategori-manset .swiper-button-next',
                  prevEl: '#swiper-archive-kategori-manset .swiper-button-prev',
              }
          });
          $('#swiper-archive-kategori-manset .swiper-pagination-bullet').hover(function() {
              $(this).trigger("click");
          });
      }

      // Ãœst Reklam Swiper
      const ustreklamEl = document.querySelector('#swiper-ustreklam');
      if (ustreklamEl) {
          new Swiper('#swiper-ustreklam', {
              direction: "vertical",
              autoHeight: true,
              autoplay: {
                  delay: 6000,
                  disableOnInteraction: false,
              },
              loop: true,
              lazy: { loadPrevNext: true },
              pagination: {
                  el: '#swiper-ustreklam .swiper-pagination',
                clickable: true,
                renderBullet: function (index, className) {
                  return '<span class="' + className + '">' + (index + 1) + "</span>";
                },
              },
          });
      }

      // DÃ¶vizler Swiper
      const dovizlerEl = document.querySelector('#swiper-dovizler');
      if (dovizlerEl) {
          new Swiper('#swiper-dovizler', {
              direction: "vertical",
              autoHeight: true,
              autoplay: {
                  delay: 5000,
                  disableOnInteraction: false,
              },
              loop: true,
              lazy: { loadPrevNext: true },
              pagination: {
                  el: '#swiper-dovizler .swiper-pagination',
                clickable: true,
                renderBullet: function (index, className) {
                  return '<span class="' + className + '">' + (index + 1) + "</span>";
                },
              },
          });
      }
  })();

  // Side ads: start from container top, then stick below header
  (function() {
      const sideAds = Array.from(document.querySelectorAll('.kose-reklam.is-fixed'));
      if (!sideAds.length) return;

      const adsContainer = document.querySelector('.reklamlar');
      if (!adsContainer) return;

      const rootStyles = getComputedStyle(document.documentElement);
      const offcanvasSticky = parseFloat(rootStyles.getPropertyValue('--mevzu-offcanvas-sticky')) || 32;
      const isLoggedIn = document.body.classList.contains('logged-in');
      const headerOffset = isLoggedIn ? Math.max(106, offcanvasSticky + 64) : 63;
      let ticking = false;

      const updateSideAdsTop = function() {
          const containerTop = adsContainer.getBoundingClientRect().top;
          const nextTop = Math.max(headerOffset, containerTop);
          const topValue = nextTop + 'px';
          sideAds.forEach(function(ad) {
              ad.style.setProperty('--mevzu-side-fixed-top', topValue);
          });
          ticking = false;
      };

      const requestUpdate = function() {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(updateSideAdsTop);
      };

      window.addEventListener('scroll', requestUpdate, { passive: true });
      window.addEventListener('resize', requestUpdate);
      window.addEventListener('load', requestUpdate);
      document.addEventListener('DOMContentLoaded', requestUpdate);
      requestUpdate();
  })();

  // All scroll and button functionality - wrapped in IIFE to prevent conflicts
  (function() {
      // Scroll progress and navbar functionality
      window.addEventListener('scroll', function () {
          const scrollTop = window.scrollY || document.documentElement.scrollTop;
          const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
          const progress = (scrollTop / scrollHeight) * 100;
          
          const scrollProgress = document.getElementById('scrollProgress');
          if (scrollProgress) {
              scrollProgress.style.width = progress + "%";
          }
          
          const navbar = document.getElementById('stickyNavbar');
          if (navbar) {
              if (scrollTop > 300) {
                  navbar.classList.add('visible');
              } else {
                  navbar.classList.remove('visible');
              }
          }
      });

      // First scroll to top button - wrapped in DOMContentLoaded
      document.addEventListener("DOMContentLoaded", function () {
          const scrollBtn1 = document.getElementById("scrollToTopBtn");
          if (scrollBtn1) {
              window.addEventListener("scroll", function () {
                  scrollBtn1.style.display = window.scrollY > 150 ? "block" : "none";
              });

              scrollBtn1.addEventListener("click", function () {
                  window.scrollTo({
                      top: 0,
                      behavior: "smooth",
                  });
              });
          }

          // Second scroll to top button - moved inside DOMContentLoaded
          const scrollBtn2 = document.getElementById("scrollToTopBtn2");
          if (scrollBtn2) {
              window.addEventListener("scroll", function () {
                  if (window.scrollY > 150) {
                      scrollBtn2.style.display = "block";
                  } else {
                      scrollBtn2.style.display = "none";
                  }
              });
              scrollBtn2.addEventListener("click", function () {
                  window.scrollTo({
                      top: 0,
                      behavior: "smooth",
                  });
              });
          }
      });

  })();

// ── Üst Manşet Swiper ──
if (document.getElementById('swiper-ust-manset')) {
    const ustMansetEl = document.getElementById('swiper-ust-manset');
    const syncUstMansetHeight = function (swiper) {
        if (!swiper || !swiper.slides || typeof swiper.activeIndex === 'undefined') return;
        const activeSlide = swiper.slides[swiper.activeIndex];
        if (!activeSlide) return;

        const mediaEl = activeSlide.querySelector('img, .ust-manset-hazirlaniyor');
        if (!mediaEl) return;

        let targetHeight = mediaEl.getBoundingClientRect().height;

        if ((!targetHeight || targetHeight < 1) && mediaEl.tagName === 'IMG' && mediaEl.naturalWidth > 0) {
            const width = ustMansetEl.clientWidth || mediaEl.clientWidth;
            targetHeight = (width * mediaEl.naturalHeight) / mediaEl.naturalWidth;
        }

        if (targetHeight && targetHeight > 0) {
            ustMansetEl.style.height = Math.round(targetHeight) + 'px';
            swiper.update();
        }
    };

    const ustMansetSwiper = new Swiper('#swiper-ust-manset', {
        direction: 'vertical',
        autoHeight: false,
        autoplay: {
            delay: 511000,
            disableOnInteraction: false,
        },
        speed: 500,
        loop: true,
        pagination: {
            el: '#swiper-ust-manset .swiper-pagination',
            clickable: false,
            renderBullet: function (index, className) {
                return '<span class="' + className + '" data-index="' + index + '">' + (index + 1) + '</span>';
            },
        },
        on: {
            init: function (swiper) {
                swiper.el.querySelectorAll('.swiper-pagination-bullet').forEach(function(bullet) {
                    bullet.addEventListener('mouseenter', function() {
                        swiper.slideToLoop(parseInt(this.dataset.index));
                    });
                });
                requestAnimationFrame(function () {
                    syncUstMansetHeight(swiper);
                });
            },
            slideChangeTransitionEnd: function (swiper) {
                syncUstMansetHeight(swiper);
            },
        },
    });

    window.addEventListener('resize', function () {
        syncUstMansetHeight(ustMansetSwiper);
    });

    ustMansetEl.querySelectorAll('img').forEach(function (img) {
        if (!img.complete) {
            img.addEventListener('load', function () {
                syncUstMansetHeight(ustMansetSwiper);
            }, { once: true });
        }
    });
}

// ── Yan Manşet — sadece mobilde swiper ──
(function() {
    var el = document.getElementById('swiper-yan-manset');
    if (!el) return;
    var swInstance = null;

    function initOrDestroy() {
        if (window.innerWidth < 992) {
            if (!swInstance) {
                // Swiper class'larını dinamik ekle
                el.classList.add('swiper');
                var wrapper = document.createElement('div');
                wrapper.className = 'swiper-wrapper';
                while (el.firstChild) wrapper.appendChild(el.firstChild);
                el.appendChild(wrapper);
                Array.from(wrapper.children).forEach(function(s) {
                    s.classList.add('swiper-slide');
                });
                swInstance = new Swiper('#swiper-yan-manset', {
                    slidesPerView: 1,
                    spaceBetween: 10,
                    grabCursor: true,
                });
            }
        } else {
            if (swInstance) {
                swInstance.destroy(true, true);
                swInstance = null;
                // Swiper class'larını ve wrapper'ı temizle
                var wrapper = el.querySelector('.swiper-wrapper');
                if (wrapper) {
                    while (wrapper.firstChild) el.insertBefore(wrapper.firstChild, wrapper);
                    wrapper.remove();
                }
                el.classList.remove('swiper');
                Array.from(el.children).forEach(function(s) {
                    s.classList.remove('swiper-slide');
                });
            }
        }
    }

    initOrDestroy();
    window.addEventListener('resize', initOrDestroy);
})();

  </main>
  <!--Footer-->
  <span class="screen-darken"></span>

  <button class="yukariCik d-none d-md-block btn btn-outline-primary p-2 bg-white rounded-circle shadow-sm position-fixed end-0 bottom-0 m-4" id="scrollToTopBtn2" style="display: none;" aria-label="Sayfanın en üstüne çık">
    <svg class="text-link" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="transform: rotate(270deg);"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"></path><path fill="currentColor" d="M16.06 10.94a1.5 1.5 0 0 1 0 2.12l-5.656 5.658a1.5 1.5 0 1 1-2.121-2.122L12.879 12L8.283 7.404a1.5 1.5 0 0 1 2.12-2.122l5.658 5.657Z"></path></g></svg>
  </button>

  <footer class="mt-3">
    <div class="container pt-3 pt-lg-5">
      <div class="row row-cols-2 row-cols-md-4 row-cols-lg-4 gx-lg-5">
          <?php for ($i = 1; $i <= 4; $i++) : 
              $menu_id = get_option('options_footer_menu_' . $i);
              if ($menu_id) :
                  // Menü adını doğrudan WordPress menü nesnesinden al
                  $menu_obj = wp_get_nav_menu_object($menu_id);
                  $title = $menu_obj ? $menu_obj->name : '';
          ?>
          <div class="col mb-4 mb-lg-0 footer-menu-col">
              <h2 class="fw-bold mb-3 h6"><?php echo esc_html($title); ?></h2>
              <?php wp_nav_menu(array(
                  'menu' => $menu_id,
                  'container' => false,
                  'menu_class' => 'list-unstyled footer-menu small',
                  'fallback_cb' => '__return_false',
              )); ?>
          </div>
          <?php endif; endfor; ?>
      </div>
      <div class="row py-3 mt-3 fz-12 footer-alt text-center align-items-center">
        <div class="col col-md-3">
          <div class="row align-items-center">
            <div class="col">
              <a href="https://kkerem.com" target="_blank">
                <div class="row align-items-center">
                  <div class="col-auto ps-0">
                    <img src="<?php bloginfo('template_url'); ?>/img/kkerem_beyaz.png" class="opacity-25" alt="Kerem ER" loading="lazy">
                  </div>
                  <div class="col-auto text-start ps-0">
                    <div class="small opacity-50">Tasarım & Yazılım</div>
                    Kerem<b>ER</b>
                  </div>
                </div>
              </a>
            </div>
            <div class="col-auto text-end d-md-none pe-0">
              <a href="https://kkerem.com/portfolyo/mevzu2/" target="_blank">
                <?php
                $tema = wp_get_theme();
                $isim = $tema->get('Name');
                $versiyon = $tema->get('Version');
            
                echo '<div class="small opacity-50">Tema</div>';
                echo sprintf(
                    '<div class="text-center">%s [v%s]</div>',
                    esc_html($isim),
                    esc_html($versiyon)              );
                ?>
              </a>
            </div>
          </div>
        </div>
        <div class="col-12 col-md p-0 text-md-center my-3 my-md-0">Copyright <small>©</small> <?php bloginfo('title') ?> <?php echo get_option('options_footer_unvan'); ?> - <?php echo date("Y", time()) ?>. Tüm Hakları Saklıdır.</div>
        <div class="col col-md-3 text-md-end">
          <ul class="social">
            <li>
            <?php if(get_option('options_facebook')) : ?>
              <a href="<?php echo get_option('options_facebook'); ?>" title="Facebook" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="20" viewBox="0 0 512 512"><path fill="currentColor" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48c27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256"/></svg>
              </a>
            <?php endif; ?>
            </li>
            <li>
            <?php if(get_option('options_twitter')) : ?>
              <a href="<?php echo get_option('options_twitter'); ?>" title="Twitter" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="20" viewBox="0 0 16 16"><path fill="currentColor" d="M9.294 6.928L14.357 1h-1.2L8.762 6.147L5.25 1H1.2l5.31 7.784L1.2 15h1.2l4.642-5.436L10.751 15h4.05zM7.651 8.852l-.538-.775L2.832 1.91h1.843l3.454 4.977l.538.775l4.491 6.47h-1.843z"/></svg>
              </a>
            <?php endif; ?>
            </li>
            <li>
            <?php if(get_option('options_instagram')) : ?>
              <a href="<?php echo get_option('options_instagram'); ?>" title="Instagram" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="27.5" height="20" viewBox="0 0 448 512"><path fill="currentColor" d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9S287.7 141 224.1 141m0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7s74.7 33.5 74.7 74.7s-33.6 74.7-74.7 74.7m146.4-194.3c0 14.9-12 26.8-26.8 26.8c-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8s26.8 12 26.8 26.8m76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9c-26.2-26.2-58-34.4-93.9-36.2c-37-2.1-147.9-2.1-184.9 0c-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9c1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0c35.9-1.7 67.7-9.9 93.9-36.2c26.2-26.2 34.4-58 36.2-93.9c2.1-37 2.1-147.8 0-184.8M398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6c-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6c-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6c29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6c11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1"/></svg>
              </a>
            <?php endif; ?>
            </li>
            <li>
            <?php if(get_option('options_youtube')) : ?>
              <a href="<?php echo get_option('options_youtube'); ?>" title="Youtube" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="32.5" height="20" viewBox="0 0 576 512"><path fill="currentColor" d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597c-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821c11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305m-317.51 213.508V175.185l142.739 81.205z"/></svg>
              </a>
            <?php endif; ?>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </footer>
  <!--Footer-->

  <?php
  // =====================================================================
  //  YENİ POPUP SİSTEMİ — Mevzu Otomasyon > Popup sayfasından yönetilir
  // =====================================================================
  if (class_exists('Mevzu_Popup_Page')) {
      $popup = Mevzu_Popup_Page::get_active_popup();

      if ($popup && !empty($popup['gorsel_url'])) {
          $img_url    = esc_url($popup['gorsel_url']);
          $gosterim   = $popup['gosterim']; // 'once' veya 'always'
          $cookie_key = 'mevzu_popup_' . esc_js($popup['key']);
          ?>
          <div class="modal fade" id="mevzuPopupModal" tabindex="-1" aria-labelledby="mevzuPopupLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content bg-transparent border-0 shadow-lg">
                      <div class="modal-body p-0 text-center position-relative">
                          <button type="button" class="btn-close position-absolute top-0 end-0 m-3 p-2 border border-2 small-2 border-secondary" data-bs-dismiss="modal" aria-label="Close"></button>
                          <img src="<?php echo $img_url; ?>" alt="Duyuru" class="img-fluid rounded">
                      </div>
                  </div>
              </div>
          </div>
          <script>
          (function() {
              var cookieName = <?php echo json_encode($cookie_key); ?>;
              var gosterim   = <?php echo json_encode($gosterim); ?>;

              function setCookie(name, days) {
                  var d = new Date();
                  d.setTime(d.getTime() + days * 86400000);
                  document.cookie = name + '=1; expires=' + d.toUTCString() + '; path=/';
              }
              function getCookie(name) {
                  var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
                  return v ? v[2] : null;
              }
              function showPopup() {
                  var el = document.getElementById('mevzuPopupModal');
                  if (!el) return;
                  var modal = new mevzu2.Modal(el);
                  modal.show();
              }

              document.addEventListener('DOMContentLoaded', function() {
                  if (gosterim === 'always') {
                      showPopup();
                  } else {
                      // 'once' — günde 1 kez
                      if (!getCookie(cookieName)) {
                          showPopup();
                          setCookie(cookieName, 1);
                      }
                  }
              });
          })();
          </script>
          <?php
      }
  }
  ?>

  <div class="position-fixed bottom-0 w-100" style="z-index:99999;display:none" id="ceresBildirimi">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-7">
          <div class="bg-dark text-white p-3 small rounded-top">
            <div class="row align-items-center">
              <div class="col-12 col-lg">
                Bu web sitesinde en iyi deneyimi yaşamanızı sağlamak için çerezler kullanılmaktadır. Detaylar için <a href="<?php bloginfo('url')?>/gizlilik-politikasi" class="text-white fw-semibold"><u>Gizlilik Politikamız</u></a>ı inceleyebilirsiniz.
              </div>
              <div class="col-lg-auto ms-lg-auto pt-3 pt-lg-0">
                <button class="btn btn-primary fw-semibold d-block w-100 px-4"
                  onclick="kabulEt()">Kabul Et</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



  <form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div class="modal fade small" id="aramaYap" data-bs-keyboard="false" tabindex="-1" aria-labelledby="aramaYapLabel" aria-hidden="true">
      <div class="modal-dialog mt-lg-5 modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header pb-0 border-bottom-0">
            <span class="text-body fw-normal"><?php echo esc_attr_x( 'Aramak istediğiniz kelimeyi yazın...', 'placeholder' ); ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <div class="input-group">
                <input type="search" class="form-control rounded-0 rounded-start" placeholder="Ara..." aria-label="Ara" aria-describedby="search-addon" value="<?php echo get_search_query(); ?>" name="s" />
              <button type="submit" class="btn btn-primary"><i class="ri-search-2-line fz-16"></i></button>
              </div>
            <hr>
            <h6 class="mt-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><path fill="currentColor" d="M10 3H4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1m10 0h-6a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1M10 13H4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-6a1 1 0 0 0-1-1m7 0a4 4 0 1 1-3.995 4.2L13 17l.005-.2A4 4 0 0 1 17 13"/></svg>
              Popüler Kategoriler
            </h6>
            <?php
            function get_top_categories($limit = 6) {
              $transient_key = 'top_categories_' . $limit;
              $categories = get_transient($transient_key);

              if ($categories === false) {
                $categories = get_categories(array(
                  'orderby'    => 'count',
                  'order'      => 'DESC',
                  'number'     => $limit,
                  'hide_empty' => true,
                  'offset'     => 3,
                ));
                set_transient($transient_key, $categories, HOUR_IN_SECONDS);
              }

              $count = 0;
              if (!empty($categories)) {
                echo '<div class="opacity-75">';
                foreach ($categories as $category) {
                  if ($count != 0) echo ', ';
                  echo '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="card-title text-capitalize link-hover fw-normal">';
                  echo esc_html($category->name);
                  echo '</a>';
                  $count++;
                }
                echo '</div>';
              } else {
                echo '<small>Henüz bir kategori bulunmuyor.</small>';
              }
            }

            get_top_categories();
            ?>

            <h6 class="mt-3">Popüler Etiketler</h6>
            <?php
            function get_top_tags($limit = 8) {
              $transient_key = 'top_tags_' . $limit;
              $tags = get_transient($transient_key);

              if ($tags === false) {
                $tags = get_tags(array(
                  'orderby'    => 'count',
                  'order'      => 'DESC',
                  'number'     => $limit,
                  'hide_empty' => true,
                ));
                set_transient($transient_key, $tags, HOUR_IN_SECONDS);
              }

              if (!empty($tags)) {
                echo '<div class="single-tags">';
                foreach ($tags as $tag) {
                  echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="tag mb-2 text-capitalize">#';
                  echo esc_html($tag->name);
                  echo '</a>';
                }
                echo '</div>';
              } else {
                echo '<small>Henüz bir etiket bulunmuyor.</small>';
              }
            }

            get_top_tags();
            ?>
          </div>
        </div>
      </div>
    </div>
  </form>
  <?php
  $mevzu_swiper_js = mevzu_asset_url('swiper_source', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', '/js/vendor/swiper-bundle.min.js', 'local');
  $mevzu_jquery_source = mevzu_get_kaynak_source('jquery_source', 'wordpress');
  ?>
  <script src="<?php echo esc_url($mevzu_swiper_js); ?>"></script>
  <script>
  var swiper_bn = new Swiper(".breaking-news", {
      slidesPerView: "auto",
      spaceBetween: 20,
      autoplay: {
          delay: 4000,
          disableOnInteraction: false,
      },
      loop: true,
      navigation: {
          nextEl: ".h-swiper-button-next",
          prevEl: ".h-swiper-button-prev",
      },
      speed: 1000,
      rewind: true,
  });
  </script>
  <?php if ($mevzu_jquery_source === 'theme') : ?>
	<script src="<?php bloginfo('template_url') ?>/js/vendor/jquery-3.7.0.min.js"></script>
	<script src="<?php bloginfo('template_url') ?>/js/mevzu2.bundle.min.js" defer></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new mevzu2.Tooltip(tooltipTriggerEl)
        });
    });
    </script>
	<script src="<?php bloginfo('template_url') ?>/js/main.min.js<?php echo '?v=' . esc_html( wp_get_theme()->get('Version') ); ?>" defer></script>
  <?php else : ?>
    <?php $mevzu_theme_version = esc_html( wp_get_theme()->get('Version') ); ?>
    <script>
    (function() {
        var bundleSrc = '<?php echo esc_url( get_template_directory_uri() . '/js/mevzu2.bundle.min.js' ); ?>';
        var mainSrc   = '<?php echo esc_url( get_template_directory_uri() . '/js/main.min.js' ); ?>?v=<?php echo esc_attr( $mevzu_theme_version ); ?>';
        function loadScript(src, cb) {
            var s = document.createElement('script');
            s.src = src;
            s.async = false;
            if (cb) s.onload = cb;
            document.body.appendChild(s);
        }
        function initTooltips() {
            if (typeof mevzu2 === 'undefined') return;
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new mevzu2.Tooltip(tooltipTriggerEl);
            });
        }
        function waitAndLoad() {
            if (typeof jQuery === 'undefined') {
                setTimeout(waitAndLoad, 50);
                return;
            }
            window.$ = window.jQuery;
            loadScript(bundleSrc, initTooltips);
            loadScript(mainSrc);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitAndLoad);
        } else {
            waitAndLoad();
        }
    })();
    </script>
  <?php endif; ?>
  <?php wp_footer(); ?>
  <?php echo (get_option('options_footer_alan') ?? ''); ?>
  </body>
</html>

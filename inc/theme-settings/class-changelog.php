<?php
/**
 * Güncelleme Bildirim / Changelog Sayfası
 * 
 * Her güncelleme sonrası açılan sayfa.
 * Güncellemelerin ne değişiklik getirdiğini gösterir.
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Changelog {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'redirect_after_update'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_menu() {
        // Gizli sayfa (menüde gözükmez ama URL ile erişilir)
        add_submenu_page(
            null,
            'Mevzu² Güncelleme Notları',
            'Güncelleme Notları',
            'publish_posts',
            'mevzu-changelog',
            array($this, 'render_page')
        );
        
        // Mevzu² Ayarları altında da bağlantı ekle
        add_submenu_page(
            'mevzu-ayarlar',
            'Güncelleme Notları',
            'Güncelleme Notları',
            'publish_posts',
            'mevzu-changelog',
            array($this, 'render_page')
        );
    }
    
    /**
     * Tema güncellendiğinde changelog sayfasına yönlendir
     */
    public function redirect_after_update() {
        if (!is_admin() || defined('DOING_AJAX') || defined('DOING_CRON')) return;
        if (!current_user_can('publish_posts')) return;
        
        $current_version = MEVZU_THEME_VERSION;
        $last_version = get_option('mevzu_last_version', '');
        
        // İlk kurulumda yönlendirme yapma (setup wizard halleder)
        if (empty($last_version)) {
            update_option('mevzu_last_version', $current_version);
            return;
        }
        
        // Versiyon değişmişse yönlendir
        if ($last_version !== $current_version) {
            update_option('mevzu_last_version', $current_version);
            set_transient('mevzu_show_changelog', 1, 60);
        }

        // Changelog sayfasının kendisindeyse yönlendirme yapma
        $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        if ($current_page === 'mevzu-changelog') return;

        if (get_transient('mevzu_show_changelog')) {
            delete_transient('mevzu_show_changelog');
            wp_redirect(admin_url('admin.php?page=mevzu-changelog'));
            exit;
        }
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'mevzu-changelog') === false) return;
    }
    
    public function render_page() {
        $changelog = $this->get_changelog();
        ?>
        <div class="wrap mevzu-settings-wrap mevzu-changelog-wrap mx-auto">
            <div class="mevzu-changelog">
                <div class="mevzu-wizard-header">
                    <div class="text-center fs-5">
                        <span class="fw-semibold">
                            <b class="text-primary">:</b>mevzu<b class="text-primary fw-bold">²</b>
                        </span>
                    </div>
                    <h1 class="text-center">Güncelleme Notları</h1>
                    <p class="version-badge small">Mevcut Versiyon: <?php echo esc_html(MEVZU_THEME_VERSION); ?></p>
                </div>
                
                <div class="mevzu-changelog-body p-3">
                    <?php if (!empty($changelog)): ?>
                        <?php 
                        $latest = $changelog[0];
                        $others = array_slice($changelog, 1);
                        ?>
                        <div class="changelog-latest">
                            <h2 class="mt-0 mb-3">Son Sürüm: <?php echo esc_html($latest['tag']); ?> (<?php echo esc_html($latest['date']); ?>)</h2>
                            <div class="changelog-content">
                                <?php echo $this->parse_markdown($latest['body']); ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="changelog-empty">
                            <p>Henüz güncelleme notu bulunmuyor veya GitHub ile bağlantı kurulamadı.</p>
                            <p class="description">
                                En son sürümün yayın notlarını <a href="https://github.com/kKerem/mevzu2/releases" target="_blank">GitHub Releases</a> sayfasından görebilirsiniz.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <style>
                .mevzu-accordion { border: 1px solid #f0f0f0; border-radius: 8px; overflow: hidden; }
                .mevzu-accordion-item { border-bottom: 1px solid #f0f0f0; }
                .mevzu-accordion-item:last-child { border-bottom: none; }
                .mevzu-accordion-header { 
                    padding: 12px 20px; background: #fafafa; cursor: pointer; list-style: none;
                    display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px;
                }
                .mevzu-accordion-header::-webkit-details-marker { display: none; }
                .mevzu-accordion-header:hover { background: #f0f6fc; }
                .mevzu-accordion-header .v-tag { color: #2271b1; }
                .mevzu-accordion-header .v-date { color: #8c8f94; font-weight: normal; font-size: 12px; margin-left: auto; }
                .mevzu-accordion-header .v-arrow { 
                    width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent;
                    border-top: 5px solid #8c8f94; transition: transform 0.2s;
                }
                .mevzu-accordion-item[open] .v-arrow { transform: rotate(180deg); }
                .mevzu-accordion-content { padding: 20px; background: #fff; border-top: 1px solid #f0f0f0; }
                .mevzu-accordion-content .changelog-content h2, .mevzu-accordion-content .changelog-content h3 { margin-top: 0; padding-top: 0; border-top: none; }
                </style>
                
                <div class="mevzu-changelog-footer p-3">
                    <a href="<?php echo admin_url(); ?>" class="button button-primary button-large">Yönetim Paneline Dön</a>
                    <a href="<?php echo admin_url('admin.php?page=mevzu-ayarlar'); ?>" class="button button-secondary button-large">Tema Ayarları</a>
                </div>
            </div>



                        <?php if (!empty($others)): ?>
                        <h3 class="mt-4 mb-3">Önceki Versiyonlar</h3>
                        <div class="changelog-history rounded-3 bg-white p-2 shadow-sm">
                            <div class="mevzu-accordion">
                                <?php foreach ($others as $index => $release): ?>
                                    <details class="mevzu-accordion-item">
                                        <summary class="mevzu-accordion-header">
                                            <span class="v-tag"><?php echo esc_html($release['tag']); ?></span>
                                            <span class="v-date"><?php echo esc_html($release['date']); ?></span>
                                            <span class="v-arrow"></span>
                                        </summary>
                                        <div class="mevzu-accordion-content">
                                            <div class="changelog-content">
                                                <?php echo $this->parse_markdown($release['body']); ?>
                                            </div>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

        </div>
        
        <?php
    }
    
    /**
     * Changelog dosyasını oku
     */
    /**
     * Changelog listesini çek
     */
    private function get_changelog() {
        $transient_key = 'mevzu2_all_changelogs';
        $changelogs = get_transient($transient_key);

        if ($changelogs !== false) {
            return $changelogs;
        }

        $api_url = 'https://api.github.com/repos/kKerem/mevzu2/releases';
        $response = wp_remote_get($api_url, array(
            'timeout'    => 15,
            'user-agent' => 'Mevzu2-Theme-Update-Agent'
        ));

        if (is_wp_error($response)) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $changelogs = array();
            foreach ($data as $release) {
                if (!is_array($release) || !isset($release['tag_name'])) {
                    continue;
                }
                $changelogs[] = array(
                    'tag'   => $release['tag_name'],
                    'name'  => $release['name'],
                    'body'  => $release['body'],
                    'date'  => date_i18n(get_option('date_format'), strtotime($release['published_at']))
                );
            }
            // 2 saatlik cache
            set_transient($transient_key, $changelogs, 2 * HOUR_IN_SECONDS);
            return $changelogs;
        }

        return array();
    }
    
    /**
     * Basit Markdown parse (heading, list, bold, code)
     */
    private function parse_markdown($text) {
        $text = esc_html($text);
        
        // Headings
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
        
        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        
        // Inline code
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        
        // Unordered lists
        $text = preg_replace('/^- (.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $text);
        
        // Line breaks
        $text = nl2br($text);
        
        // Emoji support
        $text = preg_replace('/:([\w+-]+):/', '<span class="emoji">$0</span>', $text);
        
        return $text;
    }
}

new Mevzu_Changelog();

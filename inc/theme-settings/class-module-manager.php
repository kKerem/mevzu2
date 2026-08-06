<?php
/**
 * Mevzu² Modül Yöneticisi
 * 
 * Tema özelliklerini (eklenti benzeri) aktif/deaktif yönetimi.
 * Her modül bağımsız olarak açılıp kapatılabilir.
 */
if (!defined('ABSPATH')) exit;

class Mevzu_Module_Manager {
    
    /**
     * Kayıtlı modüller
     * slug => array bilgisi
     */
    private static $modules = array();
    
    /**
     * Modül kaydet
     */
    public static function register($slug, $args) {
        $defaults = array(
            'name'        => '',
            'description' => '',
            'icon'        => 'dashicons-admin-plugins',
            'icon_type'   => 'dashicons',
            'version'     => '1.0',
            'author'      => 'Mevzu²',
            'is_premium'  => false,
            'init_file'   => '',
            'default'     => true,     // varsayılan olarak aktif mi
            'category'    => 'genel',  // genel, icerik, otomasyon, sosyal
        );
        self::$modules[$slug] = wp_parse_args($args, $defaults);
    }
    
    /**
     * Modül aktif mi kontrol et
     */
    public static function is_active($slug) {
        $modules_state = get_option('mevzu_modules', array());
        
        // Eğer hiç kaydedilmemişse, modülün varsayılan değerini kullan
        if (!isset($modules_state[$slug])) {
            return isset(self::$modules[$slug]) ? self::$modules[$slug]['default'] : false;
        }
        
        return (bool)$modules_state[$slug];
    }
    
    /**
     * Tüm kayıtlı modülleri döndür
     */
    public static function get_all() {
        return self::$modules;
    }
    
    /**
     * Modül durumunu kaydet
     */
    public static function set_active($slug, $active) {
        $modules_state = get_option('mevzu_modules', array());
        $modules_state[$slug] = $active ? 1 : 0;
        update_option('mevzu_modules', $modules_state);
    }
    
    /**
     * Aktif modülleri yükle
     */
    public static function load_modules() {
        foreach (self::$modules as $slug => $info) {
            if (self::is_active($slug) && !empty($info['init_file']) && file_exists($info['init_file'])) {
                require_once $info['init_file'];
            }
        }
    }
}

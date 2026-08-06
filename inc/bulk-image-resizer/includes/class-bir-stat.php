<?php
/**
 * Gestisco le funzioni per calcolare le statistiche
 * Ancora non usato!
 * 
 * @since      2.0.0
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;

class Bir_statistic {
	public function __construct() {
    }

    /**
     * Calcola il numero di immagini e la dimensione totale per mese
     */
    public static function count_data_filesize() {
        global $wpdb;

        $postmeta_table = $wpdb->postmeta;
        $chunk_size = 1000;

        // Orijinal dosya boyutu ve adet bilgisi SQL seviyesinde toplanır.
        $total_file_size_original = (int) $wpdb->get_var(
            "SELECT COALESCE(SUM(CAST(meta_value AS UNSIGNED)), 0)
             FROM {$postmeta_table}
             WHERE meta_key = '_bir_attachment_originalfilesize'"
        );
        $total_files_original = (int) $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$postmeta_table}
             WHERE meta_key = '_bir_attachment_originalfilesize'"
        );

        // Hangi attachment'ların karşılaştırmaya dahil olacağını set olarak tut.
        $original_ids = array_fill_keys(
            array_map('intval', $wpdb->get_col(
                "SELECT post_id
                 FROM {$postmeta_table}
                 WHERE meta_key = '_bir_attachment_originalfilesize'"
            )),
            true
        );

        $total_file_size = 0;
        $total_files = 0;

        // _wp_attachment_metadata satırlarını parçalı okuyarak bellek tüketimini düşür.
        $offset = 0;
        while (true) {
            $attachment = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id, meta_value
                     FROM {$postmeta_table}
                     WHERE meta_key = '_wp_attachment_metadata'
                     ORDER BY post_id ASC
                     LIMIT %d OFFSET %d",
                    $chunk_size,
                    $offset
                )
            );

            if (empty($attachment)) {
                break;
            }

            foreach ($attachment as $at) {
                $meta = maybe_unserialize($at->meta_value);
                if (!is_array($meta) || !isset($meta['filesize'], $meta['width'], $meta['height'])) {
                    continue;
                }

                $total_files++;
                if (isset($original_ids[(int) $at->post_id])) {
                    $total_file_size += absint($meta['filesize']);
                }
            }

            $offset += $chunk_size;
            unset($attachment);
        }

        // Ottieni la data corrente nel formato "anno-mese"
        $month = date('Ym');
        // Crea un array con i dati del mese corrente
        $month_data = array(
            'total_file_size' => $total_file_size,
            'total_files' => $total_files,
            'total_file_size_original' => $total_file_size_original,
            'total_files_original' => $total_files_original,
        );

        // Ottieni l'array di dati salvati per tutti i mesi
        $all_data = get_option('bir_monthly_stats', true);

        // Se l'array di dati non esiste, crealo
        if (!$all_data || !is_array($all_data)) {
            $all_data = array();
        }

        // Aggiungi i dati del mese corrente all'array di dati
        //print "alldata".PHP_EOL;
        //var_dump ($all_data);
        //die;
        $all_data[(string)$month] = $month_data;

        // Salva l'array di dati come metakey serializzata
        update_option('bir_monthly_stats', $all_data);
      
        return [
            'current' => $month_data,
            'all' => $all_data
        ];

    }
}


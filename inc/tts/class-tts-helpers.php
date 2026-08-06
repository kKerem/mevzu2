<?php
/**
 * TTS yardımcıları — kategori / Yapay Zeka manşeti uygunluk kontrolleri.
 *
 * @package mevzu2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hedef TTS kategori ID (yoksa 0).
 */
function mevzu_tts_target_category_id() {
	return (int) get_option( 'kkerem_tts_category_id', 0 );
}

/**
 * Yazı hedef kategoride mi?
 */
function mevzu_tts_post_in_target_category( $post_id ) {
	$target = mevzu_tts_target_category_id();
	if ( ! $target ) {
		return false;
	}
	return in_array( $target, wp_get_post_categories( $post_id ), true );
}

/**
 * Yapay Zeka manşetinde göster işaretli mi?
 */
function mevzu_tts_post_has_yapay_zeka_manset( $post_id ) {
	$positions = get_post_meta( $post_id, 'mevzu_manset_konumlari', true );
	return is_array( $positions ) && in_array( 'yapay_zeka_manset', $positions, true );
}

/**
 * Bu yazı için TTS ses dosyası üretilmeli mi?
 */
function mevzu_tts_post_should_process( $post_id ) {
	if ( mevzu_tts_post_has_yapay_zeka_manset( $post_id ) ) {
		return true;
	}
	if ( ! mevzu_tts_target_category_id() ) {
		return false;
	}
	return mevzu_tts_post_in_target_category( $post_id );
}

/**
 * Ses sentezi için yazı içeriği (otomatik kayıt / kayıtlı sürüm).
 * Editörden AJAX ile ham HTML gönderilmez — boyut ve kodlama sorunları önlenir.
 *
 * @param int $post_id Yazı ID.
 * @return string
 */
function mevzu_tts_get_post_content_for_audio( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id < 1 ) {
		return '';
	}

	$autosave = wp_get_post_autosave( $post_id );
	if ( $autosave instanceof WP_Post && $autosave->post_content !== '' ) {
		return $autosave->post_content;
	}

	$post = get_post( $post_id );
	if ( $post instanceof WP_Post ) {
		return (string) $post->post_content;
	}

	return '';
}

/**
 * Tekil yazı şablonu için Haber / Köşe detay ayarları (sesli dinle vb.).
 *
 * @param int $post_id Yazı ID (0 = mevcut yazı).
 * @return array<int, string>
 */
function mevzu_yz_get_post_detail_settings( $post_id = 0 ) {
	$post_id = (int) ( $post_id > 0 ? $post_id : get_the_ID() );
	$key     = ( $post_id > 0 && in_category( 'kose-yazilari', $post_id ) )
		? 'options_detaylar_koseyazisi'
		: 'options_detaylar';
	$detaylar = get_option( $key, array() );
	return is_array( $detaylar ) ? $detaylar : array();
}

/**
 * Ön yüzde [kkerem_tts] / sesli dinle gösterilebilir mi?
 */
function mevzu_tts_post_can_display( $post_id ) {
	if ( ! mevzu_tts_post_should_process( $post_id ) ) {
		return false;
	}
	$file_manager = new KKEREM_TTS_File_Manager();
	return $file_manager->audio_file_exists( $post_id );
}

/**
 * Yazı yayın saatini site saat diliminde formatlar.
 *
 * @param int $post_id Yazı ID.
 */
function mevzu_tts_format_post_date( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return '';
	}
	$timestamp = get_post_timestamp( $post_id, 'date' );
	if ( ! $timestamp ) {
		return '';
	}

	return wp_date( 'H:i', $timestamp );
}

/**
 * Bugün yayınlanan veya bugün güncellenen, YZ manşet işaretli ve ses dosyası olan haberler.
 *
 * @return array<int,array{id:int,title:string,url:string,permalink:string,thumb:string,audio:string}>
 */
function mevzu_tts_get_todays_yapay_zeka_playlist() {
	$year  = (int) current_time( 'Y' );
	$month = (int) current_time( 'm' );
	$day   = (int) current_time( 'd' );

	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 50,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
			'date_query'             => array(
				'relation' => 'OR',
				array(
					'column' => 'post_date',
					'year'   => $year,
					'month'  => $month,
					'day'    => $day,
				),
				array(
					'column' => 'post_modified',
					'year'   => $year,
					'month'  => $month,
					'day'    => $day,
				),
			),
			'meta_query'             => array(
				array(
					'key'     => 'mevzu_manset_konumlari',
					'value'   => 'yapay_zeka_manset',
					'compare' => 'LIKE',
				),
			),
		)
	);

	$file_manager = new KKEREM_TTS_File_Manager();
	$playlist     = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			if ( ! $file_manager->audio_file_exists( $post_id ) ) {
				continue;
			}
			$info = $file_manager->get_audio_file_info( $post_id );
			if ( empty( $info['file_url'] ) ) {
				continue;
			}
			$thumb = get_the_post_thumbnail_url( $post_id, 'gorsel-thumbnail-col-8' );
			if ( ! $thumb ) {
				$thumb = get_the_post_thumbnail_url( $post_id, 'large' );
			}
			if ( ! $thumb ) {
				$thumb = get_the_post_thumbnail_url( $post_id, 'full' );
			}
			if ( ! $thumb ) {
				$thumb = get_template_directory_uri() . '/img/404.webp';
			}

			$cat_name  = '';
			$cat_link  = '';
			$cat_color = 'primary';
			$category  = function_exists( 'get_filtered_first_category' ) ? get_filtered_first_category() : null;
			if ( ! $category ) {
				$cats = get_the_category( $post_id );
				if ( ! empty( $cats ) ) {
					$category = $cats[0];
				}
			}
			if ( $category instanceof WP_Term ) {
				$cat_name  = $category->name;
				$cat_link  = get_category_link( $category->term_id );
				$renk      = get_term_meta( $category->term_id, 'cat_renk', true );
				if ( is_string( $renk ) && $renk !== '' ) {
					$cat_color = sanitize_html_class( $renk );
				}
			}

			$excerpt = get_the_excerpt( $post_id );
			if ( ! is_string( $excerpt ) || trim( $excerpt ) === '' ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 22, '…' );
			}

			$playlist[] = array(
				'id'            => $post_id,
				'title'         => get_the_title(),
				'url'           => $info['file_url'],
				'permalink'     => get_permalink( $post_id ),
				'thumb'         => $thumb,
				'audio'         => $info['file_url'],
				'category'      => $cat_name,
				'category_url'  => $cat_link,
				'category_color'=> $cat_color,
				'date'          => mevzu_tts_format_post_date( $post_id ),
				'excerpt'       => wp_strip_all_tags( $excerpt ),
			);
		}
		wp_reset_postdata();
	}

	return $playlist;
}

/**
 * TTS ile okunan metni Spotify tarzı satırlara böler.
 *
 * @param int $post_id Yazı ID.
 * @return string[]
 */
function mevzu_tts_get_yzm_reading_lines( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return array();
	}
	$content = get_post_field( 'post_content', $post_id );
	if ( ! is_string( $content ) || trim( $content ) === '' ) {
		return array();
	}
	$service = new KKEREM_TTS_Service();
	$plain   = $service->plain_text_from_html( $content );

	return mevzu_tts_split_reading_lines( $plain );
}

/**
 * Düz metni ekranda gösterilecek satırlara ayırır.
 *
 * @param string $plain TTS düz metni.
 * @return string[]
 */
function mevzu_tts_split_reading_lines( $plain ) {
	$plain = trim( preg_replace( '/\s+/u', ' ', (string) $plain ) );
	if ( $plain === '' ) {
		return array();
	}

	$sentences = preg_split( '/(?<=[.!?…])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY );
	$lines     = array();
	$max_len   = 148;

	foreach ( $sentences as $sentence ) {
		$sentence = trim( $sentence );
		if ( $sentence === '' ) {
			continue;
		}
		if ( mb_strlen( $sentence ) <= $max_len ) {
			$lines[] = $sentence;
			continue;
		}

		/* Uzun cümle: önce virgül/noktalı virgül; gerekirse kelime sınırı. */
		$parts = preg_split( '/(?<=[,;:])(\s+)/u', $sentence, -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $parts ) > 1 ) {
			$buf = '';
			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( $part === '' ) {
					continue;
				}
				$test = ( $buf === '' ) ? $part : $buf . ' ' . $part;
				if ( mb_strlen( $test ) <= $max_len ) {
					$buf = $test;
				} else {
					if ( $buf !== '' ) {
						$lines = array_merge( $lines, mevzu_tts_chunk_words( $buf, $max_len ) );
					}
					$buf = mb_strlen( $part ) <= $max_len ? $part : '';
					if ( $buf === '' ) {
						$lines = array_merge( $lines, mevzu_tts_chunk_words( $part, $max_len ) );
					}
				}
			}
			if ( $buf !== '' ) {
				$lines = array_merge( $lines, mevzu_tts_chunk_words( $buf, $max_len ) );
			}
			continue;
		}

		$lines = array_merge( $lines, mevzu_tts_chunk_words( $sentence, $max_len ) );
	}

	return mevzu_tts_merge_short_lyric_lines( $lines );
}

/**
 * Metni kelime sınırında parçalar (cümle ortasında kopuk “alındı.” satırı olmasın diye birleştirme yapılır).
 *
 * @param string $sentence
 * @param int    $max_len
 * @return string[]
 */
function mevzu_tts_chunk_words( $sentence, $max_len ) {
	$words = preg_split( '/\s+/u', trim( $sentence ) );
	$lines = array();
	$chunk = '';

	foreach ( $words as $word ) {
		$test = ( $chunk === '' ) ? $word : $chunk . ' ' . $word;
		if ( mb_strlen( $test ) <= $max_len ) {
			$chunk = $test;
		} else {
			if ( $chunk !== '' ) {
				$lines[] = $chunk;
			}
			$chunk = $word;
		}
	}
	if ( $chunk !== '' ) {
		$lines[] = $chunk;
	}

	return $lines;
}

/**
 * Çok kısa veya cümle sonu olmayan artık satırları bir öncekiyle birleştirir.
 *
 * @param string[] $lines
 * @return string[]
 */
function mevzu_tts_merge_short_lyric_lines( array $lines ) {
	if ( count( $lines ) <= 1 ) {
		return $lines;
	}

	$out     = array();
	$min_len = 40;

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}

		if ( ! empty( $out ) ) {
			$prev       = $out[ count( $out ) - 1 ];
			$prev_ended = (bool) preg_match( '/[.!?…]["\')\]]*$/u', $prev );
			$line_short = mb_strlen( $line ) < $min_len;

			if ( $line_short || ( ! $prev_ended && mb_strlen( $line ) < $min_len * 2 ) ) {
				$out[ count( $out ) - 1 ] = $prev . ' ' . $line;
				continue;
			}
		}

		$out[] = $line;
	}

	return $out;
}

/**
 * YZ manşet ayarları için varsayılan metinler (SITE_ADI = site adı).
 *
 * @return array{baslik:string,baslangic_cumlesi:string,bitis_cumlesi:string}
 */
function mevzu_tts_get_yzm_defaults() {
	return array(
		'baslik'              => 'Günün Manşetleri',
		'baslangic_cumlesi'   => 'SITE_ADI Yapay zeka gündemine hoşgeldiniz. Bugünün öne çıkan haberleri şunlar',
		'bitis_cumlesi'       => 'Günün haberleri bu kadardı. SITE_ADI iyi günler diler.',
	);
}

/**
 * Ayar formlarında gösterilecek ham metin (kayıtlı değer yoksa varsayılan).
 *
 * @param string $key baslik | baslangic_cumlesi | bitis_cumlesi
 */
function mevzu_tts_get_yzm_setting_display( $key ) {
	$defaults = mevzu_tts_get_yzm_defaults();
	$raw      = get_opt_g( 'options_yapay_zeka_manseti', $key, '' );
	if ( is_string( $raw ) && trim( $raw ) !== '' ) {
		return $raw;
	}
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
}

/**
 * YZ manşet çubuk başlığı.
 */
function mevzu_tts_get_yzm_bar_title() {
	return trim( mevzu_tts_get_yzm_setting_display( 'baslik' ) );
}

/**
 * YZ manşet karşılama / başlangıç metni (SITE_ADI → site adı).
 */
function mevzu_tts_get_yzm_intro_text() {
	$raw = mevzu_tts_get_yzm_setting_display( 'baslangic_cumlesi' );
	return str_replace( 'SITE_ADI', get_bloginfo( 'name' ), $raw );
}

/**
 * YZ manşet kapanış metni (SITE_ADI → site adı).
 */
function mevzu_tts_get_yzm_outro_text() {
	$raw = mevzu_tts_get_yzm_setting_display( 'bitis_cumlesi' );
	return str_replace( 'SITE_ADI', get_bloginfo( 'name' ), $raw );
}

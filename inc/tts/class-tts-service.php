<?php
/**
 * Mevzu² AI ses sentezi (lisans sunucusu üzerinden).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KKEREM_TTS_Service {

	/** Tek API isteğinde en fazla bayt (Chirp3 güvenliği). */
	const MAX_SINGLE_REQUEST_BYTES = 4000;

	/** Uzun metin parça boyutu (bayt). */
	const CHUNK_BYTES = 2800;

	/**
	 * Metni ses dosyasına çevir.
	 */
	public function synthesize_text( $text, $post_id = null, $cache_basename = null ) {
		if ( empty( $text ) ) {
			return false;
		}

		if ( ! Mevzu_AI_Client::is_ready() ) {
			KKEREM_TTS_Admin::debug_log( 'Mevzu² AI kullanılamıyor: ' . Mevzu_AI_Client::get_unavailable_message() );
			return false;
		}

		if ( $cache_basename ) {
			$existing = $this->get_cached_audio_url( $cache_basename );
			if ( $existing ) {
				return array(
					'file_path' => $this->get_cached_audio_path( $cache_basename ),
					'file_url'  => $existing,
					'filename'  => sanitize_file_name( $cache_basename ) . '.mp3',
				);
			}
		} elseif ( empty( $post_id ) ) {
			return false;
		}

		if ( ! Mevzu_TTS_Daily_Limit::can_use() ) {
			KKEREM_TTS_Admin::debug_log( 'Mevzu² AI günlük kota doldu: ' . Mevzu_TTS_Daily_Limit::get_usage() . '/' . Mevzu_TTS_Daily_Limit::get_limit() );
			return false;
		}

		set_time_limit( 300 );
		@ini_set( 'memory_limit', '256M' );

		$clean_text = $this->clean_text( $text );
		if ( $clean_text === '' ) {
			KKEREM_TTS_Admin::debug_log( 'Temizlenmiş metin boş; ses üretilmedi.' );
			return false;
		}

		$text_bytes = $this->utf8_byte_length( $clean_text );
		if ( $text_bytes > self::MAX_SINGLE_REQUEST_BYTES ) {
			return $this->synthesize_long_text( $clean_text, $post_id, $cache_basename );
		}

		$request_data = $this->prepare_request_data( $clean_text );
		$result       = Mevzu_AI_Client::synthesize_request( $request_data );

		if ( is_wp_error( $result ) ) {
			error_log( 'Mevzu² AI: ' . $result->get_error_message() );
			return false;
		}

		if ( ! empty( $result['audio_content'] ) ) {
			return $this->save_audio_file( $result['audio_content'], $post_id, null, $cache_basename );
		}

		return false;
	}

	/**
	 * Uzun metinleri bölerek işle ve tek MP3'e birleştir.
	 */
	private function synthesize_long_text( $text, $post_id, $cache_basename = null ) {
		$chunks      = $this->split_text( $text, self::CHUNK_BYTES );
		$audio_files = array();

		KKEREM_TTS_Admin::debug_log( 'Uzun metin ' . count( $chunks ) . ' parçaya bölündü' );

		foreach ( $chunks as $index => $chunk ) {
			if ( ! Mevzu_TTS_Daily_Limit::can_use() ) {
				break;
			}

			$request_data = $this->prepare_request_data( $chunk );
			$result       = Mevzu_AI_Client::synthesize_request( $request_data );

			if ( is_wp_error( $result ) ) {
				error_log( 'Mevzu² AI parça ' . $index . ': ' . $result->get_error_message() );
				continue;
			}

			if ( ! empty( $result['audio_content'] ) ) {
				$saved = $this->save_audio_file( $result['audio_content'], $post_id, $index );
				if ( $saved && ! empty( $saved['file_path'] ) ) {
					$audio_files[] = $saved['file_path'];
				}
			}
		}

		if ( count( $audio_files ) > 1 ) {
			return $this->merge_audio_files( $audio_files, $post_id );
		}

		if ( count( $audio_files ) === 1 ) {
			$upload_dir = wp_upload_dir();
			$filename   = basename( $audio_files[0] );
			return array(
				'file_path' => $audio_files[0],
				'file_url'  => $upload_dir['baseurl'] . '/kkerem-tts/' . $filename,
				'filename'  => $filename,
			);
		}

		return false;
	}

	/**
	 * API isteği için veri hazırla (Mevzu² AI / Google uyumlu gövde).
	 */
	private function prepare_request_data( $text ) {
		$voice_name    = get_option( 'kkerem_tts_voice_name', 'tr-TR-Standard-A' );
		$language_code = get_option( 'kkerem_tts_language_code', 'tr-TR' );

		$ssml_gender = 'NEUTRAL';
		if ( strpos( $voice_name, 'Chirp3-HD' ) !== false ) {
			$female_names = array(
				'Achernar', 'Aoede', 'Autonoe', 'Callirrhoe', 'Despina', 'Erinome',
				'Gacrux', 'Kore', 'Laomedeia', 'Leda', 'Pulcherrima', 'Sulafat',
				'Vindemiatrix', 'Zephyr',
			);
			$voice_parts = explode( '-', $voice_name );
			$voice_style = end( $voice_parts );

			if ( in_array( $voice_style, $female_names, true ) ) {
				$ssml_gender = 'FEMALE';
			} else {
				$ssml_gender = 'MALE';
			}
		} elseif ( strpos( $voice_name, 'Neural' ) !== false || strpos( $voice_name, 'Wavenet' ) !== false ) {
			if ( strpos( $voice_name, '-A' ) !== false || strpos( $voice_name, '-C' ) !== false || strpos( $voice_name, '-E' ) !== false || strpos( $voice_name, '-G' ) !== false || strpos( $voice_name, '-I' ) !== false ) {
				$ssml_gender = 'FEMALE';
			} else {
				$ssml_gender = 'MALE';
			}
		}

		$request_data = array(
			'input'       => array(
				'text' => $text,
			),
			'voice'       => array(
				'languageCode' => $language_code,
				'name'         => $voice_name,
				'ssmlGender'   => $ssml_gender,
			),
			'audioConfig' => array(
				'audioEncoding' => 'MP3',
				'speakingRate'  => floatval( get_option( 'kkerem_tts_speaking_rate', '1.0' ) ),
				'pitch'         => floatval( get_option( 'kkerem_tts_pitch', '0.0' ) ),
			),
		);

		if ( strpos( $voice_name, 'Chirp3-HD' ) !== false ) {
			unset( $request_data['audioConfig']['speakingRate'] );
			unset( $request_data['audioConfig']['pitch'] );
		} elseif ( strpos( $voice_name, 'Neural' ) !== false ) {
			$request_data['audioConfig']['effectsProfileId'] = array( 'telephony-class-application' );
		} elseif ( strpos( $voice_name, 'Wavenet' ) !== false ) {
			$request_data['audioConfig']['effectsProfileId'] = array( 'telephony-class-application' );
		}

		return $request_data;
	}

	/**
	 * TTS ile aynı düz metin (senkron satırları için).
	 *
	 * @param string $text HTML veya düz metin.
	 */
	public function plain_text_from_html( $text ) {
		return $this->clean_text( $text );
	}

	/**
	 * UTF-8 metnin bayt uzunluğu (PHP 8.2+ utf8_encode kullanılmaz).
	 */
	private function utf8_byte_length( $text ) {
		return strlen( (string) $text );
	}

	/**
	 * Metni temizle
	 */
	private function clean_text( $text ) {
		$text = (string) $text;
		if ( function_exists( 'wp_check_invalid_utf8' ) ) {
			$text = wp_check_invalid_utf8( $text, true );
		}
		// Gutenberg / HTML yorumları ve kısa kodlar
		$text = preg_replace( '/<!--[\s\S]*?-->/', ' ', $text );
		$text = preg_replace( '/\[[^\]]+\]/', ' ', $text );
		$text = preg_replace( '/<[^>]+>/', ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		$text = preg_replace( '/([.,!?])(?=[^\s])/u', '$1 ', $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		return trim( $text );
	}

	/**
	 * Metni byte limitine göre böl
	 */
	private function split_text( $text, $max_bytes ) {
		$chunks        = array();
		$words         = explode( ' ', $text );
		$current_chunk = '';

		foreach ( $words as $word ) {
			$test_chunk = $current_chunk . ( $current_chunk ? ' ' : '' ) . $word;
			$test_bytes = $this->utf8_byte_length( $test_chunk );

			if ( $test_bytes <= $max_bytes ) {
				$current_chunk = $test_chunk;
			} else {
				if ( $current_chunk ) {
					$chunks[] = $current_chunk;
				}
				// Tek "kelime" limiti aşıyorsa bayt bayt böl
				if ( $this->utf8_byte_length( $word ) > $max_bytes ) {
					$chunks = array_merge( $chunks, $this->split_text_by_bytes( $word, $max_bytes ) );
					$current_chunk = '';
				} else {
					$current_chunk = $word;
				}
			}
		}

		if ( $current_chunk ) {
			$chunks[] = $current_chunk;
		}

		return $chunks;
	}

	/**
	 * Boşluksuz uzun dizgileri bayt limitine göre böl.
	 *
	 * @param string $text Metin.
	 * @param int    $max_bytes Parça üst sınırı.
	 * @return array<int, string>
	 */
	private function split_text_by_bytes( $text, $max_bytes ) {
		$chunks = array();
		$len    = strlen( $text );
		$offset = 0;
		while ( $offset < $len ) {
			$slice = substr( $text, $offset, $max_bytes );
			if ( $slice === '' ) {
				break;
			}
			// UTF-8 çok baytlı karakter ortasında kesmeyi önle
			while ( $offset + strlen( $slice ) < $len && preg_match( '/[\x80-\xBF]$/', $slice ) ) {
				$slice = substr( $text, $offset, strlen( $slice ) - 1 );
				if ( $slice === '' ) {
					break;
				}
			}
			$chunks[] = trim( $slice );
			$offset  += strlen( $slice );
		}
		return array_filter( $chunks );
	}

	/**
	 * YZ manşet geçiş cümlesi vb. için önbellek anahtarı (ses ayarlarına bağlı).
	 *
	 * @param string $text Okunacak metin.
	 * @return string Dosya adı (uzantısız).
	 */
	public function get_line_cache_basename( $text ) {
		$clean_text = $this->clean_text( $text );
		if ( $clean_text === '' ) {
			return '';
		}
		$voice_name    = get_option( 'kkerem_tts_voice_name', 'tr-TR-Standard-A' );
		$speaking_rate = get_option( 'kkerem_tts_speaking_rate', '1.0' );
		$pitch         = get_option( 'kkerem_tts_pitch', '0.0' );
		$language_code = get_option( 'kkerem_tts_language_code', 'tr-TR' );
		$hash          = md5( $clean_text . '|' . $voice_name . '|' . $speaking_rate . '|' . $pitch . '|' . $language_code );

		return 'yzm-trans-' . $hash;
	}

	/**
	 * Geçiş / kısa metin: Mevzu² AI + seçili ses; dosya önbelleğe alınır.
	 *
	 * @param string $text Okunacak metin.
	 * @return string|false MP3 URL veya hata.
	 */
	public function get_or_create_cached_line_audio( $text ) {
		$basename = $this->get_line_cache_basename( $text );
		if ( $basename === '' ) {
			return false;
		}

		$existing = $this->get_cached_audio_url( $basename );
		if ( $existing ) {
			return $existing;
		}

		$result = $this->synthesize_text( $text, null, $basename );
		return is_array( $result ) && ! empty( $result['file_url'] ) ? $result['file_url'] : false;
	}

	/**
	 * @param string $basename Uzantısız dosya adı (ör. yzm-trans-abc).
	 */
	private function get_cached_audio_path( $basename ) {
		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/kkerem-tts/' . sanitize_file_name( $basename ) . '.mp3';
	}

	/**
	 * @param string $basename Uzantısız dosya adı.
	 * @return string|false
	 */
	private function get_cached_audio_url( $basename ) {
		$path = $this->get_cached_audio_path( $basename );
		if ( ! file_exists( $path ) ) {
			return false;
		}
		$upload_dir = wp_upload_dir();

		return $upload_dir['baseurl'] . '/kkerem-tts/' . sanitize_file_name( $basename ) . '.mp3';
	}

	/**
	 * Ses dosyasını kaydet
	 */
	private function save_audio_file( $audio_content_base64, $post_id, $chunk_index = null, $cache_basename = null ) {
		$upload_dir = wp_upload_dir();
		$tts_dir    = $upload_dir['basedir'] . '/kkerem-tts/';

		if ( ! file_exists( $tts_dir ) ) {
			wp_mkdir_p( $tts_dir );
		}

		if ( $cache_basename ) {
			$filename = sanitize_file_name( $cache_basename ) . '.mp3';
		} elseif ( $post_id ) {
			$filename = 'post-' . intval( $post_id );
			if ( $chunk_index !== null ) {
				$filename .= '-chunk-' . intval( $chunk_index );
			}
			$filename .= '.mp3';
		} else {
			$filename = 'test-' . time() . '.mp3';
		}

		$file_path = $tts_dir . $filename;
		$decoded   = base64_decode( $audio_content_base64, true );
		if ( $decoded === false ) {
			return false;
		}

		if ( file_put_contents( $file_path, $decoded ) === false ) {
			return false;
		}

		return array(
			'file_path' => $file_path,
			'file_url'  => $upload_dir['baseurl'] . '/kkerem-tts/' . $filename,
			'filename'  => $filename,
		);
	}

	/**
	 * Birden fazla MP3 dosyasını birleştir
	 */
	private function merge_audio_files( $file_paths, $post_id ) {
		$upload_dir = wp_upload_dir();
		$output     = $upload_dir['basedir'] . '/kkerem-tts/post-' . intval( $post_id ) . '.mp3';

		$merged = '';
		foreach ( $file_paths as $path ) {
			if ( file_exists( $path ) ) {
				$merged .= file_get_contents( $path );
				unlink( $path );
			}
		}

		if ( $merged === '' ) {
			return false;
		}

		file_put_contents( $output, $merged );

		return array(
			'file_path' => $output,
			'file_url'  => $upload_dir['baseurl'] . '/kkerem-tts/post-' . intval( $post_id ) . '.mp3',
			'filename'  => 'post-' . intval( $post_id ) . '.mp3',
		);
	}

	public function test_synthesis( $text ) {
		$clean = $this->clean_text( $text );
		$basename = 'mevzu-test-' . md5( $clean . '|' . get_option( 'kkerem_tts_voice_name', '' ) );
		return $this->synthesize_text( $text, null, $basename );
	}
}

<?php
/**
 * Şablon: Haber Arşivi
 *
 * Sitede yayınlanan haberlere tarih seçerek ulaşılan arşiv sayfası.
 * Seçilen güne ait yazılar kategorilerine göre gruplanarak listelenir.
 */
get_header();

// --- Seçili tarih (varsayılan bugün) ---
$secili_tarih = isset( $_GET['tarih'] ) ? sanitize_text_field( wp_unslash( $_GET['tarih'] ) ) : current_time( 'Y-m-d' );
$ts = strtotime( $secili_tarih );
if ( ! $ts ) {
	$ts           = current_time( 'timestamp' );
	$secili_tarih = date( 'Y-m-d', $ts );
} else {
	// Girdiyi normalize et (güvenli biçim)
	$secili_tarih = date( 'Y-m-d', $ts );
}

$yil = date( 'Y', $ts );
$ay  = date( 'm', $ts );
$gun = date( 'd', $ts );

$bugun    = current_time( 'Y-m-d' );
$onceki   = date( 'Y-m-d', strtotime( '-1 day', $ts ) );
$sonraki  = date( 'Y-m-d', strtotime( '+1 day', $ts ) );
$base_url = get_permalink();

// Bugünden ilerisi seçilemez
$sonraki_aktif = ( $secili_tarih < $bugun );

// Seçili tarihin okunabilir Türkçe hâli
$turkce_aylar = array( 1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık' );
$tarih_okunabilir = intval( $gun ) . ' ' . $turkce_aylar[ intval( $ay ) ] . ' ' . $yil;
?>

<div class="container arsiv-sayfasi my-3 my-lg-4">

	<?php echo ( get_post_meta( get_the_ID(), 'reklamlari_gizle', true ) == 1 ? reklam( 'govde_ust_reklam' ) : null ); ?>

	<!-- Açıklama + Tarih seçimi -->
	<div class="tema-widget ">
		<div class="row align-items-center g-3">
			<div class="col-12 col-lg-auto ms-auto">
				<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="arsiv-tarih-form d-flex align-items-stretch bg-white shadow-sm rounded-3 mb-3">
					<span class="arsiv-takvim-ikon"><i class="ri-calendar-2-line"></i></span>
					<label class="arsiv-tarih-label">
						<span>Tarih Seçiniz</span>
						<input type="date" name="tarih" value="<?php echo esc_attr( $secili_tarih ); ?>" max="<?php echo esc_attr( $bugun ); ?>">
					</label>
					<button type="submit" class="arsiv-ara-btn">ARA</button>
				</form>
			</div>
		</div>
	</div>

	<!-- Gün gezinme -->
	<div class="tema-widget bg-white shadow-sm rounded-3 p-3 mb-3 fz-12">
		<div class="row align-items-center arsiv-gun-nav g-2">
			<div class="col text-start">
				<a href="<?php echo esc_url( add_query_arg( 'tarih', $onceki, $base_url ) ); ?>" class="ripple text-link">
					<i class="ri-arrow-left-s-line me-2"></i> Önceki Gün
				</a>
			</div>
			<div class="col text-center">
				<a href="<?php echo esc_url( add_query_arg( 'tarih', $bugun, $base_url ) ); ?>" class="<?php echo ( $secili_tarih === $bugun ? 'aktif' : '' ); ?>">
					<?php echo ( $secili_tarih === $bugun ? 'BUGÜN' : esc_html( $tarih_okunabilir ) ); ?>
				</a>
			</div>
			<div class="col text-end">
				<?php if ( $sonraki_aktif ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tarih', $sonraki, $base_url ) ); ?>" class="ripple text-link">
						Sonraki Gün <i class="ri-arrow-right-s-line ms-2"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Haber listesi -->
	<div class="tema-widget bg-white shadow-sm rounded-3 p-3 p-lg-4 arsiv-liste">
		<?php
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'ignore_sticky_posts' => true,
			'date_query'     => array(
				array(
					'year'  => (int) $yil,
					'month' => (int) $ay,
					'day'   => (int) $gun,
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :
			// Yazıları kategoriye göre grupla
			$gruplar = array();
			while ( $query->have_posts() ) :
				$query->the_post();
				$post_id = get_the_ID();
				$cats    = get_the_category();
				if ( empty( $cats ) ) {
					continue;
				}
				foreach ( $cats as $c ) {
					if ( ! isset( $gruplar[ $c->term_id ] ) ) {
						$gruplar[ $c->term_id ] = array(
							'cat'   => $c,
							'posts' => array(),
						);
					}
					$gruplar[ $c->term_id ]['posts'][] = array(
						'id'    => $post_id,
						'saat'  => get_the_date( 'H:i', $post_id ),
						'baslik' => get_the_title(),
						'link'  => get_permalink( $post_id ),
					);
				}
			endwhile;
			wp_reset_postdata();

			// Kategorileri isme göre sırala
			uasort(
				$gruplar,
				function ( $a, $b ) {
					return strcasecmp( $a['cat']->name, $b['cat']->name );
				}
			);

			foreach ( $gruplar as $grup ) :
				$cat        = $grup['cat'];
				$cat_renk   = get_term_meta( $cat->term_id, 'cat_renk', true ) ?: 'primary';
				?>
				<div class="arsiv-kategori-blok">
					<h2 class="arsiv-kategori-baslik">
						<span class="arsiv-kategori-nokta bg-<?php echo esc_attr( $cat_renk ); ?>"></span>
						<?php echo esc_html( $cat->name ); ?>
					</h2>
					<div class="row arsiv-haber-satirlari g-0">
						<?php foreach ( $grup['posts'] as $h ) : ?>
							<div class="col-12 col-lg-6">
								<a href="<?php echo esc_url( $h['link'] ); ?>" class="arsiv-haber">
									<span class="arsiv-haber-saat"><?php echo esc_html( $h['saat'] ); ?></span>
									<span class="arsiv-haber-baslik"><?php echo esc_html( $h['baslik'] ); ?></span>
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php
			endforeach;

		else :
			?>
			<div class="arsiv-bos text-center py-5 small">
				<i class="ri-inbox-line"></i>
				<p class="m-0 mt-2"><strong><?php echo esc_html( $tarih_okunabilir ); ?></strong> tarihinde yayınlanmış haber bulunamadı.</p>
			</div>
			<?php
		endif;
		?>
	</div>

</div>

<?php get_footer();

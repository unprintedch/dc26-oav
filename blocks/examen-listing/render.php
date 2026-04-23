<?php
/**
 * Examen listing block template.
 *
 * Displays all exam years as an accordion (most recent year open
 * by default). Each year contains its sessions in a grid, with
 * downloadable documents and per-user progress tracking.
 *
 * @param array $block The block settings and attributes.
 */
declare(strict_types=1);

$anchor = '';
if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

$class_name = 'dc26-examen-listing';
if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
}

if ( is_admin() ) : ?>
    <div class="dc26-examen-listing__preview">
        <p><?php echo esc_html__( 'Bloc Sessions d\'examens', 'dc26-oav' ); ?></p>
        <p><?php echo esc_html__( 'Les sections par année seront visibles sur le front.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

$is_logged_in = is_user_logged_in();
$progress     = [];
if ( $is_logged_in ) {
    $raw = get_user_meta( get_current_user_id(), 'dc26_examen_progress', true );
    if ( is_array( $raw ) ) {
        $progress = $raw;
    }
}

$examen_query = new WP_Query( [
    'post_type'      => 'examen',
    'posts_per_page' => -1,
    'post_status'    => [ 'publish', 'private' ],
    'orderby'        => 'title',
    'order'          => 'DESC',
] );

if ( ! $examen_query->have_posts() ) : ?>
    <div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
        <p class="dc26-examen-listing__empty"><?php echo esc_html__( 'Aucun examen disponible.', 'dc26-oav' ); ?></p>
    </div>
    <?php
    wp_reset_postdata();
    return;
endif;

$posts_data = [];
while ( $examen_query->have_posts() ) {
    $examen_query->the_post();
    $posts_data[] = [
        'year'    => get_the_title(),
        'post_id' => get_the_ID(),
    ];
}
wp_reset_postdata();

?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>"<?php if ( $is_logged_in ) : ?> data-logged-in data-rest-url="<?php echo esc_attr( rest_url( 'dc26/v1/examen-progress' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"<?php endif; ?>>

    <div class="dc26-examen-listing__search-wrap">
        <input type="search" class="dc26-examen-listing__search" placeholder="<?php echo esc_attr__( 'Rechercher', 'dc26-oav' ); ?>">
        <span class="dc26-examen-listing__search-icon" aria-hidden="true"></span>
    </div>

    <?php foreach ( $posts_data as $index => $item ) :
        $post_id = $item['post_id'];
        $year    = $item['year'];
        $is_open = 0 === $index;

        $year_total = 0;
        $year_done  = 0;
        if ( $is_logged_in ) {
            $sessions_raw = get_field( 'session', $post_id );
            if ( is_array( $sessions_raw ) ) {
                foreach ( $sessions_raw as $s ) {
                    if ( empty( $s['liens'] ) || ! is_array( $s['liens'] ) ) {
                        continue;
                    }
                    foreach ( $s['liens'] as $l ) {
                        if ( empty( $l['document'] ) || empty( $l['intitule'] ) ) {
                            continue;
                        }
                        $year_total++;
                        $k = $post_id . '_' . md5( $l['document'] );
                        if ( ! empty( $progress[ $k ] ) ) {
                            $year_done++;
                        }
                    }
                }
            }
        }
        $year_pct = $year_total > 0 ? round( ( $year_done / $year_total ) * 100 ) : 0;
    ?>
    <details class="dc26-examen-listing__year" <?php echo $is_open ? 'open' : ''; ?>>
        <summary class="dc26-examen-listing__year-header">
            <h2 class="dc26-examen-listing__year-title"><?php echo esc_html( $year ); ?></h2>
            <?php if ( $is_logged_in && $year_total > 0 ) : ?>
                <span class="dc26-examen-year__progress" data-done="<?php echo $year_done; ?>" data-total="<?php echo $year_total; ?>">
                    <span class="dc26-examen-year__progress-bar">
                        <span class="dc26-examen-year__progress-fill" style="width:<?php echo $year_pct; ?>%"></span>
                    </span>
                    <span class="dc26-examen-year__progress-label"><?php echo $year_done; ?>/<?php echo $year_total; ?></span>
                </span>
            <?php endif; ?>
            <span class="dc26-examen-listing__chevron" aria-hidden="true"></span>
        </summary>

        <?php if ( have_rows( 'session', $post_id ) ) : ?>
        <div class="dc26-examen-listing__sessions">
            <?php while ( have_rows( 'session', $post_id ) ) : the_row();
                $titre     = get_sub_field( 'titre' );
                $exam_date = get_sub_field( 'exam_date' );

                $total_docs = 0;
                $done_docs  = 0;
                $docs_html  = '';

                if ( have_rows( 'liens' ) ) {
                    while ( have_rows( 'liens' ) ) {
                        the_row();
                        $doc_url   = get_sub_field( 'document' );
                        $doc_label = get_sub_field( 'intitule' );
                        if ( ! $doc_url || ! $doc_label ) {
                            continue;
                        }
                        $total_docs++;
                        $doc_key   = $post_id . '_' . md5( $doc_url );
                        $is_done   = $is_logged_in && ! empty( $progress[ $doc_key ] );
                        if ( $is_done ) {
                            $done_docs++;
                        }
                        $li_class = $is_done ? ' class="is-done"' : '';

                        $docs_html .= '<li' . $li_class . ' data-key="' . esc_attr( $doc_key ) . '">';
                        if ( $is_logged_in ) {
                            $docs_html .= '<button type="button" class="dc26-examen-check" aria-label="' . esc_attr__( 'Marquer comme fait', 'dc26-oav' ) . '">';
                            $docs_html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>';
                            $docs_html .= '</button>';
                        }
                        $docs_html .= '<a class="dc26-examen-doc" href="' . esc_url( $doc_url ) . '" target="_blank" rel="noopener noreferrer">';
                        $docs_html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
                        $docs_html .= esc_html( $doc_label );
                        $docs_html .= '</a></li>';
                    }
                }

                $pct = $total_docs > 0 ? round( ( $done_docs / $total_docs ) * 100 ) : 0;
            ?>
            <div class="dc26-examen-session">
                <div class="dc26-examen-session__header">
                    <?php if ( $titre ) : ?>
                        <h3 class="dc26-examen-session__title"><?php echo esc_html( $titre ); ?></h3>
                    <?php endif; ?>
                    <?php if ( $exam_date ) : ?>
                        <span class="dc26-examen-session__date">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?php echo esc_html( $exam_date ); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ( $is_logged_in && $total_docs > 0 ) : ?>
                        <span class="dc26-examen-session__progress" data-done="<?php echo $done_docs; ?>" data-total="<?php echo $total_docs; ?>">
                            <span class="dc26-examen-session__progress-bar">
                                <span class="dc26-examen-session__progress-fill" style="width:<?php echo $pct; ?>%"></span>
                            </span>
                            <span class="dc26-examen-session__progress-label"><?php echo $done_docs; ?>/<?php echo $total_docs; ?></span>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ( $total_docs > 0 ) : ?>
                <ul class="dc26-examen-session__documents">
                    <?php echo $docs_html; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else : ?>
            <p class="dc26-examen-listing__empty"><?php echo esc_html__( 'Aucune session pour cette année.', 'dc26-oav' ); ?></p>
        <?php endif; ?>
    </details>
    <?php endforeach; ?>

</div>

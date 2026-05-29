<?php
/**
 * Member public view block template.
 *
 * Read-only display of a member's public profile, used on the
 * single-member FSE template. Data comes from the centralised
 * dc26_get_member_data() helper.
 *
 * @param array $block The block settings and attributes.
 */
declare(strict_types=1);

$anchor = '';
if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

$class_name = 'dc26-member-view has-global-padding is-layout-constrained wp-block-group-is-layout-constrained';
if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
}

if ( is_admin() ) : ?>
    <div class="dc26-member-view__preview">
        <p><?php echo esc_html__( 'Bloc Profil membre (public)', 'dc26-oav' ); ?></p>
        <p><?php echo esc_html__( 'Le profil public du membre sera affiché sur le front.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

$post_id = get_the_ID();

if ( ! $post_id || 'member' !== get_post_type( $post_id ) ) {
    return;
}

if ( has_term( 1915, 'statut', $post_id ) ) : ?>
    <div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?> dc26-member-view--private">
        <p><?php echo esc_html__( 'Ce profil n\'est pas public.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

$d            = dc26_get_member_data( $post_id );
$icon_base    = get_stylesheet_directory_uri() . '/assets/img/';
$annuaire_url = get_permalink( 6608 );
$street_line  = trim( $d['rue'] . ' ' . $d['rue_no'] );
$city_line    = trim( $d['npa'] . ' ' . $d['ville'] );

$is_own_profile = false;
if ( is_user_logged_in() ) {
    $current_member = dc26_get_member_by_user( wp_get_current_user() );
    $is_own_profile = $current_member && (int) $current_member->ID === (int) $post_id;
}
?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">

    <!-- Hero : Photo + Identity -->
    <div class="dc26-member-view__hero wp-block-columns alignwide is-layout-flex wp-block-columns-is-layout-flex" style="margin-top:var(--wp--preset--spacing--0);margin-bottom:var(--wp--preset--spacing--0)">

        <div class="dc26-member-view__photo wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:40%">
            <?php if ( $d['photo_id'] ) : ?>
                <?php echo wp_get_attachment_image( $d['photo_id'], 'medium', false, [
                    'class' => 'dc26-member-view__img',
                ] ); ?>
            <?php else : ?>
                <div class="dc26-member-view__placeholder" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
                        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>

        <div class="dc26-member-view__info wp-block-column is-layout-flow wp-block-column-is-layout-flow">
            <div class="dc26-member-view__name-row">
                <h1 class="dc26-member-view__name"><?php echo esc_html( $d['full_name'] ); ?></h1>
                <?php if ( $is_own_profile ) : ?>
                    <a class="dc26-member-view__edit-link" href="<?php echo esc_url( site_url( '/profil/' ) ); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        <?php echo esc_html__( 'Modifier mon profil', 'dc26-oav' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ( $d['status'] ) : ?>
                <p class="dc26-member-view__status"><?php echo esc_html( $d['status'] ); ?></p>
            <?php endif; ?>

            <!-- <?php if ( $d['titre'] ) : ?>
                <p class="dc26-member-view__titre"><?php echo esc_html( $d['titre'] ); ?></p>
            <?php endif; ?> -->

            <?php if ( $d['etude_name'] && $annuaire_url ) : ?>
                <p class="dc26-member-view__etude">
                    <a href="<?php echo esc_url( $annuaire_url ); ?>?fwp_etude=<?php echo esc_attr( $d['etude_slug'] ); ?>">
                        <?php echo esc_html( $d['etude_name'] ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <address class="dc26-member-view__address">
                <?php if ( $d['complement_adresse'] ) : echo esc_html( $d['complement_adresse'] ) . '<br>'; endif; ?>
                <?php if ( $street_line ) : echo esc_html( $street_line ) . '<br>'; endif; ?>
                <?php if ( $d['case_postale'] ) : echo esc_html( $d['case_postale'] ) . '<br>'; endif; ?>
                <?php if ( $city_line ) : echo esc_html( $city_line ); endif; ?>
            </address>

            <div class="dc26-member-view__contact">
                <?php if ( $d['phone'] ) : ?>
                    <a class="dc26-member-card__meta" href="tel:<?php echo esc_attr( $d['phone'] ); ?>">
                        <img src="<?php echo esc_url( $icon_base . 'phone.svg' ); ?>" alt="" style="width:0.8em;height:0.8em;vertical-align:middle;margin-right:0.35rem;">
                        <?php echo esc_html( $d['phone'] ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( $d['email'] ) : ?>
                    <span class="dc26-meta-with-copy">
                        <a class="dc26-member-card__meta" href="mailto:<?php echo esc_attr( $d['email'] ); ?>">
                            <img src="<?php echo esc_url( $icon_base . 'envelope.svg' ); ?>" alt="" style="width:0.8em;height:0.8em;vertical-align:middle;margin-right:0.35rem;">
                            <?php echo esc_html__( 'Email', 'dc26-oav' ); ?>
                        </a>
                        <button type="button" class="dc26-copy-btn" data-email="<?php echo esc_attr( $d['email'] ); ?>" aria-label="<?php esc_attr_e( 'Copier l\'adresse email', 'dc26-oav' ); ?>">
                            <img class="dc26-copy-btn__icon-copy" src="<?php echo esc_url( $icon_base . 'copy-light-full.svg' ); ?>" alt="" width="14" height="14" aria-hidden="true">
                            <svg class="dc26-copy-btn__icon-check" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </span>
                <?php endif; ?>
                <?php if ( $d['homepage_url'] ) : ?>
                    <a class="dc26-member-card__meta" href="<?php echo esc_url( $d['homepage_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url( $icon_base . 'link.svg' ); ?>" alt="" style="width:0.8em;height:0.8em;vertical-align:middle;margin-right:0.35rem;">
                        <?php echo esc_html__( 'Site', 'dc26-oav' ); ?>
                    </a>
                <?php endif; ?>
                <!-- <?php if ( $d['fax'] ) : ?>
                    <span class="dc26-member-card__meta">
                        <?php echo esc_html__( 'Fax :', 'dc26-oav' ); ?> <?php echo esc_html( $d['fax'] ); ?>
                    </span>
                <?php endif; ?> -->
            </div>
        </div>
    </div>

    <div class="dc26-member-view__sections-wrap alignfull wp-block-group has-gray-light-background-color has-background has-global-padding is-layout-constrained wp-block-group-is-layout-constrained" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

    <?php if ( ! empty( $d['specialities_fsa'] ) ) : ?>
    <div class="dc26-member-view__section wp-block-columns alignwide is-layout-flex wp-block-columns-is-layout-flex">
        <div class="dc26-member-view__section-label wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:40%">
            <h3><?php echo esc_html__( 'Spécialisations FSA', 'dc26-oav' ); ?></h3>
        </div>
        <div class="dc26-member-view__section-content wp-block-column is-layout-flow wp-block-column-is-layout-flow">
            <ul class="dc26-member-view__tags">
                <?php foreach ( $d['specialities_fsa'] as $term ) : ?>
                    <li><span class="dc26-member-view__tag"><?php echo esc_html( $term->name ); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $d['specialities'] ) ) : ?>
    <div class="dc26-member-view__section wp-block-columns alignwide is-layout-flex wp-block-columns-is-layout-flex">
        <div class="dc26-member-view__section-label wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:40%">
            <h3><?php echo esc_html__( "Domaines d'activités", 'dc26-oav' ); ?></h3>
        </div>
        <div class="dc26-member-view__section-content wp-block-column is-layout-flow wp-block-column-is-layout-flow">
            <ul class="dc26-member-view__tags">
                <?php foreach ( $d['specialities'] as $term ) : ?>
                    <li><span class="dc26-member-view__tag"><?php echo esc_html( $term->name ); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $d['languages'] ) ) : ?>
    <div class="dc26-member-view__section wp-block-columns alignwide is-layout-flex wp-block-columns-is-layout-flex">
        <div class="dc26-member-view__section-label wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:40%">
            <h3><?php echo esc_html__( 'Langues', 'dc26-oav' ); ?></h3>
        </div>
        <div class="dc26-member-view__section-content wp-block-column is-layout-flow wp-block-column-is-layout-flow">
            <ul class="dc26-member-view__tags">
                <?php foreach ( $d['languages'] as $term ) : ?>
                    <li><span class="dc26-member-view__tag"><?php echo esc_html( $term->name ); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $d['commissions'] ) ) : ?>
    <div class="dc26-member-view__section wp-block-columns alignwide is-layout-flex wp-block-columns-is-layout-flex">
        <div class="dc26-member-view__section-label wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:40%">
            <h3><?php echo esc_html__( 'Commissions', 'dc26-oav' ); ?></h3>
        </div>
        <div class="dc26-member-view__section-content wp-block-column is-layout-flow wp-block-column-is-layout-flow">
            <ul class="dc26-member-view__commissions">
                <?php foreach ( $d['commissions'] as $commission ) : ?>
                    <li>
                        <?php echo esc_html( $commission['name'] ); ?>
                        <?php if ( $commission['president'] ) : ?>
                            <em>&ndash; <?php echo esc_html__( 'président(e)', 'dc26-oav' ); ?></em>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    </div><!-- /.dc26-member-view__sections-wrap -->

    <div class="dc26-member-view__back">
        <a href="<?php echo esc_url( $annuaire_url ); ?>">&larr; <?php echo esc_html__( 'Retour à l\'annuaire', 'dc26-oav' ); ?></a>
    </div>

</div>

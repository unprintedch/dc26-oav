<?php
/**
 * Member profile block template.
 *
 * Displays the profile of the currently logged-in member with inline
 * editing capabilities powered by the dc26/v1 REST API.
 *
 * Layout: 2-col hero (photo + info) then stacked full-width cards.
 *
 * @param array $block The block settings and attributes.
 */
declare(strict_types=1);

$anchor = '';
if ( ! empty( $block['anchor'] ) ) {
    $anchor = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

$class_name = 'dc26-member-profile';
if ( ! empty( $block['className'] ) ) {
    $class_name .= ' ' . $block['className'];
}
if ( ! empty( $block['align'] ) ) {
    $class_name .= ' align' . $block['align'];
}

if ( is_admin() ) : ?>
    <div class="dc26-member-profile__preview">
        <p><?php echo esc_html__( 'Bloc Profil membre', 'dc26-oav' ); ?></p>
        <p><?php echo esc_html__( 'Le profil du membre connecté sera affiché sur le front.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

if ( ! is_user_logged_in() ) : ?>
    <div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
        <p><?php echo esc_html__( 'Veuillez vous connecter pour accéder à votre profil.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

$member_post = dc26_get_member_by_user( wp_get_current_user() );

if ( ! $member_post ) : ?>
    <div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>">
        <p><?php echo esc_html__( 'Aucun profil membre associé à votre compte.', 'dc26-oav' ); ?></p>
    </div>
    <?php return;
endif;

$d               = dc26_get_member_data( $member_post->ID );
$icon_base       = get_stylesheet_directory_uri() . '/assets/img/';
$annuaire_url    = get_permalink( 6608 );
$street_line     = trim( $d['rue'] . ' ' . $d['rue_no'] );
$city_line       = trim( $d['npa'] . ' ' . $d['ville'] );
$use_etude_addr  = (bool) get_field( 'coordonnees_idem_etude', $member_post->ID );

$all_specs  = dc26_get_all_specialities();
$all_langs  = dc26_get_all_languages();

$member_spec_ids = array_merge(
    array_map( fn( $t ) => $t->term_id, $d['specialities_fsa'] ),
    array_map( fn( $t ) => $t->term_id, $d['specialities'] )
);
$member_lang_ids = array_map( fn( $t ) => $t->term_id, $d['languages'] );

$ts_base = get_template_directory_uri() . '/assets/vendor/tom-select';
$ts_path = get_template_directory() . '/assets/vendor/tom-select';
wp_enqueue_style( 'tom-select', $ts_base . '/tom-select.min.css', [], (string) filemtime( $ts_path . '/tom-select.min.css' ) );
wp_enqueue_script( 'tom-select', $ts_base . '/tom-select.complete.min.js', [], (string) filemtime( $ts_path . '/tom-select.complete.min.js' ), true );
?>

<div <?php echo $anchor; ?>class="<?php echo esc_attr( $class_name ); ?>"
     data-rest-url="<?php echo esc_url( rest_url( 'dc26/v1/member' ) ); ?>"
     data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">

    <!-- ══════════════════════════════════════
         HERO : Photo + Infos (2 colonnes)
         ══════════════════════════════════════ -->
    <div class="dc26-member-profile__hero">

        <!-- Photo -->
        <div class="dc26-member-profile__photo" data-section="photo">
            <div class="dc26-member-profile__display">
                <?php if ( $d['photo_id'] ) : ?>
                    <?php echo wp_get_attachment_image( $d['photo_id'], 'medium', false, [
                        'class' => 'dc26-member-profile__img',
                    ] ); ?>
                <?php else : ?>
                    <div class="dc26-member-profile__placeholder" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                <button type="button" class="dc26-member-profile__edit-btn">
                    <?php echo esc_html__( 'Modifier la photo', 'dc26-oav' ); ?>
                </button>
            </div>
            <form class="dc26-member-profile__form" enctype="multipart/form-data">
                <div class="dc26-member-profile__field">
                    <label for="mp-photo"><?php echo esc_html__( 'Nouvelle photo', 'dc26-oav' ); ?></label>
                    <input type="file" id="mp-photo" name="photo" accept="image/jpeg,image/png,image/webp">
                    <small><?php echo esc_html__( 'JPG, PNG ou WebP', 'dc26-oav' ); ?></small>
                </div>
                <div class="dc26-member-profile__form-actions">
                    <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Envoyer', 'dc26-oav' ); ?></button>
                    <button type="button" class="dc26-member-profile__cancel-btn" aria-label="<?php echo esc_attr__( 'Annuler', 'dc26-oav' ); ?>">&times;</button>
                </div>
                <div class="dc26-member-profile__feedback" aria-live="polite"></div>
            </form>
        </div>

        <!-- Info -->
        <div class="dc26-member-profile__info">

            <!-- Personal -->
            <div data-section="personal">
                <div class="dc26-member-profile__section-header">
                    <h2 class="dc26-member-profile__name"><?php echo esc_html( $d['full_name'] ); ?></h2>
                    <button type="button" class="dc26-member-profile__edit-btn"><?php echo esc_html__( 'Modifier', 'dc26-oav' ); ?></button>
                </div>
                <div class="dc26-member-profile__display">
                    <?php if ( $d['status'] ) : ?>
                        <p class="dc26-member-profile__status"><?php echo esc_html( $d['status'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( $d['titre'] ) : ?>
                        <p class="dc26-member-profile__titre"><?php echo esc_html( $d['titre'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( $d['etude_name'] && $annuaire_url ) : ?>
                        <p class="dc26-member-profile__etude">
                            <a href="<?php echo esc_url( $annuaire_url ); ?>?fwp_etude=<?php echo esc_attr( $d['etude_slug'] ); ?>">
                                <?php echo esc_html( $d['etude_name'] ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                <form class="dc26-member-profile__form">
                    <div class="dc26-member-profile__field">
                        <label for="mp-prenom"><?php echo esc_html__( 'Prénom', 'dc26-oav' ); ?></label>
                        <input type="text" id="mp-prenom" name="prenom" value="<?php echo esc_attr( $d['first_name'] ); ?>">
                    </div>
                    <div class="dc26-member-profile__field">
                        <label for="mp-nom"><?php echo esc_html__( 'Nom', 'dc26-oav' ); ?></label>
                        <input type="text" id="mp-nom" name="nom" value="<?php echo esc_attr( $d['last_name'] ); ?>">
                    </div>
                    <div class="dc26-member-profile__field">
                        <label for="mp-profession"><?php echo esc_html__( 'Titre / Profession', 'dc26-oav' ); ?></label>
                        <input type="text" id="mp-profession" name="profession" value="<?php echo esc_attr( $d['titre'] ); ?>">
                    </div>
                    <div class="dc26-member-profile__form-actions">
                        <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Enregistrer', 'dc26-oav' ); ?></button>
                        <button type="button" class="dc26-member-profile__cancel-btn" aria-label="<?php echo esc_attr__( 'Annuler', 'dc26-oav' ); ?>">&times;</button>
                    </div>
                    <div class="dc26-member-profile__feedback" aria-live="polite"></div>
                </form>
            </div>

            <!-- Address -->
            <div data-section="address">
                <div class="dc26-member-profile__section-header">
                    <h4><?php echo esc_html__( 'Adresse', 'dc26-oav' ); ?></h4>
                    <?php if ( ! $use_etude_addr ) : ?>
                        <button type="button" class="dc26-member-profile__edit-btn"><?php echo esc_html__( 'Modifier', 'dc26-oav' ); ?></button>
                    <?php endif; ?>
                </div>
                <div class="dc26-member-profile__display">
                    <?php if ( $use_etude_addr ) : ?>
                        <p class="dc26-member-profile__notice"><?php echo esc_html__( 'Adresse liée à l\'étude', 'dc26-oav' ); ?></p>
                    <?php endif; ?>
                    <p class="dc26-member-profile__address">
                        <?php if ( $d['complement_adresse'] ) : echo esc_html( $d['complement_adresse'] ) . '<br>'; endif; ?>
                        <?php if ( $street_line ) : echo esc_html( $street_line ) . '<br>'; endif; ?>
                        <?php if ( $d['case_postale'] ) : echo esc_html( $d['case_postale'] ) . '<br>'; endif; ?>
                        <?php if ( $city_line ) : echo esc_html( $city_line ); endif; ?>
                    </p>
                </div>
                <?php if ( ! $use_etude_addr ) : ?>
                <form class="dc26-member-profile__form">
                    <div class="dc26-member-profile__form-row">
                        <div class="dc26-member-profile__field dc26-member-profile__field--grow">
                            <label for="mp-rue"><?php echo esc_html__( 'Rue', 'dc26-oav' ); ?></label>
                            <input type="text" id="mp-rue" name="rue" value="<?php echo esc_attr( $d['rue'] ); ?>">
                        </div>
                        <div class="dc26-member-profile__field dc26-member-profile__field--short">
                            <label for="mp-rue-no"><?php echo esc_html__( 'N°', 'dc26-oav' ); ?></label>
                            <input type="text" id="mp-rue-no" name="rue_no" value="<?php echo esc_attr( $d['rue_no'] ); ?>">
                        </div>
                    </div>
                    <div class="dc26-member-profile__field">
                        <label for="mp-complement"><?php echo esc_html__( 'Complément', 'dc26-oav' ); ?></label>
                        <input type="text" id="mp-complement" name="complement_adresse" value="<?php echo esc_attr( $d['complement_adresse'] ); ?>">
                    </div>
                    <div class="dc26-member-profile__field">
                        <label for="mp-case-postale"><?php echo esc_html__( 'Case postale', 'dc26-oav' ); ?></label>
                        <input type="text" id="mp-case-postale" name="case_postale" value="<?php echo esc_attr( $d['case_postale'] ); ?>">
                    </div>
                    <div class="dc26-member-profile__form-row">
                        <div class="dc26-member-profile__field dc26-member-profile__field--short">
                            <label for="mp-npa"><?php echo esc_html__( 'NPA', 'dc26-oav' ); ?></label>
                            <input type="text" id="mp-npa" name="npa" value="<?php echo esc_attr( $d['npa'] ); ?>">
                        </div>
                        <div class="dc26-member-profile__field dc26-member-profile__field--grow">
                            <label for="mp-localite"><?php echo esc_html__( 'Localité', 'dc26-oav' ); ?></label>
                            <input type="text" id="mp-localite" name="localite" value="<?php echo esc_attr( $d['ville'] ); ?>">
                        </div>
                    </div>
                    <div class="dc26-member-profile__form-actions">
                        <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Enregistrer', 'dc26-oav' ); ?></button>
                        <button type="button" class="dc26-member-profile__cancel-btn" aria-label="<?php echo esc_attr__( 'Annuler', 'dc26-oav' ); ?>">&times;</button>
                    </div>
                    <div class="dc26-member-profile__feedback" aria-live="polite"></div>
                </form>
                <?php endif; ?>
            </div>

            <!-- Contact -->
            <div data-section="contact">
                <div class="dc26-member-profile__section-header">
                    <h4><?php echo esc_html__( 'Contact', 'dc26-oav' ); ?></h4>
                    <button type="button" class="dc26-member-profile__edit-btn"><?php echo esc_html__( 'Modifier', 'dc26-oav' ); ?></button>
                </div>
                <div class="dc26-member-profile__display">
                    <div class="dc26-member-profile__contact">
                        <?php if ( $d['phone'] ) : ?>
                            <a class="dc26-member-profile__pill" href="tel:<?php echo esc_attr( $d['phone'] ); ?>">
                                <img src="<?php echo esc_url( $icon_base . 'phone.svg' ); ?>" alt="" width="16" height="16">
                                <?php echo esc_html( $d['phone'] ); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ( $d['email'] ) : ?>
                            <a class="dc26-member-profile__pill" href="mailto:<?php echo esc_attr( $d['email'] ); ?>">
                                <img src="<?php echo esc_url( $icon_base . 'envelope.svg' ); ?>" alt="" width="16" height="16">
                                <?php echo esc_html( $d['email'] ); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ( $d['homepage_url'] ) : ?>
                            <a class="dc26-member-profile__pill" href="<?php echo esc_url( $d['homepage_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <img src="<?php echo esc_url( $icon_base . 'link.svg' ); ?>" alt="" width="16" height="16">
                                <?php echo esc_html( $d['homepage_label'] ?: $d['homepage_url'] ); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ( $d['fax'] ) : ?>
                            <span class="dc26-member-profile__pill"><?php echo esc_html__( 'Fax :', 'dc26-oav' ); ?> <?php echo esc_html( $d['fax'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <form class="dc26-member-profile__form">
                    <div class="dc26-member-profile__form-row">
                        <div class="dc26-member-profile__field dc26-member-profile__field--grow">
                            <label for="mp-email"><?php echo esc_html__( 'Email', 'dc26-oav' ); ?></label>
                            <input type="email" id="mp-email" value="<?php echo esc_attr( $d['email'] ); ?>" readonly disabled>
                            <small><?php echo esc_html__( 'L\'email ne peut pas être modifié.', 'dc26-oav' ); ?></small>
                        </div>
                        <div class="dc26-member-profile__field dc26-member-profile__field--grow">
                            <label for="mp-tel"><?php echo esc_html__( 'Téléphone', 'dc26-oav' ); ?></label>
                            <input type="tel" id="mp-tel" name="tel1" value="<?php echo esc_attr( $d['phone'] ); ?>">
                        </div>
                    </div>
                    <div class="dc26-member-profile__form-row">
                        <div class="dc26-member-profile__field dc26-member-profile__field--grow">
                            <label for="mp-fax"><?php echo esc_html__( 'Fax', 'dc26-oav' ); ?></label>
                            <input type="tel" id="mp-fax" name="fax" value="<?php echo esc_attr( $d['fax'] ); ?>">
                        </div>
                        <div class="dc26-member-profile__field dc26-member-profile__field--grow">
                            <label for="mp-homepage"><?php echo esc_html__( 'Site web', 'dc26-oav' ); ?></label>
                            <input type="url" id="mp-homepage" name="homepage" value="<?php echo esc_attr( $d['homepage_url'] ); ?>" placeholder="https://">
                        </div>
                    </div>
                    <div class="dc26-member-profile__form-actions">
                        <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Enregistrer', 'dc26-oav' ); ?></button>
                        <button type="button" class="dc26-member-profile__cancel-btn" aria-label="<?php echo esc_attr__( 'Annuler', 'dc26-oav' ); ?>">&times;</button>
                    </div>
                    <div class="dc26-member-profile__feedback" aria-live="polite"></div>
                </form>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════
         CARDS : Sections pleine largeur
         ══════════════════════════════════════ -->

    <!-- Specialities -->
    <div class="dc26-member-profile__card" data-section="specialities">
        <div class="dc26-member-profile__section-header">
            <h3><?php echo esc_html__( 'Spécialisations & Domaines', 'dc26-oav' ); ?></h3>
            <button type="button" class="dc26-member-profile__edit-btn"><?php echo esc_html__( 'Modifier', 'dc26-oav' ); ?></button>
        </div>
        <div class="dc26-member-profile__display">
            <?php if ( ! empty( $d['specialities_fsa'] ) ) : ?>
                <div class="dc26-member-profile__tag-group">
                    <strong><?php echo esc_html__( 'FSA', 'dc26-oav' ); ?></strong>
                    <ul class="dc26-member-profile__tags">
                        <?php foreach ( $d['specialities_fsa'] as $term ) : ?>
                            <li><a class="dc26-member-profile__tag" href="<?php echo esc_url( $annuaire_url ); ?>?fwp_spcialistes_fsa=<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ( ! empty( $d['specialities'] ) ) : ?>
                <div class="dc26-member-profile__tag-group">
                    <strong><?php echo esc_html__( "Domaines d'activités", 'dc26-oav' ); ?></strong>
                    <ul class="dc26-member-profile__tags">
                        <?php foreach ( $d['specialities'] as $term ) : ?>
                            <li><a class="dc26-member-profile__tag" href="<?php echo esc_url( $annuaire_url ); ?>?fwp_specialite=<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ( empty( $d['specialities_fsa'] ) && empty( $d['specialities'] ) ) : ?>
                <p class="dc26-member-profile__empty"><?php echo esc_html__( 'Aucune spécialité sélectionnée.', 'dc26-oav' ); ?></p>
            <?php endif; ?>
        </div>
        <form class="dc26-member-profile__form">
            <div class="dc26-member-profile__field">
                <label for="mp-specialities"><?php echo esc_html__( 'Rechercher et sélectionner', 'dc26-oav' ); ?></label>
                <select id="mp-specialities" name="term_ids[]" multiple placeholder="<?php echo esc_attr__( 'Rechercher une spécialité...', 'dc26-oav' ); ?>">
                    <?php if ( ! empty( $all_specs['fsa'] ) ) : ?>
                        <optgroup label="<?php echo esc_attr__( 'Spécialisations FSA', 'dc26-oav' ); ?>">
                            <?php foreach ( $all_specs['fsa'] as $s ) : ?>
                                <option value="<?php echo esc_attr( $s['term_id'] ); ?>" <?php selected( in_array( $s['term_id'], $member_spec_ids, true ) ); ?>><?php echo esc_html( $s['name'] ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if ( ! empty( $all_specs['regular'] ) ) : ?>
                        <optgroup label="<?php echo esc_attr__( "Domaines d'activités (max. 7)", 'dc26-oav' ); ?>">
                            <?php foreach ( $all_specs['regular'] as $s ) : ?>
                                <option value="<?php echo esc_attr( $s['term_id'] ); ?>" <?php selected( in_array( $s['term_id'], $member_spec_ids, true ) ); ?>><?php echo esc_html( $s['name'] ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <div class="dc26-member-profile__field-meta">
                    <span class="dc26-member-profile__counter" id="mp-specialities-counter"><?php echo count( $member_spec_ids ); ?>/7</span>
                    <span class="dc26-member-profile__limit-notice" id="mp-specialities-notice"><?php echo esc_html__( 'Limite atteinte — supprimez un élément pour en ajouter un autre.', 'dc26-oav' ); ?></span>
                </div>
            </div>
            <div class="dc26-member-profile__form-actions">
                <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Enregistrer', 'dc26-oav' ); ?></button>
                <button type="button" class="dc26-member-profile__cancel-btn" aria-label="<?php echo esc_attr__( 'Annuler', 'dc26-oav' ); ?>">&times;</button>
            </div>
            <div class="dc26-member-profile__feedback" aria-live="polite"></div>
        </form>
    </div>

    <!-- Languages -->
    <div class="dc26-member-profile__card" data-section="languages">
        <div class="dc26-member-profile__section-header">
            <h3><?php echo esc_html__( 'Langues', 'dc26-oav' ); ?></h3>
            <button type="button" class="dc26-member-profile__edit-btn"><?php echo esc_html__( 'Modifier', 'dc26-oav' ); ?></button>
        </div>
        <div class="dc26-member-profile__display">
            <?php if ( ! empty( $d['languages'] ) ) : ?>
                <ul class="dc26-member-profile__tags">
                    <?php foreach ( $d['languages'] as $term ) : ?>
                        <li><a class="dc26-member-profile__tag" href="<?php echo esc_url( $annuaire_url ); ?>?fwp_langue=<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="dc26-member-profile__empty"><?php echo esc_html__( 'Aucune langue sélectionnée.', 'dc26-oav' ); ?></p>
            <?php endif; ?>
        </div>
        <form class="dc26-member-profile__form">
            <div class="dc26-member-profile__field">
                <label for="mp-languages"><?php echo esc_html__( 'Rechercher et sélectionner', 'dc26-oav' ); ?></label>
                <select id="mp-languages" name="term_ids[]" multiple placeholder="<?php echo esc_attr__( 'Rechercher une langue...', 'dc26-oav' ); ?>">
                    <?php foreach ( $all_langs as $l ) : ?>
                        <option value="<?php echo esc_attr( $l['term_id'] ); ?>" <?php selected( in_array( $l['term_id'], $member_lang_ids, true ) ); ?>><?php echo esc_html( $l['name'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="dc26-member-profile__form-actions">
                <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Enregistrer', 'dc26-oav' ); ?></button>
                <button type="button" class="dc26-member-profile__cancel-btn" aria-label="<?php echo esc_attr__( 'Annuler', 'dc26-oav' ); ?>">&times;</button>
            </div>
            <div class="dc26-member-profile__feedback" aria-live="polite"></div>
        </form>
    </div>

    <!-- Commissions (read-only) -->
    <?php if ( ! empty( $d['commissions'] ) ) : ?>
        <div class="dc26-member-profile__card">
            <h3><?php echo esc_html__( 'Commissions', 'dc26-oav' ); ?></h3>
            <ul class="dc26-member-profile__commissions">
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
    <?php endif; ?>

    <!-- Password -->
    <div class="dc26-member-profile__card" data-section="password">
        <h3><?php echo esc_html__( 'Modifier le mot de passe', 'dc26-oav' ); ?></h3>
        <form class="dc26-member-profile__form dc26-member-profile__form--always-visible">
            <div class="dc26-member-profile__field">
                <label for="mp-current-pw"><?php echo esc_html__( 'Mot de passe actuel', 'dc26-oav' ); ?></label>
                <div class="dc26-member-profile__pw-input-wrap">
                    <input type="password" id="mp-current-pw" name="current_password" autocomplete="current-password">
                    <button type="button" class="dc26-member-profile__pw-toggle" aria-label="<?php echo esc_attr__( 'Afficher le mot de passe', 'dc26-oav' ); ?>">
                        <img src="<?php echo esc_url( $icon_base . 'eye-light-full.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
                    </button>
                </div>
            </div>
            <div class="dc26-member-profile__field">
                <label for="mp-new-pw"><?php echo esc_html__( 'Nouveau mot de passe', 'dc26-oav' ); ?></label>
                <div class="dc26-member-profile__pw-input-wrap">
                    <input type="password" id="mp-new-pw" name="new_password" autocomplete="new-password" minlength="8">
                    <button type="button" class="dc26-member-profile__pw-toggle" aria-label="<?php echo esc_attr__( 'Afficher le mot de passe', 'dc26-oav' ); ?>">
                        <img src="<?php echo esc_url( $icon_base . 'eye-light-full.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
                    </button>
                    <button type="button" class="dc26-member-profile__pw-suggest" id="mp-pw-suggest" title="<?php echo esc_attr__( 'Générer un mot de passe sécurisé', 'dc26-oav' ); ?>">
                        <i class="fa-regular fa-wand-magic-sparkles" aria-hidden="true"></i>
                        <?php echo esc_html__( 'Générer', 'dc26-oav' ); ?>
                    </button>
                </div>
            </div>
            <div class="dc26-member-profile__form-actions">
                <button type="submit" class="dc26-member-profile__save-btn"><?php echo esc_html__( 'Changer le mot de passe', 'dc26-oav' ); ?></button>
            </div>
            <div class="dc26-member-profile__feedback" aria-live="polite"></div>
        </form>
    </div>

</div>

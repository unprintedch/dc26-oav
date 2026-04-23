<?php
/**
 * Member search block template.
 *
 * @param array $block The block settings and attributes.
 */

$anchor = '';
if (!empty($block['anchor'])) {
    $anchor = 'id="' . esc_attr($block['anchor']) . '" ';
}

$class_name = 'dc26-member-search';
if (!empty($block['className'])) {
    $class_name .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $class_name .= ' align' . $block['align'];
}

$is_admin = is_admin();
$search_title = __('Annuaire des avocats vaudois membres de l\'OAV', 'dc26-oav');

$query_args = array(
    'post_type' => 'member',
    'posts_per_page' => 100,
    'post_status' => array('publish', 'private'),
    //'orderby' => 'rand',
    'tax_query' => array(
        array(
            'taxonomy' => 'statut',
            'field' => 'slug',
            'terms' => array('membre_actif', 'stagiaire', 'membre_invite', 'avocat_conseil'),
        ),
    ),
    'facetwp' => true,
);

$etude_listing_url = get_permalink(6608);
if (!$etude_listing_url) {
    $etude_listing_url = get_post_type_archive_link('member');
}

if (!function_exists('dc26_member_search_normalize_url')) {
    /**
     * Build a safe URL string.
     *
     * @param string $url Raw URL.
     * @return string
     */
    function dc26_member_search_normalize_url(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }
}

if (!function_exists('dc26_member_search_scalar')) {
    /**
     * Normalize a field value to a scalar string.
     *
     * @param mixed $value Field value.
     * @return string
     */
    function dc26_member_search_scalar($value): string {
        if (is_array($value)) {
            if (isset($value['label']) && is_string($value['label'])) {
                return $value['label'];
            }
            if (isset($value['value']) && is_string($value['value'])) {
                return $value['value'];
            }
            $first = reset($value);
            return is_string($first) ? $first : '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
?>

<div <?php echo esc_attr($anchor); ?> class="<?php echo esc_attr($class_name); ?>">
    <?php if ($is_admin) : ?>
        <div class="dc26-member-search__preview">
            <p><?php echo esc_html__('Bloc Annuaire membres', 'dc26-oav'); ?></p>
            <p><?php echo esc_html__('Les filtres et la liste seront visibles sur le front.', 'dc26-oav'); ?></p>
        </div>
    <?php else : ?>
    
        <div class="dc26-member-search__layout">
            <aside class="dc26-member-search__filters" aria-label="<?php echo esc_attr__('Filtres membres', 'dc26-oav'); ?>">
                <div>
                    
                </div>

                <details class="dc26-member-search__advanced--mobile">
                    <summary><?php echo esc_html__('Recherche avancée', 'dc26-oav'); ?></summary>
                    <div>
                        <?php if (function_exists('facetwp_display')) : ?>
                            <?php echo facetwp_display('facet', 'recherche'); ?>
                            <?php echo facetwp_display('facet', 'categorie'); ?>
                            <?php //echo facetwp_display('facet', 'status_externe'); ?>
                            <?php echo facetwp_display('facet', 'etude'); ?>
                            <?php echo facetwp_display('facet', 'spcialistes_fsa'); ?>
                            <?php echo facetwp_display('facet', 'specialite'); ?>
                            <?php echo facetwp_display('facet', 'langue'); ?>
                            <?php echo facetwp_display('facet', 'commune'); ?>
                        <?php endif; ?>
                    </div>
                </details>

                <div class="dc26-member-search__advanced--desktop">
                    <?php if (function_exists('facetwp_display')) : ?>
                        <?php echo facetwp_display('facet', 'recherche'); ?>
                  
                        <?php echo facetwp_display('facet', 'categorie'); ?>
                        <?php //echo facetwp_display('facet', 'status_externe'); ?>
                        <?php echo facetwp_display('facet', 'etude'); ?>
                        <?php echo facetwp_display('facet', 'spcialistes_fsa'); ?>
                        <?php echo facetwp_display('facet', 'specialite'); ?>
                        <?php echo facetwp_display('facet', 'langue'); ?>
                        <?php echo facetwp_display('facet', 'commune'); ?>
                        <?php echo facetwp_display('facet', 'genre'); ?>
                    <?php endif; ?>
                </div>

                <button class="dc26-member-search__reset" type="button" onclick="FWP.reset()">
                    <i class="fa-regular fa-xmark" aria-hidden="true"></i>
                    <span><?php echo esc_html__('Annuler le filtre', 'dc26-oav'); ?></span>
                </button>
            </aside>

            <div>
              
                <div class="facetwp-template dc26-member-search__items">
                    <?php
                    $members_query = new WP_Query($query_args);
                    if ($members_query->have_posts()) :
                        while ($members_query->have_posts()) :
                            $members_query->the_post();

                            $post_id = get_the_ID();
                            $title = dc26_member_search_scalar(get_field('titre', $post_id));
                            $first_name = dc26_member_search_scalar(get_field('prenom', $post_id));
                            $last_name = dc26_member_search_scalar(get_field('nom', $post_id));
                            $homepage_field = get_field('homepage', $post_id);
                            $email = dc26_member_search_scalar(get_field('email', $post_id));
                            $telephone = dc26_member_search_scalar(get_field('tel1', $post_id));

                            $homepage = '';
                            $homepage_label = '';
                            if (is_array($homepage_field)) {
                                $homepage = !empty($homepage_field['url']) ? (string) $homepage_field['url'] : '';
                                $homepage_label = !empty($homepage_field['title']) ? (string) $homepage_field['title'] : $homepage;
                            } else {
                                $homepage = (string) $homepage_field;
                                $homepage_label = $homepage;
                            }

                            $etude = null;
                            $etude_id = 0;
                            $etude_terms = get_the_terms($post_id, 'etude');
                            if (!empty($etude_terms) && !is_wp_error($etude_terms)) {
                                $etude = $etude_terms[0];
                                $etude_id = (int) $etude->term_taxonomy_id;
                            }

                            $adresse_etude = (bool) get_field('coordonnees_idem_etude', $post_id);
                            if ($adresse_etude && $etude_id) {
                                $rue = dc26_member_search_scalar(get_field('rue', 'etude_' . $etude_id));
                                $rue_no = dc26_member_search_scalar(get_field('rue_no', 'etude_' . $etude_id));
                                $case_postale = dc26_member_search_scalar(get_field('case_postale', 'etude_' . $etude_id));
                                $complement = dc26_member_search_scalar(get_field('complement_adresse', 'etude_' . $etude_id));
                                $npa = dc26_member_search_scalar(get_field('npa', 'etude_' . $etude_id));
                                $ville = dc26_member_search_scalar(get_field('localite', 'etude_' . $etude_id));
                            } else {
                                $rue = dc26_member_search_scalar(get_field('rue', $post_id));
                                $rue_no = dc26_member_search_scalar(get_field('rue_no', $post_id));
                                $case_postale = dc26_member_search_scalar(get_field('case_postale', $post_id));
                                $complement = dc26_member_search_scalar(get_field('complement_adresse', $post_id));
                                $npa = dc26_member_search_scalar(get_field('npa', $post_id));
                                $ville = dc26_member_search_scalar(get_field('localite', $post_id));
                            }

                            $street_line = trim($rue . ' ' . $rue_no);
                            $city_line = trim($npa . ' ' . $ville);
                            $homepage_url = dc26_member_search_normalize_url($homepage);
                            ?>

                            <article class="dc26-member-card">
                                <div class="dc26-member-card__body">
                                    <h3 class="dc26-member-card__name color-primary">
                                        <?php echo esc_html(trim($first_name . ' ' . $last_name)); ?>
                                    </h3>

                                    <?php if ($etude && $etude_listing_url) : ?>
                                        <p class="dc26-member-card__study">
                                            <a href="<?php echo esc_url($etude_listing_url); ?>?fwp_etude=<?php echo esc_attr($etude->slug); ?>">
                                                <?php echo esc_html($etude->name); ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>

                                    <p class="dc26-member-card__address">
                                        <?php if ($title) : ?>
                                            <?php echo esc_html($title); ?><br>
                                        <?php endif; ?>
                                        <?php if ($street_line) : ?>
                                            <?php echo esc_html($street_line); ?><br>
                                        <?php endif; ?>
                                        <?php if ($case_postale) : ?>
                                            <?php echo esc_html($case_postale); ?><br>
                                        <?php endif; ?>
                                        <?php if ($complement) : ?>
                                            <?php echo esc_html($complement); ?><br>
                                        <?php endif; ?>
                                        <?php if ($city_line) : ?>
                                            <?php echo esc_html($city_line); ?>
                                        <?php endif; ?>
                                    </p>

                                    <?php
                                    // Chemin relatif au dossier racine du thème ou du plugin pour les icônes
                                    $icon_base_path = get_stylesheet_directory_uri() . '/assets/img/';
                                    ?>

                                    <?php if ($telephone) : ?>
                                        <a class="dc26-member-card__meta" href="tel:<?php echo esc_attr($telephone); ?>">
                                            <img src="<?php echo esc_url($icon_base_path . 'phone.svg'); ?>" alt="Phone" style="width: 0.8em; height: 0.8em; vertical-align: middle; margin-right: 0.35rem;">
                                            <?php echo esc_html($telephone); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($email && strpos($email, 'oav') === false) : ?>
                                        <span class="dc26-meta-with-copy">
                                            <a class="dc26-member-card__meta" href="mailto:<?php echo esc_attr($email); ?>">
                                                <img src="<?php echo esc_url($icon_base_path . 'envelope.svg'); ?>" alt="Email" style="width: 0.8em; height: 0.8em; vertical-align: middle; margin-right: 0.35rem;">
                                                Email
                                            </a>
                                            <button type="button" class="dc26-copy-btn" data-email="<?php echo esc_attr($email); ?>" aria-label="<?php esc_attr_e( 'Copier l\'adresse email', 'dc26-oav' ); ?>">
                                                <img class="dc26-copy-btn__icon-copy" src="<?php echo esc_url($icon_base_path . 'copy-light-full.svg'); ?>" alt="" width="14" height="14" aria-hidden="true">
                                                <svg class="dc26-copy-btn__icon-check" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($homepage_url) : ?>
                                        <a class="dc26-member-card__meta" href="<?php echo esc_url($homepage_url); ?>">
                                            <img src="<?php echo esc_url($icon_base_path . 'link.svg'); ?>" alt="Site web" style="width: 0.8em; height: 0.8em; vertical-align: middle; margin-right: 0.35rem;">
                                            Site
                                            <?php //echo esc_html($homepage_label ?: $homepage); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <p class="dc26-member-card__footer">
                                    <a class="dc26-member-card__link" href="<?php echo esc_url(get_permalink()); ?>">
                                        <?php echo esc_html__('→ Voir le profil ', 'dc26-oav'); ?>
                                    </a>
                                </p>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <p class="dc26-member-search__empty">
                            <?php echo esc_html__('Aucun résultat.', 'dc26-oav'); ?>
                        </p>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
                </div>

                <div>
                    <?php if (function_exists('facetwp_display')) : ?>
                        <?php echo facetwp_display('facet', 'pagination'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

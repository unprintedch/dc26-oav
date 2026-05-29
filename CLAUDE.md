# CLAUDE.md — dc26-oav

Child theme de `dc26-base`. Thème pour l'OAV (Ordre des Avocats Vaudois) — annuaire des membres et plateforme de formation.

> Architecture complète : voir `../dc26-base/CLAUDE.md`
> Build : `npm run dev` / `npm run build` (identique au parent)

## Functions OAV-spécifiques

| Fichier | Rôle |
|---------|------|
| `dc26-oav-enqueue.php` | Enqueue CSS/JS child (dépend de dc26-front-styles) |
| `dc26-login-screen.php` | Page login personnalisée avec logo ACF + lien retour site |
| `dc26-member.php` | Fonctions helper membres (data layer) |
| `dc26-member-api.php` | REST API membres — update profil + upload photo |
| `dc26-examen-api.php` | REST API examens — suivi progression par document |
| `dc26-oav-blocks.php` | Enregistrement blocs OAV |
| `dc26-commissions.php` | Données commissions membres |
| `dc26-query-variations.php` | Variations de requêtes WP Query |

## REST API Endpoints

| Endpoint | Méthode | Rôle |
|----------|---------|------|
| `/dc26/v1/member/update` | POST | Mise à jour sections profil (personal, address, contact, specialities, languages, password) |
| `/dc26/v1/member/photo` | POST | Upload photo membre (JPG, PNG, WebP) |
| `/dc26/v1/examen-progress` | POST | Toggle complétion d'un document d'examen (stocké en user meta) |

Tous les endpoints requièrent nonce + utilisateur connecté.

`dc26_sync_member_to_api()` synchronise vers `https://app.oav.ch/exchange/request-update-avocat.php`.

## Blocs OAV-spécifiques

| Bloc | Description |
|------|-------------|
| `member-profile` | Interface édition profil avocat |
| `member-search` | Recherche + filtres annuaire |
| `member-view` | Affichage profil public |
| `member-listing` | Liste membres (FacetWP) |
| `commissions-listing` | Liste des commissions d'un membre |
| `documentation-listing` | Liste documents/ressources |
| `examen-listing` | Liste examens avec suivi progression |
| `news-listing` | Listing posts/events avec filtres FacetWP |

## CSS OAV (`css/`)

Overrides sur le parent — 8 fichiers. Les fichiers identiques au parent ont été supprimés (ils viennent de `dc26-front-styles`).

| Fichier | Ce que ça override |
|---------|-------------------|
| `_header.css` | Padding container alignfull dans sticky header |
| `_header-sticky.css` | `sticky` au lieu de `fixed`, sélecteur `header.` |
| `_navigation.css` | Sous-menus : min-width 280px, visibility + transition |
| `_button-variants.css` | Variante outline-arrow OAV |
| `_block-style.css` | Reset listes query loop, hover cards |
| `_block-style_accordion.css` | Border/gap accordion, font-size inherit |
| `accordion-tabs.css` | Variante tabs horizontaux + bg gray-light |
| `facet-reset.css` | Bouton load-more FacetWP |

## Données membres

- CPT membres liés aux users WP via user meta
- Champs ACF : infos personnelles, adresses, spécialités, langues, commissions, photo
- Spécialités : max 7 domaines non-FSA, séparées des domaines FSA (parent term 110)
- `dc26_get_member_data()` — fonction centrale pour récupérer toutes les données d'un membre

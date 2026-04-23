# CLAUDE.md — dc26-oav

Thème pour l'OAV (Ordre des Avocats Vaudois) — annuaire des membres et plateforme de formation.

## Commands

```bash
npm install       # First time
npm run dev       # Watch CSS + JS
npm run build     # Production build
```

`build/` est gitignored — toujours lancer `npm run build` après le clone.

## Functions

| Fichier | Rôle |
|---------|------|
| `dc26-enqueue.php` | Enqueue CSS/JS, chargement conditionnel Swiper |
| `dc26-block-register.php` | Auto-register blocs + styles de blocs |
| `dc26-menu-walker.php` | Custom nav walker accordion |
| `dc26-facet.php` | FacetWP — tri "Par étude", normalisation dates en année |
| `dc26-login-screen.php` | Page login personnalisée avec logo ACF + lien retour site |
| `dc26-member.php` | Fonctions helper membres (data layer) |
| `dc26-member-api.php` | REST API membres — update profil + upload photo |
| `dc26-examen-api.php` | REST API examens — suivi progression par document |
| `dc26-woocommerce.php` | Vide (placeholder) |

## REST API Endpoints

| Endpoint | Méthode | Rôle |
|----------|---------|------|
| `/dc26/v1/member/update` | POST | Mise à jour sections profil (personal, address, contact, specialities, languages, password) |
| `/dc26/v1/member/photo` | POST | Upload photo membre (JPG, PNG, WebP) |
| `/dc26/v1/examen-progress` | POST | Toggle complétion d'un document d'examen (stocké en user meta) |

Tous les endpoints requièrent nonce + utilisateur connecté.

## Sync API externe

`dc26_sync_member_to_api()` dans `dc26-member-api.php` synchronise les données vers :
`https://app.oav.ch/exchange/request-update-avocat.php`

## Blocks

| Bloc | Description |
|------|-------------|
| `block-header` | Header custom |
| `block-video-modal` | Vidéo en modal |
| `toggle-panel` | Accordion/toggle |
| `member-profile` | Interface édition profil avocat |
| `member-search` | Recherche + filtres annuaire |
| `member-view` | Affichage profil public |
| `member-listing` | Liste membres (FacetWP) |
| `commissions-listing` | Liste des commissions d'un membre |
| `documentation-listing` | Liste documents/ressources |
| `examen-listing` | Liste examens avec suivi progression |

## Données membres

- CPT membres liés aux users WP via user meta
- Champs ACF : infos personnelles, adresses, spécialités, langues, commissions, photo
- Spécialités : max 7 domaines non-FSA, séparées des domaines FSA (parent term 110)
- `dc26_get_member_data()` — fonction centrale pour récupérer toutes les données d'un membre

# STATUS — dc26-oav

## Infos
- **Statut** : actif, **en production sur oav.ch** (migration DC25 → dc26 terminée, bascule effectuée sur https://oav.ch)
- **Site** : OAV — Ordre des Avocats Vaudois
- **Parent** : dc26-base
- **Spécificité** : gestion membres, REST API, sync API externe
- **Hébergement** : chemin serveur `sites/prod.oav.ch` (ancien site DC25 archivé dans `sites/old.oav.ch`)

## Blocs
- `commissions-listing`
- `documentation-listing`
- `examen-listing`
- `member-profile`
- `member-search`
- `member-view`
- `toggle-panel` — ✓ bloc maintenant dans dc26-base (hérité)

## Functions
- `commissions`
- `examen-api`
- `login-screen`
- `member-api`
- `member`
- `oav-enqueue`
- `query-variations`

## Open tasks
- [ ] Identifier features à remonter dans dc26-base
- [ ] **Régénérer `OAV_APP_PASSWORD`** dans `.env` — répond 401 depuis la bascule prod (compte `claude` sur oav.ch), voir WP Admin > Utilisateurs > claude
- [ ] **Compte `622` (secretairegenerale@oav.ch)** cumule les rôles `membre-oav` + `administrator` (235 capacités WP core, dont manage_options/edit_users/install_plugins) — hérité tel quel de l'ancien DC25 via `sync-accounts.php` qui copie `wp_capabilities` brut. Décision utilisateur en attente : remplacer par `administrateur_oav` (rôle custom, 95 capacités adaptées) ou laisser (à reconfirmer — la session du 2026-08-19 a clarifié la question de la admin bar mais pas celle du rôle lui-même)
- [ ] 6 anciennes newsletters "Info Minute" (n°122, 123, 137, 151, 160, 161) contiennent un lien cassé vers `/profil/` (ancienne URL avant migration de la page, mineur)
- [ ] `dc25-syncuser` plugin toujours actif en prod — vérifier si c'est voulu (le README de migration recommandait une activation manuelle a posteriori, à l'origine pas automatique)

## Session 2026-08-19 — migration formulaires + agenda + sécurité

**Migration OAV terminée, site en prod sur oav.ch** (bascule : `dev.oav.ch` → `prod.oav.ch`, ancien DC25 archivé dans `old.oav.ch`, `live.oav.ch` vidé). Comptes/membres synchronisés au préalable via `sites/oav/migration/`.

- **Gravity Forms** : 10 formulaires manquants exportés prod→dev via `GFExport`/`GFAPI` (wp-cli absent du serveur, contourné en PHP direct + `wp-load.php`) — mapping d'ID prod→dev documenté dans l'historique de conversation (ex. 260→246, 269→251...). 584 entrées + 1261 notes migrées, `created_by`/auteur de notes remappés par `user_login` (id_oav) vers l'ID dev correct. 10 articles mis à jour pour pointer vers les nouveaux `formId`. Un bug de contenu pré-existant (repéré sur prod ET répliqué sur dev) : l'article "droit des obligations" (post dev `34008`) embarquait le mauvais formulaire (droit pénal) — corrigé sur dev uniquement (formId 254), pas touché sur prod.
- **Bloc `news-listing` (agenda)** : tri par proximité de date (plus proche → plus lointain), section "Événements passés" séparée en dessous, un seul `.facetwp-template` partagé (pagination FacetWP globale). Deux bugs découverts et corrigés après coup :
  1. `min-height:100%` sur `.dc26-news-card` cassait le sizing des lignes de grille CSS quand les cartes avaient des hauteurs de contenu différentes (adresse présente/absente) → bouton pagination chevauchant les cartes. Fix : suppression de `min-height:100%` (le `stretch` par défaut de la grille suffit).
  2. Le pager FacetWP en mode `load_more` **ajoute** (append) chaque page au lieu de la remplacer → le titre "Événements passés" se dupliquait à chaque clic. Fix : le titre ne s'affiche que si `$news_query->query_vars['paged'] <= 1`.
  3. `css/facet-reset.css` est compilé dans `build/style.css` via PostCSS (`@import` dans `css/style.css`) — **ne jamais déployer une modif CSS source sans `npm run build` d'abord**, piège rencontré deux fois dans la session.
- **Sécurité** :
  - Barre d'admin WP masquée en front pour le rôle `membre-oav` (`show_admin_bar` filtré dans `dc26-oav-enqueue.php`).
  - `.claude/settings.local.json` (contient un app password en clair) exclu de tous les déploiements rsync — **toujours exclure `.claude/` sur les déploiements de tout thème/plugin dc26-***.
  - **Fuite credential trouvée et corrigée** dans le repo `dc26-login` (plugin) : `.vscode/sftp.json` committé en clair (mot de passe SFTP Infomaniak, confirmé obsolète par l'utilisateur) — retiré du tracking, `.vscode/` + `.env` ajoutés au `.gitignore`, **historique git réécrit avec `git-filter-repo` + force-push** (repo privé, 0 forks, aucun autre clone connu). `deploy.sh` de dc26-login exclut maintenant `.vscode` et `.env`.
- **Divers** :
  - Page "Votre profil" (ID 65) déplacée de `/lavocat/profil/` vers `/profil/` (retrait du parent `post_parent`, slug déjà correct).
  - `dc26-login` : redirection de déconnexion changée de `/connexion/?logged_out=1` (URL fausse, page inexistante à cet endroit) vers l'accueil (`home_url('/')`) pour tous les rôles.
  - Flow de connexion documenté : voir historique de conversation pour le détail complet (redirects par rôle stockés dans l'option `dc26_login_redirects`, contenu réservé aux membres actif sur la catégorie "Annonces" via `dc26_login_members_only_rules`).

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

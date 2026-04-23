# dc26-base - Thème WordPress

Un thème WordPress moderne et personnalisable développé pour le plaisir, utilisant les dernières technologies web et les standards WordPress.

## 🚀 Caractéristiques

- **Full Site Editing (FSE)** - Support complet de l'éditeur de site WordPress
- **PostCSS** - Compilation CSS moderne avec imports, nested et autoprefixer
- **Block Patterns** - Collection de modèles de blocs personnalisés
- **WooCommerce Ready** - Intégration complète avec WooCommerce
- **Responsive Design** - Design adaptatif pour tous les appareils
- **Accessibilité** - Conforme aux standards d'accessibilité WCAG
- **Internationalisation** - Prêt pour la traduction (RTL supporté)

## 📋 Prérequis

- WordPress 6.8+
- PHP 7.2+
- Node.js 16+ et npm
- WooCommerce (pour les fonctionnalités e-commerce)

## 🛠️ Installation et Configuration

### 1. Installation du thème

```bash
# Cloner le repository
git clone [URL_DU_REPO]
cd dc26-base

# Installer les dépendances
npm install
```

### 2. Configuration de l'environnement de développement

```bash
# Mode développement (avec watch)
npm run dev

# Build de production
npm run build:main
npm run build:admin
npm run build:js
```

### 3. Scripts disponibles

- `npm run dev` - Mode développement avec watch
- `npm run build:main` - Build du CSS principal
- `npm run build:admin` - Build du CSS éditeur
- `npm run build:js` - Build du JavaScript

## 🏗️ Structure du projet

```
dc26-base/
├── assets/                 # Assets statiques (polices, images)
├── blocks/                 # Blocs personnalisés
├── build/                  # Fichiers compilés
├── css/                    # Fichiers CSS source
├── functions/              # Fonctionnalités PHP
├── parts/                  # Parties de template réutilisables
├── patterns/               # Modèles de blocs
├── scripts/                # JavaScript source
├── styles/                 # Variations de style
├── templates/              # Templates de pages
├── functions.php           # Point d'entrée principal
├── postcss.config.js       # Configuration PostCSS
└── theme.json             # Configuration du thème
```

## 🎨 Personnalisation

### Couleurs

Le thème utilise une palette de couleurs personnalisée définie dans `theme.json` :

- **Primary**: #0d62a8
- **Primary-hover**: #4ea5ec
- **Secondary**: #d52b1e
- **Secondary-hover**: #ff4b3e
- **Gray dark**: #1c1c1c
- **Gray text**: #808080
- **Gray warm**: #84380e
- **Gray medium**: #8e8e8e
- **Gray light**: #f4f4f4
- **White**: #ffffff

### Typographie

Polices personnalisées disponibles :
- Display: Krona One
- Polices personnalisées via theme.json

### Breakpoints

- **sm**: 100%
- **md**: 960px
- **lg**: 1140px
- **xl**: 1440px
- **2xl**: 1640px

## 🔧 Développement

### Ajouter un nouveau bloc personnalisé

1. Créer un dossier dans `blocks/`
2. Ajouter `block.json` avec la configuration
3. Créer le fichier PHP du bloc

### Modifier les styles

1. Éditer les fichiers dans `css/`
2. Les changements sont automatiquement compilés en mode dev
3. Utiliser le CSS natif avec les variables WordPress pour un développement rapide

### Ajouter des patterns

1. Créer un fichier PHP dans `patterns/`
2. Utiliser la structure standard des patterns WordPress
3. Les patterns sont automatiquement disponibles dans l'éditeur

## 📱 Fonctionnalités WooCommerce

- Templates personnalisés pour tous les types de produits
- Support des produits externes, groupés et variables
- Intégration avec le mini-panier
- Templates de checkout et panier personnalisés
- Support multi-devises

## 🌐 Internationalisation

- Support complet des langues RTL
- Prêt pour la traduction
- Text Domain: `dc26-base`

## 🚀 Déploiement

### Production

```bash
# Build de production
npm run build:main
npm run build:admin
npm run build:js

# Uploader le dossier du thème sur le serveur
```

### Développement

```bash
# Mode développement avec watch
npm run dev
```

## 📚 Dépendances

### Dépendances de développement
- `postcss`: ^8.4.38
- `postcss-cli`: ^11.0.0
- `postcss-import`: ^14.0.0
- `postcss-nested`: ^5.0.3
- `esbuild`: ^0.21.1
- `autoprefixer`: ^10.4.19

### Dépendances de production
- `swiper`: ^11.1.1

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence GNU General Public License v2 ou ultérieure. Voir le fichier `LICENSE` pour plus de détails.

## 👥 Auteurs

- **unprinted** - Développement initial

## 📞 Support

Pour toute question ou problème :
- Créer une issue sur GitHub
- Contacter l'équipe de développement

---

**Note**: Ce thème est optimisé pour WordPress 6.8+ et utilise les dernières fonctionnalités du Full Site Editing. Assurez-vous que votre installation WordPress est à jour.

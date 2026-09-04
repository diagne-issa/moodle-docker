# Documentation Plateforme LMS URDFS

Documentation de la plateforme d'apprentissage Moodle de l'Université Rose Dieng France-Sénégal,
construite avec **MkDocs Material** — même architecture que la doc du site vitrine (`main-website`),
avec un `custom.css` qui reproduit le design de la documentation d'origine (hero, tuiles, badges,
cartes, encadrés, arbres de fichiers).

## Prérequis

- Python 3.10+
- `pip install mkdocs-material`

## Prévisualiser en local

```bash
mkdocs serve
```

Puis ouvrir <http://127.0.0.1:8000>. La documentation se recharge automatiquement à chaque
modification d'un fichier Markdown.

## Construire le site statique

```bash
mkdocs build
```

Le site est généré dans `site/` (à déployer sur n'importe quel hébergement statique).

## Organisation

```
mkdocs.yml                 Configuration + navigation
docs-moodle/
├── index.md               Accueil (hero + cartes de navigation)
├── demarrer/              Lancer la plateforme
├── technique/             Architecture, thème, charte, rôles, pédagogie, scripts
├── utiliser/              Guides technopédagogue / enseignant / étudiant
├── reference/             Décisions, dépannage, roadmap
└── assets/
    ├── css/custom.css     Design URDFS (charte + composants)
    └── img/               Logo et favicon
```

## À personnaliser

- **Logo & favicon** — `docs-moodle/assets/img/logo-urdfs.png` et `icon.png` sont des placeholders.
  Remplacer par les vrais visuels URDFS (par ex. depuis `theme/urdfs/pix/`).
- **Captures d'écran** — pour illustrer les guides, déposer les images dans `docs-moodle/assets/img/`
  et les insérer avec `![légende](../assets/img/mon-image.png){ loading=lazy }`.

## Composants de style disponibles

Réutilisables dans n'importe quelle page Markdown (l'extension `md_in_html` est activée) :

- Encadrés : `<div class="tip|warn|note" markdown="1"><b>Titre</b> …</div>`
- Badges : `<span class="badge ok|no|wip|neutral">…</span>`
- Cartes / tuiles : `<div class="card" markdown="1">…</div>`, `<div class="tile green">…</div>`
- Étapes : `<ol class="steps" markdown="1"><li><b>Titre</b> …</li></ol>`
- Arbre de fichiers : `<div class="tree">…</div>`
- Nuancier : `<div class="swatches"><div class="sw">…</div></div>`

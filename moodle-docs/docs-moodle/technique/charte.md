---
icon: material/brush
---

<div class="page-head" markdown="1">
<p class="crumb">Technique · 05</p>

# Charte & design system

Toutes les valeurs sont centralisées dans `scss/_variables.scss`. Aucune couleur ne doit être écrite
en dur ailleurs.
</div>

## Palette de marque

Charte définitive : **bleu marine, vert et blanc**. Pas de doré.

<div class="swatches">
<div class="sw"><i style="background:#143876"></i><span>Bleu marine<em>#143876</em></span></div>
<div class="sw"><i style="background:#06843c"></i><span>Vert URDFS<em>#06843C</em></span></div>
<div class="sw"><i style="background:#1b4794"></i><span>Marine survol<em>#1B4794</em></span></div>
<div class="sw"><i style="background:#102c5d"></i><span>Marine pressé<em>#102C5D</em></span></div>
</div>

| Variable | Valeur | Usage |
|---|---|---|
| `$urdfs-brand` | `#143876` | Couleur principale : sidebar, en-têtes de tableau, titres |
| `$urdfs-brand-600` | `#1B4794` | Survol, état actif |
| `$urdfs-brand-700` | `#102C5D` | État pressé, dégradés |
| `$urdfs-success` | `#06843C` | Vert de charte : progression, validation, accents |
| `$urdfs-ok` | `#22C55E` | Vert « succès » sémantique |

## Surfaces et texte

<div class="swatches">
<div class="sw"><i style="background:#f8fafc;border-bottom:1px solid #e2e8f0"></i><span>Fond de page<em>#F8FAFC</em></span></div>
<div class="sw"><i style="background:#ffffff;border-bottom:1px solid #e2e8f0"></i><span>Cartes<em>#FFFFFF</em></span></div>
<div class="sw"><i style="background:#f1f5f9;border-bottom:1px solid #e2e8f0"></i><span>Surfaces creusées<em>#F1F5F9</em></span></div>
<div class="sw"><i style="background:#eef3fc;border-bottom:1px solid #e2e8f0"></i><span>Bleu très pâle<em>#EEF3FC</em></span></div>
</div>

<div class="swatches">
<div class="sw"><i style="background:#0f172a"></i><span>Texte principal<em>#0F172A</em></span></div>
<div class="sw"><i style="background:#64748b"></i><span>Texte secondaire<em>#64748B</em></span></div>
<div class="sw"><i style="background:#94a3b8"></i><span>Texte tertiaire<em>#94A3B8</em></span></div>
<div class="sw"><i style="background:#e2e8f0"></i><span>Bordures<em>#E2E8F0</em></span></div>
</div>

<div class="note" markdown="1">
<b>Fond gris, cartes blanches</b>
Le fond de page n'est pas blanc mais `#F8FAFC`. Les cartes blanches se détachent ainsi naturellement,
sans avoir besoin d'ombres marquées. C'est le principe visuel qui donne à l'interface son aspect
« posé ».
</div>

## Sémantique

<div class="swatches">
<div class="sw"><i style="background:#ef4444"></i><span>Erreur<em>#EF4444</em></span></div>
<div class="sw"><i style="background:#f59e0b"></i><span>Avertissement<em>#F59E0B</em></span></div>
<div class="sw"><i style="background:#3b82f6"></i><span>Information<em>#3B82F6</em></span></div>
<div class="sw"><i style="background:#22c55e"></i><span>Succès<em>#22C55E</em></span></div>
</div>

## Accents par rôle

Chaque espace a sa couleur d'accent : l'utilisateur sait instantanément dans quel espace il se
trouve, sans lire un seul mot.

<div class="swatches">
<div class="sw"><i style="background:#143876"></i><span>Administrateur<em>#143876</em></span></div>
<div class="sw"><i style="background:#1d6fb8"></i><span>Enseignant<em>#1D6FB8</em></span></div>
<div class="sw"><i style="background:#06843c"></i><span>Étudiant<em>#06843C</em></span></div>
<div class="sw"><i style="background:#06843c"></i><span>Technopédagogue<em>#06843C</em></span></div>
</div>

Ces accents sont exploitables en CSS grâce aux classes `urdfs-role-*` posées sur le `<body>` par le
renderer.

## Typographie

**Plus Jakarta Sans** en principale, **Inter** en repli, puis les polices système. Les deux sont
embarquées en `.woff2` dans `theme/urdfs/fonts/` : la plateforme reste correcte même sans accès à
Internet.

```scss
$font-family-sans-serif: "Plus Jakarta Sans", "Inter",
                         -apple-system, BlinkMacSystemFont,
                         "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
$headings-font-weight: 700;
```

| Usage | Graisse |
|---|---|
| Titres de page | 800 (extra-bold) |
| Titres de section | 700 (bold) |
| Libellés, boutons, entrées de menu | 600 (semi-bold) |
| Texte courant | 400 (regular) |

## Rayons et surcharges Bootstrap

| Variable | Valeur | Usage |
|---|---|---|
| `$border-radius` | 1rem (16px) | Cartes, tableaux, encadrés |
| `$border-radius-sm` | 0.625rem (10px) | Boutons, champs de saisie |
| `$border-radius-lg` | 1.25rem (20px) | Grandes surfaces, carte de connexion |
| `$primary` | `$urdfs-brand` | Recolore tout Bootstrap d'un coup |
| `$body-bg` | `#F8FAFC` | Fond gris très clair |
| `$link-color` | `$urdfs-brand` | Liens |
| `$card-border-color` | `$urdfs-border` | Contour des cartes |

<div class="tip" markdown="1">
<b>La force du pre-SCSS</b>
Redéfinir `$primary` avant la compilation de Bootstrap suffit à recolorer boutons, liens, badges,
alertes, barres de progression et champs actifs dans tout Moodle, sans écrire une seule règle CSS.
C'est pour cette raison que `_variables.scss` est injecté en pre-SCSS et non ajouté à la fin.
</div>

## Le suffixe `!default`

Les variables marquées `!default` peuvent être écrasées par un réglage d'administration injecté en
amont par `lib.php`. Sans réglage, la valeur de la charte s'applique.

```scss
$urdfs-brand: #143876 !default;  // écrasable par un réglage d'admin
$urdfs-brand-600: #1b4794;       // jamais écrasable
```

Cela permet d'exposer un sélecteur de couleur dans l'administration *sans jamais en dépendre* : si la
base est réinstallée, le thème retombe sur les valeurs de la charte inscrites dans le code.

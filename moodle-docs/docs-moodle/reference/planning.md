---
icon: material/calendar-check
---

<div class="page-head" markdown="1">
<p class="crumb">Référence · 15</p>

# Planning du projet

Ce qui est livré, ce qui vient ensuite, et dans quel ordre. Chaque phase indique ce qu'elle apporte,
ce qu'elle exige comme prérequis, et ce qu'elle coûte.
</div>

## Vue d'ensemble

| Phase | Objet | État |
|---|---|---|
| **V0** | Plateforme démontrable, identité URDFS, rôles, documentation | <span class="badge wip">En cours</span> |
| **V1** | Mise en production : sécurité, disponibilité, comptes | <span class="badge neutral">À venir</span> |
| **V2** | Enrichissement pédagogique | <span class="badge neutral">À venir</span> |
| **V3** | Conformité et industrialisation | <span class="badge neutral">À venir</span> |
| **Options** | Décisions budgétaires à porter en direction | <span class="badge no">Arbitrage</span> |

<div class="note" markdown="1">
<b>Principe de séquencement</b>
Une phase ne commence pas tant que la précédente n'est pas stable. L'erreur classique consiste à
enrichir la pédagogie avant d'avoir sécurisé la production : on accumule alors des fonctionnalités
sur une base qui n'est pas fiable.
</div>

## V0 — Plateforme démontrable <span class="badge wip">En cours</span>

L'objectif est de prouver la faisabilité et de rendre la plateforme méconnaissable d'un Moodle
standard, avec un modèle de permissions réel.

### Acquis

<ul class="check" markdown="1">
<li markdown="1">Thème enfant `theme_urdfs` appliqué à l'ensemble des pages</li>
<li markdown="1">Quatre profils avec navigation et tableau de bord dédiés</li>
<li markdown="1">Rôle Technopédagogue verrouillé contre l'escalade de privilèges</li>
<li markdown="1">Cohorte pilote synchronisée, cycle remise, correction, note vérifié</li>
<li markdown="1">Visioconférence fonctionnelle sur serveur de démonstration</li>
<li markdown="1">Plugins Présence et Attestations installés par le `Dockerfile`</li>
<li markdown="1">Documentation complète et multiplateforme</li>
<li markdown="1">Scripts de sauvegarde et de restauration, restauration testée avec succès</li>
<li markdown="1">Code partagé sur dépôt privé, sans aucun secret versionné</li>
<li markdown="1">Rôles et capabilities appliqués par script</li>
<li markdown="1">Catalogue complet en place : 5 domaines, 76 catégories, 43 cohortes</li>
</ul>

### Reste à faire pour clore la V0

<ul class="check" markdown="1">
<li class="todo" markdown="1">Programmer la sauvegarde automatique dans `crontab`</li>
<li class="todo" markdown="1">Déposer le favicon URDFS dans `theme/urdfs/pix/` et dans la documentation</li>
<li class="todo" markdown="1">Retirer le thème `moove` : montage dans `docker-compose.yml` puis dossier</li>
<li class="todo" markdown="1">Vérifier la mise en forme des pages Présence et Attestations</li>
<li class="todo" markdown="1">Parcourir la plateforme avec un compte de chaque rôle, en fenêtre privée</li>
<li class="todo" markdown="1">Vérifier qu'un étudiant n'atteint aucune page d'administration par URL directe</li>
<li class="todo" markdown="1">Préparer le parcours de démonstration de bout en bout</li>
<li class="todo" markdown="1">Fusionner la branche `feat/urdfs-v0` dans `main`</li>
</ul>

<div class="note" markdown="1">
<b>Reporté volontairement</b>
Le <b>mode sombre</b> a été écarté de la V0. Le code existe dans le thème mais reste désactivé : il
sera repris quand l'identité claire sera définitivement stabilisée.
</div>

## V1 — Mise en production <span class="badge neutral">À venir</span>

Rien de ce qui suit n'est optionnel. Tant que ces points ne sont pas traités, la plateforme reste
une maquette convaincante, pas un service dont dépendent des étudiants.

<div class="grid g2" markdown="1">
<div class="card" markdown="1">
#### 1. Sauvegardes hors machine
Les scripts existent et la restauration est testée. Il reste à les exécuter sur le serveur de
production et à **déposer les archives ailleurs que sur la machine hôte**. Une sauvegarde stockée
sur le serveur qu'elle protège ne protège de rien.

<span class="badge no">Bloquant</span>
</div>
<div class="card" markdown="1">
#### 2. HTTPS et nom de domaine
Reverse proxy, certificat renouvelé automatiquement, remplacement de `localhost:8090` par une
adresse réelle. Prérequis de l'application mobile et de tout accès extérieur.

<span class="badge no">Bloquant</span>
</div>
<div class="card" markdown="1">
#### 3. Serveur SMTP
Rend à Moodle ses notifications : remises, corrections, messages, et réinitialisation autonome des
mots de passe. Sans lui, cette charge retombe entièrement sur le technopédagogue.

<span class="badge no">Bloquant</span>
</div>
<div class="card" markdown="1">
#### 4. Moodle 5.3 LTS
Version cible de la production, attendue en octobre 2026. Environ deux ans de support, et la
version que visent les auteurs de plugins. La montée se teste d'abord sur une copie.

<span class="badge no">Bloquant</span>
</div>
<div class="card" markdown="1">
#### 5. Serveur BigBlueButton dédié
Le serveur public actuel n'offre ni disponibilité garantie, ni confidentialité, et limite la durée
des sessions. À héberger soi-même ou chez un prestataire spécialisé.

<span class="badge wip">Dès que la visio devient régulière</span>
</div>
<div class="card" markdown="1">
#### 6. Rattachement à l'annuaire
Connexion par LDAP, Active Directory, CAS ou OAuth2. Les comptes suivent alors le cycle de vie de
l'université : un étudiant qui part perd son accès automatiquement.

<span class="badge wip">Structurant</span>
</div>
</div>

## V2 — Enrichissement pédagogique <span class="badge neutral">À venir</span>

Une fois la plateforme fiable, on l'enrichit. Tout ce qui suit est **gratuit**.

<ol class="steps" markdown="1">
<li markdown="1"><b>Déploiement des présences</b>
Le plugin est installé. Il reste à définir la pratique : qui saisit, à quelle fréquence, quels
statuts, quels exports pour la scolarité.</li>
<li markdown="1"><b>Attestations de suivi</b>
Concevoir le modèle d'attestation aux couleurs de l'URDFS, et définir les conditions de délivrance.</li>
<li markdown="1"><b>Contenus interactifs H5P</b>
Déjà présent dans le cœur de Moodle. Relève de la formation des enseignants, pas de l'installation.</li>
<li markdown="1"><b>Rapports de pilotage</b>
Le générateur de rapports intégré depuis Moodle 4.0 couvre l'essentiel. À configurer selon les
indicateurs attendus par la direction.</li>
<li markdown="1"><b>Modèles de cours</b>
Un cours type par niveau, dupliqué à chaque rentrée, pour garantir une structure homogène.</li>
</ol>

## V3 — Conformité et industrialisation <span class="badge neutral">À venir</span>

<div class="grid g2" markdown="1">
<div class="card" markdown="1">
#### Environnement de préproduction
Une copie du serveur pour tester les montées de version et les nouveaux plugins avant la
production. Condition d'une exploitation sereine dans la durée.
</div>
<div class="card" markdown="1">
#### Protection des données
Conditions d'utilisation et politique de confidentialité avec traçabilité des consentements,
gérées nativement par Moodle. Politique de conservation et d'archivage des comptes.
</div>
<div class="card" markdown="1">
#### Accessibilité
Audit et corrections. L'exigence est réglementaire pour un établissement, et le thème ayant été
fortement personnalisé, il doit être vérifié spécifiquement.
</div>
<div class="card" markdown="1">
#### Antivirus sur les dépôts
Analyse des fichiers téléversés par ClamAV. Mesure de durcissement, pertinente dès que des
centaines d'étudiants déposent des documents.
</div>
<div class="card" markdown="1">
#### Supervision
Surveillance de la disponibilité, de l'espace disque, des erreurs et de l'exécution du cron, qui
porte les inscriptions par cohorte et les notifications.
</div>
<div class="card" markdown="1">
#### Support et formation
Processus de support pour enseignants et étudiants, et formation à partir de cette documentation.
</div>
</div>

## Options budgétaires <span class="badge no">Arbitrage direction</span>

Ces quatre points ne sont pas des chantiers techniques mais des décisions de dépense.

| Option | Ce que cela apporte | Coût |
|---|---|---|
| **Anti-plagiat** | Contrôle des travaux rendus, via Compilatio, Turnitin ou équivalent | Abonnement, souvent au nombre d'étudiants |
| **Application mobile de marque** | L'app aux couleurs et au nom de l'URDFS sur les stores | Abonnement Moodle, tarif sur demande |
| **Notifications SMS** | Alertes critiques par SMS | Facturation au message, via un agrégateur |
| **Hébergement** | Serveur, domaine, serveur BigBlueButton | Récurrent, socle de la V1 |

<div class="tip" markdown="1">
<b>Sur les notifications</b>
Le courriel et les notifications push de l'application Moodle sont <b>gratuits</b> et couvrent
l'essentiel du besoin. Le SMS ne se justifie que pour un usage critique et rare. Pour le Sénégal,
passer par un agrégateur local est préférable aux fournisseurs internationaux, notamment pour
enregistrer un identifiant d'expéditeur affichant « URDFS » plutôt qu'un numéro inconnu.
</div>

## Ce qui est gratuit, ce qui est payant

Pour couper court à l'ambiguïté la plus fréquente.

Sont **gratuits** : Moodle, le thème URDFS, tous les plugins pédagogiques utilisés, l'intégration
BigBlueButton, l'application mobile générique, les notifications par courriel et push,
l'authentification par annuaire.

Sont **payants** : l'hébergement et le nom de domaine, le serveur BigBlueButton ou son hébergement,
éventuellement un service SMTP, et les quatre options ci-dessus si elles sont retenues.

La distinction utile n'est pas entre logiciel libre et logiciel propriétaire, mais entre le
**logiciel**, gratuit ici, et le **service** qui le fait tourner, qui a toujours un coût.

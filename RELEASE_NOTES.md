# [DoliWPshop] [23.0.0] - Gestion du stock - API sécurisée - Reprise de la ligne de version

Description : Première version publiée depuis la **1.2.0 de septembre 2021**. Elle rassemble les deux lignes de développement qui avaient divergé, apporte la **configuration de la gestion et des mouvements de stock**, un **contrôle des droits sur l'API**, et aligne la numérotation du module sur le reste du parc : le numéro majeur suit désormais la version majeure de Dolibarr, d'où le passage de 1.2.0 à **23.0.0**.

**Le module demande désormais Dolibarr 23 au minimum et 24 au maximum.** Il déclarait jusqu'ici Dolibarr 4 et PHP 5.4, qui ne correspondaient plus à rien.

## Nouvelles fonctionnalités et innovations

### Stock

* Une page d'administration dédiée permet de configurer la **méthode de gestion du stock** et les **paramètres des mouvements de stock**.

### API

* L'appel `checkPermissions` **vérifie réellement les droits** de la clé d'API sur les commandes, factures, propositions, produits, catégories et contacts de tiers, et refuse l'accès avec un code 403 explicite au lieu de laisser passer.
* La documentation des routes est complétée.

### Interface

* Bouton **« voir dans WPshop »** sur la fiche d'un tiers, à côté de ceux déjà présents sur les produits et les catégories.

## Améliorations & corrections

* **Fatale à l'initialisation du module** corrigée.
* Le trigger ne tombe plus en erreur quand `linkedObjectsIds['facture']` n'est pas un tableau.
* Création et adresse de courriel de l'utilisateur d'API corrigées.
* Le contrôle sur les codes de langue du trigger est revu.
* Les classes du module sont incluses par `dol_include_once` sans préfixe `/custom`, et les points d'entrée chargent l'environnement Dolibarr avec deux tentatives : les deux motifs pour lesquels le contrôle de paquet du Dolistore refusait le zip.

## Note sur la numérotation

Le module passe de `1.2.0` à `23.0.0`. Ce n'est pas un saut de vingt-deux versions de travail, mais un alignement : tous les modules du parc Evarisk portent un numéro majeur égal à la version majeure de Dolibarr qu'ils visent. La branche `1.3.0`, qui portait une partie des nouveautés ci-dessus, est fusionnée et devient caduque.

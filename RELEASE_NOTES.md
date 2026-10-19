# [DoliWPshop] [23.0.1] - Slugs des catégories préservés - Droits de l'utilisateur d'API

Description : Version corrective de la **23.0.0**. Elle rend la montée depuis la 1.2.0 sûre pour les boutiques dont le plugin WordPress lit encore le slug des catégories, et corrige la vérification des droits de l'utilisateur d'API.

**Si vous êtes en 1.2.0, passez directement en 23.0.1 plutôt qu'en 23.0.0.**

## Améliorations & corrections

* **Les slugs des catégories ne sont plus supprimés à l'activation du module.** La 23.0.0 retirait le champ `_wps_slug` des catégories, et avec lui les slugs déjà enregistrés. Or le plugin WordPress WPshop 2.6.1 les lit encore pour synchroniser les catégories. Le module n'écrit plus ce champ et affiche le slug depuis WordPress, mais il ne le détruit plus (#86).
* **Droits de l'utilisateur d'API** : la page de configuration vérifiait quatre droits sous un nom erroné (paiement des factures, export des tiers, création et suppression de ses événements d'agenda). Elle les réattribuait donc à chaque affichage. Les noms sont corrigés (#84).
* Le paquet de la release n'embarque plus l'outillage du dépôt (`.github`) (#77).

## Note pour les instances déjà en 23.0.0

Sur une instance où la 23.0.0 a déjà été activée, le champ `_wps_slug` a déjà été supprimé : cette version ne le recrée pas.

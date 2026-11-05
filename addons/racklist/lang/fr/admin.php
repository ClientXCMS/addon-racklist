<?php

declare(strict_types=1);

return [
    'title' => 'RackList',
    'description' => 'Synchroniser vos produits et prix avec l\'annuaire RackList',
    'settings_saved' => 'Paramètres RackList enregistrés.',
    'reference_refreshed' => 'Catégories et caractéristiques RackList rechargées.',
    'mapping_saved' => 'Correspondance enregistrée. Une synchronisation va être lancée.',
    'test_success' => 'Test réussi : :sent produit(s) vérifié(s), :rejected rejeté(s). Rien n\'a été enregistré sur RackList.',
    'sync_success' => 'Catalogue synchronisé : :sent produit(s) envoyé(s), :rejected rejeté(s).',
    'sync_failed' => 'Échec de la synchronisation [:code] : :message',

    'errors' => [
        'too_many_products' => ':count produits à envoyer, RackList en accepte :max au maximum. Excluez des produits ou des groupes.',
        'empty_catalogue' => 'Aucun produit à envoyer : une synchronisation vide retirerait toutes vos offres de RackList. Reliez au moins un groupe à une catégorie.',
        'invalid_key_format' => 'La clé doit commencer par « rk_ » suivi de 64 caractères hexadécimaux (Dashboard > Integrations sur RackList).',
        'reference_unavailable' => 'Impossible de charger les catégories RackList : :message',
    ],

    'connection' => [
        'help' => 'Votre catalogue complet est envoyé à RackList. Les produits absents de l\'envoi sont retirés de votre fiche.',
        'api_key' => 'Clé de synchronisation',
        'api_key_help' => 'Créée dans Dashboard > Integrations sur RackList. Laisser vide pour conserver la clé actuelle.',
        'auto_sync' => 'Synchroniser automatiquement à chaque modification de produit, prix ou groupe',
        'remove_key' => 'Supprimer la clé enregistrée',
        'key_configured' => 'Clé enregistrée : :key',
        'key_missing' => 'Aucune clé enregistrée.',
    ],

    'actions' => [
        'test' => 'Tester (dry run)',
        'sync' => 'Synchroniser maintenant',
        'sync_confirm' => 'Le catalogue RackList sera remplacé par les produits listés ci-dessous. Continuer ?',
        'refresh_reference' => 'Recharger les catégories',
        'sync_warning' => 'Une synchronisation complète est aussi lancée chaque nuit à 04:00.',
    ],

    'report' => [
        'title' => 'Dernière synchronisation',
        'dry_run_title' => 'Résultat du test',
        'sent' => ':count produit envoyé|:count produits envoyés',
        'success' => 'Réussie',
        'failed' => 'Échec',
        'triggers' => [
            'manual' => 'Manuelle',
            'auto' => 'Automatique',
            'cron' => 'Planifiée',
        ],
        'counters' => [
            'products_created' => 'Créés',
            'products_updated' => 'Mis à jour',
            'products_deactivated' => 'Désactivés',
            'products_rejected' => 'Rejetés',
            'conflicts_detected' => 'Conflits',
        ],
    ],

    'groups' => [
        'title' => 'Correspondance des groupes',
        'help' => 'Choisissez la catégorie RackList de chaque groupe. Ses produits en héritent ; un sous-groupe hérite de son groupe parent.',
        'group' => 'Groupe',
        'products' => 'Produits',
        'category' => 'Catégorie RackList',
        'inactive' => 'inactif',
        'inherit_parent' => 'Hériter du groupe parent',
        'not_listed' => 'Non publié sur RackList',
        'empty' => 'Aucun groupe.',
        'save' => 'Enregistrer la correspondance',
    ],

    'products' => [
        'title' => 'Aperçu du catalogue',
        'help' => ':count / :max produits seront envoyés. Prix en :currency (devise par défaut de la boutique).',
        'product' => 'Produit',
        'category' => 'Catégorie',
        'price' => 'Prix',
        'specs' => 'Caractéristiques',
        'month' => 'mois',
        'tiers' => 'cycle(s) de facturation',
        'configure' => 'Configurer',
        'reasons' => [
            'excluded' => 'Exclu manuellement',
            'no_category' => 'Aucune catégorie RackList',
            'no_pricing' => 'Aucun prix dans la devise par défaut',
            'inactive_group' => 'Groupe inactif ou absent',
        ],
    ],

    'product' => [
        'title' => 'RackList : :name',
        'back' => 'Retour à RackList',
        'category' => 'Catégorie RackList',
        'inherit' => 'Hériter du groupe (:category)',
        'excluded' => 'Ne pas publier ce produit sur RackList',
        'specs' => 'Caractéristiques',
        'specs_auto' => 'Les valeurs grisées sont lues dans la configuration :type. Remplissez un champ pour la remplacer.',
        'specs_manual' => 'Ce type de produit ne fournit pas de caractéristiques automatiques : renseignez-les ici.',
        'specs_need_category' => 'Choisissez d\'abord une catégorie pour afficher les caractéristiques acceptées.',
    ],
];

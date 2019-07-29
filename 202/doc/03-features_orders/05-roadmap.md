---
category: 'Features : Orders (specifications)'
name: '5. Roadmap'
---

Ne pas oublier de mettre des logs un peu partout; notamment dans tout les appels
à l'API.

1. Création des tables OK


2. Création des AdminTabs, répartition dans les différents onglets


3. Ajout de la configuration pour les commandes. Attention aux conditions pour :
  - Activer la synchro des commandes (l'ancien module doit être installé)
  - Activer la configuration des statuts (l'ancien module doit être installé, et le hook degreffé)


4. Ajout du hook pour "validateOrder" copier les données de l'ancien module lors de l'import des commandes
  - Condition pour savoir si la commande vient bien de Shopping Feed : $order->module == 'sfpayment'


5. Ajout du hook pour détecter les changements de statut
  - Il faudra ajouter dans la pile de données à envoyer (ShoppingfeedTaskOrder)


6. Ajout du hook pour détecter les changements sur le tracking number
  - Il faudra ajouter dans la pile de données à envoyer (ShoppingfeedTaskOrder)


7. Création du CRON avec Classlib; très similaire à celui des produits
  - 1er process : envoi des mise à jour de statut de commande, et récupération des numéros de ticket
  - 2ème process : vérification des tickets créés; alert marchand si erreur
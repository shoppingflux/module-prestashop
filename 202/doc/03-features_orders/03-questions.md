---
category: 'Features : Orders (specifications)'
name: '3. Questions'
---

Si je modifie le statut d'une commande (vers "livré" par exemple), mais que je
me suis trompé et que je reviens à "en cours d'expédition" :
* Est ce qu'on doit (et est ce qu'on peut) revenir à l'ancien statut chez SF
(annuler la demande de mise à jour) ?
  * Si la synchro est en temps réel, on ne pourra pas empêcher la demande de mise à
jour d'être envoyée.
  * Si la synchro est en différé (CRON), on pourra éviter le problème avec la 2ème
question.

---

Si je passe une commande en "livrée", puis "annulée" : 
* Si la synchro est en temps réel, on enverra les 2 demandes de mise à jour.
* Si la synchro est en différé (CRON), est ce qu'on peut se référer au statut de
la commande au moment ou le CRON passe ? Ou est ce qu'il faut enregistrer qu'il
y a eu 2 opérations ?
  * L'idée c'est que si le CRON passe, il regarde uniquement le statut _actuel_
de la commande (probablement le plus simple pour nous).
    * Si le statut est "valide", il envoie la demande.
    * Sinon, il ne fait rien.

---

Pour vérifier le statut des tickets chez SF, on est obligé d'avoir une tâche
CRON ?  
Questions plus exacte : si on est en temps réel, après quel "événement" doit on
faire la requête de vérification des tickets chez SF ?

---

Ou stocker les numéros de ticket ? Si d'autres fonctionnalités de  l'API de SF
fonctionnent avec le même système, on peut mettre une colonne dans la table
`shoppingfeed_order`; sinon, puisque c'est un process qui sort du cadre
"basique" de synchronisation, on peut créer une table dédiée
`shoppingfeed_order_tickets`.

---

A confirmer :

le module doit savoir dégreffer l'ancien module pour le hook de changement de
statut, en cas d'activation du module 202. est-ce que c'est réversible ? Si on
passe notre module(202) en off pour la gestion des modifs statuts commandes,
cela rebranche l'ancien module ? (en cas de test par exemple ou de fausse
manip).

-> Raisonnablement notre module prend la main et ne saura pas remettre le hook
dépluggé. - Clotaire

-> A priori, si on peut le faire dans un sens, on peut le faire dans l'autre. - Antoine

-> A voir selon le temps qu'il reste; ca ne fait pas partie des specs actuelles - Tetiana
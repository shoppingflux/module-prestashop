---
category: 'Features : Orders (specifications)'
name: '5. Questions'
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

Pour vérifier le statut des tickets chez SF, on est obligé d'avoir une tâche
CRON ?  
Questions plus exacte : si on est en temps réel, après quel "événement" doit on
faire la requête de vérification des tickets chez SF ?

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

-> A priori, non. On verra au kickoff, mais ca risque de poser plus de problèmes qu'autre chose.
Par contre, on peut empêcher notre synchro de commande de se lancer tant que l'ancien
module est encore greffé, et afficher un message d'erreur sur la page de conf.

---

A confirmer :

Pour passer en "shipped" : il faudra vérifier à la fois le statut et le tracking_number  
On devra détecter les changements de tracking_number ET les changements de statut  
Si on change le "tracking_number", on envoie de toute facon, quel que soit le statut  
Si on change le statut, mais qu'on a pas de "tracking_number" ou qu'on a déjà "tracking_sent", on envoie pas

---

A confirmer :

On peut avoir plusieurs statuts qui déclenchent un changement chez SF
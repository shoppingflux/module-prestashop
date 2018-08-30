{*
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from SARL 202 ecommence
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL 202 ecommence is strictly forbidden.
* In order to obtain a license, please contact us: tech@202-ecommerce.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe 202 ecommence
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
* ...........................................................................
*
* @author    202-ecommerce <tech@202-ecommerce.com>
* @copyright Copyright (c) 202-ecommerce
* @license   Commercial license
*}

{extends file="helpers/form/form.tpl"}

{block name="legend" append}
   <div class="form-group">
       <div id="accordion">
           <div class="card">
               <div class="card-header" id="headingOne">
                   <h5 class="mb-0">
                       <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                           <span>Le module 15 min Marketplace Updates - Shopping Feed fonctionne-t-il avec le module Shopping Flux Officiel ?
                           </span>
                           <i class="material-icons rotate-icon">
                               expand_less
                           </i>
                       </a>
                   </h5>
               </div>

               <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                   <div class="card-body">
                       Oui. Ce nouveau module fonctionne avec ou sans l’autre module.
                   </div>
               </div>
           </div>
           <div class="card">
               <div class="card-header" id="headingTwo">
                   <h5 class="mb-0">
                       <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <span> Le module 15 min Marketplace Updates - Shopping Feed synchronise-t-il d’autres données de mes fiches produits ?
                           </span><i class="material-icons rotate-icon">
                               expand_less
                           </i>
                       </a>
                   </h5>
               </div>
               <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                   <div class="card-body">
                       Non. Pour le moment ce module ne synchronise que les stocks, mais nous réfléchissons déjà à ajouter la mises à jour des prix selon le même principe que les stocks.
                   </div>
               </div>
           </div>
           <div class="card">
               <div class="card-header" id="headingThree">
                   <h5 class="mb-0">
                       <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            <span> Comment fonctionne le module 15 min Marketplace Updates - Shopping Feed ?</span>
                           <i class="material-icons rotate-icon">
                               expand_less
                           </i>
                       </a>
                   </h5>
               </div>
               <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                   <div class="card-body">
                       Le module a deux modes :
                       - le mode « synchronisation temps réel », dans ce cas à chaque mise à jour du stock d’un produit par exemple si vous modifiez manuellement ou si une commande passe dans le statut qui déstocke, le module enverra l’information à Shopping fFed qui mettra à jour les marketplaces. Mais certains marchand qui gère de nombreuses commandes et de nombreux produits ne peuvent pas multiplier les appels sur l’API Shopping Feed.
                       - le mode « synchronisation par tâche cron », dans ce cas la mise à jour de stock est mis en file d’attente et vous devez programmer un cron pour exécuter cette tâche qui peut être longue. Les mises à jour de stocks sont alors regroupés pour de meilleures performances.
                   </div>
               </div>
           </div>

           <div class="card">
               <div class="card-header" id="heading4">
                   <h5 class="mb-0">
                       <a class="btn btn-link collapsed" role="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="false" aria-controls="collapseThree">
                           <span>Comment contacter le support ?</span>
                           <i class="material-icons rotate-icon">
                               expand_less
                           </i>
                       </a>
                   </h5>
               </div>
               <div id="collapse4" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                   <div class="card-body">
                       Pour contacter le support vous devez vous rendre sur la fiche développeur du module puis envoyer
                       un message complétant les informations suivantes :
                       <textarea readonly rows="10" >
                            URL : PS_SHOP_URL
                            Version PHP :
                            Version PrestaShop :
                            Multiboutique activé : oui/non
                            Nombre de produits en base de données : XXX

                            Token : AUTH_TOKEN
                            Configuration temps réel : REAL_TIME_SYNCHRONIZATION
                            Nombre de produits : STOCK_SYNC_MAX_PRODUCTS
                            Date du dernier lancement du cron :
                        </textarea>
                   </div>
               </div>
           </div>

       </div>
   </div>
{/block}
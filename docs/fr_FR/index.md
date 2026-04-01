# Plugin Somfy Cloud (Overkiz)

## Presentation

Ce plugin permet de controler vos equipements Somfy directement depuis Jeedom, en passant par l'**API cloud Overkiz** (les memes serveurs que l'application **TaHoma by Somfy** sur votre telephone).

### A qui s'adresse ce plugin ?

Ce plugin est concu pour les utilisateurs qui possedent une box Somfy **ne supportant pas l'API locale** :

- **Somfy Connexoon**
- **Somfy Connectivity Kit**
- Toute box Overkiz ou le "Developer Mode" n'est pas disponible

Si votre box supporte l'API locale (TaHoma Switch, TaHoma v2 avec Developer Mode active), privilegiez un plugin local pour de meilleures performances. Ce plugin est la pour les cas ou l'API locale n'est tout simplement pas une option.

### Ce que vous pouvez faire

Tout ce que vous voyez dans l'application TaHoma sur votre telephone est controlable depuis Jeedom via ce plugin : volets roulants, stores, lumieres, portes de garage, portails, etc.

### Bon a savoir

Ce plugin passe par les serveurs cloud de Somfy. Cela implique :

- **Besoin d'internet** : si votre connexion internet est coupee ou si les serveurs Somfy sont en panne, le controle est indisponible
- **Leger delai** : les commandes mettent 1 a 3 secondes de plus qu'en local
- **Mise a jour des etats** : le plugin interroge les serveurs toutes les 5 minutes pour recuperer l'etat de vos equipements

---

## Installation

### Etape 1 : Installer le plugin

1. Dans Jeedom, allez dans **Plugins > Gestion des plugins**
2. Cliquez sur **Market** et recherchez **Somfy Cloud**
3. Installez le plugin
4. Activez-le

> **Installation manuelle :** vous pouvez aussi copier le dossier `somfycloud/` dans `/var/www/html/plugins/` sur votre serveur Jeedom.

### Etape 2 : Configurer vos identifiants

1. Allez dans la page de configuration du plugin (cliquez sur **Somfy Cloud (Overkiz)** dans la gestion des plugins)
2. Remplissez les champs :
   - **Serveur Overkiz** : choisissez votre region (Europe pour la France)
   - **Email** : l'adresse email de votre compte Somfy Connect (la meme que dans l'appli TaHoma)
   - **Mot de passe** : le mot de passe de votre compte Somfy Connect
3. Cliquez sur **Tester la connexion**

Vous devriez voir un message vert confirmant la connexion et le nombre d'equipements detectes.

> **Astuce :** vos identifiants Somfy Connect sont ceux que vous utilisez pour vous connecter a l'application TaHoma by Somfy sur votre telephone. Si vous ne vous en souvenez plus, utilisez la fonction "mot de passe oublie" sur [www.somfy-connect.com](https://www.somfy-connect.com).

### Etape 3 : Importer vos equipements

1. Toujours sur la page de configuration du plugin, cliquez sur **Synchroniser les equipements**
2. Le plugin va recuperer tous vos equipements depuis le cloud Somfy
3. Un message vert confirme le nombre d'equipements synchronises

### Etape 4 : Voir et utiliser vos equipements

1. Allez dans **Plugins > Protocole domotique > Somfy Cloud (Overkiz)**
2. Vous voyez la liste de tous vos equipements importes
3. Cliquez sur un equipement pour voir ses commandes et le configurer

Pour qu'un equipement apparaisse sur votre dashboard Jeedom :

1. Cliquez sur l'equipement
2. Choisissez un **Objet parent** (la piece ou vous voulez qu'il apparaisse)
3. Cochez **Visible**
4. Cliquez sur **Sauvegarder**

---

## Utilisation au quotidien

### Commandes disponibles

Selon le type d'equipement, le plugin cree automatiquement les commandes appropriees :

#### Volets roulants, stores, ecrans

| Commande | Description |
|---|---|
| **open** | Ouvre completement |
| **close** | Ferme completement |
| **stop** | Arrete le mouvement en cours |
| **my** | Va a la position favorite (celle programmee sur votre telecommande Somfy) |
| **Positionner** | Curseur pour choisir une position precise (0% = ouvert, 100% = ferme) |
| **Etat position** | Affiche la position actuelle en pourcentage |

#### Lumieres

| Commande | Description |
|---|---|
| **on** | Allume |
| **off** | Eteint |
| **Etat** | Affiche l'etat actuel (allume/eteint) |

### Position : comment ca marche ?

La position suit le standard Somfy :

- **0%** = completement **ouvert** (volet releve)
- **100%** = completement **ferme** (volet baisse)

Par exemple, pour fermer un volet a mi-hauteur, positionnez le curseur sur **50**.

### Utilisation dans les scenarios

Vous pouvez utiliser toutes les commandes de ce plugin dans vos scenarios Jeedom. Par exemple :

- Fermer tous les volets a la tombee de la nuit
- Ouvrir les volets du salon le matin a 7h
- Positionner un store a 50% quand la temperature depasse 25C

---

## Mise a jour des etats

Le plugin interroge automatiquement les serveurs Somfy toutes les **5 minutes** pour mettre a jour l'etat de vos equipements (position des volets, etat des lumieres, etc.).

> **Note sur les appareils RTS :** les equipements utilisant le protocole RTS (telecommandes blanches Somfy classiques) sont unidirectionnels. Le plugin peut leur envoyer des commandes, mais ne peut pas connaitre leur etat actuel. Seuls les equipements **IO Homecontrol** (telecommandes blanches avec retour d'information) remontent leur position.

---

## Depannage

### "Echec de connexion Overkiz"

- Verifiez que votre email et mot de passe sont corrects (testez-les dans l'appli TaHoma)
- Verifiez que vous avez selectionne le bon serveur (Europe pour la France)
- Verifiez que votre serveur Jeedom a acces a internet

### Aucun equipement trouve

- Verifiez que vos equipements apparaissent bien dans l'application TaHoma sur votre telephone
- Re-cliquez sur **Synchroniser les equipements**

### Les commandes ne fonctionnent pas

- Verifiez que l'equipement est active (coche **Activer** cochee)
- Consultez les logs : **Analyse > Logs > somfycloud**
- Il peut y avoir un delai de quelques secondes entre l'envoi de la commande et l'execution

### Les etats ne se mettent pas a jour

- Les etats sont rafraichis toutes les 5 minutes automatiquement
- Les appareils RTS ne remontent jamais d'etat (c'est une limitation du protocole, pas du plugin)
- Consultez les logs pour voir si le polling fonctionne correctement

---

## FAQ

**Le plugin fonctionne-t-il avec la Connexoon ?**
Oui, c'est son usage principal. La Connexoon ne supportant pas l'API locale, ce plugin utilise l'API cloud.

**Puis-je utiliser ce plugin avec une TaHoma ?**
Oui, l'API cloud Overkiz fonctionne aussi avec la TaHoma et la TaHoma Switch. Mais si votre TaHoma supporte l'API locale, privilegiez un plugin local.

**Mes volets ne remontent pas d'etat ?**
Si vos volets sont en protocole RTS, ils ne supportent pas le retour d'etat. C'est une limitation materielle, pas un probleme du plugin.

**Combien d'equipements puis-je controler ?**
Autant que vous en avez dans l'application TaHoma. Le plugin importe tous les equipements visibles.

**Le plugin envoie-t-il mon mot de passe en clair ?**
Non, le mot de passe est stocke de maniere chiffree dans Jeedom et transmis aux serveurs Somfy via HTTPS (connexion chiffree).

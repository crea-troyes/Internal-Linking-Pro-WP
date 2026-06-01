# Notice d'utilisation - ILP - Internal Linking Pro

## 1. Présentation

**ILP - Internal Linking Pro** est une extension WordPress d'audit du maillage interne. Elle analyse les articles et les pages publiés afin d'identifier les contenus peu reliés, les opportunités de liens internes, les silos éditoriaux et les risques de cannibalisation SEO.

L'extension fonctionne localement dans WordPress : aucune donnée n'est envoyée vers un service externe. L'analyse principale est lancée manuellement et son résultat est mis en cache. Elle n'ajoute donc aucun traitement aux pages publiques du site.

L'écran principal est accessible dans l'administration WordPress depuis :

`Outils > Internal Linking`

L'accès à cet écran et le lancement des scans sont réservés aux utilisateurs disposant du droit WordPress `manage_options`, généralement les administrateurs.

## 2. Liste exhaustive des fonctionnalités

### Audit général

1. Scan manuel des articles et pages publiés.
2. Mise en cache du dernier audit afin d'éviter tout impact sur les performances du site public.
3. Suppression manuelle du cache d'analyse.
4. Filtrage des résultats par articles, pages ou articles et pages.
5. Affichage de la date et de l'heure du dernier scan.
6. Exclusion manuelle de contenus par identifiant WordPress.

### Analyse du maillage

7. Tableau de bord synthétique avec score global de maillage interne.
8. Comptage des contenus analysés.
9. Comptage des liens internes et externes.
10. Calcul du nombre moyen de liens internes par contenu.
11. Calcul de la densité de liens internes pour 100 mots.
12. Classement des contenus recevant le plus de liens internes.
13. Tableau détaillé de tous les contenus analysés.
14. Recherche par titre dans les tableaux.
15. Tri des tableaux par colonne.
16. Comptage des liens internes entrants.
17. Comptage des liens internes sortants.
18. Comptage des liens externes sortants.
19. Calcul d'un PageRank interne normalisé sur 100.
20. Calcul d'un score individuel de maillage interne.

### Diagnostic SEO

21. Détection des articles isolés.
22. Détection des contenus orphelins globaux.
23. Détection technique des contenus sans lien sortant.
24. Suggestions de liens internes à ajouter entre contenus proches.
25. Détection des clusters et pages piliers.
26. Analyse des silos SEO : cohérence, rétention et fuite.
27. Recommandations d'amélioration pour chaque silo.
28. Détection des risques de cannibalisation SEO entre articles.
29. Classement des conflits par sévérité.
30. Recommandations de traitement des conflits.

### Cartographie et édition

31. Cartographie interactive du réseau de liens internes.
32. Mise en évidence des pages piliers, clusters et articles isolés dans le graphe.
33. Fiche SEO détaillée au clic sur un noeud du graphe.
34. Panneau de suggestions directement dans l'éditeur Gutenberg.
35. Suggestions basées sur le texte sélectionné, le titre, le slug et le contexte éditorial.
36. Proposition d'ancres de lien.
37. Insertion d'un lien dans le texte sélectionné depuis Gutenberg.
38. Détection d'opportunités de liens pendant la rédaction.

## 3. Installation et premier scan

1. Installez et activez l'extension dans WordPress.
2. Ouvrez `Outils > Internal Linking`.
3. Cliquez sur **Run scan**.
4. Attendez la fin de l'analyse sans fermer la page.
5. La page se recharge automatiquement lorsque le scan est terminé.

Le scan analyse uniquement les contenus :

- de type **article** (`post`) ou **page** (`page`) ;
- dont le statut est **publié** ;
- qui ne figurent pas dans la liste des exclusions.

Les liens internes absolus et relatifs sont pris en compte. Les liens vers une ancre seule (`#...`), les liens `mailto:` et les liens `tel:` sont ignorés.

## 4. Commandes communes

Ces commandes apparaissent au-dessus des résultats dans tous les onglets d'analyse, sauf **Settings**.

### Display filter

Le filtre d'affichage propose trois choix :

- **Posts** : affiche uniquement les articles ;
- **Pages** : affiche uniquement les pages ;
- **Posts + Pages** : affiche les deux types de contenus.

Le filtre agit sur les résultats affichés dans les onglets compatibles. Il ne relance pas le scan.

### Run scan

Le bouton **Run scan** relance l'audit complet. Utilisez-le après des modifications éditoriales importantes, l'ajout de liens internes, la publication de nouveaux contenus ou la modification des exclusions.

### Clear cache

Le bouton **Clear cache** supprime les résultats enregistrés. Une confirmation est demandée. Après suppression, les onglets d'analyse restent vides jusqu'au prochain scan.

## 5. Comprendre les indicateurs

### Liens entrants internes

Nombre de contenus analysés qui pointent vers la page ou l'article concerné. Un contenu sans lien entrant global est considéré comme **orphelin global**.

### Liens sortants internes

Nombre de contenus analysés vers lesquels la page ou l'article envoie au moins un lien. Plusieurs liens identiques depuis un même contenu vers la même cible ne gonflent pas ce compteur.

### Liens sortants externes

Nombre de liens HTTP sortants vers un autre domaine.

### Liens internes pour 100 mots

Densité de liens internes sortants rapportée à la longueur du contenu :

`nombre de liens internes sortants / nombre de mots x 100`

Dans le tableau, l'aide intégrée indique une zone de référence de **0,8 à 1,5 lien pour 100 mots**. Cette valeur reste un indicateur : la pertinence éditoriale prime sur l'ajout artificiel de liens.

### PageRank interne

Le PageRank interne estime l'importance relative d'un contenu dans le réseau de liens du site. Il est normalisé sur une échelle de 0 à 100 : le contenu le plus fort reçoit 100, les autres sont comparés à cette référence.

### Score individuel de maillage interne

Le score individuel, sur 100, dépend :

- du nombre de liens entrants, pour un maximum de 60 points ;
- du nombre de liens sortants, pour un maximum de 40 points ;
- d'une pénalité de 20 points pour les articles isolés.

Les couleurs du tableau facilitent la lecture :

- rouge : score inférieur à 41 ;
- orange : score de 41 à 75 ;
- vert : score de 76 à 100.

### Article isolé et contenu orphelin : quelle différence ?

Un **article isolé** est un article qui ne reçoit aucun lien depuis un autre article. Il peut toutefois recevoir un lien depuis une page.

Un **contenu orphelin global** est un article ou une page qui ne reçoit aucun lien interne depuis les contenus analysés. Le diagnostic est donc plus large.

## 6. Onglet Dashboard

L'onglet **Dashboard** fournit une vue d'ensemble de l'état du maillage interne.

### Score global

Le cadran **Internal linking score** affiche une note globale sur 100. Ce score prend en compte :

- la proportion de contenus disposant de liens entrants ;
- la proportion de contenus disposant de liens sortants ;
- la profondeur moyenne des liens entrants et sortants ;
- la proportion d'articles non isolés ;
- la proportion de contenus ayant à la fois des liens entrants et sortants ;
- la densité globale de liens internes pour 100 mots.

### Analyzed content

Ce bloc affiche le nombre total de contenus analysés et répartit les contenus entre :

- **Correct** : contenus avec au moins un lien entrant et un lien sortant ;
- **Isolated** : articles sans lien entrant depuis un autre article ;
- **Without outgoing internal links** : contenus sans lien interne sortant.

### Most linked

Liste des contenus recevant le plus de liens internes. Utilisez-la pour repérer les pages déjà fortes et vérifier si elles correspondent bien à vos priorités SEO.

### Links suggestions

Affiche le nombre total d'opportunités détectées et les cinq premières suggestions. Le bouton **View opportunities** ouvre l'onglet complet **Link suggestions**.

### Statistiques principales

Le tableau de bord affiche également :

- les contenus orphelins ;
- les contenus sans lien interne sortant ;
- les articles isolés ;
- le nombre de clusters ;
- le nombre moyen de liens internes par contenu ;
- le total des liens internes ;
- le total des liens externes ;
- la densité globale de liens internes pour 100 mots.

### Liste des clusters

Le tableau des clusters affiche :

- la page pilier ;
- le nombre de contenus reliés à cette page pilier ;
- un score de cluster ;
- un bouton **Details**.

Le détail liste jusqu'à 30 contenus du cluster et les ancres utilisées pour pointer vers la page pilier. Les colonnes peuvent être triées.

## 7. Onglet Table

L'onglet **Table** présente la liste détaillée des contenus correspondant au filtre courant.

Chaque ligne indique :

- le type de contenu ;
- le titre et l'URL ;
- les liens internes entrants ;
- les liens internes sortants ;
- les liens externes sortants ;
- la densité de liens internes pour 100 mots ;
- le PageRank interne ;
- le score individuel de maillage interne.

La zone **Search content...** filtre les lignes à partir des mots présents dans le titre. Cliquez sur un en-tête de colonne pour trier le tableau. Cliquez sur l'URL d'un contenu pour l'ouvrir dans un nouvel onglet.

## 8. Onglet Isolated posts

L'onglet **Isolated posts** liste uniquement les articles qui ne reçoivent aucun lien depuis un autre article.

Le tableau sépare :

- **Incoming links (posts)** : toujours égal à zéro dans cette vue ;
- **Incoming links (pages)** : nombre de liens reçus depuis des pages.

Cet onglet sert à réintégrer les articles dans le parcours éditorial. Commencez par les articles stratégiques et ajoutez des liens contextuels depuis des articles connexes.

Le filtre **Pages** ne retourne aucun résultat dans cet onglet, car la notion d'article isolé ne s'applique ici qu'aux articles.

## 9. Onglet Global orphans

L'onglet **Global orphans** liste les articles et pages sans aucun lien interne entrant.

Un contenu présent dans cette liste est difficile à découvrir depuis les autres contenus analysés. Vérifiez :

- s'il doit réellement rester accessible ;
- s'il mérite un lien depuis une page pilier ;
- s'il doit être relié depuis plusieurs contenus complémentaires ;
- s'il est obsolète et doit être fusionné, redirigé ou exclu de l'analyse.

## 10. Onglet Link suggestions

L'onglet **Link suggestions** propose des liens internes à créer entre contenus proches.

Pour chaque suggestion, le tableau affiche :

- le contenu source (**From**) ;
- le contenu cible (**To**) ;
- leur URL ;
- un score de pertinence (**Relevance**).

Le score repose sur la proximité des titres. Une suggestion n'est affichée que si :

- les deux contenus sont du même type : article vers article ou page vers page ;
- le lien n'existe pas déjà ;
- la similarité des titres dépasse 60 %.

Les suggestions sont classées par pertinence décroissante et limitées aux 50 meilleures opportunités. Validez toujours la pertinence éditoriale avant d'ajouter un lien.

## 11. Onglet Conflicts

L'onglet **Conflicts** détecte les articles susceptibles de viser une intention de recherche trop proche et de se concurrencer dans Google.

### Résumé

La partie supérieure affiche :

- le nombre total de conflits ;
- les conflits **Critical**, **Medium** et **Light** ;
- le nombre de contenus affectés ;
- le cluster le plus concerné ;
- l'expression la plus souvent impliquée ;
- la date de l'analyse utilisée.

### Filtres

Les boutons **All**, **Critical**, **Medium** et **Light** filtrent les conflits affichés.

### Tableau

Chaque conflit indique :

- sa sévérité ;
- l'expression ou le thème concerné ;
- le nombre de contenus affectés ;
- un score de similarité ;
- les types de signaux détectés ;
- une recommandation ;
- un bouton **Details**.

### Détail d'un conflit

Le bouton **Details** affiche :

- le contenu principal suggéré ;
- le contenu concurrent ;
- la dernière date de mise à jour ;
- le score interne, les liens entrants et les liens sortants ;
- le cluster éventuel ;
- les expressions et ancres communes ;
- une carte simplifiée ;
- l'explication du risque ;
- l'action recommandée.

### Calcul et interprétation

Le score de conflit combine notamment la similarité des titres, la similarité des slugs, les ancres communes, les expressions communes, l'appartenance à un même cluster et les sources de liens communes.

Les niveaux sont :

- **Critical** : score supérieur ou égal à 75 ;
- **Medium** : score de 50 à 74 ;
- **Light** : score de 30 à 49.

Le bouton **Recalculate conflicts** force le recalcul de cette analyse. Utilisez-le après avoir fusionné des contenus, modifié leurs angles éditoriaux, ajusté les ancres ou créé des redirections.

Cette fonctionnalité est une aide à la décision. Deux articles proches ne sont pas nécessairement en concurrence si leurs intentions de recherche sont clairement différentes.

## 12. Onglet Silos

L'onglet **Silos** vérifie si les contenus sont organisés autour de pages piliers cohérentes.

Un silo est détecté autour d'une page recevant suffisamment de liens internes. Le seuil minimal est configurable dans **Settings**. Un seuil dynamique minimal s'applique également selon la taille du site.

### Colonnes

- **Silo** : titre et URL de la page pilier ;
- **Pages** : nombre de contenus rattachés ;
- **Coherence** : solidité du parcours interne ;
- **Leakage** : part des liens internes envoyés hors du silo ;
- **Issues** : problèmes détectés ;
- **Recommendations** : améliorations conseillées.

### Cohérence

La cohérence combine :

- la rétention des liens dans le silo ;
- la présence de liens internes depuis les contenus membres ;
- les liens latéraux entre contenus complémentaires ;
- la redistribution des liens depuis la page pilier vers les contenus membres.

### Fuite

La fuite mesure uniquement les liens internes envoyés vers d'autres silos. Les liens externes sont diagnostiqués séparément.

Repères visuels :

- fuite inférieure ou égale à 15 % : satisfaisante ;
- fuite de 16 à 30 % : à surveiller ;
- fuite supérieure à 30 % : élevée.

Les recommandations peuvent inviter à renforcer les liens vers la page pilier, créer des liens latéraux, limiter des sorties vers d'autres sujets ou équilibrer les liens externes.

## 13. Onglet Graph view

L'onglet **Graph view** affiche une carte interactive du réseau de liens internes.

### Utilisation

- faites glisser la carte pour vous déplacer ;
- utilisez la molette ou les commandes de navigation pour zoomer ;
- cliquez sur un noeud pour afficher ses voisins directs et sa fiche SEO ;
- double-cliquez dans le graphe pour réinitialiser la vue ;
- cliquez dans une zone vide pour enlever la mise en évidence.

### Lecture visuelle

- cercle jaune : page pilier ;
- noeud rouge : article isolé ;
- noeud vert : page hors cluster ;
- noeud bleu : article hors cluster ;
- contour coloré : contenu rattaché à un cluster ;
- flèche : sens du lien interne.

La taille d'un noeud augmente avec son nombre de liens entrants. La fiche latérale précise le type de contenu, les liens entrants et sortants, le rôle éventuel de page pilier, le cluster, le score du cluster et l'URL du contenu.

## 14. Onglet Settings

L'onglet **Settings** permet de régler les exclusions et la détection des clusters.

### Excluded content

Saisissez les identifiants WordPress des contenus à ignorer. Les séparateurs acceptés sont les virgules, espaces, points-virgules et retours à la ligne.

Exemple :

```text
12, 45, 78
```

Cas d'usage :

- mentions légales ;
- pages temporaires ;
- campagnes sponsorisées ;
- contenus qui ne doivent pas participer à la stratégie de maillage.

Les exclusions sont appliquées aux résultats existants dès l'enregistrement. Relancez néanmoins un scan pour disposer d'un cache entièrement à jour après une modification importante.

### Cluster settings

Le champ **Minimum incoming links** définit le nombre minimal de liens entrants requis pour qu'un contenu soit considéré comme une page pilier potentielle.

Repères affichés dans l'interface :

- `3` : cluster faible ;
- `8` à `10` : cluster fort.

La valeur acceptée est comprise entre `1` et `100`. Cliquez sur **Save settings** pour enregistrer les modifications.

## 15. Panneau Gutenberg

L'extension ajoute un panneau **Internal linking suggestions** dans l'éditeur de blocs Gutenberg pour les articles et les pages.

### Suggestions générales

Lorsque vous rédigez un contenu, le panneau analyse le titre, le slug, les titres de sections et le texte. Il propose jusqu'à cinq contenus pertinents à relier.

Chaque suggestion affiche :

- le titre et l'URL de la cible ;
- un score ;
- des badges expliquant la relation détectée ;
- une justification ;
- des ancres suggérées ;
- un bouton **Insert link**.

### Suggestions à partir d'une sélection

Pour obtenir une suggestion plus précise :

1. sélectionnez une expression dans un paragraphe ou un titre ;
2. attendez l'actualisation du panneau ;
3. vérifiez l'expression indiquée sous **Analyzed selection** ;
4. choisissez une suggestion ;
5. cliquez sur une ancre proposée ou sur **Insert link**.

L'extension tente d'insérer le lien directement sur le texte sélectionné. Si la sélection a été perdue, sélectionnez à nouveau le texte et réessayez.

### Données utilisées

Les suggestions Gutenberg fonctionnent même sans scan récent grâce au catalogue des contenus publiés. Un scan du tableau de bord enrichit cependant les recommandations avec les données du graphe : liens entrants, liens sortants, PageRank et clusters.

## 16. Vue technique sans lien sortant

Le code de l'extension contient une vue supplémentaire nommée `dead_end`. Elle liste les contenus sans aucun lien sortant interne ou externe.

Cette vue n'a actuellement pas d'onglet visible dans la barre de navigation. Elle peut être ouverte manuellement avec une URL de ce type :

```text
/wp-admin/tools.php?page=cma-maillage&view=dead_end
```

Cette vue technique complète le compteur **Without outgoing links** du tableau de bord, mais son critère est plus strict : elle exige l'absence de lien interne **et** externe.

## 17. Routine de travail recommandée

1. Lancez un scan après une phase de publication ou de refonte.
2. Consultez le **Dashboard** pour identifier les tendances générales.
3. Traitez les **Global orphans** et les **Isolated posts** prioritaires.
4. Vérifiez les contenus faibles dans **Table**.
5. Ajoutez les opportunités pertinentes depuis **Link suggestions**.
6. Corrigez les problèmes structurels signalés dans **Silos**.
7. Examinez les conflits **Critical** puis **Medium** dans **Conflicts**.
8. Utilisez **Graph view** pour valider la structure globale.
9. Exploitez le panneau Gutenberg lors de la rédaction de nouveaux contenus.
10. Relancez le scan pour mesurer les effets des corrections.

## 18. Limites à connaître

- Les métriques portent uniquement sur les articles et pages publiés.
- Les types de contenus personnalisés ne sont pas analysés.
- Les suggestions automatiques doivent être validées éditorialement.
- Un score élevé ne garantit pas une meilleure performance SEO.
- Un lien utile et naturel vaut mieux qu'un ajout artificiel destiné uniquement à augmenter un indicateur.
- L'extension détecte des risques de cannibalisation, pas les positions réelles dans Google.
- Les modifications réalisées après le dernier scan ne sont pleinement intégrées au tableau de bord qu'après un nouveau scan.


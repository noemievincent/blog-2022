# Le blog sans DB

Votre tâche est de réaliser un blog alimenté par des fichiers au format json.

Bien que pas idéale, la solution est réalisable avec les outils que vous avez vu jusqu’ici.

Les articles du blog doivent être stockés dans des fichiers *json*, un par article.

Chaque article est donc un objet *json* avec un titre, un texte, une date de publication qui correspond à sa date d’enregistrement, un nom d’auteur et un nom de catégorie.

Vous devez charger tous les articles dans la mémoire à chaque requête. C’est une mauvaise idée et on ne fait pas ça dans la vraie vie, car les performances découlant d’une telle stratégie se dégraderont très vite. Mais pour l’exercice, vous procéderez ainsi. Votre objectif est de vous débrouiller pour récupérer les contenus à partir des objets *json* chargés en mémoire. 

Le nom du fichier est un identifiant arbitraire, unique.

## Créer un post

Le blog a une petite fonction d’administration qui lui permet de créer un nouvel article, toujours écrit par le même auteur (un auteur est fictivement connecté, ce qui se manifeste par la présence de son nom dans la barre de navigation), mais avec la possibilité de choisir une catégorie parmi celles déjà présentes dans les articles déjà postés.

Les informations de l’auteur à savoir son nom et son avatar, sont codés en dur. Reprenez un des auteurs des fichiers *json* fournis, au choix.

Le formulaire doit être validé : les champs contenant le titre de l’article, l’excerpt et le corps de l’article doivent être présents. Le titre aura une taille comprise entre 5 et 100 caractères unicode, l’excerpt entre 20 et 200, le corps, entre 100 et 1000. 

La catégorie doit être une des valeurs existantes.

La stratégie de validation sera de stocker les erreurs de validation dans une variable de session, de rediriger vers le formulaire (avec l’entête `Location: une-adresse.php` ) et de vérifier dans celui-ci si des erreurs existent dans la session. Si c’est le cas, prévoyez de les afficher là où il est intéressant de les afficher, près des champs concernés. Et n’oubliez pas de repeupler le formulaire avec les données en question.

La navigation est un fragment de vue (un *partial* comme dit au cours) proposé en deux versions, *connected* et *not-connected*. Le menu secondaire sur la droite (*authors*, *categories*, *recent posts*) est également un fragment de vue.

Le select au-dessus de la liste des articles est également un partiel.

Toutes les requêtes HTTP pointent vers /index.php. Pour distinguer entre les trois pages prévues dans le site (index des articles, vue d’un seul article, formulaire), vous devrez passer un argument dans la *query-string*. Celui-ci se nommera `action` et prendra les valeurs `index` pour la liste des posts, `show` pour un post unique, `create` pour le formulaire de création, et `store` pour l’action d’enregistrer.

Le menu sur le côté permet permet de filtrer les articles dans l’index des articles. La pagination filtre également les articles.  Définissez une pagination de 5 articles par page. Le select au dessus de la liste des articles permet de les trier pour afficher les plus récents d’abord ou les plus anciens d’abord. Les filtres et les tris ne se combinent pas. Quand l’un est appliqué, les autres ne sont pas contrôlés.
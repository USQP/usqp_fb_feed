![GitHub Banner](https://unsitequipeps.fr/wp-content/uploads/2025/02/github_banner-900x202.jpg)

# USQP Facebook Feed Documentation

USQP Facebook Feed est une extension WordPress permettant d'afficher un feed Facebook sur votre site en utilisant l'API Graph de Facebook. Elle offre une gestion simplifiée du token d'accès, un système de cache performant, et une personnalisation avancée du rendu via un widget Elementor et une intégration CSS frontend complètement personnalisable.

Pour plus d'informations, consultez la [page de l'extension](https://unsitequipeps.fr/blog/usqp_fb_feed/).

---

## 📌 Fonctionnalités principales

### ✅ Gestion du token d'accès
- Connexion et génération automatique du token.
- Conversion en token longue durée.
- Renouvellement automatique toutes les 4 semaines.
- Possibilité de régénérer manuellement le token à tout moment.

### ✅ Gestion du cache
- Configuration de la fréquence de mise à jour : minute, heure, jour, semaine (par défaut : 1 heure).
- Filtrage des contenus affichés : vous pouvez spécifier les types de publications à afficher.
- Suppression et mise à jour manuelle du cache pour un rafraîchissement instantané.

### ✅ Intégration frontend
- Personnalisation complète du CSS via le panneau d’administration pour que le feed s'adapte parfaitement au design de votre site.
- Générateur de shortcode pour une intégration facile dans vos pages et articles.

### ✅ Intégration Elementor
- Widget dédié entièrement personnalisable pour afficher le feed Facebook directement avec Elementor, facilitant ainsi l'intégration dans vos pages créées avec cet outil.

---

## 📥 Installation

### Création de l'Application dans l'API Graph de Facebook

Avant de pouvoir utiliser l'extension USQP Facebook Feed, il est nécessaire de créer une application Facebook via la plateforme Meta for Developers pour obtenir un token d'accès valide.

#### Étapes pour créer une application sur Meta for Developers :
1. Allez sur [Meta for Developers](https://developers.facebook.com/).
2. Connectez-vous à votre compte Facebook si ce n'est pas déjà fait.
3. Dans le menu principal, cliquez sur "Mes applications", puis "Créer une application".
4. Sélectionnez "Application pour la gestion d'une entreprise" ou "Application pour un site web" selon votre besoin, puis cliquez sur "Suivant".
5. Remplissez les informations requises, notamment le nom de l'application, l'adresse email de contact, et cliquez sur "Créer l'application".
6. Vous serez redirigé vers le tableau de bord de l'application que vous venez de créer.

#### Configuration de l’application :
- Ajoutez votre App ID et votre Clé secrète dans le fichier `.env` à la base de l’extension. Vous pourrez trouver ces informations dans la section "Paramètres" de votre application sur le tableau de bord de Meta for Developers.

    ```
    FACEBOOK_APP_ID=your_app_id
    FACEBOOK_APP_SECRET=your_app_secret
    ```

- Ajoutez l'URL de votre site Web dans la section "Paramètres > Basique" de votre application, ainsi que dans "Produits > Facebook Login > Paramètres" pour éviter toute erreur d'authentification lors de la génération du token d’accès.
- Ensuite, dans "Produits > Facebook Login > Paramètres", vous trouverez une option pour activer le SDK JavaScript. Activez cette option pour permettre à votre site d'utiliser le SDK afin de connecter les utilisateurs avec leurs identifiants Facebook.

Pour que l'application puisse récupérer et afficher le feed Facebook, elle doit disposer des autorisations suivantes dans l'API Graph de Facebook :
- `pages_read_engagement` : Nécessaire pour lire les publications de la page.
- `pages_read_user_content` : Permet d'accéder au contenu publié par les utilisateurs sur la page.
- `pages_show_list` : Permet de lister les pages administrées par l'utilisateur.
- `pages_manage_metadata` : Utile pour gérer les informations des pages et obtenir un token valide.

---

### Installation de l'extension USQP Facebook Feed

Une fois l’application Facebook configurée, vous pouvez procéder à l’installation de l'extension USQP Facebook Feed sur votre site WordPress.

#### Étapes d'installation :
1. Téléchargez l'extension.
2. Ajoutez l'extension sur WordPress :
     - Allez dans votre tableau de bord WordPress.
     - Naviguez vers "Extensions" > "Ajouter".
     - Cliquez sur "Téléverser une extension" et sélectionnez le fichier ZIP de l’extension USQP Facebook Feed.
     - Une fois le fichier téléchargé, cliquez sur "Installer maintenant" puis "Activer" l'extension.

---

## Configuration de l'extension USQP Facebook Feed sur WordPress

3. **Gestion du Token d'Accès :**
     - **Objectif** : Permet de se connecter à l'API Graph de Facebook et de gérer le token d’accès.
     - **Options disponibles** :
         - Connexion et génération automatique du token : Entrez votre App ID et App Secret pour générer le token d'accès.
         - Renouvellement automatique : Le token sera automatiquement renouvelé toutes les 4 semaines.
         - Régénération manuelle : Vous pouvez régénérer manuellement le token à tout moment.
         - Déconnexion : La déconnexion supprime le token d'accès et réinitialise le cache.

4. **Gestion du Cache :**
     - **Objectif** : Permet de configurer le comportement du cache pour améliorer les performances et la gestion du contenu affiché.
     - **Options disponibles** :
         - Fréquence de mise à jour : Choisissez la fréquence de mise à jour du cache : minute, heure, jour, ou semaine (par défaut : 1 heure).
         - Filtrage du contenu : Sélectionnez les types de contenu à afficher.
         - Supprimez ou mettez à jour manuellement le cache pour rafraîchir les données.

5. **Intégration Frontend :**
     - **Objectif** : Permet de personnaliser l'apparence du feed Facebook sur votre site.
     - **Options disponibles** :
         - Ajoutez du code CSS personnalisé pour ajuster l'apparence du feed.
         - Utilisez un générateur de shortcode pour intégrer facilement le feed dans vos pages ou articles.

6. **Intégration Elementor :**
     - **Objectif** : Ajoutez facilement le feed Facebook à vos pages créées avec Elementor.
     - **Options disponibles** :
         - Widget personnalisable : Le widget USQP Facebook Feed est entièrement personnalisable pour s'adapter à votre design.

---

## 📞 Support

Si vous avez des questions ou rencontrez des problèmes, ouvrez une issue sur GitHub

💙 Merci d'utiliser **USQP Facebook Feed** ! N'hésitez pas à contribuer et à partager vos retours. 🚀

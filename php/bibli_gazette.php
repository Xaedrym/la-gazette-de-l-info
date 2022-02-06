<?php

    /*********************************************************
     *        Bibliothèque de fonctions spécifiques          *
     *               à l'application Gazette                 *
     *********************************************************/

    define ('BD_SERVER' , 'localhost'); // nom d'hôte ou adresse IP du serveur MySQL
    define ('BD_NAME' , 'gazette_bd'); // nom de la base sur le serveur MySQL
    define ('BD_USER' , 'root'); // nom de l'utilisateur de la base
    define ('BD_PASS' , ''); // mot de passe de l'utilisateur de la base

    /* define ('BD_SERVER' , 'localhost'); // nom d'hôte ou adresse IP du serveur MySQL
    define ('BD_NAME' , 'rollet_gazette'); // nom de la base sur le serveur MySQL
    define ('BD_USER' , 'rollet_u'); // nom de l'utilisateur de la base
    define ('BD_PASS' , 'rollet_p'); // mot de passe de l'utilisateur de la base */

    // longueurs minimale et maximale du pseudo
    define('LMIN_PSEUDO', 4);
    define('LMAX_PSEUDO', 20); //taille du champ usPseudo de la table users

    // longueur minimale du mot de passe
    define('LMIN_PASSWORD', 4);

    // longueurs minimale et maximale du nom
    define('LMAX_NOM', 50); //taille du champ usNom de la table users
    define('LMAX_PRENOM', 60);  //taille du champ usPrenom de la table users

    define('LMAX_EMAIL', 255); //taille du champ usMail de la table users

    define('NB_ANNEE_DATE_NAISSANCE', 100);

    //Definition du décalage horaire 
    date_default_timezone_set('Europe/Paris');

    //____________________________________________________________________________
    /**
     * Fonction affichant l'entête d'une page php.
     *
     * A appeler impérativement en début de page php.
     *
     * @param string	$pre	Prefixe pour les images
     * @param string	$css	Page CSS a lier
     */
    function tr_aff_head_nav($pre,$css) {
        echo '<!doctype html>',
        '<html lang="fr">',

        '<head>',
            '<meta charset="UTF-8">',
            '<title>La gazette de L-INFO</title>',
            '<link rel="stylesheet" type="text/css" href="',$css,'">',
        '</head>',

        '<body>',
            '<nav>',
                '<ul>',
                    '<li><a href="',$pre,'/index.php">Accueil</a></li>',
                    '<li><a href="',$pre,'/php/actus.php">Toute l\'actu</a></li>',
                    '<li><a href="',$pre,'/php/recherche.php">Recherche</a></li>',
                    '<li><a href="',$pre,'/php/redaction.php">La rédac\'</a></li>',
                    '<li><a href="#">Se connecter</a>',
                '</ul>',
            '</nav>';
    }

    //________________________________PROF____________________________
    /**
     *  Affichage du début de la page (jusqu'au tag ouvrant de l'élément body)
     *
     *
     *  @param  string  $title      Le titre de la page (<head>)
     *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
     *  @param  array   $css        Le nom de la feuille de style à inclure
     */
    function em_aff_debut($title = '', $prefix='..', $css = 'gazette.css') {
        
        echo 
            '<!doctype html>', 
            '<html lang="fr">',
                '<head>',   
                    '<meta charset="UTF-8">',
                    '<title>La gazette de L-INFO', ($title != '') ? ' | ' : '', $title, '</title>',
                    $css != '' ? "<link rel='stylesheet' type='text/css' href='{$prefix}/styles/{$css}'>" : '',
                '</head>',
                '<body>';
    }
        
    //_____________________________PROF______________________________
    /**
     *  Affiche le code du menu de navigation. 
     *
     *  @param  string  $pseudo     chaine vide quand l'utilisateur n'est pas authentifié
     *  @param  array   $droits     Droits rédacteur à l'indice 0, et administrateur à l'indice 1  
     *  @param  String  $prefix     le préfix du chemin relatif vers la racine du site 
     */
    function em_aff_menu($pseudo='', $droits = array(false, false), $prefix = '..') {
        
        echo '<nav><ul>',
                '<li><a href="', $prefix, '/index.php">Accueil</a></li>',
                '<li><a href="', $prefix, '/php/actus.php">Toute l\'actu</a></li>',
                '<li><a href="', $prefix, '/php/recherche.php">Recherche</a></li>',
                '<li><a href="', $prefix, '/php/redaction.php">La rédac\'</a></li>', 
                '<li>';
        
        // dernier item du menu ("se connecter" ou sous-menu)
        if ($pseudo) {
            echo '<a href="#">', $pseudo, '</a>', 
                    '<ul>', 
                        '<li><a href="', $prefix, '/php/compte.php">Mon profil</a></li>',
                        $droits[0] ? "<li><a href=\"{$prefix}/php/nouveau.php\">Nouvel article</a></li>" : '',
                        $droits[1] ? "<li><a href=\"{$prefix}/php/administration.php\">Administration</a></li>" : '',
                        '<li><a href="', $prefix, '/php/deconnexion.php">Se déconnecter</a></li>', 
                    '</ul>';
        }
        else {
            echo '<a href="', $prefix, '/php/connexion.php">Se connecter</a>';
        }
                
        echo '</li></ul></nav>';
    }

    //____________________________________________________________________________
    /**
     * Fonction affichant le "header" d'une page php.
     *
     * A appeler impérativement en début de page php après le menu de navigation.
     *
     * @param string	$titre	Titre de la page
     * @param string	$ID	    ID du main
     */
    function tr_aff_header($titre,$pre='..',$ID=null) {
        echo '<header>',
            '<img src="',$pre,'/images/titre.png" alt="La gazette de L-INFO" width="780" height="83">',
            '<h1>',$titre,'</h1>',
        '</header>',
        (($ID != null) ? "<main id='$ID'>" : '<main>');
    }

     //____________________________PROF________________________________
    /**
     *  Affichage de l'élément header
     *
     *  @param  string  $h1         Le titre dans le bandeau (<header>)
     *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
     */
    function em_aff_header($h1, $prefix='..'){             
        echo '<header>', 
                '<img src="', $prefix, '/images/titre.png" alt="La gazette de L-INFO" width="780" height="83">',
                '<h1>', $h1, '</h1>',
            '</header>';
    }

    //____________________________________________________________________________
    /**
     * Fonction affichant le pied d'une page php.
     *
     * A appeler impérativement en fin de page php.
     */
    function tr_aff_footer(){
        echo '</main>',
            '<footer>&copy; Licence Informatique - Janvier 2020 - Tous droits réservés</footer>',
            '</body>',
        '</html>';
    }

    //____________________________________________________________________________
    /**
     * Fonction affichant une section dans une page php.
     *
     * A appeler lors de l'affichage d'une vignette dans une section.
     */
    function tr_aff_vignette($tab) {
        echo '<a href="./php/article.php?id=',$tab['ID'],'">',
            '<img src="upload/',$tab['ID'],'.jpg" alt="',$tab['alt'],'"><br>',
            $tab['titre'],
            '</a>';
    }

    //____________________________________________________________________________
    /**
     * Fonction affichant une section dans une page php.
     *
     * A appeler lors de l'affichage d'une section.
     * 
     * @param string	$titre	Titre de la section
     * @param string	$contenu    Contenu de la section
     * @param string	$classe    Nom de la classe s'il y en a, sinon rien
     */
    function tr_aff_section($titre,$contenu,$classe=null) {
        echo
        (($classe != null) ? "<section class='$classe'>" : '<section>'),
        '<h2>',$titre,'</h2>';
        foreach($contenu as $key => $valeur){
            tr_aff_vignette($valeur);
        }
        echo '</section>';
    }

    //_______________________________________________________________
    /**
     *  Affchage d'un message d'erreur dans une zone dédiée de la page.
     *  @param  String  $msg    le message d'erreur à afficher.
     */
    function tr_aff_erreur($msg) {
        echo    '<section>', 
                    '<h2>Oups, il y a une erreur...</h2>',
                    '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :</p>',
                    '<blockquote>', $msg, '</blockquote>', 
                '</section>';
    }

    //_______________________________________________________________
    /**
     *  Affchage du résumé de l'article dans une session.
     *  @param  String  $msg    le message d'erreur à afficher.
     */
    function tr_aff_article($id,$titre,$resume,$date) {
        echo    '<article class="resume">',
                        '<img width="160" src="',em_url_image_illustration($id),'" alt="Photo d\'illustration | ', $titre, '">',
                        '<h3>',$titre,'</h3>',
                        '<p>',$resume,'</p>',
                        '<footer><a href="../php/article.php?id=',$id,'">Lire l\'article</a></footer>',
                    '</article>';         
    }

    //_______________________________________________________________
    /**
     *  Conversion d'une date format AAAAMMJJHHMM au format JJ mois AAAA à HHhMM
     *
     *  @param  int     $date   la date à afficher. 
     *  @return string          la chaîne qui reprsente la date
     */
    function tr_mois_annee_string($date) {
        // les champs date (coDate, arDatePublication, arDateModification) sont de type BIGINT dans la base de données
        // donc pas besoin de les protéger avec htmlentities()
        $mois = substr($date, -8, 2);
        $annee = substr($date, 0, -8);
        $month = em_get_tableau_mois();    
        return mb_strtolower($month[$mois - 1], 'UTF-8'). ' '. $annee;
    }

    //_______________________________________________________________
        /**
         *  Comparaison entre les mois et l'année d'une date.
         *
         *  @param  int     $date   la date à afficher. 
         *  @return boolean   True si le mois et l'annee sont les mêmes, false sinon.
         */
        function tr_same_mois_annee_string($date1,$date2) {
            $mois1 = substr($date1, -8, 2);
            $annee1 = substr($date1, 0, -8);

            $mois2 = substr($date2, -8, 2);
            $annee2 = substr($date2, 0, -8);

            return ($annee1 == $annee2) && ($mois1 && $mois2);
        }

    // ---------------------------------------------- CORRECTION DES PROFS -------------------------------------------//
    //_______________________________________________________________
    /**
     *  Affichage du début de la page (de l'élément doctype jusqu'à l'élément header inclus)
     *
     *  Affiche notamment le menu de navigation en utilisant $_SESSION
     *
     *  @param  string  $h1         Le titre dans le bandeau (<header>)
     *  @param  string  $title      Le titre de la page (<head>)
     *  @param  string  $prefix     Le chemin relatif vers le répertoire racine du site
     *  @param  array   $css        Le nom de la feuille de style à inclure
     *  @global array   $_SESSION 
     */
    function em_aff_entete($h1, $title='', $prefix='..', $css = 'gazette.css'){
        em_aff_debut($title, $prefix, $css);
        $pseudo = '';
        $droits = array(false, false);
        if (isset($_SESSION['user'])){
            $pseudo = $_SESSION['user']['pseudo'];
            $droits = array($_SESSION['user']['redacteur'], $_SESSION['user']['administrateur']);
        }
        em_aff_menu($pseudo, $droits, $prefix);
        em_aff_header($h1, $prefix);
    }

    //_______________________________________________________________
    /**
     *  Affichage du pied de page du document. 
     */
    function em_aff_pied() {
        echo    '<footer>&copy; Licence Informatique - Janvier 2020 - Tous droits réservés</footer>',
            '</body>', 
        '</html>';  
    }

    //_______________________________________________________________
    /**
     *  Génère l'URL de l'image d'illustration d'un article en fonction de son ID
     *  - si l'image ou la photo existe dans le répertoire /upload, on renvoie son url 
     *  - sinon on renvoie l'url d'une image générique 
     *  @param  int     $id         l'identifiant de l'article
     *  @param  String  $prefix     le chemin relatif vers la racine du site
     */
    function em_url_image_illustration($id, $prefix='..') {

        $url = "{$prefix}/upload/{$id}.jpg";
        
        if (! file_exists($url)) {
            return "{$prefix}/images/none.jpg" ;
        }
        
        return $url;
    }

    //_______________________________________________________________
    /**
    * Vérifie si l'utilisateur est authentifié. 
    *
    * Termine la session et redirige l'utilisateur
    * sur la page connexion.php s'il n'est pas authentifié.
    *
    * @global array   $_SESSION 
    */
    function em_verifie_authentification() {
        if (! isset($_SESSION['user'])) {
            em_session_exit('./connexion.php');
        }
    }

    //_______________________________________________________________
    /**
     * Termine une session et effectue une redirection vers la page transmise en paramètre
     *
     * Elle utilise :
     *   -   la fonction session_destroy() qui détruit la session existante
     *   -   la fonction session_unset() qui efface toutes les variables de session
     * Elle supprime également le cookie de session
     *
     * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une 
     * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
     * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour 
     * stocker par exemple l'adresse IP, etc.
     * 
     * @param string    URL de la page vers laquelle l'utilisateur est redirigé
     */
    function em_session_exit($page = '../index.php') {
        session_destroy();
        session_unset();
        $cookieParams = session_get_cookie_params();
        setcookie(session_name(), 
                '', 
                time() - 86400,
                $cookieParams['path'], 
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        header("Location: $page");
        exit();
    }

    function tr_redirect_exit($page = '../index.php') {
        header("Location: $page");
        exit();
    }

    //___________________________________________________________________
    /**
     * Vérification des champs nom et prénom
     *
     * @param  string       $texte champ à vérifier
     * @param  string       $nom chaîne à ajouter dans celle qui décrit l'erreur
     * @param  array        $erreurs tableau dans lequel les erreurs sont ajoutées
     * @param  int          $long longueur maximale du champ correspondant dans la base de données
     */
    function em_verifier_texte($texte, $nom, &$erreurs, $long = -1){
        mb_regex_encoding ('UTF-8'); //définition de l'encodage des caractères pour les expressions rationnelles multi-octets
        if (empty($texte)){
            $erreurs[] = "$nom ne doit pas être vide.";
        }
        else if(strip_tags($texte) != $texte){
            $erreurs[] = "$nom ne doit pas contenir de tags HTML";
        }
        elseif ($long > 0 && mb_strlen($texte, 'UTF-8') > $long){
            // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strlen.php
            $erreurs[] = "$nom ne peut pas dépasser $long caractères";
        }
        elseif(!mb_ereg_match('^[[:alpha:]]([\' -]?[[:alpha:]]+)*$', $texte)){
            $erreurs[] = "$nom contient des caractères non autorisés";
        }
    }

    //___________________________________________________________________
    /**
     * Affichage d'un succès
     * 
     *  @param  string       $mess chaine de succès a afficher
     */
    function tr_message_succes($mess){
        echo '<div class="succes"><p>',$mess,'<p></div>';
    }
?>
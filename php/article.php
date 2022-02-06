<?php
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');


// bufferisation des sorties
ob_start();

// démarrage de la session
session_start();

// si il y a une autre clé que id dans $_GET, piratage ?
// => l'utilisateur est redirigé vers index.php
if (!em_parametres_controle('get', array(), array('id'))) {
    header('Location: ../index.php');
    exit;
}

em_aff_entete('L\'actu', 'L\'actu');

// affichage du contenu (article + commentaires)
eml_aff_article();

// pied de page
em_aff_pied();
//tr_aff_footer();

// fin du script
ob_end_flush();


/**
 * Affichage de l'article et de ses commentaires
 */
function eml_aff_article() {
    
    // vérification du format du paramètre dans l'URL
    if (!isset($_GET['id'])) {
        eml_aff_erreur ('Identifiant d\'article non fourni.');
        return;     // ==> fin de la fonction
    }
        
    if (!em_est_entier($_GET['id']) || $_GET['id'] <= 0) {
        eml_aff_erreur ('Identifiant d\'article invalide.');    
        return;     // ==> fin de la fonction
    }
    $id = (int)$_GET['id'];

    //Definition de la page appelante pour connexion.php ou inscription.php
    $_SESSION['refer'] = ' article.php?id='.$id;
    
    // ouverture de la connexion à la base de données
    $bd = tr_bd_connecter();
    
    // Récupération de l'article, des informations sur son auteur (y compris ses éventuelles infos renseignées dans la table 'redacteur'),
    // de ses éventuelles commentaires
    $sql = "SELECT *  
            FROM ((article INNER JOIN utilisateur ON arAuteur = utPseudo)
            LEFT OUTER JOIN redacteur ON utPseudo = rePseudo)
            LEFT OUTER JOIN commentaire ON arID = coArticle
            WHERE arID = {$id}
            ORDER BY coDate DESC, coID DESC";

    $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);

    // pas d'articles --> fin de la fonction
    if (mysqli_num_rows($res) == 0) {
        eml_aff_erreur ('Identifiant d\'article non reconnu.');
        mysqli_free_result($res);
        mysqli_close($bd);
        return;         // ==> fin de la fonction
    }

    // ---------------- GENERATION DE L'ARTICLE ------------------

    $tab = mysqli_fetch_assoc($res);

    // affichage de l'article et des commentaires associés
    echo '<main>';

    $id = (int)$_GET['id'];
    if ( (isset($_SESSION['user'])) && ($_SESSION['user']['pseudo'] == $tab['arAuteur']) ){
        echo '<section id="articleAuteur"><p>Vous êtes l\'auteur de cet article, <a href="edition.php?id=',$id,'">cliquez ici pour le modifier ou le supprimer</a>.</p></section>';
    }

    

    // Mise en forme du prénom et du nom de l'auteur pour affichage dans le pied du texte de l'article
    // Par exemple, pour 'johnny' 'bigOUde', ça donne 'J. Bigoude'
    // A faire avant la protection avec htmlentities() à cause des éventuels accents
    $auteur = eml_mb_ucfirst_lcremainder(mb_substr($tab['utPrenom'], 0, 1, 'UTF-8')) . '. ' . eml_mb_ucfirst_lcremainder($tab['utNom']);
    
    // protection contre les attaques XSS
    $auteur = em_html_proteger_sortie($auteur);

    // protection contre les attaques XSS
    $tab = em_html_proteger_sortie($tab);
    
    $imgFile = "../upload/{$id}.jpg";
    
    
    // génération du bloc <article>
    echo '<article>', 
            '<h3>', $tab['arTitre'], '</h3>',
            ((file_exists($imgFile)) ? "<img src='{$imgFile}' alt=\"Photo d\'illustration | {$tab['arTitre']}\">" : ''),
            tr_BBCode_article($tab['arTexte']),        
            '<footer>Par ',
            // si l'auteur a encore le droit de rédacteur et si il a enregistré des informations dans la table redacteur
            // on affiche un lien vers sa présentation sur la page redaction.php, 
            // sinon on affiche uniquement $auteur
            ((isset($tab['rePseudo']) && ($tab['utStatut'] == 1 || $tab['utStatut'] == 3)) ?
            "<a href='../php/redaction.php#{$tab['utPseudo']}'>$auteur</a>" : $auteur), 
            '. Publié le ', eml_date_to_string($tab['arDatePublication']);

    // ajout dans le pied d'article d'une éventuelle date de modification
    if (isset($tab['arDateModification'])) {
        echo ', modifié le '. eml_date_to_string($tab['arDateModification']);
    }
    
    // fin du bloc <article>
    echo '</footer>',                
        '</article>';

    //pour accéder une seconde fois au premier enregistrement de la sélection
    mysqli_data_seek($res, 0); 

    // Génération du début de la zone de commentaires
    echo '<section>',
            '<h2>Réactions</h2>';    
    
    // s'il existe des commentaires, on les affiche un par un.
    if (isset($tab['coID'])) {
        echo '<ul>';
        while ($tab = mysqli_fetch_assoc($res)) {
            echo '<li>',
                    '<p>Commentaire de <strong>', em_html_proteger_sortie($tab['coAuteur']), '</strong>, le ',
                        eml_date_to_string($tab['coDate']), 
                    '</p>',
                    '<blockquote>', tr_BBCode_commentaire(em_html_proteger_sortie($tab['coTexte'])), '</blockquote>';
                    if( (isset($_SESSION['user'])) && ($tab['coAuteur'] == $_SESSION['user']['pseudo'] || $_SESSION['user']['pseudo'] == $tab['arAuteur']) ){
                        //Suppression de commentaire 
                        $comID = $tab['coID'];
                        if(isset($_POST['btnSupprimerCom'.$comID])){
                            $sqlDeleteCom = "DELETE FROM commentaire WHERE coID='{$comID}' AND coAuteur='{$_SESSION['user']['pseudo']}'";
                            mysqli_query($bd, $sqlDeleteCom) or tr_bd_erreur($bd, $sqlDeleteCom);
                            echo "<meta http-equiv='refresh' content='0'>";
                        }
                        echo '<form action="article.php?id=',$id,'" method="post">',
                            '<input type="submit" name="btnSupprimerCom',$comID,'" value="Supprimer le commentaire">',
                            '</form>';
                    }
                echo '</li>';
        }
        echo '</ul>';
    }
    // sinon on indique qu'il n'y a pas de commentaires
    else {
        echo '<p>Il n\'y a pas de commentaires à cet article. </p>';  
    }
    
    // libération des ressources
    mysqli_free_result($res);
    
    // fermeture de la connexion à la base de données
    mysqli_close($bd);

  
    if (!isset($_SESSION['user'])){
        echo    '<p>', 
                '<a href="connexion.php">Connectez-vous</a> ou <a href="inscription.php">inscrivez-vous</a> ',
                'pour pouvoir commenter cet article !',
             '</p>';
    }else{
        if (isset($_POST['btnPublierCom'])) {
            $erreurs = trl_traitement_partie_commentaire($id);
            echo "<meta http-equiv='refresh' content='0'>";
        }
        else{
            $erreurs = FALSE;
        }
        trl_aff_partie_commentaire($erreurs,$id);
    }

    echo '</section></main> ';
}

//_______________________________________________________________
/**
 *  Conversion d'une date format AAAAMMJJHHMM au format JJ mois AAAA à HHhMM
 *
 *  @param  int     $date   la date à afficher. 
 *  @return string          la chaîne qui reprsente la date
 */
function eml_date_to_string($date) {
    // les champs date (coDate, arDatePublication, arDateModification) sont de type BIGINT dans la base de données
    // donc pas besoin de les protéger avec htmlentities()
    
    // si un article a été publié avant l'an 1000, ça marche encore :-)
    $min = substr($date, -2);
    $heure = (int)substr($date, -4, 2); //conversion en int pour supprimer le 0 de '07' pax exemple
    $jour = (int)substr($date, -6, 2);
    $mois = substr($date, -8, 2);
    $annee = substr($date, 0, -8);
    
    $month = em_get_tableau_mois();    
    
    return $jour. ' '. mb_strtolower($month[$mois - 1], 'UTF-8'). ' '. $annee . ' à ' . $heure . 'h' . $min;
    // mb_* -> pour l'UTF-8, voir : https://www.php.net/manual/fr/function.mb-strtolower.php
}

//___________________________________________________________________
/**
 * Renvoie une copie de la chaîne UTF8 transmise en paramètre après avoir mis sa
 * première lettre en majuscule et toutes les suivantes en minuscule
 *
 * @param  string   $str    la chaîne à transformer
 * @return string           la chaîne résultat
 */
function eml_mb_ucfirst_lcremainder($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $fc = mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'));
    return $fc.mb_substr($str, 1, mb_strlen($str), 'UTF-8');
}

//_______________________________________________________________
/**
 *  Affchage d'un message d'erreur dans une zone dédiée de la page.
 *  @param  String  $msg    le message d'erreur à afficher.
 */
function eml_aff_erreur($msg) {
    echo '<main>', 
            '<section>', 
                '<h2>Oups, il y a une erreur...</h2>',
                '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :</p>',
                '<blockquote>', $msg, '</blockquote>', 
            '</section>', 
        '</main>';
}

    //_______________________________________________________________
     /**
     * Affichage de la partie commentaire si connecté
     *
     * En absence de soumission, $erreurs est égal à FALSE
     * Quand l'inscription échoue, $erreurs est un tableau de chaînes  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_partie_commentaire($erreurs,$id) {
        if(isset($_POST['btnPublierCom'])){
            $commentaire = em_html_proteger_sortie(trim($_POST['commentaire']));
        }else{
            $commentaire = '';
        }
        echo '<div id="ajoutCommentaire">',
            '<p>Ajoutez un commentaire</p>',
            '<form action="article.php?id=',$id,'" method="post">';

            if ($erreurs) { 
                echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de la tentative de modification de l\'article :<ul>';
                foreach ($erreurs as $err) {
                    echo '<li>', $err, '</li>';   
                }
                echo '</ul></div>';
            }

                echo '<table>',
                '<tr><td colspan="2"><textarea name="commentaire" maxlength="300" required>',$commentaire,'</textarea></td></tr>',
                '<tr><td colspan="2"><input type="submit" name="btnPublierCom" value="Publier ce commentaire"></td></tr>',
                '</table>',
            '</form>',
        '</div>';
    }

    /**
     *  Traitement d'une modification des informations redacteur. 
     *  
     *  @global array    $_POST
     *  @global array    $_SESSION
     *  @return array    un tableau contenant les erreurs s'il y en a
     */
    function trl_traitement_partie_commentaire($id) {

        $erreurs = array();

        // Verification du texte du commentaire
        $commentaire = trim($_POST['commentaire']);
        if (empty($commentaire)) {
            $erreurs[] = 'Le texte du commentaire ne doit pas être vide.';
        }

        // si erreurs --> retour
        if (count($erreurs) > 0) {
            return $erreurs;   //===> FIN DE LA FONCTION
        }

        $bd = tr_bd_connecter();
        $commentaire = mysqli_real_escape_string($bd, $commentaire);
        $datePubli = date("YmdHi");
        $sqlCommentaire = "INSERT INTO commentaire SET coAuteur='{$_SESSION['user']['pseudo']}', coTexte='{$commentaire}', coDate='{$datePubli}', coArticle='{$id}'";
        mysqli_query($bd, $sqlCommentaire) or tr_bd_erreur($bd, $sqlCommentaire);
        mysqli_close($bd);
    }
?>

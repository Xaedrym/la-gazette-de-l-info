<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    // si il y a une autre clé que id dans $_GET, piratage ?
    // => l'utilisateur est redirigé vers index.php
    if ( !isset($_SESSION['user']) || ($_SESSION['user']['redacteur'] == false) || (!em_parametres_controle('get', array(), array('id'))) ) {
        header ('location: ../index.php');
        exit();
    }

    em_aff_entete('Nouvel Article', 'Nouvel Article');
    echo '<main>';

    // si formulaire soumis, traitement de la demande de publication de l'article
    if (isset($_POST['btnEnregistrer'])) {
        $erreurs = trl_traitement_modif_article();
    }
    else{
        $erreurs = FALSE;
    }

    trl_aff_modif_article($erreurs);

    tr_aff_footer();
    ob_end_flush();


    //---------------------------------------------- Fonctions Locales --------------------------------------------//
    /**
     * Contenu de la page : affichage du formulaire d'information rédacteur
     *
     * En absence de soumission, $erreurs est égal à FALSE
     * Quand l'inscription échoue, $erreurs est un tableau de chaînes  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_modif_article($erreurs) {
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

        echo   '<section>',
                '<h2>Edition de l\'article</h2>',
                '<p>Vous pouvez éditer votre article.</p>',            
                '<form action="edition.php?id=',$id,'" method="post" enctype="multipart/form-data">',
                '<input type="hidden" name="MAX_FILE_SIZE" value="1048576">';

        if (isset($_POST['btnSupprimer'])){
            $bd = tr_bd_connecter();
            $sqlDeleteCom = "DELETE FROM commentaire WHERE coArticle='{$id}'";
            mysqli_query($bd, $sqlDeleteCom) or tr_bd_erreur($bd, $sqlDeleteCom);
            $sqlDeleteArticle = "DELETE FROM article WHERE arID='{$id}'";
            mysqli_query($bd, $sqlDeleteArticle) or tr_bd_erreur($bd, $sqlDeleteArticle);
            mysqli_close($bd);
            header ('location: ../index.php');
            exit();
        }else{
            if (isset($_POST['btnEnregistrer'])){
                $titre = em_html_proteger_sortie(trim($_POST['titre']));
                $resume = em_html_proteger_sortie(trim($_POST['resume']));
                $article = em_html_proteger_sortie(trim($_POST['article']));
            }
            else{
                $bd = tr_bd_connecter();
                $sql = "SELECT arID, arTitre, arResume, arTexte, arAuteur FROM article WHERE arID='{$id}'";
                $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
                $t = mysqli_fetch_assoc($res);
                //Verification de sécurité supplémentaire
                $auteur = em_html_proteger_sortie(trim($t['arAuteur']));
                if($auteur != $_SESSION['user']['pseudo']){
                    header('Location: ../index.php');
                    exit;
                }
                $titre = em_html_proteger_sortie(trim($t['arTitre']));
                $resume = em_html_proteger_sortie(trim($t['arResume']));
                $article = em_html_proteger_sortie(trim($t['arTexte']));
                mysqli_free_result($res);
                mysqli_close($bd);
            }
            
            if($erreurs && ($erreurs[0] == 1)){
                tr_message_succes('Les modifications ont été enregistrées');
            }else{
                if ($erreurs) { 
                    echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de la tentative de modification de l\'article :<ul>';
                    foreach ($erreurs as $err) {
                        echo '<li>', $err, '</li>';   
                    }
                    echo '</ul></div>';
                }
            }
            
            echo '<table>';
                    em_aff_ligne_input('text','Titre de l\'article :', 'titre', $titre, array('required' => 0));
                echo '<tr>',
                        '<td><label>Résumé de l\'article : </label></td>',
                        '<td><textarea name="resume" maxlength="600" class="resumeArticle" required>',$resume,'</textarea></td>',
                    '</tr>',
                    '<tr>',
                        '<td><label>Texte de l\'article : </label></td>',
                            '<td><textarea name="article" maxlength="5000" required>',$article,'</textarea></td>',
                    '</tr>',
                    '<tr>', 
                        '<td><label>Image de l\'article : </label></td>',
                        '<td><input type="file" name="uplFichier"></td>',
                    '</tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnEnregistrer" value="Enregistrer">',
                            '<input type="reset" value="Réinitialiser">', 
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<a href="#oModal" id="boutonSuppr">Supprimer l\'article</a>',
                            '<div id="oModal" class="oModal">',
                                '<div>',
                                    '<header>',
                                        '<a href="#fermer" title="Fermer la fenêtre" class="droite">X</a>',
                                        '<h2>Êtes-vous sûr de voulour supprimer cet article ?</h2>',
                                    '</header>',
                                    '<footer class="cf">',
                                        '<input type="submit" name="btnSupprimer" value="Supprimer l\'article">',
                                    '</footer>',
                                '</div>',
                            '</div>',
                        '</td>',
                    '</tr>',
                '</table>',
                '</form>',
                '</section>';
        }
    }

     /**
     *  Traitement d'une modification des informations redacteur. 
     *  
     *  @global array    $_POST
     *  @global array    $_SESSION
     *  @return array    un tableau contenant les erreurs s'il y en a
     */
    function trl_traitement_modif_article() {
        if(!em_parametres_controle('post', array('MAX_FILE_SIZE','titre', 'resume', 'article', 'btnEnregistrer'), array('uplFichier'))) {
            em_session_exit(); 
        }

        $erreurs = array();

        // Verification du titre de l'article
        $titre = trim($_POST['titre']);
        if (empty($titre)) {
            $erreurs[] = 'Le titre de l\'article ne doit pas être vide.';
        }

        // Verification du titre de l'article
        $resume = trim($_POST['resume']);
        if (empty($resume)) {
            $erreurs[] = 'Le résumé de l\'article ne doit pas être vide.';
        }

        // Verification du texte de l'article
        $article = trim($_POST['article']);
        if (empty($article)) {
            $erreurs[] = 'Le texte de l\'article ne doit pas être vide.';
        }

        // si erreurs --> retour
        if (count($erreurs) > 0) {
            return $erreurs;   //===> FIN DE LA FONCTION
        }
        
        // ouverture de la connexion à la base 
        $bd = tr_bd_connecter();

        $titre = mysqli_real_escape_string($bd, $titre);
        $resume = mysqli_real_escape_string($bd, $resume);
        $article = mysqli_real_escape_string($bd, $article);
        $dateMAJ = date("YmdHi"); 

        $id = (int)$_GET['id'];

        $sql = "UPDATE article
                SET arTitre = '{$titre}', arResume = '{$resume}', arTexte = '{$article}', arDateModification = '{$dateMAJ}'
                WHERE arID='{$id}' 
                AND arAuteur = '{$_SESSION['user']['pseudo']}'";
            
        mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);

        // fermeture de la connexion à la base de données
        mysqli_close($bd);

        if($_FILES['uplFichier']['name'] != ''){
            // Vérification si erreurs photo
            $f = $_FILES['uplFichier'];
            if($f['type'] != "image/jpeg"){
                $erreurs[] = $f['name'].' doit être du type jpg.';
            }
            switch ($f['error']) {
                case 1:
                case 2:
                    $erreurs[] = $f['name'].' est trop gros.';
                    break;
                case 3:
                    $erreurs[] = 'Erreur de transfert de '.$f['name'];
                    break;
                case 4:
                    $erreurs[] = $f['name'].' introuvable.';
            }

            // si erreurs --> retour
            if (count($erreurs) > 0) {
                return $erreurs;   //===> FIN DE LA FONCTION
            }

            // Pas d'erreur => placement du fichier
            if (! @is_uploaded_file($f['tmp_name'])) {
                trl_aff_photo('Erreur interne de transfert');
                exit();
            }

            $place = realpath('../upload').'/'.$id.".jpg";
            if (@move_uploaded_file($f['tmp_name'], $place)) {
            } else {
                $erreurs = 'Erreur interne de transfert';
            }

            // si erreurs --> retour
            if (count($erreurs) > 0) {
                return $erreurs;   //===> FIN DE LA FONCTION
            }
        }

        //succès des changements
        $erreurs[] = 1;
        return $erreurs;
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
?>
<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    if (!isset($_SESSION['user']) || $_SESSION['user']['redacteur'] == false) {
        header ('location: ../index.php');
        exit();
    }

    em_aff_entete('Nouvel Article', 'Nouvel Article');
    echo '<main>';

    // si formulaire soumis, traitement de la demande de publication de l'article
    if (isset($_POST['btnPublier'])) {
        $erreurs = trl_traitement_nouvel_article();
    }
    else{
        $erreurs = FALSE;
    }

    trl_aff_nouvel_article($erreurs);

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
    function trl_aff_nouvel_article($erreurs) {
        echo   '<section>',
                '<h2>Publication Nouvel Article</h2>',
                '<p>Vous pouvez publier un article.</p>',            
                '<form action="nouveau.php" method="post" enctype="multipart/form-data">',
                '<input type="hidden" name="MAX_FILE_SIZE" value="1048576">';

        if (isset($_POST['btnPublier'])){
            $titre = em_html_proteger_sortie(trim($_POST['titre']));
            $resume = em_html_proteger_sortie(trim($_POST['resume']));
            $article = em_html_proteger_sortie(trim($_POST['article']));
        }
        else{
            $titre = $resume = $article = '';
        }
        
        if($erreurs && ($erreurs[0] == 1)){
            tr_message_succes('L\'article a été publié.');
        }else{
            if ($erreurs) {
                echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de la tentative de publication de l\'article :<ul>';
                foreach ($erreurs as $err) {
                    echo '<li>', $err, '</li>';   
                }
                echo '</ul></div>';
            }
        }
        
        echo '<table>';
                em_aff_ligne_input('text','Titre de l\'article :', 'titre', $titre, array('required' => 0));
            echo '<tr>',
                    '<td><label for="textResume">Résumé de l\'article : </label></td>',
                    '<td><textarea name="resume" id="textResume" maxlength="600" class="resumeArticle" required></textarea></td>',
                '</tr>',
                '<tr>',
                    '<td><label for="textArticle">Texte de l\'article : </label></td>',
                        '<td><textarea name="article" id="textArticle" maxlength="5000" required></textarea></td>',
                '</tr>',
                '<tr>', 
                    '<td><label>Image de l\'article : </label></td>',
                    '<td><input type="file" name="uplFichier"></td>',
                '</tr>',
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnPublier" value="Publier">',
                        '<input type="reset" value="Réinitialiser">', 
                    '</td>',
                '</tr>',
            '</table>',
            '</form>',
            '</section>';
    }

     /**
     *  Traitement d'une modification des informations redacteur. 
     *  
     *
     *  @global array    $_POST
     *  @global array    $_SESSION
     *  @return array    un tableau contenant les erreurs s'il y en a
     */
    function trl_traitement_nouvel_article() {
        if(!em_parametres_controle('post', array('MAX_FILE_SIZE','titre', 'resume', 'article', 'btnPublier'), array('uplFichier'))) {
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
        $datePubli = date("YmdHi");  

        $sql = "INSERT INTO  article
                SET arTitre = '{$titre}', arResume = '{$resume}', arTexte = '{$article}', arDatePublication = '{$datePubli}', arAuteur = '{$_SESSION['user']['pseudo']}'";
            
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

            $bd = tr_bd_connecter();

            $sqlPhoto = "SELECT  arID FROM article 
            WHERE arTitre = '{$titre}' 
            AND arResume = '{$resume}' 
            AND  arTexte = '{$article}'
            AND arDatePublication = '{$datePubli}'
            AND arAuteur = '{$_SESSION['user']['pseudo']}'
            ORDER BY arID DESC
            LIMIT 0,1";
            $resPhoto = mysqli_query($bd, $sqlPhoto) or tr_bd_erreur($bd, $sqlPhoto);
            $t = mysqli_fetch_assoc($resPhoto);
            $id = $t['arID'];

            mysqli_free_result($resPhoto);
            mysqli_close($bd);
            
            $place = realpath('../upload').'/'.$id.".jpg"; //On peut ajouter "aa_" devant l'id pour montrer que c'est une image d'un article et non une photo de profil
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
?>
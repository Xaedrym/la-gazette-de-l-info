<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    // si l'utilisateur est déjà authentifié
    if (isset($_SESSION['user'])){
        header ('location: ../index.php');
        exit();
    }

    // si formulaire soumis, traitement de la demande d'inscription
    if (isset($_POST['btnConnexion'])) {
        $erreurs = trl_traitement_connexion();
    }
    else{
        $erreurs = FALSE;
    }

    // génération de la page
    em_aff_entete('Connexion', 'Connexion');

    trl_aff_formulaire($erreurs);

    em_aff_pied();

    ob_end_flush(); //FIN DU SCRIPT


    //-- Fonctions Locales --//
    /**
     *  Traitement d'une demande de connexion. 
     *  
     *  Si l'inscription réussit, un nouvel enregistrement est ajouté dans la table utilisateur, 
     *  la variable de session $_SESSION['user'] est créée et l'utilisateur est redirigé vers la
     *  page index.php
     *
     *  @global array    $_POST
     *  @global array    $_SESSION
     *  @return array    un tableau contenant les erreurs s'il y en a
     */
    function trl_traitement_connexion() {
        if(!em_parametres_controle('post', array('pseudo','passe','btnConnexion'))) {
            em_session_exit();   
        }

        $erreurs = array();

        // Verification de la présence du pseudo
        $pseudo = trim($_POST['pseudo']);
        if(empty($pseudo)){
            $erreurs[] = 'Le pseudo doit être renseigné.';
        }
         // Verification de la présence du mot de passe
        $passe = trim($_POST['passe']);
        if(empty($pseudo)){
            $erreurs[] = 'Le mot de passe doit être renseigné.';
        }

        // si erreurs --> retour
        if (count($erreurs) > 0) {
            return $erreurs;   //===> FIN DE LA FONCTION
        }

        // Ouverture de la connexion à la base 
        $bd = tr_bd_connecter();

        // On vérifie si un pseudo correspond au pseudo ET au hashage du mot de passe
        $sql = "SELECT utPseudo, utStatut, utPasse
                FROM utilisateur
                WHERE utPseudo = '{$pseudo}'";

        $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);

        // Si pas de ligne => erreur, sinon on récupère les droits 
        if(mysqli_num_rows($res) == 0){
            $erreurs[] = 'Echec d\'authentification. Utilisateur inconnu.';
        }else{
            $tab = mysqli_fetch_assoc($res);
            if(password_verify($passe, $tab['utPasse'])){
                $statut = $tab['utStatut'];
                if($statut == 3){
                    $redac = true;
                    $admin = true;
                }
                if($statut == 2){
                    $redac = false;
                    $admin = true;
                }
                if($statut == 1){
                    $redac = true;
                    $admin = false;
                }
                if($statut == 0){
                    $redac = false;
                    $admin = false;
                }
            }else{
                $erreurs[] = 'Echec d\'authentification. Utilisateur inconnu ou mot de passe incorrect.';
            }
        }

        // si erreurs --> retour
        if (count($erreurs) > 0) {
            mysqli_close($bd);
            return $erreurs;   //===> FIN DE LA FONCTION
        }

        // Libération de la mémoire associée au résultat de la requête
        mysqli_free_result($res);

        mysqli_close($bd);

        // On affecte la variable $_SESSION['user'] avec le pseudo et les droits
        $_SESSION['user'] = array('pseudo' => $pseudo, 'redacteur' => $redac, 'administrateur' => $admin);

        tr_redirect_exit(isset($_SESSION['refer']) ? $_SESSION['refer'] : '../index.php');
    }

    /**
     * Contenu de la page : affichage du formulaire d'inscription
     *
     * En absence de soumission, $erreurs est égal à FALSE
     * Quand la tentative de connexion échoue, $erreurs est un tableau de chaînes  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_formulaire($erreurs) {
    // affectation des valeurs à afficher dans les zones du formulaire
    if (isset($_POST['btnConnexion'])){
        $pseudo = em_html_proteger_sortie(trim($_POST['pseudo']));
    }
    else{
        $pseudo = '';
    }
    
    echo
        '<main>',
        '<section>',
            '<h2>Formulaire de connexion</h2>',
            '<p>Pour vous identifier, remplissez le formulaire ci-dessous.</p>',            
            '<form action="connexion.php" method="post">';

    if ($erreurs) {
        echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de votre tentative de connexion :<ul>';
        foreach ($erreurs as $err) {
            echo '<li>', $err, '</li>';   
        }
        echo '</ul></div>';
    }
    
    echo '<table>';

    em_aff_ligne_input('text', 'Pseudo :', 'pseudo', $pseudo, array('placeholder' => '4 caractères minimum', 'required' => 0));
    em_aff_ligne_input('password', 'Mot de passe :', 'passe', '', array('required' => 0));
    
    echo    '<tr>',
                '<td colspan="2">',
                    '<input type="submit" name="btnConnexion" value="Se connecter">',
                    '<input type="reset" value="Annuler">', 
                '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '<p>Pas encore inscrit ? N\'attendez pas, <a href="./inscription.php">inscrivez-vous</a> !.</p>',
        '</section></main>';
    }
?>
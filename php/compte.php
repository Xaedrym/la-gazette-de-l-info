<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    // si l'utilisateur n'est pas authentifié
    if (!isset($_SESSION['user'])){
        header ('location: ../index.php');
        exit();
    }

    em_aff_entete('Mon compte', 'Mon compte');

    echo '<main>';

    // si formulaire soumis, traitement de la demande de modification des informations personelles
    if (isset($_POST['btnEnregistrerInfo'])) {
        $erreurs_info = trl_traitement_info();
    }
    else{
        $erreurs_info = FALSE;
    }

    trl_aff_info($erreurs_info);

    // si formulaire soumis, traitement de la demande de modification du mot de passe
    if (isset($_POST['btnEnregistrerPass'])) {
        $erreurs_authentification = trl_traitement_authentification();
    }
    else{
        $erreurs_authentification = FALSE;
    }

    trl_aff_authentification($erreurs_authentification);

    // Si l'utilisateur a les droits de rédacteur
    if ($_SESSION['user']['redacteur'] == true) {
        //Info rédacteur
        if (isset($_POST['btnEnregistrerRedac'])) {
            $erreurs_redacteur = trl_traitement_redacteur();
        }
        else{
            $erreurs_redacteur = FALSE;
        }
        trl_aff_redacteur($erreurs_redacteur);

        
        $erreurs = '';
        
        // Premier affichage
        if (!isset($_POST['btnAppliquerPhoto'])) {
            trl_aff_photo($erreurs);
            tr_aff_footer();
            exit();
        }
        
        // Phase traitement de la soumission du formulaire
        
        // Vérification si erreurs
        $f = $_FILES['uplFichier'];
        if($f['type'] != "image/jpeg"){
            $erreurs = $f['name'].' doit être du type jpg.';
        }
        switch ($f['error']) {
            case 1:
            case 2:
                $erreurs = $f['name'].' est trop gros.';
                break;
            case 3:
                $erreurs = 'Erreur de transfert de '.$f['name'];
                break;
            case 4:
                $erreurs = $f['name'].' introuvable.';
            }
            if ($erreurs != '') {
                trl_aff_photo($erreurs);
                exit();
        }
        
        // Pas d'erreur => placement du fichier
        if (! @is_uploaded_file($f['tmp_name'])) {
            trl_aff_photo('Erreur interne de transfert');
            exit();
        }
        
        $place = realpath('../upload').'/'.$_SESSION['user']['pseudo'].".jpg";
        if (@move_uploaded_file($f['tmp_name'], $place)) {
            $erreurs = 1;
        } else {
            $erreurs = 'Erreur interne de transfert';
        }
        
        trl_aff_photo($erreurs);
    }

    echo '</main>';

    em_aff_pied();

    ob_end_flush(); //FIN DU SCRIPT

    //---------------------------------------------- Fonctions Locales --------------------------------------------//

    //---------------------------------------------- Informations Personnelles --------------------------------------------//
    /**
     * Contenu de la page : affichage du formulaire d'information personelle
     *
     * En absence de soumission, $erreurs est égal à FALSE
     * Quand l'inscription échoue, $erreurs est un tableau de chaînes  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_info($erreurs) {
        $anneeCourante = (int) date('Y');
        // affectation des valeurs à afficher dans les zones du formulaire
        if (isset($_POST['btnEnregistrerInfo'])){
            $nom = em_html_proteger_sortie(trim($_POST['nom']));
            $prenom = em_html_proteger_sortie(trim($_POST['prenom']));
            $email = em_html_proteger_sortie(trim($_POST['email']));
            $jour = (int)$_POST['naissance_j'];
            $mois = (int)$_POST['naissance_m'];
            $annee = (int)$_POST['naissance_a'];
            $civilite = (isset($_POST['radSexe'])) ? (int)$_POST['radSexe'] : 3;
            $mails_pourris = isset($_POST['cbSpam']);
        }
        else{
            $bd = tr_bd_connecter();
            $sql = "SELECT utNom, utPrenom, utEmail, utDateNaissance, utCivilite, utMailsPourris
                    FROM utilisateur
                    WHERE utPseudo = '{$_SESSION['user']['pseudo']}'";
            $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
            $t = mysqli_fetch_assoc($res);
            $nom = em_html_proteger_sortie(trim($t['utNom']));
            $prenom =em_html_proteger_sortie(trim( $t['utPrenom']));
            $email = em_html_proteger_sortie(trim($t['utEmail']));
            $jour = (int)substr($t['utDateNaissance'], -2);
            $mois = (int)substr($t['utDateNaissance'], 4, 2);
            $annee = (int)substr($t['utDateNaissance'], 0, 4);
            if ($t['utCivilite'] == 'h'){
                $civilite = 1;
            }else{
                $civilite = 2;
            }
            $mails_pourris = $t['utMailsPourris'];
            mysqli_free_result($res);
            mysqli_close($bd);
        }
        
        echo  '<section>',
                '<h2>Informations personelles</h2>',
                '<p>Vous pouvez modifier les informations suivantes.</p>',            
                '<form action="compte.php" method="post">';
        if($erreurs && ($erreurs[0] == 1)){
            tr_message_succes('Les modifications ont été enregistrées');
        }else{
            if ($erreurs) {
                echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de la tentative de modification de vos informations personnelles :<ul>';
                foreach ($erreurs as $err) {
                    echo '<li>', $err, '</li>';   
                }
                echo '</ul></div>';
            }
        }
        
        echo '<table>';
        em_aff_ligne_input_radio('Votre civilité :', 'radSexe', array(1 => 'Monsieur', 2 => 'Madame'), $civilite, array('required' => 0));
        em_aff_ligne_input('text', 'Votre nom :', 'nom', $nom, array('required' => 0));
        em_aff_ligne_input('text', 'Votre prénom :', 'prenom', $prenom, array('required' => 0));
        
        em_aff_ligne_date('Votre date de naissance :', 'naissance', $anneeCourante - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante, $jour, $mois, $annee);
        
        em_aff_ligne_input('email', 'Votre email :', 'email', $email, array('required' => 0));
        
        echo    '<tr>', '<td colspan="2">';
        // l'attribut required est un attribut booléen qui n'a pas de valeur
        
        $attributs_checkbox = array();
        if ($mails_pourris){
            // l'attribut checked est un attribut booléen qui n'a pas de valeur
            $attributs_checkbox['checked'] = 0;
        }
        em_aff_input_checkbox('J\'accepte de recevoir des tonnes de mails pourris', 'cbSpam', 1, $attributs_checkbox);
                    
        echo    '</td></tr>',
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnEnregistrerInfo" value="Enregistrer">',
                        '<input type="reset" value="Réinitialiser">', 
                    '</td>',
                '</tr>',
            '</table>',
            '</form>',
            '</section>';
    }

    /**
     *  Traitement d'une modification des informations personelles. 
     *  
     *
     *  @global array    $_POST
     *  @global array    $_SESSION
     *  @return array    un tableau contenant les erreurs s'il y en a
     */
    function trl_traitement_info() {
        if( !em_parametres_controle('post', array('nom', 'prenom', 'naissance_j', 'naissance_m', 'naissance_a', 'email', 'btnEnregistrerInfo'), array('cbSpam', 'radSexe'))) {
            em_session_exit();   
        }
        
        $erreurs = array();
        
        // vérification de la civilité
        if (! isset($_POST['radSexe'])){
            $erreurs[] = 'Vous devez choisir une civilité.';
        }
        else if (! (em_est_entier($_POST['radSexe']) && em_est_entre($_POST['radSexe'], 1, 2))){
            em_session_exit(); 
        }
        
        // vérification des noms et prénoms
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        em_verifier_texte($nom, 'Le nom', $erreurs, LMAX_NOM);
        em_verifier_texte($prenom, 'Le prénom', $erreurs, LMAX_PRENOM);
        
        // vérification de la date
        if (! (em_est_entier($_POST['naissance_j']) && em_est_entre($_POST['naissance_j'], 1, 31))){
            em_session_exit(); 
        }
        
        if (! (em_est_entier($_POST['naissance_m']) && em_est_entre($_POST['naissance_m'], 1, 12))){
            em_session_exit(); 
        }
        $anneeCourante = (int) date('Y');
        if (! (em_est_entier($_POST['naissance_a']) && em_est_entre($_POST['naissance_a'], $anneeCourante  - NB_ANNEE_DATE_NAISSANCE + 1, $anneeCourante))){
            em_session_exit(); 
        }
        
        $jour = (int)$_POST['naissance_j'];
        $mois = (int)$_POST['naissance_m'];
        $annee = (int)$_POST['naissance_a'];
        if (!checkdate($mois, $jour, $annee)) {
            $erreurs[] = 'La date de naissance n\'est pas valide.';
        }
        else if (mktime(0,0,0,$mois,$jour,$annee+18) > time()) {
            $erreurs[] = 'Vous devez avoir au moins 18 ans pour vous inscrire.'; 
        }
        
        // vérification du format de l'adresse email
        $email = trim($_POST['email']);
        if (empty($email)){
            $erreurs[] = 'L\'adresse mail ne doit pas être vide.'; 
        }
        else if (mb_strlen($email, 'UTF-8') > LMAX_EMAIL){
            $erreurs[] = 'L\'adresse mail ne peut pas dépasser '.LMAX_EMAIL.' caractères.';
        }
        else if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L\'adresse mail n\'est pas valide.';
        }
        
        // vérification si l'utilisateur accepte de recevoir les mails pourris
        if (isset($_POST['cbSpam']) && ! (em_est_entier($_POST['cbSpam']) && $_POST['cbSpam'] == 1)){
            em_session_exit(); 
        }
        
        // si erreurs --> retour
        if (count($erreurs) > 0) {
            return $erreurs;   //===> FIN DE LA FONCTION
        }
        
        // ouverture de la connexion à la base 
        $bd = tr_bd_connecter();
        
        // vérification de l'existence de l'email
        $emaile = mysqli_real_escape_string($bd, $email);
        $sql = "SELECT utPseudo, utEmail FROM utilisateur WHERE utEmail = '{$emaile}'";
        $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
        
        while($tab = mysqli_fetch_assoc($res)) {
            if (($tab['utEmail'] == $email) && ($tab['utPseudo'] != $_SESSION['user']['pseudo'])){
                $erreurs[] = 'Cette adresse email est déjà inscrite.';
            }
        }
        // Libération de la mémoire associée au résultat de la requête
        mysqli_free_result($res);

        // si erreurs --> retour
        if (count($erreurs) > 0) {
            // fermeture de la connexion à la base de données
            mysqli_close($bd);
            return $erreurs;   //===> FIN DE LA FONCTION
        }
 
        if ($mois < 10) {
            $mois = '0' . $mois;   
        }
        if ($jour < 10) {
            $jour = '0' . $jour;   
        }
        $civilite = (int) $_POST['radSexe'];
        $civilite = $civilite == 1 ? 'h' : 'f';
        
        $mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;
        
        $nom = mysqli_real_escape_string($bd, $nom);
        $prenom = mysqli_real_escape_string($bd, $prenom);

        $sql = "UPDATE utilisateur 
                SET utNom = '{$nom}', utPrenom = '{$prenom}', utEmail = '{$email}', utCivilite = '{$civilite}',
                utMailsPourris = '{$mailsPourris}', utDateNaissance ='{$annee}{$mois}{$jour}'
                WHERE utPseudo = '{$_SESSION['user']['pseudo']}'";
            
        mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql); 

        // fermeture de la connexion à la base de données
        mysqli_close($bd);

        //succès des changements
        $erreurs[] = 1;
        return $erreurs;
    }









    //---------------------------------------------- Authentification --------------------------------------------//
    /**
     * Contenu de la page : affichage du formulaire d'authentification
     *
     * En absence de soumission, $erreurs est égal à FALSE
     * Quand l'inscription échoue, $erreurs est un tableau de chaînes  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_authentification($erreurs) {
        echo   '<section>',
                '<h2>Authentification</h2>',
                '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>',            
                '<form action="compte.php" method="post">';
        
        if($erreurs && ($erreurs[0] == 1)){
            tr_message_succes('Le mot de passe a été changé avec succès.');
        }else{
            if ($erreurs) {
                echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de la tentative de changement de mot de passe :<ul>';
                foreach ($erreurs as $err) {
                    echo '<li>', $err, '</li>';   
                }
                echo '</ul></div>';
            }
        }
        
        echo '<table>';

        em_aff_ligne_input('password', 'Choisissez un mot de passe :', 'passe1', '', array('required' => 0));
        em_aff_ligne_input('password', 'Répétez le mot de passe :', 'passe2', '', array('required' => 0));
              
        echo   '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnEnregistrerPass" value="Enregistrer">',
                    '</td>',
                '</tr>',
            '</table>',
            '</form>',
            '</section>';
    }

    /**
     *  Traitement d'une demande d'inscription. 
     *  
     *
     *  @global array    $_POST
     *  @global array    $_SESSION
     *  @return array    un tableau contenant les erreurs s'il y en a
     */
    function trl_traitement_authentification() {
        if(!em_parametres_controle('post', array( 'passe1', 'passe2', 'btnEnregistrerPass'))) {
            em_session_exit();   
        }
        
        $erreurs = array();

        // vérification des mots de passe
        $passe1 = trim($_POST['passe1']);
        $passe2 = trim($_POST['passe2']);
        if (empty($passe1) || empty($passe2)) {
            $erreurs[] = 'Les mots de passe ne doivent pas être vides.';
        }
        else if ($passe1 !== $passe2) {
            $erreurs[] = 'Les mots de passe doivent être identiques.';
        }
        
        // si erreurs --> retour
        if (count($erreurs) > 0) {
            return $erreurs;   //===> FIN DE LA FONCTION
        }
        
        // ouverture de la connexion à la base 
        $bd = tr_bd_connecter();
        
        // calcul du hash du mot de passe pour enregistrement dans la base.
        $passe = password_hash($passe1, PASSWORD_DEFAULT);   
        $passe = mysqli_real_escape_string($bd, $passe);
        
        $sql = "UPDATE utilisateur SET utPasse = '{$passe}' WHERE utPseudo = '{$_SESSION['user']['pseudo']}'";
            
        mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql); 

        
        // fermeture de la connexion à la base de données
        mysqli_close($bd);

        //succès des changements
        $erreurs[] = 1;
        return $erreurs;
    }





    //---------------------------------------------- Modifications Rédacteurs --------------------------------------------//
    /**
     * Contenu de la page : affichage du formulaire d'information rédacteur
     *
     * En absence de soumission, $erreurs est égal à FALSE
     * Quand l'inscription échoue, $erreurs est un tableau de chaînes  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_redacteur($erreurs) {
        echo   '<section>',
                '<h2>Informations Rédacteur</h2>',
                '<p>Vous pouvez modifier les informations suivantes.</p>',            
                '<form action="compte.php" method="post">';

        if (isset($_POST['btnEnregistrerRedac'])){
            $categorie = em_html_proteger_sortie(trim($_POST['categorie']));
            $fonction = em_html_proteger_sortie(trim($_POST['fonction']));
            $biographie = em_html_proteger_sortie(trim($_POST['biographie']));
        }
        else{
            $bd = tr_bd_connecter();
            $sql = "SELECT reBio, reCategorie, reFonction
                    FROM redacteur
                    WHERE rePseudo = '{$_SESSION['user']['pseudo']}'";
            $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
            $t = mysqli_fetch_assoc($res);
            $categorie = em_html_proteger_sortie(trim($t['reCategorie']));
            $fonction = em_html_proteger_sortie(trim($t['reFonction']));
            $biographie = em_html_proteger_sortie(trim($t['reBio']));
            mysqli_free_result($res);
            mysqli_close($bd);
        }
        
        if($erreurs && ($erreurs[0] == 1)){
            tr_message_succes('Les modifications ont été enregistrées.');
        }else{
            if ($erreurs) {
                echo '<div class="erreur">Les erreurs suivantes ont été relevées lors de la tentative de modification de vos informations rédacteur :<ul>';
                foreach ($erreurs as $err) {
                    echo '<li>', $err, '</li>';   
                }
                echo '</ul></div>';
            }
        }
        
        echo '<table>';
        echo    '<tr>', 
                    '<td><label>Votre catégorie rédacteur : </label></td>',
                    '<td>';           
        em_aff_liste('categorie', array(1 => 'Rédacteur en Chef', 2 => 'Premier Violon', 3 => 'Sous-fifre'), $categorie);
        echo '</td></tr>';
        em_aff_ligne_input('text', 'Votre fonction :', 'fonction', $fonction);
        echo '<tr><td><label for="textbiographie">Votre biographie : </label></td>',
                    '<td><textarea name="biographie" id="textbiographie" maxlength="1000" required>',$biographie,'</textarea></td></tr>';
               
        echo   '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnEnregistrerRedac" value="Enregistrer">',
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
    function trl_traitement_redacteur() {
        if(!em_parametres_controle('post', array('categorie', 'fonction', 'biographie', 'btnEnregistrerRedac'))) {
            em_session_exit(); 
        }
        
        $erreurs = array();

        // Verification de la biographie
        $biographie = trim($_POST['biographie']);
        if (empty($biographie)) {
            $erreurs[] = 'La biographie ne doit pas être vide.';
        }
        
        // si erreurs --> retour
        if (count($erreurs) > 0) {
            return $erreurs;   //===> FIN DE LA FONCTION
        }
        
        // ouverture de la connexion à la base 
        $bd = tr_bd_connecter();

        $fonction = trim($_POST['fonction']);
        $fonction = mysqli_real_escape_string($bd, $fonction);
        
        $biographie = mysqli_real_escape_string($bd, $biographie);
        $categorie = mysqli_real_escape_string($bd, $_POST['categorie']);    

        $sql = "UPDATE redacteur 
                SET reBio = '{$biographie}', reFonction = '{$fonction}', reCategorie = '{$categorie}'
                WHERE rePseudo = '{$_SESSION['user']['pseudo']}'";
            
        mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);

        // fermeture de la connexion à la base de données
        mysqli_close($bd);

        //succès des changements
        $erreurs[] = 1;
        return $erreurs;
    }







    //---------------------------------------------- Photo Rédacteur --------------------------------------------//
    /**
     * Contenu de la page : affichage de la zone didié à la modification de la photo du rédacteur  
     *
     *  @param mixed    $erreurs
     *  @global array   $_POST
     */
    function trl_aff_photo($erreurs) {
        echo  '<section>',
            '<h2>Image du rédacteur</h2>',
            '<p>Vous pouvez ici, changer votre image rédacteur (seules les images avec une extension ".jpg" seront acceptées).</p>',            
            '<form action="compte.php" method="post" enctype="multipart/form-data">',
            '<input type="hidden" name="MAX_FILE_SIZE" value="1048576">';

            if($erreurs == 1){
                tr_message_succes('L\'image a été modifié avec succès.');
            }else{
                if ($erreurs != '') {
                    echo '<div class="erreur">Erreur lors de la tentative de changement d\'image rédacteur :<ul>';                
                        echo '<li>', $erreurs, '</li>';
                    echo '</ul></div>';
                }
            }

            echo '<table>',
                '<tr>', 
                    '<td><label>Votre photo de rédacteur : </label></td>',
                    '<td><input type="file" name="uplFichier"></td>',
                '</tr>',         
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnAppliquerPhoto" value="Appliquer">', 
                    '</td>',
                '</tr>',
            '</table>',
            '</form>',
            '</section>';
    }

?>
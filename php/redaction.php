<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    if ( isset($_SERVER['HTTP_REFERER']) ){
        $_SESSION['refer'] = $_SERVER['HTTP_REFERER'];
    }

    em_aff_entete('Rédaction', 'Rédaction');
    echo '<main>';
    //Affichage de la première section
   echo '<section>',
            '<h2>Le mot de la rédaction</h2>',
            '<p>Passionnés par le journalisme d\'investigation depuis notre plus jeune âge, nous avons créé en 2019 ce site pour répondre à un ',
                'réel besoin : celui de fournir une information fiable et précise sur la vie de la ',
                '<abbr title="Licence Informatique">L-INFO</abbr>',
                'de l\'<a href="http://www.univ-fcomte.fr" target="_blank">Université de Franche-Comté</a>.</p>',
            '<p>Découvrez les hommes et les femmes qui composent l\'équipe de choc de la Gazette de L-INFO. </p>',
    '</section>';

    $bd = tr_bd_connecter();

    $sql = "SELECT utPseudo, utPrenom, utNom, utStatut, reBio, reFonction, catLibelle, catID
            FROM (categorie INNER JOIN redacteur ON reCategorie = catID)
            INNER JOIN utilisateur ON rePseudo = utPseudo
            WHERE reBio != ''
            AND reCategorie IS NOT NULL
            AND (utStatut = 1 OR utStatut = 3)
            ORDER BY catID, utPseudo ASC";

    $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
    $nb_redac = mysqli_num_rows($res);
    // N'est pas sensé arriver.
    if ($nb_redac == 0) {
        tr_aff_erreur ('Aucun membre rédacteur.');
        mysqli_free_result($res);
        mysqli_close($bd);
    }else{
        // Si il y a des articles
        $i = 1;
        while ($t = mysqli_fetch_assoc($res)) {
            $pseudo = em_html_proteger_sortie(trim($t['utPseudo']));
            $prenom = em_html_proteger_sortie(trim($t['utPrenom']));
            $nom = em_html_proteger_sortie(trim($t['utNom']));
            $bio = em_html_proteger_sortie(trim($t['reBio']));
            $fonction = em_html_proteger_sortie(trim($t['reFonction']));
            $catlib = em_html_proteger_sortie(trim($t['catLibelle']));
            if($i == 1){
                $catID = $t['catID'];
                if($bio){
                    echo '<section>', 
                    '<h2> Notre ',$catlib,'</h2>';
                    trl_aff_article_redac($pseudo,$prenom,$nom,$bio,$fonction);
                } 
            }else{
                if($t['catID'] == $catID){
                    if($bio){
                        trl_aff_article_redac($pseudo,$prenom,$nom,$bio,$fonction);
                    }
                }else{
                    $catID = $t['catID'];
                    if($bio){
                        echo '</section>',
                            '<section>',
                            '<h2> Nos ',$catlib,'</h2>';
                            trl_aff_article_redac($pseudo,$prenom,$nom,$bio,$fonction);
                    }
                }
                $catID = $t['catID'];
                if($i == $nb_redac){
                    echo '</section>';
                }
            }  
            $i++;
        }
        mysqli_free_result($res);
        mysqli_close($bd);
    }
    //Affichage de la dernière section
    echo '<section>',
        '<h2>La Gazette de L-INFO recrute !</h2>',
        '<p>Si vous souhaitez vous aussi faire partie de notre team, rien de plus simple. Envoyez-nous un mail grâce au lien dans le menu de navigation, et rejoignez l\'équipe. </p>',
    '</section>';

    tr_aff_footer();
    ob_end_flush();

    // FONCTION LOCALE
    //_______________________________________________________________
    /**
     *  Affichage d'un article représentant un rédacteur
     *
     *  @param string          $pseudo La chaîne qui represente le prenom du rédacteur
     *  @param string          $prenom  La chaîne qui represente le prenom du rédacteur
     *  @param string          $nom La chaîne qui represente le nom du rédacteur
     *  @param string          $bio La chaîne qui represente la biographie du rédacteur
     *  @param string          $fonction La chaîne qui represente la fonction du rédacteur
     */
    function trl_aff_article_redac($pseudo,$prenom,$nom,$bio,$fonction) {
        $image = tr_choix_image($pseudo);
        echo '<article class="redacteur" id="',$pseudo,'">',
             '<img src="../',$image,'" width="150" height="200" alt="',$prenom,' ',$nom,'">',
             '<h3>',$prenom,' ',$nom,'</h3>';
        if($fonction != NULL){
            echo '<h4>',$fonction,'</h4>';
        }    
        echo '<p>',tr_BBCode_article($bio),'</p>',
             '</article>';
    }
?>
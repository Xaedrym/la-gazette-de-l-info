<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    $_SESSION['refer'] = ' recherche.php';

    em_aff_entete('Recherche', 'Recherche');
    echo '<main>';

    // création et affichage du formulaire de recherche
    echo '<section>', 
            '<p>Les critères de recherche doivent faire au moins trois caractères pour être pris en compte.</p>',
            '<h2>Recherche...</h2>',
            '<form method="GET">',
                '<input type="search" name="rechercher" placeholder="Rechercher...">',
                '<input type="submit" name="btnRechercher" value="Rechercher">',
            '</form>',
            '</section>';

    if(isset($_GET['rechercher']) && !empty($_GET['rechercher'])){
        //connection a la base de donnée
        $bd = tr_bd_connecter();
        $rechercher = htmlspecialchars(trim($_GET['rechercher']));
        //Definition de la page appelante pour connexion.php ou inscription.php
        $_SESSION['refer'] = ' recherche.php?rechercher='.urlencode($rechercher).'&amp;btnRechercher=Rechercher';
        
        $rechercher =  mysqli_real_escape_string($bd, $rechercher);
        $array_rechercher = explode(' ',$rechercher);
        trl_exit_to_short($array_rechercher);
        $indice = 0;
        foreach($array_rechercher as $search){
            if($indice == 0){
                $sql = "SELECT * 
                FROM article 
                WHERE (arTitre LIKE '%{$search}%' OR arResume LIKE '%{$search}%')";
            }else{
                $sql = $sql . "AND (arTitre LIKE '%{$search}%' OR arResume LIKE '%{$search}%')";
            }
            $indice++;
        }
        $sql = $sql . " ORDER BY arDatePublication DESC";

        $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
        $nb_article = mysqli_num_rows($res);

        // Si pas d'articles
        if (mysqli_num_rows($res) == 0) {
            tr_aff_erreur ('Aucun article ne correspond a votre recherche.');
            mysqli_free_result($res);
            mysqli_close($bd);
        }else{
            // Si il y a des articles
            $i = 1;
            while ($t = mysqli_fetch_assoc($res)) {
                $id = $t['arID'];
                $titre = em_html_proteger_sortie($t['arTitre']);
                $resume = em_html_proteger_sortie($t['arResume']);
                if($i == 1){ 
                    $date = tr_mois_annee_string($t['arDatePublication']);
                    echo '<section>', 
                    '<h2>',$date,'</h2>';
                    tr_aff_article($id,$titre,$resume,$date);
                }else{
                    if(tr_same_mois_annee_string(tr_mois_annee_string($t['arDatePublication']),$date)){
                        tr_aff_article($id,$titre,$resume,$date);
                    }else{
                        $date = tr_mois_annee_string($t['arDatePublication']);
                        echo '</section>',
                                '<section>',
                                '<h2>',$date,'</h2>';
                        tr_aff_article($id,$titre,$resume,$date);
                    }
                    $date = tr_mois_annee_string($t['arDatePublication']);
                    if($i == $nb_article){
                        echo '</section>';
                    }
                }
            $i++;
            }
            mysqli_free_result($res);
            mysqli_close($bd);
        }
    }

    tr_aff_footer(); 
    ob_end_flush();

    // FONCTION LOCALE
    //_______________________________________________________________
    /**
     *  Arret de la recherche si jamais un des mots dans la barre de recherche est inférieur a 3 caractères
     *
     *  @param array     $array_search le tableau de chaîne qui represente la recherche
     */
    function trl_exit_to_short($array_search) {
        $assezLong = 0;
        foreach($array_search as $search){
            if(strlen($search) >= 3){
                $assezLong++;
            }
        }
        if($assezLong == 0){
            tr_aff_erreur ('Au moins un des critères de recherche doit être constitué de trois caractères minimum.');
                exit();
        }
    }
?>
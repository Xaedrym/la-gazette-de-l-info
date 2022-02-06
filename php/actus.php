<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    em_aff_entete('L\'actu', 'L\'actu');
    echo '<main>';

    $bd = tr_bd_connecter();

    $article_par_pages = 4;

    $nb_article_sql = "SELECT arID FROM article";
    $nb_article_res = mysqli_query($bd, $nb_article_sql) or tr_bd_erreur($bd, $nb_article_sql);
    $nb_article = mysqli_num_rows($nb_article_res);

    $nb_pages = ceil($nb_article / $article_par_pages);

    if(isset($_GET['page']) && !empty($_GET['page']) && $_GET['page'] > 0 && $_GET['page'] <= $nb_pages){
        $page = intval($_GET['page']);
        $page_courrante = $_GET['page'];
    }else{
        $page_courrante = 1;
    }
    //Definition de la page appelante pour connexion.php ou inscription.php
    $_SESSION['refer'] = ' actus.php?page='.$page_courrante;

    $depart = ($page_courrante - 1) * $article_par_pages;

    echo  '<section id="secPages">',
            '<p>Pages : ';                      
    for($i = 1; $i <= $nb_pages; $i++){
        if($i == $page_courrante){
            echo '<a href="actus.php?page=',$i,'" class="actif"> ', $i ,' </a>';
        }else{
            echo '<a href="actus.php?page=',$i,'" class="pages"> ', $i ,' </a>';
        }
    }
   echo '</p></section>';

    $sql = "SELECT * 
            FROM article 
            ORDER BY arDatePublication DESC
            LIMIT {$depart},{$article_par_pages}";

    $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);

    // Si pas d'articles (normalement n'est pas sensé arrivé, sécurité)
    if (mysqli_num_rows($res) == 0) {
        tr_aff_erreur ('Aucun article n\'a été publié.');
        mysqli_free_result($res);
        mysqli_close($bd);
    }else{
        // Si il y a des articles
        $i = (int)1;
        while ($t = mysqli_fetch_assoc($res)) {
            if($i == 1){ 
                $id = $t['arID'];
                $titre = em_html_proteger_sortie($t['arTitre']);
                $resume = em_html_proteger_sortie($t['arResume']);
                $date = tr_mois_annee_string($t['arDatePublication']);
                echo '<section>', 
                '<h2>',$date,'</h2>';
                tr_aff_article($id,$titre,$resume,$date);
            }else{
                $id = $t['arID'];
                $titre = em_html_proteger_sortie($t['arTitre']);
                $resume = em_html_proteger_sortie($t['arResume']);
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
                if($i == $article_par_pages){
                    echo '</section>';
                }
            }
            $i++;
        }
        mysqli_free_result($res);
        mysqli_close($bd);
    }
    tr_aff_footer();
    ob_end_flush();
?>
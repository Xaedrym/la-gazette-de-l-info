<?php
    require_once('./bibli_gazette.php');
    require_once('./bibli_generale.php');

    // bufferisation des sorties
    ob_start();

    // démarrage de la session
    session_start();

    if (!isset($_SESSION['user']) || $_SESSION['user']['administrateur'] == false) {
        header ('location: ../index.php');
        exit();
    }

    em_aff_entete('Administration', 'Administration');

    echo '<main>';
        
    // Premier affichage
    if (isset($_POST['btnEnregistrer'])) {
        $succes = 1;
    }else{
        $succes = '';
    }
    
    trl_aff_admin($succes);

    //header('location: administration.php');

    tr_aff_footer();
    ob_end_flush(); //FIN DU SCRIPT


    // ______________________________ FONCTIONS LOCALES ______________________________ //
    
    function trl_aff_admin($succes){
        echo '<section>',
        '<h2>Administration des utilisateurs</h2>',
        '<form action="administration.php" method="post">';
    
        if($succes == 1){
            tr_message_succes('Les modifications ont été enregistrées');
        }
    
        echo '<table>',
            '<tr>',
                '<td>Pseudo</td>',
                '<td>Statut</td>',
                '<td>Nombre commentaire publié</td>',
                '<td>Nombre de commentaire moyen par article</td>',
                '<td>Nombre d\'article publié</td>',
            '</tr>';

            $bd = tr_bd_connecter();
            $sql = "SELECT utPseudo, utStatut, COUNT(C1.coID) AS NbComPublie, COUNT(C2.coID) AS NbComArticle, COUNT(distinct arID) AS NbArticle
                    FROM (utilisateur LEFT OUTER JOIN commentaire AS C1 ON utPseudo=C1.coAuteur)
                        LEFT OUTER JOIN (article LEFT OUTER JOIN commentaire AS C2 ON arID=C2.coArticle) ON utPseudo=arAuteur
                        GROUP BY utPseudo, utStatut";

            $res = mysqli_query($bd, $sql) or tr_bd_erreur($bd, $sql);
            while ($t = mysqli_fetch_assoc($res)) {
                $pseudo = em_html_proteger_sortie(trim($t['utPseudo']));
                $statut = $t['utStatut']; 
                $NbComPublie = $t['NbComPublie'];
                $NbComArticle = $t['NbComArticle'];
                $NbArticle = $t['NbArticle'];
                if($NbArticle != 0){
                    $NbComArticleMoyen = ($NbComArticle / $NbArticle);
                }else{
                    $NbComArticleMoyen = 0;
                }
                echo '<tr>',
                    '<td>',$pseudo,'</td>';
                    if($pseudo == $_SESSION['user']['pseudo']){
                        if($statut == 3){
                            echo '<td>Admin&Redac</td>';
                        }else{
                            echo '<td>Administrateur</td>';
                        }  
                    }else{
                        if (isset($_POST['btnEnregistrer'])){
                            $statutPseudo = $pseudo.'_statut';
                            if($statut != $_POST[$statutPseudo]){
                                $statut = $_POST[$statutPseudo];
                                $pseudo =mysqli_real_escape_string($bd, $pseudo);
                                $sql1 = "UPDATE utilisateur SET utStatut='{$statut}' WHERE utPseudo='{$pseudo}'";
                                mysqli_query($bd, $sql1) or tr_bd_erreur($bd, $sql1);
                                $sqlSearch = "SELECT rePseudo FROM redacteur WHERE rePseudo='{$pseudo}'";
                                $resSearch = mysqli_query($bd, $sqlSearch) or tr_bd_erreur($bd, $sqlSearch);
                                if ( mysqli_num_rows($resSearch) == 0 ){
                                    if(($statut%2) == 1){ 
                                        $sqlInsert = "INSERT INTO redacteur SET rePseudo='{$pseudo}', reFonction=NULL, reBio='', reCategorie=3";
                                        mysqli_query($bd, $sqlInsert) or tr_bd_erreur($bd, $sqlInsert);
                                    }
                                }
                            }
                            echo '<td>',em_aff_liste($pseudo.'_statut', array(0 => 'Aucun', 1 => 'Rédacteur', 2 => 'Administrateur', 3 => 'Admin&Redac'), $statut),'</td>';
                        }else{
                            echo '<td>',em_aff_liste($pseudo.'_statut', array(0 => 'Aucun', 1 => 'Rédacteur', 2 => 'Administrateur', 3 => 'Admin&Redac'), $statut),'</td>';
                        }
                    }
                    echo '<td>',$NbComPublie,'</td>',
                    '<td>',$NbComArticleMoyen,'</td>',
                    '<td>',$NbArticle,'</td>',
                '</tr>';
            }
            mysqli_free_result($res);
            mysqli_close($bd);
            
            echo '<tr>',
            '<td colspan="5">',
                '<input type="submit" name="btnEnregistrer" value="Enregistrer">',
                '<input type="reset" value="Réinitialiser">', 
            '</td>',
            '</tr>',
        '</table>',
        '</form>',
        '</section>';
    }
?>
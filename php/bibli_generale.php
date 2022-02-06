<?php
    //____________________________________________________________________________
    /** 
     *  Ouverture de la connexion à la base de données
     *  En cas d'erreur de connexion le script est arrêté.
     *
     *  @return objet 	connecteur à la base de données
     */
    function tr_bd_connecter() {
        $conn = mysqli_connect(BD_SERVER, BD_USER, BD_PASS, BD_NAME);
        if ($conn !== FALSE) {
            //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
            //de données depuis et vers le serveur de base de données.
            mysqli_set_charset($conn, 'utf8') 
            or tr_bd_erreur_exit('<h4>Erreur lors du chargement du jeu de caractères utf8</h4>');
            return $conn;     // ===> Sortie connexion OK
        }
        // Erreur de connexion
        // Collecte des informations facilitant le debugage
        $msg = '<h4>Erreur de connexion base MySQL</h4>'
                .'<div style="margin: 20px auto; width: 350px;">'
                .'BD_SERVER : '. BD_SERVER
                .'<br>BD_USER : '. BD_USER
                .'<br>BD_PASS : '. BD_PASS
                .'<br>BD_NAME : '. BD_NAME
                .'<p>Erreur MySQL numéro : '.mysqli_connect_errno()
                //appel de htmlentities() pour que les éventuels accents s'affiche correctement
                .'<br>'.htmlentities(mysqli_connect_error(), ENT_QUOTES, 'ISO-8859-1')  
                .'</div>';
        tr_bd_erreur_exit($msg);
    }

    //____________________________________________________________________________
    /**
     * Arrêt du script si erreur base de données 
     *
     * Affichage d'un message d'erreur, puis arrêt du script
     * Fonction appelée quand une erreur 'base de données' se produit :
     * 		- lors de la phase de connexion au serveur MySQL
     *		- ou indirectement lorsque l'envoi d'une requête échoue
    *
    * @param string	$msg	Message d'erreur à afficher
    */
    function tr_bd_erreur_exit($msg) {
        ob_end_clean();	// Suppression de tout ce qui a pu être déja généré

        echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">',
                '<title>Erreur base de données</title>',
                '<style>',
                    'table{border-collapse: collapse;}td{border: 1px solid black;padding: 4px 10px;}',
                '</style>',
                '</head><body>',
                $msg,
                '</body></html>';
        exit(1);		// ==> ARRET DU SCRIPT
    }


    //____________________________________________________________________________
    /**
     * Gestion d'une erreur de requête à la base de données.
     *
     * A appeler impérativement quand un appel de mysqli_query() échoue 
     * Appelle la fonction tr_bd_erreurExit() qui affiche un message d'erreur puis termine le script
     *
     * @param objet		$bd		Connecteur sur la bd ouverte
     * @param string	$sql	requête SQL provoquant l'erreur
     */
    function tr_bd_erreur($bd, $sql) {
        $errNum = mysqli_errno($bd);
        $errTxt = mysqli_error($bd);

        // Collecte des informations facilitant le debugage
        $msg =  '<h4>Erreur de requête</h4>'
                ."<pre><b>Erreur mysql :</b> $errNum"
                ."<br> $errTxt"
                ."<br><br><b>Requête :</b><br> $sql"
                .'<br><br><b>Pile des appels de fonction</b></pre>';

        // Récupération de la pile des appels de fonction
        $msg .= '<table>'
                .'<tr><td>Fonction</td><td>Appelée ligne</td>'
                .'<td>Fichier</td></tr>';

        $appels = debug_backtrace();
        for ($i = 0, $iMax = count($appels); $i < $iMax; $i++) {
            $msg .= '<tr style="text-align: center;"><td>'
                    .$appels[$i]['function'].'</td><td>'
                    .$appels[$i]['line'].'</td><td>'
                    .$appels[$i]['file'].'</td></tr>';
        }

        $msg .= '</table>';

        tr_bd_erreur_exit($msg);	// ==> ARRET DU SCRIPT
    }

    //____________________________________________________________________________
    /** 
     *  Protection des sorties (code HTML généré à destination du client).
     *
     *  Fonction à appeler pour toutes les chaines provenant de :
     *      - de saisies de l'utilisateur (formulaires)
     *      - de la bdD
     *  Permet de se protéger contre les attaques XSS (Cross site scripting)
     *  Convertit tous les caractères éligibles en entités HTML, notamment :
     *      - les caractères ayant une signification spéciales en HTML (<, >, ...)
     *      - les caractères accentués
     *
     *  @param  string  $text   la chaine à protéger  
     *  @return string  la chaîne protégée
     */
    function tr_protect_sortie($str) {
        $str = trim($str);
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }

    //____________________________________________________________________________
    /**
     * Fonction donnant l'age actuel d'une personne
     * 
     *  @param  int  $naissance_j  Le jour de la date de naissance
     *  @param  int  $naissance_m  Le mois de la date de naissance
     *  @param  int  $naissance_a  L'annee de la date de naissance
     *  */
    function tr_age($naissance_j, $naissance_m, $naissance_a) {
        $aujourdhui = explode('/', date('d/m/Y'));
        if(($naissance_m < $aujourdhui[1]) || (($naissance_m == $aujourdhui[1]) && ($naissance_j <= $aujourdhui[0]))){
            return $aujourdhui[2] - $naissance_a;
        }
        return $aujourdhui[2] - $naissance_a - 1;
    }

    //_______________________________________________________________
    /**
    * Transformation d'un date amj en clair.
    *
    * Aucune vérification n'est faite sur la validité de la date car
    * on considère que c'est bien une date valide sous la forme aaaammjj
    *
    * @param integer    $amj        La date sous la forme aaaammjj
    *
    * @return string    La date sous la forme jj mois aaaa (1 janvier 2000)
    */
    function tr_date_amj_clair($amj) {
        $jj = substr($amj, -2);
        $mm = (int)substr($amj, 4, 2);

        return $jj.' '.fd_get_mois($mm).' '.substr($amj, 0, 4);
    }

    // CORRECTION DES PROFS //

    /** 
     *  Protection des sorties (code HTML généré à destination du client).
     *
     *  Fonction à appeler pour toutes les chaines provenant de :
     *      - de saisies de l'utilisateur (formulaires)
     *      - de la bdD
     *  Permet de se protéger contre les attaques XSS (Cross site scripting)
     *  Convertit tous les caractères éligibles en entités HTML, notamment :
     *      - les caractères ayant une signification spéciales en HTML (<, >, ", ', ...)
     *      - les caractères accentués
     * 
     *  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
     *  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées. 
     *
     *  @param  mixed  $content   la chaine à protéger ou un tableau contenant des chaines à protéger 
     *  @return mixed             la chaîne protégée ou le tableau
     */
    function em_html_proteger_sortie($content) {
        if (is_array($content)) {
            foreach ($content as &$value) {
                $value = em_html_proteger_sortie($value);   
            }
            unset ($value); // à ne pas oublier (de façon générale)
            return $content;
        }
        if (is_string($content)){
            return htmlentities($content, ENT_QUOTES, 'UTF-8');
        }
        return $content;
    }

    //___________________________________________________________________
    /**
     * Teste si une valeur est une valeur entière
     *
     * @param mixed     $x  valeur à tester
     * @return boolean  TRUE si entier, FALSE sinon
     */
    function em_est_entier($x) {
        return is_numeric($x) && ($x == (int) $x);
    }

    //___________________________________________________________________
    /**
     * Teste si un nombre est compris entre 2 autres
     *
     * @param integer   $x  nombre ‡ tester
     * @return boolean  TRUE si ok, FALSE sinon
     */
    function em_est_entre($x, $min, $max) {
        return ($x >= $min) && ($x <= $max);
    }


    //___________________________________________________________________
    /**
     * Renvoie un tableau contenant le nom des mois (utile pour certains affichages)
     *
     * @return array    Tableau à indices numériques contenant les noms des mois
     */
    function em_get_tableau_mois(){
        return array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    }

    //___________________________________________________________________
    /**
     * Contrôle des clés présentes dans les tableaux $_GET ou $_POST - piratage ?
     *
     * Cette fonction renvoie false en présence d'une suspicion de piratage 
     * et true quand il n'y a pas de problème détecté.
     *
     * Soit $x l'ensemble des clés contenues dans $_GET ou $_POST 
     * L'ensemble des clés obligatoires doit être inclus dans $x.
     * De même $x doit être inclus dans l'ensemble des clés autorisées, formé par l'union de l'ensemble 
     * des clés facultatives et de l'ensemble des clés obligatoires.
     * Si ces 2 conditions sont vraies, la fonction renvoie true, sinon, elle renvoie false.
     * Dit autrement, la fonction renvoie false si une clé obligatoire est absente ou 
     * si une clé non autorisée est présente; elle renvoie true si "tout va bien"
     * 
     * @param string    $tab_global 'post' ou 'get'
     * @param array     $cles_obligatoires tableau contenant les clés qui doivent obligatoirement être présentes
     * @param array     $cles_facultatives tableau contenant les clés facultatives
     * @global array    $_GET
     * @global array    $_POST
     * @return boolean  true si les paramètres sont corrects, false sinon
     */
    function em_parametres_controle($tab_global, $cles_obligatoires, $cles_facultatives = array()){
        $x = strtolower($tab_global) == 'post' ? $_POST : $_GET;

        $x = array_keys($x);
        // $cles_obligatoires doit être inclus dans $x
        if (count(array_diff($cles_obligatoires, $x)) > 0){
            return false;
        }
        // $x doit être inclus dans $cles_obligatoires Union $cles_facultatives
        if (count(array_diff($x, array_merge($cles_obligatoires,$cles_facultatives))) > 0){
            return false;
        }
        
        return true;
    }

    //___________________________________________________________________
    /**
     * Affiche une liste déroulante à partir des options passées en paramètres.
     *
     * @param string    $nom       Le nom de la liste déroulante (valeur de l'attribut name)
     * @param array     $options   Un tableau associatif donnant la liste des options sous la forme valeur => libelle 
     * @param string    $default   La valeur qui doit être sélectionnée par défaut. 
     */
    function em_aff_liste($nom, $options, $defaut) {
        echo '<select name="', $nom, '">';
        foreach ($options as $valeur => $libelle) {
            echo '<option value="', $valeur, '"', (($defaut == $valeur) ? ' selected' : '') ,'>', $libelle, '</option>';
        }
        echo '</select>';
    }

    //___________________________________________________________________
    /**
     * Affiche une liste déroulante représentant les 12 mois de l'année
     *
     * @param string    $nom       Le nom de la liste déroulante (valeur de l'attribut name)
     * @param int       $default   Le mois qui doit être sélectionné par défaut (1 pour janvier)
     */
    function em_aff_liste_mois($nom, $defaut) {
        $mois = em_get_tableau_mois();
        $m = array();
        foreach ($mois as $k => $v) {
            $m[$k+1] = mb_strtolower($v, 'UTF-8');   
            // comme on est en UTF-8 on utilise la fonction mb_strtolower
            // voir : https://www.php.net/manual/fr/function.mb-strtolower.php
        }
        em_aff_liste($nom, $m, $defaut);
    }

    //___________________________________________________________________
    /**
     * Affiche une liste déroulante d'une suite de nombre à partir des options passées en paramètres.
     *
     * @param string    $nom       Le nom de la liste déroulante (valeur de l'attribut name)
     * @param int       $min       La valeur minimale de la liste
     * @param int       $max       La valeur maximale de la liste 
     * @param int       $pas       Le pas d'itération (si positif, énumération croissante, sinon décroissante) 
     * @param int       $default   La valeur qui doit être sélectionnée par défaut. 
     */
    function em_aff_liste_nombre($nom, $min, $max, $pas, $defaut) {
        echo '<select name="', $nom, '">';
        if ($pas > 0) {
            for ($i=$min; $i <= $max; $i += $pas) {
                echo '<option value="', $i, '"', (($defaut == $i) ? ' selected' : '') ,'>', $i, '</option>';
            }
        }
        else {
            for ($i=$max; $i >= $min; $i += $pas) {
                echo '<option value="', $i, '"', (($defaut == $i) ? ' selected' : '') ,'>', $i, '</option>';
            }
        }
        echo '</select>';
    }

    //___________________________________________________________________
    /**
     * Affiche 3 listes déroulantes (jour, mois, année) représentant une date
     *
     * La liste des jours est une liste de nombres (1-31) nommée {$name}_j
     * La liste des mois, nommée {$name}_m, est une liste d'options associant le nom du mois (libellé) à une valeur entière
     * (exemple : février est associé à la valeur 2)
     * La liste des années, nommée {$name}_a, est une liste de nombres ({$annee_min}-{$annee_max})
     * Si le jour sélectionné vaut 0, la fonction sélectionne le jour courant. Idem pour le mois et l'année.
     *
     * @param string    $name           Le nom utilisé comme préfixe pour nommer les listes déroulantes
     * @param int       $annee_min      La plus petite année affichée
     * @param int       $annee_max      La plus grande année affichée
     * @param int       $j_s            Le jour sélectionné
     * @param int       $m_s            Le mois sélectionné (1 pour janvier)
     * @param int       $a_s            L'année sélectionnée
     * @param int       $pas_annee      Le pas d'itération de l'année (si positif, énumération croissante, sinon décroissante) 
     */
    function em_aff_listes_date($name, $annee_min, $annee_max, $j_s = 0, $m_s = 0, $a_s = 0, $pas_annee = -1){ 
        list($jj, $mm, $aa) = explode('-', date('j-n-Y'));
        em_aff_liste_nombre("{$name}_j", 1, 31, 1, $j_s ? $j_s : $jj);
        em_aff_liste_mois("{$name}_m", $m_s ? $m_s : $mm);
        em_aff_liste_nombre("{$name}_a", $annee_min, $annee_max, $pas_annee, $a_s ? $a_s : $aa);
    }

    //___________________________________________________________________
    /**
     * Affiche une ligne d'un tableau permettant la saisie d'une date
     *
     * La ligne est constituée de 2 cellules :
     * - la 1ère cellule contient un libellé
     * - la 2ème cellule contient les 3 listes déroulantes (jour, mois, année) représentant la date
     *
     * @param string    $libelle        Le libellé affiché à gauche des listes déroulantes
     * @param string    $name           Le nom utilisé comme préfixe pour nommer les listes déroulantes
     * @param int       $annee_min      La plus petite année affichée
     * @param int       $annee_max      La plus grande année affichée
     * @param int       $j_s            Le jour sélectionné
     * @param int       $m_s            Le mois sélectionné (1 pour janvier)
     * @param int       $a_s            L'année sélectionnée
     * @param int       $pas_annee      Le pas d'itération de l'année (si positif, énumération croissante, sinon décroissante) 
     */
    function em_aff_ligne_date($libelle, $name, $annee_debut, $annee_fin, $j_s = 0, $m_s = 0, $a_s = 0, $pas_annee = -1){
        echo '<tr>', '<td>', $libelle, '</td>', '<td>';
        em_aff_listes_date($name, $annee_debut, $annee_fin, $j_s, $m_s, $a_s, $pas_annee);
        echo '</td>', '</tr>';
    }


    //___________________________________________________________________
    /**
     * Affiche une ligne d'un tableau permettant la saisie d'un champ input de type 'text', 'password' ou 'email'
     *
     * La ligne est constituée de 2 cellules :
     * - la 1ère cellule contient un label permettant un "contrôle étiqueté" de l'input 
     * - la 2ème cellule contient l'input
     *
     * @param string    $type           Le type de l'input : 'text', 'password' ou 'email'
     * @param string    $libelle        Le label associé à l'input
     * @param string    $name           Le nom de l'input
     * @param string    $value          La valeur de l'input
     * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
     * @param string    $prefix_id      Le préfixe utilisé pour l'id de l'input, ce qui donne un id égal à {$prefix_id}{$name}
     */
    function em_aff_ligne_input($type, $libelle, $name, $value = '', $attributs = array(), $prefix_id = 'text'){
        echo    '<tr>', 
                    '<td><label for="', $prefix_id, $name, '">', $libelle, '</label></td>',
                    '<td><input type="', $type, '" name="', $name, '" id="', $prefix_id, $name, '" value="', $value,'"'; 
                    
        foreach ($attributs as $cle => $value){
            echo ' ', $cle, ($value ? "='{$value}'" : '');
        }
        echo '></td></tr>';
    }

    //___________________________________________________________________
    /**
     * Affiche un groupe de boutons radio contenu dans un élément label
     *
     * @param string    $name           Le nom des input de type radio
     * @param array     $options        Un tableau associatif donnant la liste des choix possibles sous la forme valeur => libelle 
     * @param mixed     $default        La valeur qui doit être sélectionnée par défaut. 
     * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
     */
    function em_aff_input_radio($name, $options, $defaut, $attributs = array()){
        foreach ($options as $valeur => $libelle){
            echo '<label><input type="radio" name="', $name, '" value="', $valeur, '"', 
            ($valeur == $defaut ? ' checked' : '');
            foreach ($attributs as $cle => $value){
                echo ' ', $cle, ($value ? "='{$value}'" : '');
            }
            echo '> ',$libelle, '</label>';
        }
    }

    //___________________________________________________________________
    /**
     * Affiche une ligne d'un tableau contenant un libellé et un groupe de boutons radio
     *
     * @param string    $libelle        Le libellé associé aux boutons radio
     * @param string    $name           Le nom des input de type radio
     * @param array     $options        Un tableau associatif donnant la liste des choix possibles sous la forme valeur => libelle 
     * @param mixed     $default        La valeur qui doit être sélectionnée par défaut. 
     * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
     */
    function em_aff_ligne_input_radio($libelle, $name, $options, $defaut, $attributs = array()){
        echo '<tr>',
                '<td>', $libelle, '</td>',
                '<td>';
        em_aff_input_radio($name, $options, $defaut, $attributs);
        echo '</td></tr>';
    }

    //___________________________________________________________________
    /**
     * Affiche un input de type checkbox suivi d'un libellé
     *
     * @param string    $libelle        Le libellé associé à la case à cocher
     * @param string    $name           Le nom des input de type checkbox
     * @param string    $value          La valeur de l'input
     * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
     */
    function em_aff_input_checkbox($libelle, $name, $value = 1, $attributs=array()){
        echo '<label><input type="checkbox" name="', $name, '" value="', $value, '"';
        foreach ($attributs as $cle => $value){
            echo ' ', $cle, ($value ? "='{$value}'" : '');
        }
        echo '> ', $libelle, '</label>';
    }

    //__________________ MOI A NOUVEAU _________//

    //___________________________________________________________________
    /**
     * Choix de l'image du redacteur (son image si existente, anonyme.jpg sinon)
     *               ou de l'article (son image si existente, none.jpg sinon)
     *
     * @param  mixed       $image  id de l'article si int, pseudo du redacteur sinon
     * @return string      $image_ret   le chemin vers l'image
     */
    function tr_choix_image($image){
        // pour un article
        $image_ret = '../upload/'.$image.'.jpg';
        if(em_est_entier($image)){
            if(is_file($image_ret)){
                $image_ret = 'upload/'.$image.'.jpg';
            }
            else{
                $image_ret = 'images/none.jpg';
            }
        }
        // pour un redacteur
        else{
            if(is_file($image_ret)){
                $image_ret = 'upload/'.$image.'.jpg';
            }
            else{
                $image_ret = 'images/anonyme.jpg';
            }
        }
        return $image_ret;
    }

    //___________________________________________________________________
    /**
     * Fonction gérant le BBcode pour un commentaire
     * A appeler pour les textes en contenant 
     *
     * @param  string      $texte  Le texte contenant du BBcode (ou non) a transformer
     * @return string      $texte   Le texte avec le BBCode filtré et devenu des tags htmls
     */
    function tr_BBCode_commentaire($texte) {
        $texte = preg_replace('#\[\#(.+)\]#isU', '&#$1;', $texte);
        $texte = preg_replace('#\[\#x(.+)\]#isU', '&#x$1;', $texte);
        return $texte;
    }

    //___________________________________________________________________
    /**
     * Fonction gérant le BBcode pour un article
     * A appeler pour les textes en contenant 
     *
     * @param  string      $texte  Le texte contenant du BBcode (ou non) a transformer
     * @return string      $texte   Le texte avec le BBCode filtré et devenu des tags htmls
     */
    function tr_BBCode_article($texte) {
        $texte = preg_replace('#\[p\](.+)\[\/p\]#isU', '<p>$1</p>', $texte);
        $texte = preg_replace('#\[gras\](.+)\[\/gras\]#isU', '<strong>$1</strong>', $texte);
        $texte = preg_replace('#\[it\](.+)\[\/it\]#isU', '<em>$1</em>', $texte);
        $texte = preg_replace('#\[citation\](.+)\[\/citation\]#isU', '<blockquote>$1</blockquote>', $texte);
        $texte = preg_replace('#\[liste\](.+)\[\/liste\]#isU', '<ul>$1</ul>', $texte);
        $texte = preg_replace('#\[item\](.+)\[\/item\]#isU', '<li>$1</li>', $texte);
        $texte = preg_replace('#\[br\]#isU', '<br>', $texte);
        $texte = preg_replace('#\[\#(.+)\]#isU', '&#$1;', $texte);
        $texte = preg_replace('#\[\#x(.+)\]#isU', '&#x$1;', $texte);
        
        // [a:url]contenu[/a] filter_var() pour autres sites, parse_url() pour site interne
        $texte = preg_replace('#\[a:(.+)\](.+)\[\/a\]#isU', '<a href="$1">$2</a>', $texte);

        /*while(preg_match ('#\[a:(.+)\](.+)\[\/a\]#isU', $texte, $matches) && ( (filter_var ($matches[1], FILTER_VALIDATE_URL) != FALSE) || (file_exists($matches[1]) == TRUE) ) ){
            $texte = preg_replace('#\[a:'.$matches[1].'\]'.$matches[2].'\[/a\]#isU', '<a href="'.$matches[1].'">'.$matches[2].'</a>', $texte);
            var_dump(parse_url($matches[1]));
            var_dump($matches[1]);
        } */  // -> fonctionne pas de trop quoi 
        
        // [youtube:w:h:url]
        while(preg_match ('#\[youtube:([0-9]+):([0-9]+):([^ ]+)\]#isU', $texte, $matches) && filter_var($matches[3], FILTER_VALIDATE_URL) != FALSE){
            $texte = preg_replace('#\[youtube:'.$matches[1].':'.$matches[2].':'.$matches[3].'\]#isU', '<iframe width='.$matches[1].' height'.$matches[2].' src='.$matches[3].' allowfullscreen></iframe>', $texte);
        }
        
        // [youtube:w:h:url legende]
        while(preg_match ('#\[youtube:([0-9]{1,3}):([0-9]{1,3}):([^ ]+) (.+)\]#isU', $texte, $matches) && filter_var($matches[3], FILTER_VALIDATE_URL) != FALSE){
            $texte = preg_replace('#\[youtube:'.$matches[1].':'.$matches[2].':'.$matches[3].' '.$matches[4].'\]#isU', '<figure><iframe width='.$matches[1].' height'.$matches[2].' src='.$matches[3].' allowfullscreen></iframe><figcaption>'.$matches[4].'<figcaption></figure>', $texte);
        }

        return $texte;
    }

?>
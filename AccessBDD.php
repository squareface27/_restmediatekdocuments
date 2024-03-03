<?php
include_once("ConnexionPDO.php");

/**
 * Classe de construction des requêtes SQL à envoyer à la BDD
 */
class AccessBDD {
    
    public $login="marjorie1";
    public $mdp="mediatekdocuments";
    public $bd="mediatekdocuments";
    public $serveur="localhost";
    public $port="3306";
    public $conn = null;

    /**
     * constructeur : demande de connexion à la BDD
     */
    public function __construct(){
        try{
            $this->conn = new ConnexionPDO($this->login, $this->mdp, $this->bd, $this->serveur, $this->port);
        }catch(Exception $e){
            throw $e;
        }
    }

    /**
     * récupération de toutes les lignes d'une table
     * @param string $table nom de la table
     * @return lignes de la requete
     */
    public function selectAll($table){
        if($this->conn != null){
            switch ($table) {
                case "livre" :
                    return $this->selectAllLivres();
                case "dvd" :
                    return $this->selectAllDvd();
                case "revue" :
                    return $this->selectAllRevues();
                case "maxcommande" :
                    return $this->selectMaxCommande();
                case "genre" :
                case "public" :
                case "rayon" :
                case "etat" :
                    // select portant sur une table contenant juste id et libelle
                    return $this->selectTableSimple($table);
                default:
                    // select portant sur une table, sans condition
                    return $this->selectTable($table);
            }
        }else{
            return null;
        }
    }

    /**
     * récupération des lignes concernées
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs de recherche
     * @return lignes répondant aux critères de recherches
     */
    public function select($table, $champs){
        if($this->conn != null && $champs != null){
            switch($table){
                case "exemplaire" :
                    return $this->selectExemplairesRevue($champs['id']);
                case "commandedocument" :
                    return $this->selectCommandeDocument($champs['id']);
                default:
                    // cas d'un select sur une table avec recherche sur des champs
                    return $this->selectTableOnConditons($table, $champs);
            }
        }else{
                return null;
        }
    }

    /**
     * récupération de toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return lignes triées sur lebelle
     */
    public function selectTableSimple($table){
        $req = "select * from $table order by libelle;";
        return $this->conn->query($req);
    }

    /**
     * récupération de toutes les lignes d'une table
     * @param string $table
     * @return toutes les lignes de la table
     */
    public function selectTable($table){
        $req = "select * from $table;";
        return $this->conn->query($req);
    }
    
    /**
     * récupération des lignes d'une table dont les champs concernés correspondent aux valeurs
     * @param type $table
     * @param type $champs
     * @return type
     */
    public function selectTableOnConditons($table, $champs){
        // construction de la requête
        $requete = "select * from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-3);
        return $this->conn->query($requete, $champs);
    }

    /**
     * récupère le nombre maximal de commande
     */
    public function selectMaxCommande(){
        $req = "SELECT MAX(id) FROM commande;";
        return $this->conn->query($req);
    }

    /**
     * Retrieve all rows from the Livre table and associated tables
     * @return query rows
     */
    public function selectAllLivres(){
        $req = "SELECT l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "FROM livre l JOIN document d ON l.id=d.id ";
        $req .= "JOIN genre g ON g.id=d.idGenre ";
        $req .= "JOIN public p ON p.id=d.idPublic ";
        $req .= "JOIN rayon r ON r.id=d.idRayon ";
        $req .= "ORDER BY titre ";
        return $this->conn->query($req);
    }


    /**
     * récupération de toutes les lignes de la table DVD et les tables associées
     * @return lignes de la requete
     */
    public function selectAllDvd(){
        $req = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from dvd l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";
        return $this->conn->query($req);
    }

    /**
     * récupération de toutes les lignes de la table Revue et les tables associées
     * @return lignes de la requete
     */
    public function selectAllRevues(){
        $req = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $req .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $req .= "from revue l join document d on l.id=d.id ";
        $req .= "join genre g on g.id=d.idGenre ";
        $req .= "join public p on p.id=d.idPublic ";
        $req .= "join rayon r on r.id=d.idRayon ";
        $req .= "order by titre ";
        return $this->conn->query($req);
    }

    /**
     * récupération de tous les exemplaires d'une revue
     * @param string $id id de la revue
     * @return lignes de la requete
     */
    public function selectExemplairesRevue($id){
        $param = array(
                "id" => $id
        );
        $req = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $req .= "from exemplaire e join document d on e.id=d.id ";
        $req .= "where e.id = :id ";
        $req .= "order by e.dateAchat DESC";
        return $this->conn->query($req, $param);
    }

    /**
     * Récupération des commandes d'un document
     * @param string $id
     * @return query rows
     */
    public function selectCommandeDocument($id){
        $params = array (
            "id" => $id
        );
        $query = "SELECT c.id, cd.nbExemplaire AS nombreExemplaire, s.id AS idSuivi,  ";
        $query .= "s.libelle AS libelleSuivi, cd.idLivreDvd, c.dateCommande, c.montant  ";
        $query .= "FROM commande c JOIN commandedocument cd ON c.id = cd.id ";
        $query .= "JOIN suivi s ON cd.idSuivi = s.id ";
        $query .= "WHERE cd.idLivreDvd = :id ";
        $query .= "ORDER BY dateCommande DESC";
        return $this->conn->query($query, $params);
    }

    /**
     * suppresion d'une ou plusieurs lignes dans une table
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs
     * @return true si la suppression a fonctionné
     */
    public function delete($table, $champs){
        if($this->conn != null){
            // construction de la requête
            $requete = "delete from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);
            return $this->conn->execute($requete, $champs);
        }else{
            return null;
        }
    }

    /**
     * ajout d'une ligne dans une table
     * @param string $table nom de la table
     * @param array $champs nom et valeur de chaque champs de la ligne
     * @return true si l'ajout a fonctionné
     */
    public function insertOne($table, $champs){
        if($this->conn != null && $champs != null){
            // construction de la requête
            $requete = "insert into $table (";
            foreach ($champs as $key => $value){
                $requete .= "$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $requete .= ") values (";
            foreach ($champs as $key => $value){
                $requete .= ":$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $requete .= ");";
            return $this->conn->execute($requete, $champs);
        }else{
            return null;
        }
    }

    /**
     * modification d'une ligne dans une table
     * @param string $table nom de la table
     * @param string $id id de la ligne à modifier
     * @param array $param nom et valeur de chaque champs de la ligne
     * @return true si la modification a fonctionné
     */
    public function updateOne($table, $id, $champs){
        if($this->conn != null && $champs != null){
            // construction de la requête
            $requete = "update $table set ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key,";
            }
            // (enlève la dernière virgule)
            $requete = substr($requete, 0, strlen($requete)-1);
            $champs["id"] = $id;
            $requete .= " where id=:id;";
            return $this->conn->execute($requete, $champs);
        }else{
            return null;
        }
    }

    /**
     * Ajout d'une commande d'un document dans la base de données
     */
    public function insertCommandeDocument($champs){
        $commande = [
            "id" => $champs["Id"],
            "dateCommande" => $champs["dateCommande"],
            "montant" => $champs["Montant"]
        ];
        $resultCommande = $this->conn->insertOne("commande", $commande);

        if ($resultCommande) {
            $commandedocument = [
                "id" => $champs["Id"],
                "nbExemplaire" => $champs["nbExemplaire"],
                "idSuivi" => $champs["idSuivi"],
                "idLivreDvd" => $champs["idLivreDvd"]
            ];
            $resultCommandedocument = $this->conn->insertOne("commandedocument", $commandedocument);

            return $resultCommandedocument;
        } else {
            $this->reponse(400, "Impossible d'ajouter une commande");
        }
    }


}

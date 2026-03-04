<?php

use function PHPUnit\Framework\isEmpty;
require_once "User.php";
require_once __DIR__ . "/../Interfaces/AmministrativoInterface.php";
/**
 *  @class Amministrativo
 *  Oggeto Amministrativo che ha come scopo quello di controllare le richieste che cercano dei dati nel db inerenti ad un amministrativo.
 *  Le funzioni che può svolgere sono:
 *      - restituire il valore dell'id dell'unità dell'amministrativo
 *      - restituire le pratiche che può vedere un'amministrativo
 *      - restituire l'id dalla mail dell'amministrativo
 *  @param int $IdUnita si salva il valore dell'id dell'unità che servirà nel caso si da la chiamata getPraticheDirettore()
 */
class Amministrativo extends User implements AmministrativoInterface{
    private $IdUnita;

    /**
     *  Richiama in costrutore di user User.php e fa una query che richiede le informazioni aggiuntive riguardanti l'id dell'unità e le salva all'interno del parametro IdUnita. In caso di elemento vuoto imposta l'id a -1
     *  @param Database $dbconn Richiama oggetto Database::query() per creare una connesione con il db
     *  @param string $query una query che in base all' id dell'amministrativo restituisce l'IdUnita
     *  @param mysqli_result $result richiama la funzione query($sql, $params = []) dell'oggetto Database.php
     *  @param array $row recupera una riga di dati dal set di risultati e la restituisce come un array associativo
     */
    public function __construct($Id) {
        parent::__construct($Id);
        $dbconn = new Database();	
        $query = "SELECT Amministrativo.IdUnita FROM Amministrativo WHERE Amministrativo.IdAmministrativo = ?";
        $results = $dbconn->query($query,[parent::getId()]);
        $row = $results->fetch_assoc();
        if(empty($row['IdUnita']))
            $this->IdUnita=-1;
        else $this->IdUnita=$row["IdUnita"];
    }

    public function getIdUnita() {
        return $this->IdUnita;
    }


    /**
     *  Funzione che restituisce le pratiche che possono essere viste da un determinato amministratore, che non è direttore, richiamando la funzione di Pratica(Pratica::getPraticheAmministrativo())
     *  @return array|null 
     */
    public function getPraticheAmministrativo() {
        return Pratica::getPraticheAmministrativo($this->getId());

    }

    /**
     *  Funzione necessaria per l'ottenimento dell'Id dalla Mail di un amministrativo per l'assegnazione del passaggio.
     * 
     *  @param string $mail contine la mail dell'amministrativo 
     *  @param Database $dbconn Richiama oggetto Database::query() per creare una connesione con il db
     *  @param string $query una query che in base alla mail dell'amministrativo restituisce l'id di quest'ultimo
     *  @param mysqli_result $result richiama la funzione query($sql, $params = []) dell'oggetto  Database.php
     *  @param array $row recupera una riga di dati dal set di risultati e la restituisce come un array associativo
     * 
     *  @return int|null $row["Id"] che contiene l'id relativo alla mail 
     * 
     */
    public static function getIdFromMail($mail) {
        $dbconn = new Database();
        $query = "SELECT Id
                    FROM Utente
                    WHERE Mail = ?;";
        $result = $dbconn->query($query,[$mail]);
        if(!empty($result)){
            $row = $result->fetch_assoc();
            return $row["Id"];
        }
        else return null;
    }
}

?>
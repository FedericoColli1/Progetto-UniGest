<?php

 require_once __DIR__ . '/../DocumentHandler.php';
 require_once __DIR__ . "/Pratica.php";
 require_once __DIR__ . "/../Interfaces/UserInterface.php";
/**
 * @class User
 * Classe che rappresenta un utente nel sistema.
 * Implementa l'interfaccia UserInterface.
 * Le funzioni che può svolgere sono:
 * - Inizializzare un utente recuperando i suoi dati dal database.
 * - Restituire i valori dei suoi attributi (ID, Nome, Mail, Pwd, DataCreazione, Jolly).
 * - Ottenere le pratiche associate all'utente.
 * - Ottenere i documenti relativi a una specifica tipologia di pratica.
 * - Aggiungere una nuova pratica.
 */
class User implements UserInterface {
    private $Id;
    private $Nome;
    private $Mail;
    private $Pwd;
    private $DataCreazione;
    private $Jolly;
    private $DocHandler;
    private $dbconn;

/**
     * Costruttore della classe User.
     * Inizializza gli attributi dell'utente recuperando i suoi dati dal database tramite l'ID fornito.
     *
     * @param int $Id L'ID univoco dell'utente.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare i dati dell'utente.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $row Un array associativo contenente i dati dell'utente.
     */
    public function __construct($Id) {
        $this->Id = $Id;
        $dbconn = new Database();
        $query = "SELECT Utente.Id, Utente.Nome, Utente.Mail, Utente.Pwd, Utente.DataCreazione, Utente.Jolly FROM Utente WHERE Utente.Id = ?;";
        $results = $dbconn->query($query,[$Id]);
        $row = $results->fetch_assoc();
        $this->Nome = $row["Nome"];
        $this->Mail = $row["Mail"];
        $this->Pwd = $row["Pwd"];
        $this->DataCreazione = $row["DataCreazione"];
        $this->Jolly = $row["Jolly"];
    }

    public function getId() {
        return $this->Id;
    }

    public function getNome() {
        return $this->Nome;
    }

    public function getMail() {
        return $this->Mail;
    }

    public function getPwd() {
        return $this->Pwd;
    }

    public function getDataCreazione() {
        return $this->DataCreazione;
    }

    public function getJolly() {
        return $this->Jolly;
    }

/**
     * Recupera tutte le pratiche associate a questo utente.
     * Delega la chiamata al metodo statico `getPratiche` della classe `Pratica`.
     *
     * @return array|null Un array di pratiche associate all'utente o null in caso di errore.
     */
    public function getPratiche() {
        return Pratica::getPratiche($this->Id);
    }

/**
     * Recupera la lista di documenti associati a una specifica tipologia di pratica.
     * La query cerca la colonna 'Passaggi' (presumibilmente contiene un JSON con i dettagli dei passaggi inclusi i documenti)
     * e poi tenta di estrarre i "Documenti". Nota: la riga `$documenti = $rows["Documenti"];` sembra fare riferimento a una colonna
     * "Documenti" che non è selezionata nella query "SELECT Tipologia.Passaggi".
     * Potrebbe essere un errore logico o una colonna omessa nella query.
     *
     * @param string $tipologia La tipologia di pratica di cui si vogliono ottenere i documenti.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare i passaggi della tipologia.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $rows Un array associativo contenente i dettagli dei passaggi.
     * @return mixed La lista dei documenti o un valore indefinito se la colonna "Documenti" non esiste o la query fallisce.
     */
    public function getDocumenti($tipologia) {
        $dbconn = new Database();
        $query = "SELECT Tipologia.Passaggi FROM Tipologia WHERE Tipologia.Tipologia = ?";
        try {
            $results = $dbconn->query($query, [$tipologia]);
            $rows = $results->fetch_assoc();
        } catch (Exception $e) {
            echo "Error:". $e->getMessage();
        }
        $documenti = $rows["Documenti"];
        return $documenti;
    }


/**
     * Aggiunge una nuova pratica per l'utente corrente.
     * Delega la logica di aggiunta al metodo statico `AddPratica` della classe `Pratica`.
     *
     * @param array $data Un array contenente i dati necessari per creare la pratica.
     * @return void
     */
    public function addPratica($data = []) {
        Pratica::AddPratica($this->Id,$data);
    } 
    
}

?>
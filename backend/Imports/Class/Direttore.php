<?php
require_once __DIR__ . "/../Database.php";
require_once __DIR__ . "/Amministrativo.php";
require_once __DIR__ . "/../DocumentHandler.php";
require_once __DIR__ . "/../Interfaces/DirettoreInterface.php";

/**
 * @class Direttore
 * classe derviata da Amministrativo.php, che ha come scopo quello di controllare le richieste che cercano dei dati nel db inerenti ad un direttore e serve per gestire le sue attivita' relative.
 * Le funzioni che può svolgere sono:
 *      - restituire il valore del flag che determina se l'amministrativo è un direttore (campo Direttore)
 *      - restituire le pratiche che può vedere un'amministrativo
 *      - assegnare una pratica ad uno o più amministratori della  stessa unità
 *      - restituire le mail degli amministrativi della propria unità
 *      - restituire gli amministrativi che sono assegnati ad un determinato passaggio di una pratica
 * @param bool $Direttore server per aggiungere il campo Direttore ad amministrativo per differenziarlo da un amministrativo comune
 */
class Direttore extends Amministrativo implements DirettoreInterface
{

    private $Direttore;
    /**
     * Costruttore della classe Direttore.
     * Inizializza l'oggetto Direttore, richiamando il costruttore della classe padre Amministrativo,
     * e imposta la proprietà interna $Direttore a 1 per indicare lo stato di direttore.
     *
     * @param int $Id L'ID dell'utente direttore.
     */
    public function __construct($Id)
    {
        parent::__construct($Id);
        $this->Direttore = 1;
    }

    public function getDirettore()
    {
        return $this->Direttore;
    }
    /**
     * Restituisce le pratiche che un direttore può visualizzare o gestire.
     * Richiama la funzione statica `getPraticheDirettore` della classe `Pratica`,
     * passando l'ID dell'unità a cui il direttore appartiene.
     *
     * @return array|null Un array di pratiche o `null` se non ci sono pratiche per l'unità.
     */
    public function getPraticheDirettore()
    {
        return Pratica::getPraticheDirettore($this->getIdUnita());
    }
    /**
     * Assegna un passaggio di una pratica a un amministrativo specifico.
     * Questo metodo statico inserisce un nuovo record nella tabella `Assegnazione`
     * per collegare un passaggio a un amministrativo.
     *
     * @param int $IdPassaggio L'ID del passaggio da assegnare.
     * @param int $IdAmministrativo L'ID dell'amministrativo a cui assegnare il passaggio.
     * @param Database $dbconn Oggetto Database per la connessione al DB.
     * @param string $query La query SQL per l'inserimento dell'assegnazione.
     * @param mixed $result Il risultato dell'operazione sul database.
     *
     * @return void La funzione stampa un JSON con un messaggio di successo (codice 201)
     * o di fallimento (codice 400).
     */
    public static function Assegnazione($IdPassaggio, $IdAmministrativo)
    {
        $dbconn = new Database();

        $query = "INSERT INTO Assegnazione (IdAmministrativo,IdPassaggio)
                VALUES(?,?);";
        $result = $dbconn->query($query, [$IdAmministrativo, $IdPassaggio]);
        if (!is_null($result)) {
            http_response_code(201);
            echo json_encode(["successo" => "Assegnazione avvenuta con successo"]);
        } else {
            http_response_code(400);
            echo json_encode(["fallito" => "Assegnazione fallita"]);
        }
        $dbconn->close();

    }
    /**
     * Recupera gli amministratori (non direttori) appartenenti alla stessa unità del direttore corrente.
     * Esegue una query sul database per ottenere le mail di tutti gli amministratori
     * che condividono l'ID dell'unità del direttore, escludendo altri direttori.
     *
     * @param Database $dbconn Oggetto Database per la connessione al DB.
     * @param string $query La query SQL per recuperare gli amministratori.
     * @param mysqli_result $result Il risultato della query.
     * @param array $row Un array associativo che contiene la riga di dati recuperata.
     * @param array $rows Un array per contenere tutte le righe di risultati.
     *
     * @return void La funzione stampa un JSON con le mail degli amministratori (codice 201)
     * o un messaggio di errore (codice 400) in caso di problemi o risultati vuoti.
     */
    public function getAmministrativi()
    {
        $dbconn = new Database();
        try {
            $query = "SELECT Utente.Mail
                    FROM Utente LEFT JOIN Amministrativo ON Utente.Id=Amministrativo.IdAmministrativo
                    WHERE Amministrativo.IdUnita=? AND Amministrativo.Direttore=0";
            $result = $dbconn->query($query, [parent::getIdUnita()]);
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            if ($rows != null) {
                http_response_code(201);
                echo json_encode($rows);
            } else {
                http_response_code(400);
                echo json_encode(["fallito" => "Vuoto"]);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["Error: " => $e->getMessage()]);
        }

        $dbconn->close();
    }
    /**
     * Recupera gli amministratori già assegnati a un passaggio specifico di una pratica.
     * Questa funzione interroga il database per ottenere le mail degli amministratori
     * che sono stati precedentemente assegnati a un dato passaggio.
     *
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param Database $dbconn Oggetto Database per la connessione al DB.
     * @param string $query La query SQL per recuperare gli amministratori assegnati.
     * @param mysqli_result $result Il risultato della query.
     * @param array $row Un array associativo che contiene la riga di dati recuperata.
     * @param array $rows Un array per contenere tutte le righe di risultati.
     *
     * @return void La funzione stampa un JSON con le mail degli amministratori assegnati (codice 201)
     * o un messaggio di errore (codice 400) in caso di problemi o risultati vuoti.
     */
    public function getAmministrativiAssegnati($IdPratica, $NPassaggio)
    {
        $dbconn = new Database();
        try {
            $query = "SELECT Utente.Mail
                    FROM Utente
                    JOIN Amministrativo ON Utente.Id = Amministrativo.IdAmministrativo
                    JOIN Assegnazione ON Amministrativo.IdAmministrativo = Assegnazione.IdAmministrativo
                    JOIN Passaggio ON Assegnazione.IdPassaggio = Passaggio.IdPassaggio
                    WHERE Passaggio.IdPratica = ? AND Passaggio.NPassaggio = ?";


            $result = $dbconn->query($query, [$IdPratica, $NPassaggio]);
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            if ($rows != null) {
                http_response_code(201);
                echo json_encode($rows);
            } else {
                http_response_code(400);
                echo json_encode(["fallito" => "Vuoto"]);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["Error: " => $e->getMessage()]);
        }

        $dbconn->close();
    }
}


?>
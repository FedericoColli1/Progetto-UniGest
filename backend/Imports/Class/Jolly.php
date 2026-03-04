<?php

require_once __DIR__ . "/Pratica.php";
require_once __DIR__ . "/User.php";
require_once __DIR__ . "/../Interfaces/JollyInterface.php";

/**
 * @class Jolly
 * classe derviata da @see User.php, che ha come scopo quello di controllare le richieste che cercano dei dati nel db inerenti ad un jolly e serve per gestire le sue attivita' relative.
 * Le funzioni che può svolgere sono:
 *      - eliminare una pratica e tutti i suoi passaggi sia nel db sia in memoria
 *      - terminare una pratica in modo forzato impostando un codice d'errore
 *      - terminare un passaggio, ovvero mandare lui i file necessari al posto di un amministrativo
 *      - restituire le tutte le pratiche attive o terminate
 *      - restituire le mail degli amministrativi della propria unità
 *      - restituire gli amministrativi che sono assegnati ad un determinato passaggio di una pratica
 * @param bool $Jolly server per aggiungere il campo Jolly ad uno user per differenziarlo da un utente comune
 */
Class Jolly extends User implements JollyInterface{

    private $Jolly;

    /**
     * Costruttore della classe Jolly.
     * Richiama il costruttore della classe padre User e inizializza il parametro Jolly a 1.
     * @param int $Id L'ID dell'utente Jolly.
     */
    public function __construct($Id){
        parent::__construct($Id);
        $this->Jolly=1;
    }
    /**
     * Funzione che elimina una pratica richiamando la funzione statica DeletePratica della classe Pratica.
     * @param int $IdPratica L'ID della pratica da eliminare.
     * @return void
     */
    public function DeletePratica($IdPratica) {
        Pratica::DeletePratica($IdPratica);
    }
    /**
     * Funzione che termina forzatamente una pratica richiamando la funzione statica TerminazioneForzata della classe Pratica.
     * @param int $IdPratica L'ID della pratica da terminare.
     * @param string $Codice Il codice di terminazione della pratica.
     * @return bool Ritorna true in caso di successo, false altrimenti.
     */
    public function TerminazionePratica($IdPratica,$Codice) {
        return Pratica::TerminazioneForzata($IdPratica,$Codice);
    }

    /**
     * Funzione che termina un passaggio specifico di una pratica richiamando la funzione statica Terminazione della classe Passaggio.
     * @param int $Npassaggio Il numero del passaggio da terminare.
     * @param int $IdPratica L'ID della pratica a cui appartiene il passaggio.
     * @param int $IdPassaggio L'ID del passaggio da terminare.
     * @return void
     */
    public function TerminazionePassaggio($Npassaggio,$IdPratica, $IdPassaggio) {
        Passaggio::Terminazione($Npassaggio,$IdPratica,$IdPassaggio);
    }
    /**
     * Funzione che restituisce tutte le pratiche visibili o gestibili dal Jolly richiamando la funzione statica getPraticheJolly della classe Pratica.
     * @return array|null Un array di pratiche o null se non ce ne sono.
     */
    public function getPraticheJolly() {
        return Pratica::getPraticheJolly();
    }
    /**
     * Funzione che recupera le mail degli amministrativi (non direttori) appartenenti alla stessa unità del passaggio specificato.
     *
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query che seleziona le mail degli utenti Amministrativi basandosi sull'IdUnita associato al passaggio.
     * @param mysqli_result $result Richiama la funzione query($sql, $params = []) dell'oggetto Database.php.
     * @param array $row Recupera una riga di dati dal set di risultati e la restituisce come un array associativo.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return void Stampa un JSON con le mail degli amministrativi o un messaggio di errore.
     */
    public function getAmministrativi($IdPratica, $NPassaggio)
    {
        $dbconn = new Database();
        try {
            $query = "SELECT Utente.Mail
                        FROM Utente LEFT JOIN Amministrativo ON Utente.Id=Amministrativo.IdAmministrativo
                        WHERE Amministrativo.IdUnita = (SELECT Passaggio.IdUnita
                                                            FROM Pratica JOIN Passaggio ON Pratica.IdPratica = Passaggio.IdPratica
                                                            WHERE Pratica.IdPratica = ? AND Passaggio.NPassaggio = ?)
                        AND Amministrativo.Direttore=0;";
            $result = $dbconn->query($query, [$IdPratica, $NPassaggio]);
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
     * Funzione che recupera le mail degli amministrativi specificamente assegnati a un dato passaggio di una pratica.
     *
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query che seleziona le mail degli utenti Amministrativi assegnati a un passaggio specifico.
     * @param mysqli_result $result Richiama la funzione query($sql, $params = []) dell'oggetto Database.php.
     * @param array $row Recupera una riga di dati dal set di risultati e la restituisce come un array associativo.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return void Stampa un JSON con le mail degli amministrativi assegnati o un messaggio di errore.
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


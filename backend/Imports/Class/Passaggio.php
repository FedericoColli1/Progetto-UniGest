<?php

use Dom\Document;
use PHPUnit\Framework\Constraint\IsEmpty;
use PHPUnit\Framework\Constraint\IsNull;


require_once __DIR__ . "/Pratica.php";
require_once __DIR__ . "/../Interfaces/PassaggioInterface.php";
/**
 * @class Passaggio
 * Oggetto Passaggio che rappresenta un'entità all'interno del flusso di una pratica.
 * Implementa l'interfaccia PassaggioInterface.
 * Le funzioni che può svolgere sono:
 * - Restituire i valori dei suoi attributi (ID, numero, ID pratica, ID unità, liste documenti).
 * - Aggiornare lo stato di una pratica.
 * - Terminare un passaggio specifico, verificando i documenti necessari.
 * - Aggiornare lo stato di un passaggio.
 * - Ottenere il numero di un passaggio.
 * - Ottenere l'ID di un passaggio dato il numero e l'ID pratica.
 * - Caricare documenti per un passaggio.
 * - Caricare documenti per l'inizio di una pratica.
 * - Ottenere la lista dei documenti richiesti per un passaggio.
 * - Ottenere la lista dei documenti in uscita per un passaggio.
 * - Inviare documenti relativi a un passaggio.
 * - Salvare un nome di file nella lista dei documenti in uscita.
 *
 * @param int $IdPassaggio L'ID univoco del passaggio.
 * @param int $NPassaggio Il numero progressivo del passaggio all'interno della pratica.
 * @param int $IdPratica L'ID della pratica a cui il passaggio appartiene.
 * @param int $IdUnita L'ID dell'unità responsabile del passaggio (opzionale, default -1).
 * @param string $ListaDocRichiesti Una stringa che contiene i nomi dei documenti richiesti per il passaggio (opzionale, default "").
 * @param string $ListaDocUscita Una stringa che contiene i nomi dei documenti generati/in uscita dal passaggio (opzionale, default "").
 */
Class Passaggio implements PassaggioInterface {
    private $IdPassaggio;
    private $NPassaggio;
    private $IdPratica;
    private $IdUnita;
    private $ListaDocUscita;
    private $ListaDocRichiesti;
/**
     * Costruttore della classe Passaggio.
     * Inizializza gli attributi del passaggio con i valori forniti.
     * @param int $IdPassaggio L'ID univoco del passaggio.
     * @param int $IdPratica L'ID della pratica a cui il passaggio appartiene.
     * @param int $NPassaggio Il numero progressivo del passaggio all'interno della pratica.
     * @param int $IdUnita L'ID dell'unità responsabile del passaggio.
     * @param string $ListaDocRichiesti Una stringa che contiene i nomi dei documenti richiesti per il passaggio.
     * @param string $ListaDocUscita Una stringa che contiene i nomi dei documenti generati/in uscita dal passaggio.
     */
    public function __construct($IdPassaggio, $IdPratica, $NPassaggio, $IdUnita = -1, $ListaDocRichiesti = "", $ListaDocUscita = ""){
        $this->IdPassaggio=$IdPassaggio;
        $this->NPassaggio=$NPassaggio;
        $this->IdPratica=$IdPratica;
        $this->IdUnita=$IdUnita;
        $this->ListaDocRichiesti=$ListaDocRichiesti;
        $this->ListaDocUscita=$ListaDocUscita;
    }

    public function getIdPassaggio() {
        return $this->IdPassaggio;
    }

    public function getNPassaggio() {
        return $this->NPassaggio;
    }

    public function getIdPratica() {
        return $this->IdPratica;
    }

    public function getIdUnita() {
        return $this->IdUnita;
    }

    public function getListaDocRichiesti() {
        return $this->ListaDocRichiesti;
    }

    public function getListaDocUscita() {
        return $this->ListaDocUscita;
    }
/**
     * Aggiorna lo stato di una pratica impostando il suo codice a 1 (presumibilmente "completata" o "terminata").
     * @param int $IdPratica L'ID della pratica da aggiornare.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per aggiornare il campo 'codice' della pratica.
     * @param mysqli_result $result Il risultato della query.
     * @return mixed Il risultato dell'esecuzione della query.
     */
    public static function UpdatePratica($IdPratica) {
        $dbconn = new Database();
        $query="UPDATE Pratica
                SET Pratica.codice = 1
                WHERE Pratica.IdPratica=?;";
        $result=$dbconn->query($query,[$IdPratica]);
        $dbconn->close();
        return $result;
    }

/**
     * Termina un passaggio specifico di una pratica. Verifica se tutti i documenti necessari sono stati caricati.
     * Se è l'ultimo passaggio della pratica, aggiorna anche lo stato della pratica stessa.
     * Restituisce una risposta HTTP in formato JSON che indica il successo o il fallimento dell'operazione.
     * @param int $Npassaggio Il numero del passaggio da terminare.
     * @param int $IdPratica L'ID della pratica a cui appartiene il passaggio.
     * @param int $IdPassaggio L'ID del passaggio da terminare.
     * @param int $NPassaggi Il numero massimo di passaggi per la pratica, ottenuto da Pratica::GetMaxPassaggio().
     * @param bool $check Il risultato della verifica dei documenti tramite DocumentHandler::CheckFile().
     * @param mixed $result Il risultato dell'aggiornamento del passaggio.
     * @return void Stampa un JSON che indica l'esito della terminazione.
     */
    public static function Terminazione($Npassaggio,$IdPratica,$IdPassaggio) {
        $result = NULL;
        $NPassaggi = Pratica::GetMaxPassaggio($IdPratica);
        $check = DocumentHandler::CheckFile( $Npassaggio, $IdPratica,$NPassaggi);
        if($check == TRUE){
            $result = self::UpdatePassaggio((int)$IdPassaggio,(int)$IdPratica);
            
            if($Npassaggio==$NPassaggi-1) {
                self::UpdatePratica((int)$IdPratica);
            }
            
            if($result != null && $result != 0) {
                http_response_code(202);
                echo json_encode(["success" => "Terminata con successo"]);
            }
            else {
                http_response_code(409);
                echo json_encode(["failed" => "Terminata con errore"]);
            }
        }
    }

/**
     * Aggiorna lo stato di un passaggio, impostando il campo 'terminato' a TRUE.
     * @param int $IdPassaggio L'ID del passaggio da aggiornare.
     * @param int $IdPratica L'ID della pratica a cui appartiene il passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per aggiornare il campo 'terminato' del passaggio.
     * @param mysqli_result $result Il risultato della query.
     * @return mixed Il risultato dell'esecuzione della query.
     */
    public static function UpdatePassaggio($IdPassaggio,$IdPratica) {
        $dbconn = new Database();
        $query="UPDATE Passaggio
                SET Passaggio.terminato = TRUE
                WHERE Passaggio.IdPassaggio=? AND Passaggio.IdPratica=?;";
        $result=$dbconn->query($query,[$IdPassaggio,$IdPratica]);
        $dbconn->close();
        return $result;
    }

/**
     * Ottiene il numero di un passaggio data l'ID della pratica e l'ID del passaggio.
     * @param int $pratica L'ID della pratica.
     * @param int $passaggio L'ID del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare il numero del passaggio.
     * @param mysqli_result $result Il risultato della query.
     * @return mysqli_result|null Il set di risultati della query o null in caso di errore.
     */
    public static function GetNumPassaggio($pratica,$passaggio) {
        $dbconn = new Database();
        $query = "SELECT Passaggio.NPassaggio
                    FROM Pratica JOIN PASSAGGIO ON Passaggio.IdPratica = Pratica.IdPratica
                    WHERE Pratica.IdPratica= ? AND Passaggio.IdPassaggio = ?";
        try{
            $result = $dbconn->query($query, [$pratica,$passaggio]);
            return $result;
        }
        catch(Exception $e) {
            http_response_code(400);
            echo json_encode(["failed" => "Errore nella ricerca"]);
            return null;
        }
    }

/**
     * Ottiene l'ID di un passaggio dato l'ID della pratica e il numero del passaggio.
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare l'ID del passaggio.
     * @param mysqli_result $result Il risultato della query.
     * @param array $IdPassaggio Un array associativo contenente l'ID del passaggio.
     * @return int|null L'ID del passaggio o null in caso di errore.
     */
    public static function GetIdPassaggioFromNum($IdPratica,$NPassaggio) {
        $dbconn = new Database();
        $query = "SELECT Passaggio.IdPassaggio
                    FROM Passaggio
                    WHERE Passaggio.IdPratica= ? AND Passaggio.NPassaggio = ?";
        try{
            $result = $dbconn->query($query, [$IdPratica,$NPassaggio]);
            $IdPassaggio = $result->fetch_assoc();
            return $IdPassaggio["IdPassaggio"];
        }
        catch(Exception $e) {
            http_response_code(400);
            echo json_encode(["failed" => "Errore nella ricerca"]);
            return null;
        }
    }

/**
     * Carica documenti relativi a un passaggio specifico della pratica.
     * Richiama la funzione DocumentHandler::AddDocument().
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param int $IdPassaggio L'ID del passaggio (ottenuto internamente).
     * @return mixed Il risultato della funzione DocumentHandler::AddDocument().
     */
    public static function CaricaDocumenti($IdPratica,$NPassaggio) {
        $IdPassaggio = self::GetIdPassaggioFromNum($IdPratica,$NPassaggio);
        return DocumentHandler::AddDocument($IdPratica, $NPassaggio, $IdPassaggio);
    }

/**
     * Carica documenti per l'inizio di una pratica.
     * Richiama la funzione DocumentHandler::AddDocumentStart().
     * @param int $IdPratica L'ID della pratica.
     * @return mixed Il risultato della funzione DocumentHandler::AddDocumentStart().
     */
    public static function CaricaDocumentiStart($IdPratica) {
        return DocumentHandler::AddDocumentStart($IdPratica);
    }

/**
     * Ottiene la lista dei documenti richiesti per un passaggio specifico della pratica.
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare la lista dei documenti richiesti.
     * @param mysqli_result $result Il risultato della query.
     * @return string|null La stringa con la lista dei documenti richiesti o null in caso di errore.
     */
    public static function GetListDocRichiesti($IdPratica,$NPassaggio) {
        $dbconn = new Database();
        $query = "SELECT Passaggio.ListDocRichiesti
                    FROM Pratica JOIN Passaggio ON Passaggio.IdPratica = Pratica.IdPratica
                    WHERE Pratica.IdPratica= ? AND Passaggio.NPassaggio = ?";
        try{
            $result = $dbconn->query($query, [$IdPratica,$NPassaggio]);
            $dbconn->close();
            $result = $result->fetch_assoc();
            return $result["ListDocRichiesti"];
        }
        catch(Exception $e) {
            $dbconn->close();
            http_response_code(400);
            echo json_encode(["failed" => "Errore nella ricerca"]);
            return null;
        }
    }

/**
     * Ottiene la lista dei documenti in uscita da un passaggio specifico della pratica.
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare la lista dei documenti in uscita.
     * @param mysqli_result $result Il risultato della query.
     * @return string|null La stringa con la lista dei documenti in uscita o null in caso di errore.
     */
    public static function ListDocUscita($IdPratica,$NPassaggio) {
        $dbconn = new Database();
        $query = "SELECT Passaggio.ListDocUscita
                    FROM Pratica JOIN Passaggio ON Passaggio.IdPratica = Pratica.IdPratica
                    WHERE Pratica.IdPratica= ? AND Passaggio.NPassaggio = ?";
        try{
            $result = $dbconn->query($query, [$IdPratica,$NPassaggio]);
            $dbconn->close();
            $result = $result->fetch_assoc();
            return $result["ListDocUscita"];
        }
        catch(Exception $e) {
            $dbconn->close();
            http_response_code(400);
            echo json_encode(["failed" => "Errore nella ricerca"]);
            return null;
        }
    }

/**
     * Invia i documenti relativi a un passaggio specifico.
     * Richiama la funzione DocumentHandler::SearchDocumentPassaggio().
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio.
     * @return void
     */
    public static function SendDocument($IdPratica,$NPassaggio) {
        DocumentHandler::SearchDocumentPassaggio($IdPratica,$NPassaggio);
    }

/**
     * Salva il nome di un file nella lista dei documenti in uscita per un dato passaggio.
     * Se la lista è vuota, il nome del file viene aggiunto direttamente; altrimenti, viene concatenato con una virgola.
     * @param string $filename Il nome del file da salvare.
     * @param int $IdPratica L'ID della pratica.
     * @param int $IdPassaggio L'ID del passaggio.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per aggiornare la lista dei documenti in uscita.
     * @param mixed $results Il risultato dell'esecuzione della query (numero di righe interessate).
     * @return bool True se il salvataggio ha avuto successo, false altrimenti. Stampa un JSON di errore in caso di eccezione.
     */
    public static function SalvaDoc($filename,$IdPratica,$IdPassaggio) {
        $dbconn = new Database();
        $query = "UPDATE Passaggio
                    SET ListDocUscita = IF( ListDocUscita = '', ? , CONCAT(ListDocUscita,',',?) )
                    WHERE IdPratica=? AND IdPassaggio=?;";
        try{
            $results = $dbconn->query($query,[$filename,$filename,$IdPratica,$IdPassaggio]);
            $dbconn->close();
            if($results == 1){
                return true;
            }
            else return false;
        }
        catch(Exception $e) {
            $dbconn->close();
            http_response_code(400);
            echo json_encode(["failed" => "Errore nella modifica"]);
            return false;
        }
    }

}
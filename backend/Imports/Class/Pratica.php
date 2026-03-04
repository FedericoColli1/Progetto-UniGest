<?php

require_once __DIR__ . "/../Interfaces/PraticaInterface.php";

/**
 * @class Pratica
 * Classe astratta che gestisce tutte le informazioni e le azioni sulla pratica.
 * Implementa l'interfaccia PraticaInterface.
 * Questa classe fornisce metodi statici per interagire con il database e gestire le pratiche.
 * Le funzioni che può svolgere sono:
 * - Restituire tutte le pratiche assegnate a un amministrativo.
 * - Restituire la lista di pratiche create da un docente.
 * - Restituire tutte le pratiche attive che sono assegnate a una determinata unità (per il Direttore).
 * - Aggiungere una nuova pratica con i relativi passaggi e documenti iniziali.
 * - Eliminare una pratica, inclusi i suoi dati nel database e i documenti associati.
 * - Restituire il numero massimo di passaggi di una pratica.
 * - Terminare una pratica in modo forzato, impostando un codice d'errore.
 * - Restituire tutte le pratiche, siano esse attive o terminate (per il Jolly).
 */
abstract class Pratica implements PraticaInterface{
    /**
     * Restituisce un elenco di pratiche assegnate a un determinato amministrativo.
     * La query recupera dettagli come ID pratica, descrizione, data di creazione, tipologia, nome del docente,
     * corso del docente, numero del passaggio attuale, liste di documenti richiesti e in uscita,
     * e il numero totale di passaggi per la tipologia della pratica, ma solo per le pratiche attive (Codice = '0')
     * e con passaggi non terminati (Terminato = '0').
     *
     * @param int $IdAmministrtivo L'ID dell'amministrativo di cui recuperare le pratiche.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL che seleziona le pratiche assegnate all'amministrativo.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return array|null Un array di pratiche o null in caso di errore.
     */
    public static function getPraticheAmministrativo($IdAmministrtivo) {
        $dbconn = new Database();

        $query = "SELECT Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia, Utente.Nome, Docente.Corso, Passaggio.NPassaggio, Passaggio.ListDocRichiesti, Passaggio.ListDocUscita, Tipologia.NPassaggi
                    FROM Passaggio 
                        JOIN Assegnazione ON Passaggio.IdPassaggio = Assegnazione.IdPassaggio 
                        JOIN Pratica ON Pratica.IdPratica = Passaggio.IdPratica 
                        JOIN Utente ON Pratica.IdDocente = Utente.Id 
                        JOIN Docente ON Utente.Id = Docente.IdDocente
                        JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                    WHERE Assegnazione.IdAmministrativo = ?
                    	AND Pratica.Codice = '0'
                        AND Passaggio.Terminato = '0';";

        try {
            $results = $dbconn->query($query, [$IdAmministrtivo]);
            $i = 0;
            $rows = [];
            while ($row = $results->fetch_assoc()) {
                $rows[] = $row;
                $i++;
            }
            return $rows;

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Restituisce un elenco di pratiche create da un docente specifico.
     * La query unisce le pratiche attive (con il minimo passaggio non terminato)
     * e le pratiche terminate (dove il massimo passaggio è terminato) per lo stesso docente.
     * Recupera informazioni come ID pratica, descrizione, data di creazione, tipologia, codice,
     * il passaggio attuale (minimo per attive, massimo per terminate) e il numero totale di passaggi.
     *
     * @param int $IdUser L'ID dell'utente (docente) di cui recuperare le pratiche.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL che unisce pratiche attive e terminate per il docente.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return array|null Un array di pratiche o null in caso di errore.
     */
    public static function getPratiche($IdUser) {
        $dbconn = new Database();

        $query = "SELECT Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia, Pratica.Codice, 
		                MIN(Passaggio.NPassaggio) AS PassaggioAttuale, 
    	                MAX(Tipologia.NPassaggi) AS PassaggiMAX
                    FROM Pratica 
                        JOIN Utente ON Pratica.IdDocente = Utente.Id 
                        JOIN Passaggio ON Passaggio.IdPratica = Pratica.IdPratica 
                        JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                    WHERE Utente.Id = ? AND Passaggio.Terminato = 0
                    GROUP BY Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia
                UNION
                SELECT Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia, Pratica.Codice, 
		                MAX(Passaggio.NPassaggio) AS PassaggioAttuale, 
    	                MAX(Tipologia.NPassaggi) AS PassaggiMAX
                    FROM Pratica 
                        JOIN Utente ON Pratica.IdDocente = Utente.Id 
                        JOIN Passaggio ON Passaggio.IdPratica = Pratica.IdPratica 
                        JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                    WHERE Utente.Id = ? AND Passaggio.Terminato = 1
                    GROUP BY Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia
                    HAVING MAX(Passaggio.NPassaggio) = MAX(Tipologia.NPassaggi);";
        try {
            $rows = null;
            $results = $dbconn->query($query, [$IdUser, $IdUser]);
            $i = 0;
            while ($row = $results->fetch_assoc()) {
                $rows[] = $row;
                $i++;
            }

            return $rows;

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }

    }

    /**
     * Restituisce un elenco di pratiche attive per le quali un direttore è responsabile,
     * basandosi sull'ID dell'unità a cui il direttore appartiene.
     * Recupera dettagli come ID pratica, descrizione, data di creazione, tipologia, nome del docente,
     * corso del docente, stato di assegnazione ("Da Assegnare" o "Assegnato"), numero del passaggio attuale,
     * e liste di documenti.
     * Vengono considerate solo le pratiche al loro passaggio minimo non ancora terminato e associate all'unità del direttore.
     *
     * @param int $IdUnita L'ID dell'unità a cui il direttore è associato.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL che seleziona le pratiche pertinenti per il direttore.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return array|null Un array di pratiche o null in caso di errore.
     */
    public static function getPraticheDirettore($IdUnita) {
        $dbconn = new Database();

        $query = "SELECT DISTINCT Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia, Utente.Nome, Docente.Corso, IF( Assegnazione.IdAmministrativo IS NULL,'Da Assegnare','Assegnato') AS Azione, Passaggio.NPassaggio, Passaggio.ListDocRichiesti, Passaggio.ListDocUscita, Tipologia.NPassaggi
                        FROM Passaggio 
                            LEFT JOIN Assegnazione ON Passaggio.IdPassaggio = Assegnazione.IdPassaggio 
                            JOIN Pratica ON Pratica.IdPratica = Passaggio.IdPratica 
                            JOIN Utente ON Pratica.IdDocente = Utente.Id 
                            JOIN Docente ON Utente.Id = Docente.IdDocente
                            JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                        WHERE (Pratica.IdPratica, Passaggio.NPassaggio) IN (SELECT Passaggio.IdPratica, MIN(Passaggio.NPassaggio)
                            						                            FROM Passaggio 
                                                                            		JOIN Pratica ON Passaggio.IdPratica = Pratica.IdPratica
                                                                            		AND Pratica.Codice = '0'
                                                                            		AND Passaggio.Terminato = 0
                            						                            GROUP BY Passaggio.IdPratica)
                            						                            AND Passaggio.IdUnita = ? 
                                                                                ORDER BY Pratica.IdPratica;";
        try {
            $results = $dbconn->query($query, [$IdUnita]);
            $i = 0;
            while ($row = $results->fetch_assoc()) {
                $rows[] = $row;
                $i++;
            }
            $dbconn->close();
            return $rows;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            $dbconn->close();
            return null;
        }

    }
    /**
     * Aggiunge una nuova pratica al sistema.
     * Questa funzione gestisce la creazione della pratica, l'associazione dei passaggi in base alla tipologia
     * e il caricamento dei documenti iniziali.
     * Esegue una serie di controlli sui file caricati per assicurarsi che corrispondano a quelli richiesti per la tipologia di pratica.
     * Utilizza transazioni per garantire l'integrità dei dati.
     *
     * @param int $IdUser L'ID dell'utente (docente) che sta creando la pratica.
     * @param array $data Un array contenente i dati della pratica (es. [tipologia, descrizione]).
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param DocumentHandler $DocHandler Un'istanza di DocumentHandler per la gestione dei file.
     * @param string $query Una query SQL per selezionare i dettagli della tipologia di pratica e per inserire la pratica e i passaggi.
     * @param mysqli_result $results Il set di risultati delle query.
     * @param array $rows Un array associativo contenente i dettagli della tipologia.
     * @param array $fileNames Un array di nomi di file richiesti dalla tipologia.
     * @param int $IdPratica L'ID della pratica appena inserita.
     * @param bool $attiva Variabile di controllo per la transazione.
     * @param array $passaggi Un array decodificato dei dettagli dei passaggi dalla tipologia.
     * @param mixed $result Il risultato dell'operazione di caricamento documenti iniziali.
     * @return void Stampa un JSON con il risultato dell'operazione.
     */
    public static function addPratica($IdUser, $data = [])
    {

        $dbconn = new Database();

        $DocHandler = new DocumentHandler();

        $query = "SELECT Tipologia.Passaggi, Tipologia.NPassaggi, Tipologia.Inizio FROM Tipologia WHERE Tipologia.Tipologia = ?;";
        try {
            $results = $dbconn->query($query, [$data[0]]);
            $rows = $results->fetch_assoc();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        $dbconn->close();

        if (!empty($_FILES["files"]["name"][0])) {
            $fileNames = explode(",", $rows["Inizio"]);
            if (count($_FILES["files"]["name"]) == count($fileNames)) {
                foreach ($_FILES["files"]["name"] as $file) {
                    if (!in_array($file, $fileNames)) {
                        http_response_code(400);
                        echo json_encode("File necessari: " . Tipologia::getFileInizio($data[0]));
                        return;
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode("File necessari: " . Tipologia::getFileInizio($data[0]));
                return;
            }
        } else {
            http_response_code(400);
            echo json_encode("File necessari: " . Tipologia::getFileInizio($data[0]));
            return;
        }

        $dbconn = new Database();

        $dbconn->begin_transaction();
        $attiva = true;
        $query = "INSERT INTO Pratica(IdDocente,Descrizione,Tipologia) 
                    VALUES (?,?,?);";
        try {
            $IdPratica = $dbconn->query($query, [$IdUser, $data[1], $data[0]]);
        } catch (Exception $e) {
            echo "Errore creazione pratica: " . $e->getMessage();
            $dbconn->rollback();
            $attiva = false;
        }

        if ($attiva == true) {
            $dbconn->getLastId();

            $passaggi = json_decode($rows["Passaggi"], true);

            for ($i = 0; $i < $rows["NPassaggi"]; $i++) {
                $query = "INSERT INTO Passaggio (NPassaggio,IdPratica,IdUnita,ListDocUscita,ListDocRichiesti)
                                    VALUES(?,@id,?,?,?);";
                $elementi = explode(";", $passaggi[strval($i)]);
                try {
                    $dbconn->query($query, [$elementi[0], $elementi[1], "", $elementi[2]]);
                } catch (Exception $e) {
                    echo "Errore creazione passaggi: " . $e->getMessage();
                    $dbconn->rollback();
                    $attiva = false;
                }
            }
            $DocHandler->CreateDirectory($IdPratica, $rows["NPassaggi"], $attiva);
            $result = Passaggio::CaricaDocumentiStart($IdPratica);
            if ($attiva == true && $result == 201) {
                $dbconn->commit();
                http_response_code($result);
                echo json_encode("Risultato: " . $IdPratica);
            } else {
                $dbconn->rollback();
                http_response_code($result);
                echo json_encode("Risultato: " . $IdPratica);
            }

        }
        $dbconn->close();
    }

    /**
     * Elimina una pratica dal database e le directory dei documenti ad essa associate.
     * Prima di eliminare la pratica, recupera i numeri di passaggio per consentire
     * la corretta eliminazione delle directory dei documenti.
     * Esegue l'operazione all'interno di una transazione per garantire l'atomicità.
     *
     * @param int $IdPratica L'ID della pratica da eliminare.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare i numeri di passaggio e per eliminare la pratica.
     * @param array $passaggi Un array per memorizzare i numeri di passaggio (include -1 per la cartella radice).
     * @param mysqli_result $results Il set di risultati della query di selezione.
     * @param bool $Risultato Il risultato dell'operazione di eliminazione delle directory.
     * @return bool Il risultato dell'operazione di eliminazione (true per successo, false per fallimento).
     * @throws Exception Se si verifica un errore durante l'interazione con il database o il filesystem.
     */
    public static function DeletePratica($IdPratica)
    {
        $dbconn = new Database();
        if (isset($IdPratica)) {
            try {
                $query = "SELECT Passaggio.NPassaggio
                        FROM Pratica JOIN Passaggio ON Passaggio.IdPratica = Pratica.IdPratica
                        WHERE Pratica.IdPratica = ?;";
                $passaggi = [];
                $passaggi[] = -1;
                $results = $dbconn->query($query, [$IdPratica]);
                while ($row = $results->fetch_assoc()) {
                    $passaggi[] = $row["NPassaggio"];
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(["Error: " => $e->getMessage()]);
                $dbconn->close();
            }
            $dbconn->begin_transaction();
            $query = "DELETE 
                        FROM Pratica
                        WHERE IdPratica = ?";
            try {
                $result = $dbconn->query($query, [$IdPratica]);
                if (isset($result)) {

                    $Risultato = DocumentHandler::DeleteDirectory($IdPratica, $passaggi);
                    if ($Risultato) {
                        $dbconn->commit();
                    } else {
                        $dbconn->rollback();
                    }
                } else {
                    $dbconn->rollback();
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(["Error: " => $e->getMessage()]);
                $dbconn->rollback();
            }
        }
        $dbconn->close();
        return $Risultato;
    }
    /**
     * Restituisce il numero massimo di passaggi definiti per la tipologia di una pratica specifica.
     *
     * @param int $IdPratica L'ID della pratica di cui si vuole conoscere il numero massimo di passaggi.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare il numero di passaggi dalla tabella Tipologia.
     * @param mysqli_result $result Il set di risultati della query.
     * @return int Il numero massimo di passaggi.
     * @throws Exception Se si verifica un errore durante l'interazione con il database.
     */
    public static function GetMaxPassaggio($IdPratica)
    {
        $dbconn = new Database();
        try {
            $query = "SELECT Tipologia.NPassaggi
                    FROM Pratica JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                    WHERE Pratica.IdPratica= ?";
            $result = $dbconn->query($query, [$IdPratica]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["Error: " => $e->getMessage()]);
        }
        $dbconn->close();
        $result = $result->fetch_assoc();
        return $result["NPassaggi"];
    }
    /**
     * Termina forzatamente una pratica impostando un codice di stato specifico e segnando
     * tutti i passaggi della pratica come terminati.
     *
     * @param int $IdPratica L'ID della pratica da terminare.
     * @param string $Codice Il codice di stato da assegnare alla pratica terminata (es. '1' per successo, '99' per errore).
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per aggiornare il codice della pratica e lo stato dei passaggi.
     * @return int Restituisce 200 in caso di successo. Stampa un JSON di errore in caso di eccezione.
     * @throws Exception Se si verifica un errore durante l'interazione con il database.
     */
    public static function TerminazioneForzata($IdPratica, $Codice)
    {
        $dbconn = new Database();
        try {
            $query = "UPDATE Pratica
                        SET Pratica.Codice = ?
                        WHERE Pratica.IdPratica = ? ;";
            $dbconn->query($query, [$Codice, $IdPratica]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["Error: " => $e->getMessage()]);
        }

        try {
            $query = "UPDATE Passaggio
                        SET Passaggio.Terminato = 1
                        WHERE Passaggio.IdPratica = ? ;";
            $dbconn->query($query, [$IdPratica]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["Error: " => $e->getMessage()]);
        }

        $dbconn->close();

        return 200;
    }
    /**
     * Restituisce un elenco di tutte le pratiche (attive e terminate) visibili dall'utente Jolly.
     * Questa funzione fornisce una panoramica completa di tutte le pratiche nel sistema,
     * inclusi i dettagli sui documenti richiesti e in uscita, lo stato di terminazione, il codice della pratica,
     * lo stato di assegnazione del passaggio corrente e il numero totale di passaggi.
     * La query recupera sia le pratiche attive (minimo passaggio non terminato) sia quelle terminate (massimo passaggio con codice != '0').
     *
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL complessa che unisce pratiche attive e terminate.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return array|null Un array di pratiche o null in caso di errore.
     * @throws Exception Se si verifica un errore durante l'interazione con il database.
     */
    public static function getPraticheJolly()
    {
        $dbconn = new Database();
        $query = "SELECT DISTINCT Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia, Utente.Nome, Passaggio.ListDocUscita, Passaggio.ListDocRichiesti, Passaggio.Terminato, Pratica.Codice, IF( Assegnazione.IdAmministrativo IS NULL,'Da Assegnare','Assegnato') AS Azione, Passaggio.NPassaggio, Tipologia.NPassaggi
                        FROM Passaggio 
                            LEFT JOIN Assegnazione ON Passaggio.IdPassaggio = Assegnazione.IdPassaggio 
                            JOIN Pratica ON Pratica.IdPratica = Passaggio.IdPratica 
                            JOIN Utente ON Pratica.IdDocente = Utente.Id 
                            JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                            WHERE (Pratica.IdPratica, Passaggio.NPassaggio) IN (SELECT Passaggio.IdPratica, MIN(Passaggio.NPassaggio)
                                                                                    FROM Passaggio 
                                                                                    JOIN Pratica ON Passaggio.IdPratica = Pratica.IdPratica
                                                                                    WHERE Pratica.Codice = '0'
                                                                                    AND Passaggio.Terminato = 0
                                                                                    GROUP BY Passaggio.IdPratica)
                    UNION
                    SELECT DISTINCT Pratica.IdPratica, Pratica.Descrizione, Pratica.DataCreazione, Pratica.Tipologia, Utente.Nome, Passaggio.ListDocUscita, Passaggio.ListDocRichiesti, Passaggio.Terminato, Pratica.Codice, 'Completata' AS Azione, Passaggio.NPassaggio, Tipologia.NPassaggi
                        FROM Passaggio 
                            LEFT JOIN Assegnazione ON Passaggio.IdPassaggio = Assegnazione.IdPassaggio 
                            JOIN Pratica ON Pratica.IdPratica = Passaggio.IdPratica 
                            JOIN Utente ON Pratica.IdDocente = Utente.Id 
                            JOIN Tipologia ON Tipologia.Tipologia = Pratica.Tipologia
                            WHERE (Pratica.IdPratica, Passaggio.NPassaggio) IN (SELECT Passaggio.IdPratica, MAX(Passaggio.NPassaggio)
                                                                                    FROM Passaggio 
                                                                                    JOIN Pratica ON Passaggio.IdPratica = Pratica.IdPratica
                                                                                    WHERE Pratica.Codice != '0'
                                                                                    GROUP BY Passaggio.IdPratica);";
        try {
            $results = $dbconn->query($query, []);
            $i = 0;
            while ($row = $results->fetch_assoc()) {
                $rows[] = $row;
                $i++;
            }
            $dbconn->close();
            return $rows;
        } catch (Exception $e) {
            $dbconn->close();
            throw new Exception($e);
        }
    }
}
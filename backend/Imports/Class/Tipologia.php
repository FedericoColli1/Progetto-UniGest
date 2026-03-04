<?php

require_once __DIR__ . "/../Database.php";
require_once __DIR__ . "/../Interfaces/TipologiaInterface.php";

/**
 * @class Tipologia
 * Classe astratta che gestisce le informazioni relative alle diverse tipologie di pratiche.
 * Implementa l'interfaccia TipologiaInterface.
 * Le funzioni che può svolgere sono:
 * - Restituire tutte le tipologie di pratiche disponibili.
 * - Restituire la lista dei documenti richiesti per l'inizio di una specifica tipologia di pratica.
 */
abstract class Tipologia implements TipologiaInterface {

/**
     * Recupera tutte le tipologie di pratiche dal database, inclusi i documenti iniziali richiesti.
     *
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare la tipologia e i documenti iniziali.
     * @param mysqli_result $results Il set di risultati della query.
     * @param array $rows Un array per accumulare tutte le righe di risultati.
     * @return array|null Un array di tipologie con i rispettivi documenti iniziali, o null se non ne vengono trovate.
     */
    static function getTipologie() {
        $dbconn = new Database();
        $query = "SELECT Tipologia, Inizio
                FROM Tipologia;";
        $results= $dbconn->query($query, []);
        $dbconn->close();
        if(!empty($results)) {
            while ($row = $results->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }
        else return null;
    }
/**
     * Recupera la stringa che elenca i nomi dei file richiesti per l'inizio di una specifica tipologia di pratica.
     *
     * @param string $data Il nome della tipologia di cui si vogliono ottenere i file iniziali.
     * @param Database $dbconn Richiama oggetto Database::query() per creare una connessione con il db.
     * @param string $query Una query SQL per selezionare i documenti iniziali di una tipologia specifica.
     * @param mysqli_result $results Il set di risultati della query.
     * @return string|null La stringa contenente i nomi dei file iniziali separati da virgole, o null se la tipologia non esiste o non ha file iniziali.
     */
    static function getFileInizio($data = []) {
        $dbconn = new Database();
        $query = "SELECT Tipologia.Inizio
                FROM Tipologia
                WHERE Tipologia.Tipologia = ?;";
        $results= $dbconn->query($query, [$data]);
        $dbconn->close();
        if(!empty($results)) {
            return $results->fetch_assoc()["Inizio"];
        }
        else return null;
    }
}


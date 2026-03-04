<?php
/**
 * @class Database
 * Classe per la gestione della connessione e delle operazioni con il database MySQL.
 * Utilizza le variabili d'ambiente per configurare i parametri di connessione.
 * Le funzioni che può svolgere sono:
 * - Stabilire una connessione al database.
 * - Eseguire query SQL con o senza parametri preparati.
 * - Gestire transazioni (begin, commit, rollback).
 * - Recuperare l'ultimo ID inserito dopo un'operazione INSERT.
 * - Recuperare il numero di righe influenzate da operazioni UPDATE/DELETE.
 * - Chiudere la connessione al database.
 */
class Database
{
    private $host;
    private $username;
    private $password;
    private $dbname;
    private $connection;
    /**
     * Costruttore della classe Database.
     * Inizializza le proprietà di connessione recuperando i valori dalle variabili d'ambiente
     * e stabilisce la connessione al database.
     */
    public function __construct()
    {
        $this->host = getenv('MYSQL_HOST');
        $this->username = getenv('MYSQL_USER');
        $this->password = getenv('MYSQL_PASSWORD');
        $this->dbname = getenv('MYSQL_DATABASE');


        $this->connect();
    }
    /**
     * Avvia una nuova transazione del database.
     * Questo permette di raggruppare più operazioni SQL in un'unica unità atomica.
     */
    public function begin_transaction(){
        $this->connection->begin_transaction();
    }
    /**
     * Esegue il commit della transazione corrente.
     * Tutte le modifiche fatte durante la transazione vengono salvate permanentemente nel database.
     */
    public function commit(){
        $this->connection->commit();
    }
    /**
     * Esegue il rollback della transazione corrente.
     * Tutte le modifiche fatte durante la transazione vengono annullate e il database torna allo stato precedente.
     */
    public function rollback(){
        $this->connection->rollback();
    }
    /**
     * Imposta una variabile di sessione MySQL `@id` con l'ultimo ID generato automaticamente.
     * Questo è utile per recuperare l'ID di una riga appena inserita e usarlo in query successive.
     * Gestisce anche eventuali errori durante l'esecuzione della query.
     * @param string $query Una query SQL per impostare l'ultimo ID inserito.
     * @throws Exception Se si verifica un errore durante l'esecuzione della query.
     */
    public function getLastId(){
        $query = "SET @id = LAST_INSERT_ID();";
        try {
            $this->query($query,[]);
        } catch (Exception $e) {
            echo "Errore ricezione id: ". $e->getMessage();
        }
    }
    /**
     * Stabilisce la connessione al database MySQL utilizzando le credenziali definite.
     * In caso di errore di connessione, il programma termina e visualizza un messaggio di errore.
     */
    private function connect()
    {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        if ($this->connection->connect_error) {
            die("Connessione fallita: " . $this->connection->connect_error);
        }
    }
    /**
     * Esegue una query SQL preparata.
     * Supporta l'inserimento, l'aggiornamento, l'eliminazione e la selezione di dati.
     * Utilizza prepared statements per prevenire SQL injection.
     *
     * @param string $sql La query SQL da eseguire.
     * @param array $params Un array di parametri da associare alla query preparata (opzionale).
     * @param mysqli_stmt $stmt Lo statement preparato.
     * @param string $types Una stringa che specifica i tipi dei parametri ('s' per stringa, 'i' per intero, ecc.).
     * @param int $last_id L'ultimo ID inserito (per query INSERT).
     * @param int $affected_rows Il numero di righe influenzate (per query UPDATE/DELETE).
     * @return mixed Il risultato della query (oggetto mysqli_result per SELECT, ID per INSERT, numero di righe per UPDATE/DELETE).
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);

        if ($params) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if (preg_match('/^\s*INSERT/i', $sql)) {
            $last_id = $this->connection->insert_id;
            $stmt->close();
            return $last_id;
        }
        if (preg_match('/^\s*UPDATE/i', $sql)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            $stmt->close();
            return $affected_rows;
        }
        if (preg_match('/^\s*DELETE/i', $sql)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            $stmt->close();
            return $affected_rows;
        }

        $stmt->close();

        return $result;
    }
    /**
     * Chiude la connessione al database.
     * È buona pratica chiudere la connessione una volta terminate tutte le operazioni.
     */
    public function close()
    {
        $this->connection->close();
    }
}

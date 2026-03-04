<?php

require_once __DIR__ . "/Interfaces/DownloadInterface.php";
/**
 * @class Download
 * Classe astratta responsabile della gestione del download dei file.
 * Implementa l'interfaccia DownloadInterface.
 * L'unica funzionalità offerta è:
 * - Consentire il download di un file specifico associato a una pratica e un passaggio.
 */
abstract class Download implements DownloadInterface {
    /**
     * Gestisce il download di un file specifico richiesto tramite parametri GET.
     * Recupera l'ID della pratica, il numero del passaggio e il nome del file dai parametri della richiesta.
     * Verifica l'esistenza del file sul server e, se presente, imposta gli header HTTP appropriati per il download
     * e invia il contenuto del file al browser.
     * In caso il file non esista, restituisce un errore HTTP 404.
     *
     * @param string $_GET['IdPratica'] L'ID della pratica a cui il file appartiene.
     * @param string $_GET['NPassaggio'] Il numero del passaggio a cui il file è associato.
     * @param string $_GET['File'] Il nome del file da scaricare.
     * @param string $pratica L'ID della pratica estratto da $_GET.
     * @param string $passaggio Il numero del passaggio estratto da $_GET.
     * @param string $file Il nome base del file estratto da $_GET.
     * @param string $path Il percorso completo del file sul server.
     * @return void Il file viene scaricato direttamente dal browser o viene restituito un messaggio di errore.
     */
    public static function DownloadFile() {
        $pratica = $_GET['IdPratica'];
        $passaggio = $_GET['NPassaggio'];
        $file = basename($_GET['File']); 
        $path = "/var/www/api/documenti/$pratica/$passaggio/$file";

        if (file_exists($path)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        } else {
            http_response_code(404);
            echo "File non trovato.";
        }
    }
}

?>
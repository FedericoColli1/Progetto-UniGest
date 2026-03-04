<?php

require_once 'Database.php';
require_once __DIR__ . '/Class/Passaggio.php';
require_once __DIR__ . "/Interfaces/DocInterface.php";
/**
 * @class DocumentHandler
 * Classe per la gestione dei documenti e delle directory associate alle pratiche.
 * Implementa l'interfaccia DocInterface.
 * Le funzioni che può svolgere sono:
 * - Verificare la presenza di file richiesti in un determinato passaggio di una pratica.
 * - Aggiungere documenti a un passaggio specifico di una pratica.
 * - Aggiungere documenti iniziali a una nuova pratica.
 * - Creare le directory necessarie per una nuova pratica e i suoi passaggi.
 * - Eliminare ricorsivamente directory e file associati a una pratica.
 * - Eliminare i file all'interno di una directory specificata.
 * - Cercare e listare i documenti presenti in un determinato passaggio di una pratica.
 */
class DocumentHandler implements DocInterface {
    private $Directory = "/var/www/api/documenti/";

    /**
     * Verifica che tutti i file richiesti per un determinato passaggio di una pratica siano stati caricati.
     * Confronta i file richiesti per il passaggio corrente con tutti i file caricati fino a quel passaggio.
     *
     * @param int $NPassaggio Il numero del passaggio corrente da controllare.
     * @param int $IdPratica L'ID della pratica a cui il passaggio appartiene.
     * @param int $NPassaggi Il numero totale di passaggi per la pratica (usato solo per informazione, non direttamente nel controllo dei file).
     * @param string $Directory La directory base dove si trovano i documenti.
     * @param string $directory Il percorso completo della directory della pratica.
     * @param array $dirs Un array dei contenuti della directory della pratica (file e sottodirectory).
     * @param string $file_richiesti Una stringa (poi convertita in array) dei nomi dei file richiesti per il passaggio corrente.
     * @param array $file_caricati Un array accumulato di tutti i file caricati nei passaggi precedenti e nel passaggio corrente.
     * @param string $file_caricati_passaggio Una stringa (poi convertita in array) dei nomi dei file caricati in un passaggio specifico.
     * @return bool True se tutti i file richiesti sono presenti, false altrimenti.
     * @throws Exception Se si verifica un errore durante la lettura delle directory o l'interazione con la classe Passaggio.
     */
    public static function CheckFile( $NPassaggio, $IdPratica, $NPassaggi) {
        $Directory = '/var/www/api/documenti/';
        error_log($NPassaggio);
        try{
            $directory= $Directory . $IdPratica . "/";
            error_log($directory);
            $dirs = scandir($directory);
            $dirs = array_filter($dirs, function($element) {
                return !in_array($element, ['.', '..']);
            });
            if(empty($dirs)){
                return false;
            }
            else{
                $file_richiesti = Passaggio::GetListDocRichiesti($IdPratica,$NPassaggio);
                $file_richiesti = array_map('trim', explode(',', $file_richiesti));
                $file_caricati=[];
                for($i=0;$i<=$NPassaggio;$i++) {
                    $file_caricati_passaggio = Passaggio::ListDocUscita($IdPratica,$i);
                    if (isset($file_caricati_passaggio)) {
                        $file_caricati = array_merge($file_caricati, array_map('trim', explode(',', $file_caricati_passaggio)));
                    }
                }
                foreach($file_richiesti as $file_richiesto) {
                    if (!in_array($file_richiesto, $file_caricati)) {
                        return false;
                    }
                }
            }
            return true;
        }
        catch(Exception $e) {
            throw ($e);
        }
    }


    /**
     * Aggiunge i documenti caricati tramite un form HTTP a un passaggio specifico di una pratica.
     * Verifica che i file caricati siano tra quelli richiesti per il passaggio e li sposta nella directory corretta.
     * Aggiorna anche la lista dei documenti in uscita nel database per il passaggio.
     *
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio a cui aggiungere i documenti.
     * @param int $IdPassaggio L'ID del passaggio nel database.
     * @param string $Directory La directory base dove memorizzare i documenti.
     * @param string $target_dir La directory di destinazione per i file caricati.
     * @param array $filename Un array dei nomi dei file caricati.
     * @param array $temp_filename Un array dei percorsi temporanei dei file caricati.
     * @param string $file_richiesti Una stringa (poi convertita in array) dei nomi dei file richiesti per il passaggio.
     * @param string $file_caricati_passaggio Una stringa (poi convertita in array) dei nomi dei file già caricati per il passaggio.
     * @return int Codice HTTP 201 (Created) se il caricamento ha successo, 400 (Bad Request) altrimenti.
     * @throws Exception Se si verifica un errore durante lo spostamento dei file o l'interazione con la classe Passaggio.
     */
    public static function AddDocument($IdPratica, $NPassaggio, $IdPassaggio) {
        $Directory = "/var/www/api/documenti/";
        $target_dir = $Directory . $IdPratica . "/" . ($NPassaggio) . "/";
        if(!empty($_FILES["files"]["name"][0])) { 
            try{
                $filename = $_FILES["files"]["name"];
                $temp_filename=$_FILES["files"]["tmp_name"];
                error_log($NPassaggio);
                $file_richiesti = Passaggio::GetListDocRichiesti($IdPratica,$NPassaggio);
                $file_richiesti = array_map('trim', explode(',', $file_richiesti));
                $file_caricati_passaggio = Passaggio::ListDocUscita($IdPratica,$NPassaggio);
                $file_caricati_passaggio = array_map('trim', explode(',', $file_caricati_passaggio));
                foreach($filename as $key=>$file_input){
                    
                    if (!in_array($file_input, $file_richiesti)) {
                        unset($filename[$key]);
                    }
                }

                for($i = 0; $i < count($filename); $i++) {  
                    $target_file = $target_dir . basename($filename[$i]);
                    if(!move_uploaded_file($temp_filename[$i], $target_file)) {
                        throw new Exception("Errore nel caricamento di: " . $filename[$i]);
                    }
                    error_log($filename[$i]);
                    if (!in_array($filename[$i], $file_caricati_passaggio)) {
                        Passaggio::SalvaDoc($filename[$i],$IdPratica,$IdPassaggio);
                    }
                }
                Passaggio::Terminazione($NPassaggio,$IdPratica, $IdPassaggio);

                return 201;
            }
            catch(Exception $e) {
                http_response_code(400);
                echo json_encode(["error" => $e->getMessage()]);
                var_dump($e);
                return 400;
            }
        }
        else {
            return 400;
        }
    }

    /**
     * Aggiunge i documenti iniziali di una pratica.
     * Questi documenti vengono caricati nella sottocartella speciale "-1" della pratica.
     * Non esegue controlli sui nomi dei file rispetto a una lista richiesta, assumendo che i controlli siano stati fatti altrove.
     *
     * @param int $IdPratica L'ID della pratica.
     * @param string $Directory La directory base dove memorizzare i documenti.
     * @param int $cartella Il numero della sottocartella per i documenti iniziali (qui è -1).
     * @param string $target_dir La directory di destinazione per i file caricati.
     * @param array $filename Un array dei nomi dei file caricati.
     * @param array $temp_filename Un array dei percorsi temporanei dei file caricati.
     * @return int Codice HTTP 201 (Created) se il caricamento ha successo, 400 (Bad Request) altrimenti.
     * @throws Exception Se si verifica un errore durante lo spostamento dei file.
     */
    public static function AddDocumentStart($IdPratica) {
        $Directory = "/var/www/api/documenti/";
        $cartella = -1;
        $target_dir = $Directory . $IdPratica . "/" . ($cartella) . "/";
        if(!empty($_FILES["files"]["name"][0])) { 
            try{
                $filename = $_FILES["files"]["name"];
                $temp_filename=$_FILES["files"]["tmp_name"];

                for($i = 0; $i < count($filename); $i++) {  
                    $target_file = $target_dir . basename($filename[$i]);
                    if(!move_uploaded_file($temp_filename[$i], $target_file)) {
                        throw new Exception("Errore nel caricamento di: " . $filename[$i]);
                    }
                }

                return 201;
            }
            catch(Exception $e) {
                http_response_code(400);
                echo json_encode(["error" => $e->getMessage()]);
                var_dump($e);
                return 400;
            }
        }
        else {
            return 400;
        }
    }
    
    
    /**
     * Crea le directory per una nuova pratica e per tutti i suoi passaggi.
     * Include una sottocartella speciale per i documenti iniziali (cartella -1).
     * Se la creazione fallisce, tenta di eliminare le directory già create per pulire.
     *
     * @param int $IdPratica L'ID della pratica per cui creare le directory.
     * @param int $Npassaggio Il numero totale di passaggi per la pratica.
     * @param bool $attiva Passato per riferimento, indica lo stato di successo della transazione/operazione. Viene impostato a false in caso di errore.
     * @param string $dirname Il percorso della directory radice della pratica.
     * @param array $array_creati Un array per tenere traccia delle directory create, utile per il rollback.
     * @param string $dirpassaggio Il percorso di una sottodirectory di passaggio.
     * @return void Imposta il valore di $attiva e può stampare un JSON di errore.
     * @throws Exception Se si verifica un errore durante la creazione delle directory.
     */
    public function CreateDirectory($IdPratica, $Npassaggio, &$attiva) {
        error_log($IdPratica);
        $dirname=$this->Directory.$IdPratica;
        try{
            if(!is_dir($dirname) ) { 
                mkdir($dirname, 0755, 1);
            }
            $array_creati=[];
            if(!empty($Npassaggio)) {
                for($i = -1; $i < $Npassaggio; $i++) {
                    $dirpassaggio=$dirname. "/" . $i;
                    if(!is_dir($dirpassaggio) ) {
                        mkdir($dirpassaggio, 0755, 1);
                    }
                    $array_creati[$i]=$i;
                }
    
            }
            $attiva=true;
        }
        catch(Exception $e) {
            $attiva=false;
            self::DeleteDirectory($IdPratica,$array_creati);
            error_log($e->getMessage());
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Elimina una directory associata a una pratica e tutti i suoi contenuti (file e sottodirectory).
     * Se vengono forniti specifici numeri di passaggio, elimina solo le sottodirectory corrispondenti.
     * Tenta di eliminare anche la directory radice della pratica se tutte le sottodirectory sono state rimosse.
     *
     * @param int $IdPratica L'ID della pratica di cui eliminare le directory.
     * @param array $Npassaggio Un array di numeri di passaggio da eliminare (vuoto per eliminare tutto).
     * @param string $Directory La directory base dove si trovano i documenti.
     * @param string $dirname Il percorso della directory radice della pratica.
     * @param int $delete Flag che indica se è stata effettuata almeno un'eliminazione (1 per sì, 0 per no).
     * @param string $dirpassaggio Il percorso di una sottodirectory di passaggio.
     * @return bool True se è stata effettuata almeno un'eliminazione, false altrimenti.
     */
    public static function DeleteDirectory($IdPratica, $Npassaggio = []) {
        $Directory = "/var/www/api/documenti/";
        $dirname=$Directory . "/" . $IdPratica;
        error_log($dirname);
        $delete = 0;
        foreach($Npassaggio as $passaggio) {
            $dirpassaggio = $dirname . "/" . $passaggio;
            error_log($dirpassaggio);
            if(is_dir($dirpassaggio)) { 
                self::DeleteFile($dirpassaggio);
                rmdir($dirpassaggio);
                $delete = 1;
            }
            elseif(is_file($dirpassaggio)){
                unlink($dirpassaggio);
                $delete = 1;
            }
        }
        if($delete==1 && count(glob("$dirname/*")) === 0) {
            if (rmdir($dirname)) {
                error_log("Eliminata cartella pratica: $dirname");
            } else {
                error_log("Errore rmdir su cartella pratica: $dirname");
            }
        }

        return $delete;
    }

    /**
     * Elimina ricorsivamente tutti i file e le sottodirectory all'interno di una data directory.
     *
     * @param string $dir Il percorso della directory da pulire.
     * @param array $files Un array di tutti gli elementi (file e directory) all'interno della directory.
     * @return void
     */
    public static function DeleteFile($dir) {
        $files = glob($dir . "/*");
        foreach( $files as $file) {
                if(is_file($file)) {
                    unlink($file);
                }
                elseif( is_dir($file)){
                    self::DeleteFile($file);
                    rmdir($file);
                }
            
        }
    }

    /**
     * Cerca e restituisce (come JSON) l'elenco dei documenti presenti in una specifica sottocartella di un passaggio.
     *
     * @param int $IdPratica L'ID della pratica.
     * @param int $NPassaggio Il numero del passaggio di cui cercare i documenti.
     * @param string $Directory La directory base dove si trovano i documenti.
     * @param string $directory Il percorso completo della sottodirectory del passaggio.
     * @param array $dirs Un array dei contenuti della sottodirectory del passaggio (file e sottodirectory).
     * @return void Stampa un JSON con i nomi dei file o un messaggio di errore HTTP.
     * @throws Exception Se si verifica un errore durante la scansione della directory.
     */
    public static function SearchDocumentPassaggio($IdPratica,$NPassaggio) {
        try{
            $Directory = "/var/www/api/documenti/";
            $directory= $Directory . $IdPratica . "/" . ($NPassaggio);
            $dirs = scandir($directory);
            $dirs = array_filter($dirs, function($element) {
                return !in_array($element, ['.', '..']);
            });
            if(empty($dirs)){
                return NULL;
            }
            else{
                if (!empty($dirs)) {
                    echo json_encode(array_values($dirs));
                }
                else {
                    http_response_code(404);
                    echo "File not found.";
                }
            }
        }
        catch(Exception $e) {
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

}

?>